<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Pmt;
use App\Models\User;
use App\Models\Dtr;
use App\Models\LeaveApplication;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\LearningDev; 
use App\Models\VoluntaryWork;
use App\Models\Application;
use App\Models\JobHiring;
use App\Models\Setting;
use App\Models\OfficialTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class MasterController extends Controller
{
    private function dtrTimes($value)
    {
        if (!$value) {
            return collect();
        }

        return collect(explode(',', $value))
            ->map(fn ($time) => trim($time))
            ->filter()
            ->sortBy(fn ($time) => strtotime($time))
            ->values();
    }

    private function formatDtrTime($value)
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('h:i A');
        } catch (\Exception $e) {
            return $value;
        }
    }

    private function arrangedDtrPunches($dtr)
    {
        if (!$dtr) {
            return collect();
        }

        return $this->dtrTimes($dtr->time_in)
            ->map(fn ($time) => ['time' => $time, 'label' => 'IN'])
            ->merge($this->dtrTimes($dtr->time_out)->map(fn ($time) => ['time' => $time, 'label' => 'OUT']))
            ->merge($this->dtrTimes($dtr->time_over)->map(fn ($time) => ['time' => $time, 'label' => 'OT']))
            ->sortBy(fn ($punch) => strtotime($punch['time']))
            ->values()
            ->map(function ($punch) {
                $punch['formatted'] = $this->formatDtrTime($punch['time']);

                return $punch;
            });
    }

    private function firstDtrIn($dtr)
    {
        return $this->formatDtrTime($this->dtrTimes(optional($dtr)->time_in)->first());
    }

    private function lastDtrOut($dtr)
    {
        return $this->formatDtrTime($this->dtrTimes(optional($dtr)->time_out)->last());
    }

    private function parseOfficialRange($range, $fallbackStart, $fallbackEnd)
    {
        $times = $range ? explode('-', $range) : [];

        return [
            $times[0] ?? $fallbackStart,
            $times[1] ?? $fallbackEnd,
        ];
    }

    private function officialScheduleForDate($officialTime, $date)
    {
        $day = strtolower(Carbon::parse($date)->format('D'));
        $dayMap = [
            'mon' => ['morn_mon', 'aft_mon'],
            'tue' => ['morn_tue', 'aft_tue'],
            'wed' => ['morn_wed', 'aft_wed'],
            'thu' => ['morn_thu', 'aft_thu'],
            'fri' => ['morn_fri', 'aft_fri'],
        ];

        if (!$officialTime || !isset($dayMap[$day])) {
            return [
                'mornin' => '08:00:00',
                'mornout' => '12:00:00',
                'aftin' => '13:00:00',
                'aftout' => '17:00:00',
            ];
        }

        [$morningField, $afternoonField] = $dayMap[$day];
        [$mornIn, $mornOut] = $this->parseOfficialRange($officialTime->{$morningField}, '08:00:00', '12:00:00');
        [$aftIn, $aftOut] = $this->parseOfficialRange($officialTime->{$afternoonField}, '13:00:00', '17:00:00');

        return [
            'mornin' => $mornIn,
            'mornout' => $mornOut,
            'aftin' => $aftIn,
            'aftout' => $aftOut,
        ];
    }

    private function dailyWorkPunches($dtr, $schedule = null)
    {
        $timeIns = $this->dtrTimes(optional($dtr)->time_in);
        $timeOuts = $this->dtrTimes(optional($dtr)->time_out);
        $schedule = $schedule ?: [
            'mornin' => '08:00:00',
            'mornout' => '12:00:00',
            'aftin' => '13:00:00',
            'aftout' => '17:00:00',
        ];

        $latestUsefulTimeIn = Carbon::parse($schedule['aftin'])->copy()->addMinutes(30)->format('H:i');
        $earliestUsefulTimeOut = Carbon::parse($schedule['mornout'])->copy()->subMinutes(60)->format('H:i');

        $dailyTimeIns = $timeIns
            ->filter(fn ($time) => substr($time, 0, 5) <= $latestUsefulTimeIn)
            ->values();

        $dailyTimeOuts = $timeOuts
            ->filter(fn ($time) => substr($time, 0, 5) >= $earliestUsefulTimeOut)
            ->values();

        return [
            'am_in' => $dailyTimeIns->first(),
            'am_out' => $dailyTimeOuts->first(),
            'pm_in' => $dailyTimeIns->count() >= 2 ? $dailyTimeIns->last() : null,
            'pm_out' => $dailyTimeOuts->count() >= 2 ? $dailyTimeOuts->last() : null,
        ];
    }

    private function minutesAfter($time, $limit)
    {
        if (!$time) {
            return 0;
        }

        $actual = Carbon::createFromFormat('H:i', substr($time, 0, 5));
        $expected = Carbon::createFromFormat('H:i', $limit);

        return $actual->greaterThan($expected) ? $actual->diffInMinutes($expected) : 0;
    }

    private function minutesBefore($time, $limit)
    {
        if (!$time) {
            return 0;
        }

        $actual = Carbon::createFromFormat('H:i', substr($time, 0, 5));
        $expected = Carbon::createFromFormat('H:i', $limit);

        return $actual->lessThan($expected) ? $actual->diffInMinutes($expected) : 0;
    }

    private function dtrTardinessSummary($dtrRecords, $officialTime = null)
    {
        return $dtrRecords->reduce(function ($summary, $dtr) use ($officialTime) {
            $schedule = $this->officialScheduleForDate($officialTime, $dtr->date);
            $punches = $this->dailyWorkPunches($dtr, $schedule);

            $lateMinutes = $this->minutesAfter($punches['am_in'], substr($schedule['mornin'], 0, 5))
                + $this->minutesAfter($punches['pm_in'], substr($schedule['aftin'], 0, 5));
            $undertimeMinutes = $this->minutesBefore($punches['am_out'], substr($schedule['mornout'], 0, 5))
                + $this->minutesBefore($punches['pm_out'], substr($schedule['aftout'], 0, 5));

            $summary['late_minutes'] += $lateMinutes;
            $summary['undertime_minutes'] += $undertimeMinutes;
            $summary['late_days'] += $lateMinutes > 0 ? 1 : 0;
            $summary['undertime_days'] += $undertimeMinutes > 0 ? 1 : 0;

            return $summary;
        }, [
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'late_days' => 0,
            'undertime_days' => 0,
        ]);
    }

    private function formatMinutes($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return $remainingMinutes . ' min';
        }

        return $hours . ' hr' . ($hours == 1 ? '' : 's') . ' ' . $remainingMinutes . ' min';
    }

    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function dashboard(Request $request)
    {
        $guard = $this->getGuard();
        $userCount = User::all();
        $dtrCount = Dtr::whereDate('date', Carbon::now('Asia/Manila')->toDateString())->count();
        $chartEmployee = Employee::where('stat_1', 1)->get();

        $leaveappCount = LeaveApplication::where('emp_esign', '=', 0)->where('history', 1)->where('status', 1)->count('empid');
        $eliCount = Eligibility::where('status', 0)->count();
        $workexpCount = WorkExperience::where('status', 0)->count();
        $learDevCount = LearningDev::where('status', 0)->count();
        $volWorkCount = VoluntaryWork::where('status', 0)->count();

        $totalEmployees = $chartEmployee->count();
        $empStatuses = [1, 2, 3, 4];

        // Calculate percentage for each emp_status and ensure the correct order
        $empStatusPercentages = collect($empStatuses)->mapWithKeys(function ($status) use ($chartEmployee, $totalEmployees) {
            $count = $chartEmployee->where('emp_status', $status)->count();
            $percentage = $totalEmployees > 0 ? ($count / $totalEmployees) * 100 : 0;
            return [$status => ['count' => $count, 'percentage' => $percentage]];
        });
        
        $offCount = Office::all();
    
        if (\Auth::guard('web')->check()) {
            $today = Carbon::now();
            $currentYear = $today->year;

            $today = Carbon::today();
            
            $upcomingBirthdays = Employee::whereNotNull('employees.bdate')
            ->join('offices', 'employees.emp_dept', '=', 'offices.id')
            ->select('employees.id', 'employees.fname', 'employees.lname', 'employees.mname', 'employees.profile', 'employees.bdate', 'offices.office_abbr')
            ->orderByRaw("
                CASE
                    WHEN DATE_FORMAT(employees.bdate, '%m-%d') >= ? THEN 0
                    ELSE 1
                END, DATE_FORMAT(employees.bdate, '%m-%d') ASC", [$today->format('m-d')])
            ->take(10)
            ->get()
            ->each(function ($employee) {
                $employee->bdate = Carbon::parse($employee->bdate);
            });
        
            return view("home.dashboard", compact('eliCount', 'workexpCount', 'learDevCount', 'volWorkCount', 'dtrCount', 'totalEmployees', 'leaveappCount', 'eliCount', 'offCount', 'userCount', 'chartEmployee', 'empStatusPercentages', 'upcomingBirthdays', 'guard'));
        }
    
        if (\Auth::guard('employee')->check()) {
            $employee = \Auth::guard('employee')->user();
            $officialTime = OfficialTime::where('empid', $employee->emp_ID)->first();
            $today = Carbon::now('Asia/Manila')->toDateString();
            $dateFrom = $request->input('date_from', Carbon::now('Asia/Manila')->startOfWeek()->toDateString());
            $dateTo = $request->input('date_to', Carbon::now('Asia/Manila')->endOfWeek()->toDateString());

            if (Carbon::parse($dateFrom)->greaterThan(Carbon::parse($dateTo))) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            $todayDtr = Dtr::where('emp_ID', $employee->emp_ID)
                ->whereDate('date', $today)
                ->orderBy('time_in')
                ->first();

            $filteredDtrs = Dtr::where('emp_ID', $employee->emp_ID)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->orderBy('date', 'desc')
                ->orderBy('time_in', 'desc')
                ->get();

            $recentDtrs = $filteredDtrs;
            $isRegularEmployee = (int) $employee->emp_status === 1;

            $leaveApplications = $isRegularEmployee
                ? LeaveApplication::where('empid', $employee->emp_ID)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                : collect();

            $leaveCount = $isRegularEmployee
                ? LeaveApplication::where('empid', $employee->emp_ID)->count()
                : 0;
            $serviceYears = $employee->date_hired
                ? Carbon::parse($employee->date_hired)->diffInYears(Carbon::now('Asia/Manila'))
                : null;
            $todayTimeIn = $this->firstDtrIn($todayDtr);
            $todayTimeOut = $this->lastDtrOut($todayDtr);
            $todayPunches = $this->arrangedDtrPunches($todayDtr);
            $todaySchedule = $this->officialScheduleForDate($officialTime, $today);
            $todayDailyPunchesRaw = $this->dailyWorkPunches($todayDtr, $todaySchedule);
            $todayDailyPunches = [
                'am_in' => $this->formatDtrTime($todayDailyPunchesRaw['am_in']),
                'am_out' => $this->formatDtrTime($todayDailyPunchesRaw['am_out']),
                'pm_in' => $this->formatDtrTime($todayDailyPunchesRaw['pm_in']),
                'pm_out' => $this->formatDtrTime($todayDailyPunchesRaw['pm_out']),
            ];
            $tardinessSummary = $this->dtrTardinessSummary($filteredDtrs, $officialTime);
            $totalLate = $this->formatMinutes($tardinessSummary['late_minutes']);
            $totalUndertime = $this->formatMinutes($tardinessSummary['undertime_minutes']);
            $recentDtrs = $recentDtrs->map(function ($dtr) use ($officialTime) {
                $schedule = $this->officialScheduleForDate($officialTime, $dtr->date);
                $dailyPunches = $this->dailyWorkPunches($dtr, $schedule);

                $dtr->formatted_time_in = $this->firstDtrIn($dtr);
                $dtr->formatted_time_out = $this->lastDtrOut($dtr);
                $dtr->arranged_punches = $this->arrangedDtrPunches($dtr);
                $dtr->official_schedule = [
                    'am' => $this->formatDtrTime($schedule['mornin']) . ' - ' . $this->formatDtrTime($schedule['mornout']),
                    'pm' => $this->formatDtrTime($schedule['aftin']) . ' - ' . $this->formatDtrTime($schedule['aftout']),
                ];
                $dtr->daily_punches = [
                    'am_in' => $this->formatDtrTime($dailyPunches['am_in']),
                    'am_out' => $this->formatDtrTime($dailyPunches['am_out']),
                    'pm_in' => $this->formatDtrTime($dailyPunches['pm_in']),
                    'pm_out' => $this->formatDtrTime($dailyPunches['pm_out']),
                ];

                return $dtr;
            });

            return view("home.dashboard", compact(
                'offCount',
                'userCount',
                'chartEmployee',
                'guard',
                'employee',
                'todayDtr',
                'todayTimeIn',
                'todayTimeOut',
                'todayPunches',
                'todayDailyPunches',
                'officialTime',
                'dateFrom',
                'dateTo',
                'filteredDtrs',
                'tardinessSummary',
                'totalLate',
                'totalUndertime',
                'recentDtrs',
                'leaveApplications',
                'leaveCount',
                'serviceYears',
                'isRegularEmployee'
            ));
        }
    }
    
    public function dashboard1(){
        $guard = $this->getGuard();
        $userCount = User::all();
        $chartEmployee = Employee::all();
            
        $offCount = Office::all();
    
        if (\Auth::guard('web')->check()) {
            $empCount = Employee::count();

                return view("home.dashboard1", compact('empCount', 'offCount', 'userCount', 'chartEmployee', 'guard'));
        }
    
        if (\Auth::guard('employee')->check()) {
            return view("home.dashboard1", compact('offCount', 'userCount', 'chartEmployee', 'guard'));
        }
    }

    public function logout()
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            return redirect()->route('getLogin')->with('success', 'You have been successfully logged out');
        }

        if (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
            return redirect()->route('getLogin')
                             ->with('success', 'You have been successfully logged out');
        }

        return redirect()->route('getLogin')
                         ->with('error', 'No authenticated user to log out');
    }

    public function dataPrivacy()
    {
        $guard = $this->getGuard();
        $customPaper = [0, 0, 684, 1050];
        $pdf = \PDF::loadView('data-privacy', compact('guard'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
            ])
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                },
            ]);

        return $pdf->stream(); // stream to iframe
    } 

    public function appList(Request $request){
        $guard = $this->getGuard();
        $request->validate([
            'position_id' => 'nullable|integer|exists:job_hirings,id',
            'status' => 'nullable|integer|in:0,1,2,3,4,5,6,7',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = Application::join('job_hirings', 'applications.jid', '=', 'job_hirings.id')
            ->select('applications.*', 'job_hirings.title as position', 'job_hirings.plantilla_item_no');

        if ($request->filled('position_id')) {
            $query->where('applications.jid', $request->position_id);
        }

        if ($request->filled('status')) {
            $query->where('applications.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('applications.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('applications.created_at', '<=', $request->date_to);
        }

        $applications = $query
            ->orderByDesc('applications.created_at')
            ->get();
        $jobs = JobHiring::orderBy('title')->get();

        return view('career.application', compact('applications', 'jobs', 'guard'));
    }

    public function applicationReport(Request $request)
    {
        $statusLabels = [
            0 => 'Application Submitted',
            1 => 'Reviewing',
            2 => 'Qualified / Ready for Interview',
            3 => 'Disqualified',
            4 => 'Qualified yet not selected',
            5 => 'Top 5 / Psychological or Pre-Employment Test',
            6 => 'Not Hired',
            7 => 'Hired',
        ];

        $request->validate([
            'position_id' => 'nullable|integer|exists:job_hirings,id',
            'status' => 'nullable|integer|in:0,1,2,3,4,5,6,7',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = Application::join('job_hirings', 'applications.jid', '=', 'job_hirings.id')
            ->select(
                'applications.*',
                'job_hirings.title as position',
                'job_hirings.assignment as program',
                'job_hirings.education as required_education',
                'job_hirings.training as required_training',
                'job_hirings.experience as required_experience'
            );

        if ($request->filled('position_id')) {
            $query->where('applications.jid', $request->position_id);
        }

        if ($request->filled('status')) {
            $query->where('applications.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('applications.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('applications.created_at', '<=', $request->date_to);
        }

        $applications = $query
            ->orderBy('job_hirings.title')
            ->orderBy('applications.last_name')
            ->orderBy('applications.first_name')
            ->get();

        $selectedPosition = $request->filled('position_id')
            ? JobHiring::find($request->position_id)
            : null;

        $selectedStatus = $request->filled('status')
            ? ($statusLabels[(int) $request->status] ?? 'Unknown')
            : 'All Statuses';
        $selectedDateFrom = $request->date_from;
        $selectedDateTo = $request->date_to;

        $customPaper = [0, 0, 1296, 612];
        $pdf = \PDF::loadView('career.application-report', compact(
            'applications',
            'selectedPosition',
            'selectedStatus',
            'selectedDateFrom',
            'selectedDateTo'
        ))->setPaper($customPaper);

        return $pdf->stream('application-report.pdf');
    }

    public function systemSetting(){
        $guard = $this->getGuard();
        $employees = Employee::select('id', 'emp_ID', 'fname', 'lname')->get();
        $settings = Setting::first();

        $kioskAccess = explode(',', $settings->hr_kiosk);
        $dtrFullAccess = explode(',', $settings->dtr_acct);

        $stations = \App\Models\AttendanceStation::orderBy('name')->get();

        return view('settings.index', compact('guard', 'employees', 'settings', 'kioskAccess', 'dtrFullAccess', 'stations'));
    }

    public function dataPrivacyNotice(Request $request)
    {
        $guard = $this->getGuard();
        $user = Employee::find(auth()->guard($guard)->user()->id);
        $user->dpn = 1; 
        $user->save();

        return redirect()->back();
    }

}
