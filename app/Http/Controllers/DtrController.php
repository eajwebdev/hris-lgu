<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\Fdevice;
use App\Models\Logzone;
use App\Models\OfficialTime;
use App\Models\Setting;
use Carbon\Carbon; 
use PDF;

class DtrController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    /**
     * Whose daily time records the signed-in user is allowed to look at.
     *
     * Returns [$employees, $acctstat] where $acctstat is the flag the DTR views
     * already use to decide whether to draw the employee picker at all.
     *
     *   Admin / HR             — everyone.
     *   Settings → DTR Full Access — their own office, and nobody outside it.
     *                            The list grants HR-like reach over one office,
     *                            not over the whole LGU.
     *   Everyone else          — themselves only.
     *
     * Office-wide reach needs an office to scope to. An employee on the list
     * whose emp_dept is not set falls back to their own record rather than
     * matching every other employee with a blank office.
     */
    private function dtrScope(): array
    {
        $guard = $this->getGuard();
        $user  = auth()->guard($guard)->user();

        if ($user->role !== 'employee') {
            return [Employee::all(), 1];
        }

        $own = Employee::where('emp_ID', $user->emp_ID)->get();
        $emp = $own->first();

        $setting = Setting::first();
        // The column holds a comma-joined list of employees.id.
        $list = $setting ? array_filter(explode(',', (string) $setting->dtr_acct)) : [];

        if ($emp && $emp->emp_dept && in_array((string) $emp->id, $list, true)) {
            return [Employee::where('emp_dept', $emp->emp_dept)->get(), 1];
        }

        return [$own, 0];
    }

    /**
     * Refuse a DTR belonging to someone outside the caller's scope.
     *
     * The picker is filtered, but a filtered dropdown is presentation — this is
     * the boundary. Hand-typing another office's emp_ID into the POST lands
     * here.
     */
    private function assertMayViewDtr(?string $employeeId): void
    {
        if ($employeeId === null || $employeeId === '') {
            return;
        }

        [$scope] = $this->dtrScope();

        if (! $scope->contains('emp_ID', $employeeId)) {
            abort(403, 'That employee is outside your DTR access.');
        }
    }

    public function dtrRead()
    {
        $guard = $this->getGuard();
        [$employeeall, $acctstat] = $this->dtrScope();

        return view('dtr.dtr', compact('guard', 'employeeall', 'acctstat'));
    }

    public function dtrSearch(Request $request)
    {
        $guard = $this->getGuard();
        $request->validate([
            'employee' => 'nullable',
            'period' => 'required',
            'date' => 'required|date_format:Y-m',
        ]);

        $empid = $request->employee ?? auth()->guard($guard)->user()->emp_ID;

        // The picker only offers what the caller may see; this refuses anything
        // else, and re-derives acctstat instead of trusting the posted copy.
        $this->assertMayViewDtr($empid);
        [$employeeall, $acctstat] = $this->dtrScope();

        $employee = Employee::where('emp_ID', $empid)->first();

        $employ = $request->input('employee');
        $period = $request->input('period');
        $date = $request->input('date');
        $overtime = $request->input('overtime');

        $dtr = Dtr::where('emp_ID', $employ)
                ->whereYear('date', substr($date, 0, 4)) 
                ->whereMonth('date', substr($date, 5, 2))
                ->get();

        return view('dtr.dtr', compact('guard', 'dtr', 'employeeall', 'employee', 'period', 'date', 'overtime', 'acctstat'));
    }

    public function dtrPdf(Request $request)
    {
        $request->validate([
            'employee' => 'required',
            'period' => 'required',
            'date' => 'required|date_format:Y-m',
            'overtime' => 'nullable',
        ]);
    
        $employeeId = $request->input('employee');
        $period = $request->input('period');
        $date = $request->input('date');
        $overtime = $request->input('overtime');

        // A printable DTR is the same disclosure as the on-screen one.
        $this->assertMayViewDtr($employeeId);

        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

         // Calculate the start and end dates based on the period
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 1:
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = Carbon::createFromDate($year, $month, 15);
                break;
            case 2:
                $startDate = Carbon::createFromDate($year, $month, 16);
                $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
                break;
            case 3:
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
                break;
        }
    
        $employee = Employee::where('employees.emp_ID', $employeeId)
        ->leftjoin('offices', 'employees.emp_dept', '=', 'offices.id')
        ->select(
            'employees.*',
            'offices.office_name'
        )
        ->first();

        $supervisor = Employee::where('id', $employee->supervisor)
        ->select('employees.fname', 'employees.lname', 'employees.mname', 'employees.prefix')
        ->first();
        
        $dtrRecords = Dtr::where('emp_ID', $employeeId)
                        ->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->get();
        $offtime = OfficialTime::where('empid', '=', $employeeId)->first();

        // dd($offtime);
        
        $form = ($overtime == 1) ? 'dtr.dtr-pdf-overtime' : 'dtr.dtr-pdf';
    
        $pdf = PDF::loadView($form, [
            'employee' => $employee,
            'supervisor' => $supervisor,
            'dtrRecords' => $dtrRecords,
            'period' => $period,
            'date' => $date,
            'startDate' => $startDate->format('F j'),
            'endDate' => $endDate->format('j'),
            'year' => $year, 
            'offtime' => $offtime,
        ])->setPaper('Legal', 'portrait');
    
        return $pdf->stream();
    }

    // public function dtrLogs(Request $request)
    // {
    //     $guard = $this->getGuard();
        
    //     $acctstat = 0;
    //     if (auth()->guard($guard)->user()->role == "employee") {
    //         $setting = Setting::first();
        
    //         if ($setting) {
    //             $accntlist = explode(',', $setting->dtr_acct);
    //             $empid = auth()->guard($guard)->user()->emp_ID;
        
    //             $emp = Employee::where('emp_ID', $empid)->first();
        
    //             if (in_array($emp->id, $accntlist)) {
    //                 $employeeall = Employee::all();
    //                 $acctstat = 1;
    //             } else {
    //                 $employeeall = Employee::where('emp_ID', $empid)->get();
    //                 $acctstat = 0;
    //             }
    //         } else {
    //             $employeeall = collect();
    //             $acctstat = 0;
    //         }
    //     } else {
    //         $employeeall = Employee::all();
    //         $acctstat = 1;
    //     }    
    
    //     $data = null;
    
    //     if ($request->isMethod('post')) {
    //         $employeeId = $request->input('employee') ?? auth()->guard($guard)->user()->emp_ID;
    //         $dateFrom = $request->input('date_from', null);
    //         $dateTo = $request->input('date_to', null);
    //         $overtime = $request->input('overtime', null);
    
    //         $dtrRecords = Dtr::where('emp_ID', $employeeId)
    //             ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
    //                 return $query->whereBetween('date', [$dateFrom, $dateTo]);
    //             })
    //             ->get();
    
    //         $devices = Fdevice::all();
    //         $deviceLabels = $devices->pluck('label', 'id')->toArray();
    
    //         $processedLogs = [];
    //         foreach ($dtrRecords as $record) {
    //             $date = $record->date;
    
    //             // Time IN
    //             $timeInArray = explode(',', $record->time_in);
    //             $deviceInArray = explode(',', $record->device_id_in ?? '');
    
    //             foreach ($timeInArray as $index => $timeIn) {
    //                 if (!empty($timeIn)) {
    //                     $deviceInId = $deviceInArray[$index] ?? null;
    //                     $processedLogs[] = [
    //                         'time' => $timeIn,
    //                         'type' => 'time_in',
    //                         'date' => $date,
    //                         'device_label' => $deviceLabels[$deviceInId] ?? 'TBD',
    //                     ];
    //                 }
    //             }
    
    //             // Time OUT
    //             $timeOutArray = explode(',', $record->time_out);
    //             $deviceOutArray = explode(',', $record->device_id_out ?? '');
    
    //             foreach ($timeOutArray as $index => $timeOut) {
    //                 if (!empty($timeOut)) {
    //                     $deviceOutId = $deviceOutArray[$index] ?? null;
    //                     $processedLogs[] = [
    //                         'time' => $timeOut,
    //                         'type' => 'time_out',
    //                         'date' => $date,
    //                         'device_label' => $deviceLabels[$deviceOutId] ?? 'TBD',
    //                     ];
    //                 }
    //             }
    
    //             // OVERTIME
    //             $overtimeArray = explode(',', $record->time_over);
    //             $deviceOverArray = explode(',', $record->device_id_over ?? '');
    
    //             foreach ($overtimeArray as $index => $timeOver) {
    //                 if (!empty($timeOver)) {
    //                     $deviceOverId = $deviceOverArray[$index] ?? null;
    //                     $processedLogs[] = [
    //                         'time' => $timeOver,
    //                         'type' => 'overtime',
    //                         'date' => $date,
    //                         'device_label' => $deviceLabels[$deviceOverId] ?? 'TBD',
    //                     ];
    //                 }
    //             }
    //         }
    
    //         $data = [
    //             "employeeId" => $employeeId,
    //             "dateFrom" => $dateFrom,
    //             "dateTo" => $dateTo,
    //             "overtime" => $overtime,
    //             "logs" => $processedLogs,
    //         ];
    //     }
    
    //     return view('dtr.log', compact('guard', 'employeeall', 'data', 'acctstat'));
    // }  
    // public function logDtrView($employeeId, $dateFrom = null, $dateTo = null, $overtime = null)
    // {
    //     $guard = $this->getGuard();
    //     $currentDate = Carbon::now()->toDateString();
    
    //     $data = [
    //         "employeeId" => $employeeId,
    //         "dateFrom" => $dateFrom,
    //         "dateTo" => $dateTo,
    //         "overtime" => $overtime
    //     ];
    
    //     // Fetch DTR records with necessary conditions
    //     $dtrRecords = Dtr::join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
    //         ->when(is_null($dateFrom) && is_null($dateTo), function ($query) use ($currentDate, $employeeId) {
    //             return $query->whereDate('dtrs.date', $currentDate)
    //                 ->where('dtrs.emp_ID', $employeeId);
    //         })
    //         ->when(!is_null($dateFrom) && !is_null($dateTo), function ($query) use ($employeeId, $dateFrom, $dateTo) {
    //             return $query->where('dtrs.emp_ID', $employeeId)
    //                 ->whereBetween('dtrs.date', [$dateFrom, $dateTo]);
    //         })
    //         ->select('dtrs.*', 'employees.lname', 'employees.fname', 'employees.suffix')
    //         ->orderBy('dtrs.date', 'asc')
    //         ->orderBy('dtrs.time_in', 'asc')
    //         ->orderBy('dtrs.time_out', 'asc')
    //         ->get();
    
    //     $groupedRecords = $dtrRecords->groupBy('emp_ID');
    
    //     $devices = Fdevice::all();
    //     $deviceLabels = $devices->pluck('label', 'id')->toArray();
    
    //     $processedLogs = [];
    
    //     foreach ($groupedRecords as $employeeId => $records) {
    //         $logSessions = [];
            
    //         foreach ($records as $record) {
    //             if($overtime == null){
    //                 $timeInArray = explode(',', $record->time_in);
    //                 $deviceInCampArray = explode(',', $record->device_id_in);
        
    //                 foreach ($timeInArray as $index => $timeIn) {
    //                     $deviceInId = $deviceInCampArray[$index] ?? null;
    //                     $logSessions[] = [
    //                         'time' => $timeIn,
    //                         'type' => 'time_in',
    //                         'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Noon' : 'Afternoon'),
    //                         'date' => $record->date,
    //                         'lname' => $record->lname,
    //                         'fname' => $record->fname,
    //                         'suffix' => $record->suffix,
    //                         'device_in_label' => $deviceLabels[$deviceInId] ?? 'TBD',
    //                         'device_in_campus' => $deviceCampus[$deviceInId] ?? 'TBD',
    //                     ];
    //                 }
        
    //                 $timeOutArray = explode(',', $record->time_out);
    //                 $deviceOutCampArray = explode(',', $record->device_id_out);
        
    //                 foreach ($timeOutArray as $index => $timeOut) {
    //                     $deviceOutId = $deviceOutCampArray[$index] ?? null;
    //                     $logSessions[] = [
    //                         'time' => $timeOut,
    //                         'type' => 'time_out',
    //                         'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Afternoon' : 'Evening'),
    //                         'date' => $record->date,
    //                         'lname' => $record->lname,
    //                         'fname' => $record->fname,
    //                         'suffix' => $record->suffix,
    //                         'device_out_label' => $deviceLabels[$deviceOutId] ?? 'TBD',
    //                         'device_out_campus' => $deviceCampus[$deviceOutId] ?? 'TBD',
    //                     ];
    //                 }
    //             }
    //             $overtimeArray = explode(',', $record->time_over);
    //             $deviceOvertimeCampArray = explode(',', $record->device_id_over);
                
    //             foreach ($overtimeArray as $index => $timeOut) {
    //                 $deviceOvertimeId = $deviceOvertimeCampArray[$index] ?? null;
    //                 $logSessions[] = [
    //                     'time' => $timeOut,
    //                     'type' => 'overtime',
    //                     'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Afternoon' : 'Evening'),
    //                     'date' => $record->date,
    //                     'lname' => $record->lname,
    //                     'fname' => $record->fname,
    //                     'suffix' => $record->suffix,
    //                     'device_out_label' => $deviceLabels[$deviceOvertimeId] ?? 'TBD',
    //                     'device_out_campus' => $deviceCampus[$deviceOvertimeId] ?? 'TBD',
    //                 ];
    //             }
                
    //         }
    
    //         // Sort sessions by time
    //         usort($logSessions, function ($a, $b) {
    //             return strtotime($a['time']) - strtotime($b['time']);
    //         });
    
    //         $processedLogs[$employeeId] = $logSessions;
    //     }
    
    //     // Define paper size and margins
    //     $customPaper = [0, 0, 612, 970];
    //     $page = ($overtime == 1) ? 'dtr.logs-pdf-overtime' : 'dtr.logs-pdf';
    //     $pdf = \PDF::loadView($page, compact('guard', 'dtrRecords', 'processedLogs', 'data'))
    //         ->setPaper($customPaper, 'portrait')
    //         ->setOptions([
    //             'margin-top' => 10,
    //             'margin-right' => 10,
    //             'margin-bottom' => 10,
    //             'margin-left' => 10,
    //         ])
    //         ->setCallbacks([
    //             'before_render' => function ($domPdf) {
    //                 $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
    //             },
    //         ]);
    
    //     return $pdf->stream();
    // }
    public function dtrLogs(Request $request)
    {
        $guard = $this->getGuard();
        
        [$employeeall, $acctstat] = $this->dtrScope();

        $data = null;

        if ($request->isMethod('post')) {
            $employeeId = $request->input('employee') ?? auth()->guard($guard)->user()->emp_ID;
            $this->assertMayViewDtr($employeeId);
            $dateFrom = $request->input('date_from', null);
            $dateTo = $request->input('date_to', null);
            $overtime = $request->input('overtime', null);

            $dtrRecords = Dtr::where('emp_ID', $employeeId)
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    return $query->whereBetween('date', [$dateFrom, $dateTo]);
                })
                ->get();

            // Devices
            $devices = Fdevice::all();
            $deviceLabels = $devices->pluck('label', 'id')->toArray();

            // Logzones (IDs are already negative)
            $zones = Logzone::all();
            $zoneLabels = $zones->pluck('label', 'id')->toArray();

            // Resolver
            $labelFor = function ($rawId) use ($deviceLabels, $zoneLabels) {
                if ($rawId === null) return 'TBD';
                $s = trim((string)$rawId);
                if ($s === '' || !preg_match('/^-?\d+$/', $s)) return 'TBD';
                $id = (int)$s;
                return $id < 0 ? ($zoneLabels[$id] ?? 'TBD') : ($deviceLabels[$id] ?? 'TBD');
            };

            $processedLogs = [];
            foreach ($dtrRecords as $record) {
                $date = $record->date;

                // Time IN
                $timeInArray   = array_map('trim', explode(',', $record->time_in ?? ''));
                $deviceInArray = array_map('trim', explode(',', $record->device_id_in ?? ''));

                foreach ($timeInArray as $index => $timeIn) {
                    if ($timeIn !== '') {
                        $deviceInId = $deviceInArray[$index] ?? null;
                        $processedLogs[] = [
                            'time' => $timeIn,
                            'type' => 'time_in',
                            'date' => $date,
                            'device_label' => $labelFor($deviceInId),
                        ];
                    }
                }

                // Time OUT
                $timeOutArray   = array_map('trim', explode(',', $record->time_out ?? ''));
                $deviceOutArray = array_map('trim', explode(',', $record->device_id_out ?? ''));

                foreach ($timeOutArray as $index => $timeOut) {
                    if ($timeOut !== '') {
                        $deviceOutId = $deviceOutArray[$index] ?? null;
                        $processedLogs[] = [
                            'time' => $timeOut,
                            'type' => 'time_out',
                            'date' => $date,
                            'device_label' => $labelFor($deviceOutId),
                        ];
                    }
                }

                // OVERTIME
                $overtimeArray   = array_map('trim', explode(',', $record->time_over ?? ''));
                $deviceOverArray = array_map('trim', explode(',', $record->device_id_over ?? ''));

                foreach ($overtimeArray as $index => $timeOver) {
                    if ($timeOver !== '') {
                        $deviceOverId = $deviceOverArray[$index] ?? null;
                        $processedLogs[] = [
                            'time' => $timeOver,
                            'type' => 'overtime',
                            'date' => $date,
                            'device_label' => $labelFor($deviceOverId),
                        ];
                    }
                }
            }

            $data = [
                "employeeId" => $employeeId,
                "dateFrom" => $dateFrom,
                "dateTo" => $dateTo,
                "overtime" => $overtime,
                "logs" => $processedLogs,
            ];
        }

        return view('dtr.log', compact('guard', 'employeeall', 'data', 'acctstat'));
    }
    public function logDtrView($employeeId, $dateFrom = null, $dateTo = null, $overtime = null)
    {
        // The employee is named in the URL, so this one is reachable by simply
        // editing the address bar.
        $this->assertMayViewDtr($employeeId);

        $guard = $this->getGuard();
        $currentDate = Carbon::now()->toDateString();

        $data = [
            "employeeId" => $employeeId,
            "dateFrom" => $dateFrom,
            "dateTo" => $dateTo,
            "overtime" => $overtime
        ];

        $dtrRecords = Dtr::join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
            ->when(is_null($dateFrom) && is_null($dateTo), function ($query) use ($currentDate, $employeeId) {
                return $query->whereDate('dtrs.date', $currentDate)
                            ->where('dtrs.emp_ID', $employeeId);
            })
            ->when(!is_null($dateFrom) && !is_null($dateTo), function ($query) use ($employeeId, $dateFrom, $dateTo) {
                return $query->where('dtrs.emp_ID', $employeeId)
                            ->whereBetween('dtrs.date', [$dateFrom, $dateTo]);
            })
            ->select('dtrs.*', 'employees.lname', 'employees.fname', 'employees.suffix')
            ->orderBy('dtrs.date', 'asc')
            ->orderBy('dtrs.time_in', 'asc')
            ->orderBy('dtrs.time_out', 'asc')
            ->get();

        $groupedRecords = $dtrRecords->groupBy('emp_ID');

        // Devices
        $devices = Fdevice::all();
        $deviceLabels = $devices->pluck('label', 'id')->toArray();

        // Logzones (IDs are already negative)
        $zones = Logzone::all();
        $zoneLabels = $zones->pluck('label', 'id')->toArray();

        // Resolver
        $labelFor = function ($rawId) use ($deviceLabels, $zoneLabels) {
            if ($rawId === null) return 'TBD';
            $s = trim((string)$rawId);
            if ($s === '' || !preg_match('/^-?\d+$/', $s)) return 'TBD';
            $id = (int)$s;
            return $id < 0 ? ($zoneLabels[$id] ?? 'TBD') : ($deviceLabels[$id] ?? 'TBD');
        };

        $processedLogs = [];

        foreach ($groupedRecords as $empId => $records) {
            $logSessions = [];
            
            foreach ($records as $record) {
                if ($overtime === null) {
                    // time_in
                    $timeInArray       = array_map('trim', explode(',', $record->time_in ?? ''));
                    $deviceInCampArray = array_map('trim', explode(',', $record->device_id_in ?? ''));
                    foreach ($timeInArray as $index => $timeIn) {
                        if ($timeIn === '') continue;
                        $deviceInId = $deviceInCampArray[$index] ?? null;
                        $logSessions[] = [
                            'time' => $timeIn,
                            'type' => 'time_in',
                            'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Noon' : 'Afternoon'),
                            'date' => $record->date,
                            'lname' => $record->lname,
                            'fname' => $record->fname,
                            'suffix' => $record->suffix,
                            'device_in_label' => $labelFor($deviceInId),
                        ];
                    }

                    // time_out
                    $timeOutArray       = array_map('trim', explode(',', $record->time_out ?? ''));
                    $deviceOutCampArray = array_map('trim', explode(',', $record->device_id_out ?? ''));
                    foreach ($timeOutArray as $index => $timeOut) {
                        if ($timeOut === '') continue;
                        $deviceOutId = $deviceOutCampArray[$index] ?? null;
                        $logSessions[] = [
                            'time' => $timeOut,
                            'type' => 'time_out',
                            'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Afternoon' : 'Evening'),
                            'date' => $record->date,
                            'lname' => $record->lname,
                            'fname' => $record->fname,
                            'suffix' => $record->suffix,
                            'device_out_label' => $labelFor($deviceOutId),
                        ];
                    }
                }

                // overtime
                $overtimeArray       = array_map('trim', explode(',', $record->time_over ?? ''));
                $deviceOverCampArray = array_map('trim', explode(',', $record->device_id_over ?? ''));
                foreach ($overtimeArray as $index => $timeOver) {
                    if ($timeOver === '') continue;
                    $deviceOverId = $deviceOverCampArray[$index] ?? null;
                    $logSessions[] = [
                        'time' => $timeOver,
                        'type' => 'overtime',
                        'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Afternoon' : 'Evening'),
                        'date' => $record->date,
                        'lname' => $record->lname,
                        'fname' => $record->fname,
                        'suffix' => $record->suffix,
                        'device_out_label' => $labelFor($deviceOverId),
                    ];
                }
            }

            usort($logSessions, function ($a, $b) {
                return strtotime($a['time']) <=> strtotime($b['time']);
            });

            $processedLogs[$empId] = $logSessions;
        }

        $customPaper = [0, 0, 612, 970];
        $page = ($overtime == 1) ? 'dtr.logs-pdf-overtime' : 'dtr.logs-pdf';
        $pdf = \PDF::loadView($page, compact('guard', 'dtrRecords', 'processedLogs', 'data'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
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

        return $pdf->stream();
    }

    public function storeDtr(Request $request)
    {
        $data = $request->json()->all();

        if (!is_array($data)) {
            return response()->json(['error' => 'Invalid JSON format'], 400);
        }

        foreach ($data as $item) {
            if (!isset($item['emp_ID']) || !isset($item['date'])) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            Dtr::create([
                'device_id_in' => $item['device_id_in'] ?? null,
                'device_id_out' => $item['device_id_out'] ?? null,
                'device_id_over' => $item['device_id_over'] ?? null,
                'emp_ID' => $item['emp_ID'],
                'time_in' => $item['time_in'] ?? null,
                'time_out' => $item['time_out'] ?? null,
                'time_over' => $item['time_over'] ?? null,
                'date' => $item['date'],
            ]);
        }

        return response()->json(['message' => 'DTR records stored successfully'], 201);
    }
}
