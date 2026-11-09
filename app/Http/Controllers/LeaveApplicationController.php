<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\Office;
use App\Models\Setting;
use Carbon\Carbon;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A leave application goes through four signatures:
 *
 *   1. HR checks the days (leaveWpay)              emp_esign 0 -> 1
 *   2. the employee e-signs (by 0)                 emp_esign 1 -> 2
 *   3. the HRMO approves (by 1)                    status 1 -> 2
 *   4. the supervisor or the office OIC (by 2)     status 2 -> 3, credits deducted
 *   5. the Mayor or the Vice Mayor (approve-pres)  status 3 -> 4, history 2
 *
 * remarks_stat records a refusal: 1 HR, 2 the supervisor, 3 the Mayor / Vice
 * Mayor, 4 HR cancelling an approved leave. A refused or finished application
 * moves to History (history 2).
 *
 * Every step checks that the person posting is the one whose turn it is and
 * that the application is still at that step: the buttons on the page are
 * only presentation, and a replayed or double-clicked post must not deduct
 * credits twice or let an employee approve their own leave.
 */
class LeaveApplicationController extends Controller
{
    /** Leave-notification categories in the employee bell. */
    private const NOTIFY_TO_SIGN = 1;       // the applicant: HR checked the days, please e-sign
    private const NOTIFY_APPROVED = 2;      // the applicant: final approval given
    private const NOTIFY_DISAPPROVED = 3;   // the applicant: refused, with the reason
    private const NOTIFY_CANCELLED = 4;     // the applicant: HR cancelled it
    private const NOTIFY_SUPERVISOR = 5;    // the supervisor / OIC: waiting on your signature
    private const NOTIFY_APPROVER = 6;      // the Mayor / Vice Mayor: waiting on final approval

    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    private function me()
    {
        $guard = $this->getGuard();

        return $guard ? auth()->guard($guard)->user() : null;
    }

    private function deny(string $message, int $status = 403)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /** May the signed-in HR account act at the HRMO stop? */
    private function isHr(): bool
    {
        return $this->getGuard() === 'web' && $this->me()->canApproveLeave();
    }

    /**
     * Is the signed-in employee the one who signs for the applicant's
     * supervisor: the supervisor named on the application, or the OIC of the
     * applicant's office.
     */
    private function isSupervisorOf(LeaveApplication $leave, Employee $applicant): bool
    {
        if ($this->getGuard() !== 'employee') {
            return false;
        }

        $meId = (int) $this->me()->id;
        if ((int) $leave->supervisor === $meId) {
            return true;
        }

        $office = Office::find($applicant->emp_dept);

        return $office && (int) $office->oic_id === $meId;
    }

    private function approvingRole(): ?string
    {
        if ($this->getGuard() !== 'employee') {
            return null;
        }

        return Setting::first()?->approvingRole($this->me()->id);
    }

    /**
     * Take the application's days off the employee's leave credits ($sign -1)
     * or give them back ($sign +1). The split between vacation and sick leave
     * was worked out by HR when the days were checked.
     */
    private function applyCredits(LeaveApplication $leave, Employee $employee, int $sign): void
    {
        $paid = (float) ($leave->days ?? 0) - (float) ($leave->day_wpay ?? 0);
        $lessVl = (float) ($leave->less_vl ?? 0);
        $lessSl = (float) ($leave->less_sl ?? 0);

        $employee->vl = $employee->vl ?? 0;
        $employee->sl = $employee->sl ?? 0;

        switch ((int) $leave->leave_type) {
            case 1:
            case 2:
                $employee->vl += $sign * $lessVl;
                break;
            case 3:
                $employee->vl += $sign * $lessVl;
                $employee->sl += $sign * $lessSl;
                break;
            case 6:
                $employee->special_pl = ($employee->special_pl ?? 0) + $sign * $paid;
                break;
            case 14:
                $employee->servcred_leave = ($employee->servcred_leave ?? 0) + $sign * $paid;
                break;
            case 15:
                $employee->well_leave = ($employee->well_leave ?? 0) + $sign * $paid;
                break;
        }

        $employee->save();
    }

    /**
     * Credits come off when the supervisor signs (status 3). A supervisor's
     * refusal never took any — older rows marked it status 3 as well.
     */
    private function creditsTaken(LeaveApplication $leave): bool
    {
        return (int) $leave->status >= 3 && (int) $leave->remarks_stat !== 2;
    }

    private function notifyEmployee(LeaveApplication $leave, $empId, int $category): void
    {
        if (blank($empId)) {
            return;
        }

        Notification::create([
            'empid' => $empId,
            'lapp_id' => $leave->id,
            'category' => $category,
            'utype' => 'employee',
            'module' => 'leave',
        ]);
    }

    /** The supervisor and, if the office has one, its OIC. */
    private function notifySupervisor(LeaveApplication $leave, Employee $applicant): void
    {
        $ids = array_filter([(int) $leave->supervisor, (int) Office::find($applicant->emp_dept)?->oic_id]);
        foreach (Employee::whereIn('id', array_unique($ids))->pluck('emp_ID') as $empId) {
            $this->notifyEmployee($leave, $empId, self::NOTIFY_SUPERVISOR);
        }
    }

    /** Both the Mayor and the Vice Mayor: whichever is available signs. */
    private function notifyApprovers(LeaveApplication $leave): void
    {
        $setting = Setting::first();
        $ids = array_filter([(int) $setting?->mayor, (int) $setting?->vice_mayor]);
        foreach (Employee::whereIn('id', array_unique($ids))->pluck('emp_ID') as $empId) {
            $this->notifyEmployee($leave, $empId, self::NOTIFY_APPROVER);
        }
    }

    private function markRead(LeaveApplication $leave, string $utype, array $categories): void
    {
        Notification::where('lapp_id', $leave->id)
            ->where('module', 'leave')
            ->where('utype', $utype)
            ->whereIn('category', $categories)
            ->update(['status' => 1]);
    }

    /** The stored PDF is a convenience; failing to draw it must not undo a signature. */
    private function regenerate(int $id): void
    {
        try {
            $this->genApplication($id);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function LeaveAppCreate(Request $request)
    {
        $request->validate([
            'empid' => 'required|exists:employees,emp_ID',
            'date_range' => 'required|string',
            'leave_type' => 'required|integer|between:1,15',
            'days' => 'required|numeric|gt:0',
            'date_filing' => 'nullable|date',
        ]);

        $guard = $this->getGuard();
        $employee = Employee::where('emp_ID', $request->empid)->first();

        // An employee files for themselves only.
        if ($guard === 'employee' && (int) auth()->guard('employee')->user()->id !== (int) $employee->id) {
            return redirect()->back()->with('error', 'You can only file your own leave application.');
        }

        if (!$employee->isLeaveEligible()) {
            return redirect()->back()->with('error', 'This employee\'s employment status does not earn leave credits.');
        }

        $checkleave = LeaveApplication::where('empid', $request->empid)->where('history', 1)->whereNotIn('status', [3, 4])->exists();
        if ($checkleave) {
            return redirect()->back()->with('error', 'One Leave Application at a time');
        }

        if (is_null($employee->supervisor) || $employee->supervisor == 0 || !($supemp = Employee::find($employee->supervisor))) {
            return redirect()->back()->with(['error' => 'No Supervisor Assigned']);
        }

        // Without an HR Head and someone to give final approval the application
        // would have nowhere to go; it used to fail here with a server error.
        $setting = Setting::first();
        $hremp = $setting?->hr ? Employee::find($setting->hr) : null;
        $approverId = $setting?->mayor ?: $setting?->vice_mayor;
        $approver = $approverId ? Employee::find($approverId) : null;

        if (!$hremp || !$approver) {
            return redirect()->back()->with('error', 'Leave approvals are not set up yet. HR has to choose the HR Head and the Mayor (or Vice Mayor) in Settings first.');
        }

        $leaveDetails = array_filter((array) $request->input('leave_detail', []), fn ($v) => filled($v));
        $firstDetail = reset($leaveDetails) ?: null;
        $purpose = $request->leave_purpose ?: null;

        $lastTransnum = (int) LeaveApplication::max(DB::raw('CAST(transnum AS UNSIGNED)'));
        $transnum = str_pad($lastTransnum + 1, 6, '0', STR_PAD_LEFT);

        $filed = $request->date_filing ?: Carbon::now('Asia/Manila')->toDateString();

        $leaveApplication = LeaveApplication::create([
            'transnum' => $transnum,
            'empid' => $request->empid,
            'position' => $employee->position,
            'leave_type' => $request->leave_type,
            'leave_purpose' => $purpose,
            'leave_detail' => $firstDetail,
            'date_range' => $request->date_range,
            'days' => $request->days,
            'total_vl' => $employee->vl,
            'total_sl' => $employee->sl,
            'date_filing' => $filed . ' ' . Carbon::now('Asia/Manila')->format('H:i:s'),
            'salary' => $employee->emp_salary,
            'commutation' => ($purpose == 7 || $purpose == 8) ? 2 : 1,
            'supervisor' => $employee->supervisor,
            'sup_prefix' => $supemp->prefix,
            // The Mayor is the default approver; if the Vice Mayor signs
            // instead, the approver fields are overwritten at approval time.
            'approver' => $approver->id,
            'approver_prefix' => $approver->prefix,
            'approver_role' => $approver->id == $setting->mayor ? 'Mayor' : 'Vice Mayor',
            'hr' => $hremp->id,
            'hr_prefix' => $hremp->prefix,
            'department' => $employee->emp_dept,
            'status' => 1,
            'emp_esign' => 0,
            'remarks_stat' => 0,
            'holiday' => 0,
            'day_wpay' => 0,
            'history' => 1,
        ]);

        Notification::create([
            'empid' => $request->empid,
            'lapp_id' => $leaveApplication->id,
            'category' => 1,
            'utype' => 'hr',
            'module' => 'leave',
        ]);

        return redirect()->back()->with('success', 'Submitted successfully');
    }

    public function leaveStatus($id = null){
        $guard = $this->getGuard();
        $me = auth()->guard($guard)->user();

        // HR opens a named employee's page (the first one when none is named,
        // as on the credits screen); an employee always opens their own.
        $employee = $guard === 'web'
            ? ($id ? Employee::find($id) : Employee::leaveEligible()->first())
            : Employee::find($me->id);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $empid = $employee->id;

        $oic = Office::leftJoin('employees as oic', 'offices.oic_id', '=', 'oic.id')
            ->where('offices.id', $employee->emp_dept)
            ->select(
                'offices.*',
                'oic.fname as ofname',
                'oic.lname as olname',
                'oic.mname as omname',
                'oic.suffix as osuffix'
            )
            ->first();
        $isOfficeHead = Office::where('office_head_id', $employee->id)->first();

        // Left joins for the two signatories: an application filed before a
        // supervisor was assigned or an HR Head chosen has neither, and would
        // otherwise never show here.
        $leavesapp = LeaveApplication::where('empid', $employee->emp_ID)
        ->leftJoin('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
        ->join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
        ->leftJoin('employees as hr', 'hr.id', '=', 'leave_applications.hr')
        ->select(
            'leave_applications.*',
            'emp.id as employid',
            'sup.lname as supervisor_lname',
            'sup.fname as supervisor_fname',
            'sup.mname as supervisor_mname',
            'sup.suffix as supervisor_suffix',
            'hr.lname as hr_lname',
            'hr.fname as hr_fname',
            'hr.mname as hr_mname',
            'hr.suffix as hr_suffix',
            'hr.id as hr_id'
        )
        ->orderBy('leave_applications.id', 'desc')
        ->where('leave_applications.history', 1)
        ->get();

        // Left joins, and a blank row as the last resort: nobody is an
        // approving official until one is named in Settings.
        $setting = Setting::leftJoin('employees as hr', 'hr.id', '=', 'settings.hr')
        ->leftJoin('employees as mayor', 'mayor.id', '=', 'settings.mayor')
        ->select(
            'settings.*',
            'hr.lname as hr_lname',
            'hr.fname as hr_fname',
            'hr.mname as hr_mname',
            'hr.suffix as hr_suffix',
            'mayor.lname as mayor_lname',
            'mayor.fname as mayor_fname',
            'mayor.mname as mayor_mname',
            'mayor.suffix as mayor_suffix',
        )
        ->first() ?? new Setting;

        // Other people's applications that come to this employee: the ones
        // they supervise, the ones from an office they head or are OIC of,
        // and, for the Mayor and the Vice Mayor, everything waiting for final
        // approval. (This used to depend on the employee's own office having
        // an OIC row, so an assigned supervisor who was not office head never
        // saw what was waiting on them, and the Mayor never saw the
        // applications of the department heads they supervise.)
        $leavesapphead = collect();
        if ($guard === 'employee') {
            $isApprover = $setting->isApprovingOfficial($me->id);
            $offices = Office::where('oic_id', $me->id)->orWhere('office_head_id', $me->id)->pluck('id');

            $leavesapphead = LeaveApplication::join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
                ->leftJoin('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
                ->leftJoin('employees as hr', 'hr.id', '=', 'leave_applications.hr')
                ->leftJoin('offices as eo', 'eo.id', '=', 'emp.emp_dept')
                ->where('leave_applications.history', 1)
                ->where('leave_applications.empid', '!=', $employee->emp_ID)
                ->where(function ($q) use ($me, $offices, $isApprover) {
                    $q->where('leave_applications.supervisor', $me->id);
                    if ($offices->isNotEmpty()) {
                        $q->orWhereIn('emp.emp_dept', $offices);
                    }
                    if ($isApprover) {
                        $q->orWhere('leave_applications.status', 3);
                    }
                })
                ->select(
                    'leave_applications.*',
                    'hr.lname as hr_lname',
                    'hr.fname as hr_fname',
                    'hr.mname as hr_mname',
                    'hr.suffix as hr_suffix',
                    'emp.id as employid',
                    'emp.lname as employee_lname',
                    'emp.fname as employee_fname',
                    'emp.mname as employee_mname',
                    'emp.suffix as employee_suffix',
                    'sup.lname as supervisor_lname',
                    'sup.fname as supervisor_fname',
                    'sup.mname as supervisor_mname',
                    'sup.suffix as supervisor_suffix',
                    'sup.emp_dept as supervisor_emp_dept',
                    'eo.oic_id as office_oic_id',
                )
                ->orderBy('leave_applications.id', 'desc')
                ->get();
        }

        $emplalls = Employee::leaveEligible()->get();

        return view("leaves.status", compact('guard', 'setting', 'employee', 'leavesapp', 'isOfficeHead', 'leavesapphead', 'oic', 'emplalls', 'empid'));
    }

    /** HR's first look: holidays and days without pay, then to the employee to sign. */
    public function leaveWpay(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'day_wpay' => 'required|numeric|min:0',
            'holiday' => 'required|numeric|min:0',
        ]);

        if ($this->getGuard() !== 'web') {
            return $this->deny('Only HR checks the days of a leave application.');
        }

        $leaveApplication = LeaveApplication::find($request->id);
        if (!$leaveApplication) {
            return $this->deny('Leave application not found.', 404);
        }
        if ((int) $leaveApplication->history !== 1 || (int) $leaveApplication->status !== 1 || (int) $leaveApplication->emp_esign === 2) {
            return $this->deny('This application is past the point where its days can be changed.', 422);
        }

        $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Checking the days again (after a return) starts from the days applied
        // for, not from a figure that already had the holidays taken off.
        $applied = (float) $leaveApplication->days;
        if ((int) $leaveApplication->emp_esign !== 0) {
            $applied += (float) $leaveApplication->holiday;
        }

        $days = $applied - (float) $request->holiday;
        if ($days < 0) {
            return response()->json(['error' => 'That is more holidays than days applied for.'], 422);
        }
        $daysdeduct = $days - (float) $request->day_wpay;
        if ($daysdeduct < 0) {
            return response()->json(['error' => 'That is more days without pay than the days left.'], 422);
        }

        $leavetype = (int) $leaveApplication->leave_type;
        $leaveApplication->holiday = $request->holiday;
        $leaveApplication->days = $days;
        $leaveApplication->day_wpay = $request->day_wpay;
        $leaveApplication->less_vl = 0;
        $leaveApplication->less_sl = 0;

        $vl = (float) ($employee->vl ?? 0);
        $sl = (float) ($employee->sl ?? 0);

        if ($leavetype == 3) {
            if ($daysdeduct > $sl) {
                $remainingDays = $daysdeduct - $sl;
                if ($remainingDays > $vl) {
                    return response()->json(['error' => 'Insufficient leave credits'], 400);
                }
                $leaveApplication->less_sl = $sl;
                $leaveApplication->less_vl = $remainingDays;
            } else {
                $leaveApplication->less_sl = $daysdeduct;
            }
        }

        if ($leavetype == 1 || $leavetype == 2) {
            if ($daysdeduct > $vl) {
                return response()->json(['error' => 'Insufficient leave credits'], 400);
            }
            $leaveApplication->less_vl = $daysdeduct;
        }

        $balances = [6 => 'special_pl', 14 => 'servcred_leave', 15 => 'well_leave'];
        if (isset($balances[$leavetype]) && $daysdeduct > (float) ($employee->{$balances[$leavetype]} ?? 0)) {
            return response()->json(['error' => 'Insufficient leave credits'], 400);
        }

        $leaveApplication->total_vl = $employee->vl;
        $leaveApplication->total_sl = $employee->sl;
        $leaveApplication->emp_esign = 1;
        $leaveApplication->as_of = Carbon::now();
        $leaveApplication->save();

        $this->markRead($leaveApplication, 'hr', [1]);
        $this->notifyEmployee($leaveApplication, $leaveApplication->empid, self::NOTIFY_TO_SIGN);

        return response()->json([
            'success' => true,
            'message' => 'Leave approved successfully.',
            'datetime' => now(),
            'withpay' =>  $daysdeduct,
            'withoutpay' => $request->day_wpay,
        ]);
    }

    public function leaveApprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:leave_applications,id',
            'by' => 'required|integer|min:0|max:3',
            'day_wpay' => 'nullable|numeric',
        ]);

        // Final approval has its own checks: only the Mayor or the Vice Mayor.
        if ((int) $request->by === 3) {
            return $this->leaveApprovePres($request);
        }

        $guard = $this->getGuard();
        $me = $this->me();
        $leaveApplication = LeaveApplication::find($request->id);
        $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');

        if ((int) $leaveApplication->history !== 1 || (int) $leaveApplication->remarks_stat !== 0) {
            return $this->deny('This leave application has already been decided.', 422);
        }

        // ------------------------------------------------- the employee signs
        if ((int) $request->by === 0) {
            if ($guard !== 'employee' || (int) $me->id !== (int) $employee?->id) {
                return $this->deny('Only the employee who filed it can sign this application.');
            }
            if ((int) $leaveApplication->emp_esign !== 1) {
                return $this->deny('This application is not waiting on your signature.', 422);
            }
            if (blank($me->esign)) {
                return $this->deny('Upload your e-signature first.', 422);
            }

            $leaveApplication->emp_esign = 2;
            $leaveApplication->save();

            $this->markRead($leaveApplication, 'employee', [self::NOTIFY_TO_SIGN]);
            Notification::create([
                'empid' => $leaveApplication->empid,
                'lapp_id' => $leaveApplication->id,
                'category' => 2,
                'utype' => 'hr',
                'module' => 'leave',
            ]);
        }

        // ---------------------------------------------------- the HRMO approves
        if ((int) $request->by === 1) {
            if (!$this->isHr()) {
                return $this->deny('Only HR with the Leave right can approve at the HRMO stop.');
            }
            if ((int) $leaveApplication->status !== 1 || (int) $leaveApplication->emp_esign !== 2) {
                return $this->deny('This application is not waiting on the HRMO.', 422);
            }

            $leaveApplication->hr_sdate = Carbon::now();
            $leaveApplication->hr_sign = 2;
            $leaveApplication->status = 2;
            $leaveApplication->save();

            $this->markRead($leaveApplication, 'hr', [2]);
            $this->notifySupervisor($leaveApplication, $employee);
        }

        // ------------------- the supervisor or OIC signs, or HR forwards it on
        if ((int) $request->by === 2) {
            $asSupervisor = $this->isSupervisorOf($leaveApplication, $employee);
            $asHr = $this->isHr();

            if (!$asSupervisor && !$asHr) {
                return $this->deny('Only the immediate supervisor, the office OIC or HR can sign at this stop.');
            }
            // Signing twice would take the leave credits twice.
            if ((int) $leaveApplication->status >= 3) {
                return $this->deny('This application has already been signed for the supervisor.', 422);
            }
            if ($asSupervisor && !$asHr && (int) $leaveApplication->status !== 2) {
                return $this->deny('This application is not waiting on the supervisor yet.', 422);
            }
            if ((int) $leaveApplication->emp_esign === 0) {
                return $this->deny('HR has to check the days before this application can go further.', 422);
            }

            $leaveApplication->sup_sdate = Carbon::now();
            $leaveApplication->hr_sign = 2;

            if ($asSupervisor && (int) $me->id !== (int) $leaveApplication->supervisor) {
                // The office OIC signs in the supervisor's place.
                $leaveApplication->oic = $me->id;
            } else {
                $leaveApplication->sup_sign = 2;
            }

            $this->applyCredits($leaveApplication, $employee, -1);
            $leaveApplication->status = 3;
            $leaveApplication->save();

            $this->markRead($leaveApplication, 'hr', [2]);
            $this->markRead($leaveApplication, 'employee', [self::NOTIFY_SUPERVISOR]);
            $this->notifyApprovers($leaveApplication);
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave approved successfully.',
            'datetime' => $currdate1,
        ]);
    }

    public function leaveApprovePres(Request $request)
    {
        $request->validate([
            'id'   => 'required|integer|exists:leave_applications,id',
            'by'   => 'required|integer|in:3', // only allow final approval
        ]);

        $signatoryId = $this->me()->id;
        $role = $this->approvingRole();

        // Either the Mayor or the Vice Mayor may sign, whoever is available.
        if ($role === null) {
            return response()->json([
                'success' => false,
                'message' => 'Only the Mayor or the Vice Mayor may approve a leave application.',
            ], 403);
        }

        $leaveApplication = LeaveApplication::find($request->id);

        if ((int) $leaveApplication->history !== 1 || (int) $leaveApplication->status !== 3 || (int) $leaveApplication->remarks_stat !== 0) {
            return $this->deny('This application is not waiting on final approval.', 422);
        }

        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');

        // Final approval. Record who actually signed — the form prints their title.
        $signatory = Employee::find($signatoryId);
        $leaveApplication->approver = $signatoryId;
        $leaveApplication->approver_prefix = $signatory?->prefix;
        $leaveApplication->approver_role = $role;
        $leaveApplication->approver_sdate = Carbon::now();
        $leaveApplication->approver_sign = 2;
        $leaveApplication->history = 2; // mark in history
        $leaveApplication->status = 4;  // approved by Mayor / Vice Mayor
        $leaveApplication->save();

        // Rebuild the PDF so it carries the approver's e-signature. No upload
        // is required — the signed form is generated from the record itself.
        $this->regenerate($leaveApplication->id);

        $this->markRead($leaveApplication, 'employee', [self::NOTIFY_APPROVER]);
        $this->notifyEmployee($leaveApplication, $leaveApplication->empid, self::NOTIFY_APPROVED);

        return response()->json([
            'success'  => true,
            'message'  => 'Leave approved by the ' . $role . ' successfully.',
            'datetime' => $currdate1,
        ]);
    }

    /**
     * Refuse with a reason: 1 HR, 2 the supervisor / OIC, 3 the Mayor / Vice
     * Mayor. 4 is HR cancelling a leave already approved. Whatever credits the
     * application took are given back.
     */
    public function leaveDisapprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'by' => 'required|integer|in:1,2,3,4',
            'remarks' => 'required|string',
            'day_wpay' => 'nullable|numeric',
        ]);

        $leaveApplication = LeaveApplication::find($request->id);
        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.',
            ], 404);
        }

        $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
        $currdate = Carbon::now('Asia/Manila')->toDateTimeString();
        $by = (int) $request->by;
        $inProgress = (int) $leaveApplication->history === 1 && (int) $leaveApplication->remarks_stat === 0;

        switch ($by) {
            case 1:
                if (!$this->isHr()) {
                    return $this->deny('Only HR can disapprove at the HRMO stop.');
                }
                if (!$inProgress || (int) $leaveApplication->status !== 1) {
                    return $this->deny('This application is not with the HRMO.', 422);
                }
                $leaveApplication->hr_sdate = $currdate;
                $leaveApplication->remarks_details = $request->remarks;
                break;

            case 2:
                if (!$employee || !$this->isSupervisorOf($leaveApplication, $employee)) {
                    return $this->deny('Only the immediate supervisor or the office OIC can disapprove at this stop.');
                }
                if (!$inProgress || (int) $leaveApplication->status !== 2) {
                    return $this->deny('This application is not waiting on the supervisor.', 422);
                }
                $leaveApplication->sup_sdate = $currdate;
                $leaveApplication->remarks_details = $request->remarks;
                break;

            case 3:
                if ($this->approvingRole() === null) {
                    return $this->deny('Only the Mayor or the Vice Mayor can disapprove at this stop.');
                }
                if (!$inProgress || (int) $leaveApplication->status !== 3) {
                    return $this->deny('This application is not waiting on final approval.', 422);
                }
                $leaveApplication->approver_sdate = $currdate;
                $leaveApplication->remarks_details1 = $request->remarks;
                break;

            case 4:
                if ($this->getGuard() !== 'web') {
                    return $this->deny('Only HR can cancel an approved leave.');
                }
                if ((int) $leaveApplication->history !== 2 || (int) $leaveApplication->remarks_stat !== 0) {
                    return $this->deny('Only an approved leave can be cancelled here.', 422);
                }
                $leaveApplication->remarks_details2 = $request->remarks;
                break;
        }

        // The supervisor's signature took the credits; a refusal after that
        // (the Mayor's, or HR cancelling) gives them back. The Mayor's refusal
        // used to keep them.
        if (in_array($by, [3, 4], true) && $employee && $this->creditsTaken($leaveApplication)) {
            $this->applyCredits($leaveApplication, $employee, +1);
        }

        $leaveApplication->remarks_stat = $by;
        $leaveApplication->history = 2;
        $leaveApplication->save();

        $this->regenerate($leaveApplication->id);

        // Nothing is waiting on anyone any more; tell the employee why.
        Notification::where('lapp_id', $leaveApplication->id)->where('module', 'leave')->update(['status' => 1]);
        $this->notifyEmployee($leaveApplication, $leaveApplication->empid, $by === 4 ? self::NOTIFY_CANCELLED : self::NOTIFY_DISAPPROVED);

        return response()->json([
            'success' => true,
            'message' => $by === 4 ? 'Leave cancelled successfully.' : 'Leave disapproved successfully.',
        ]);
    }

    /**
     * Send an application one stop back: 1 HR to the employee (sign again),
     * 2 the supervisor to the HRMO, 3 the Mayor / Vice Mayor to the supervisor
     * (the credits the supervisor's signature took are given back).
     */
    public function leaveReturn(Request $request, $id = null)
    {
        $request->validate([
            'to' => 'required|integer|min:1|max:3',
        ]);

        $id = $id ?? $request->id;
        $leaveApplication = LeaveApplication::find($id);

        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.'
            ], 404);
        }

        if ((int) $leaveApplication->history !== 1 || (int) $leaveApplication->remarks_stat !== 0) {
            return $this->deny('This leave application has already been decided.', 422);
        }

        $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();

        switch ((int) $request->to) {
            case 1:
                if ($this->getGuard() !== 'web') {
                    return $this->deny('Only HR can return an application to the employee.');
                }
                if ((int) $leaveApplication->status !== 1) {
                    return $this->deny('This application is no longer with the HRMO.', 422);
                }
                $leaveApplication->emp_esign = 1;
                $leaveApplication->save();
                $this->markRead($leaveApplication, 'hr', [2]);
                $this->notifyEmployee($leaveApplication, $leaveApplication->empid, self::NOTIFY_TO_SIGN);
                break;

            case 2:
                if (!$employee || !$this->isSupervisorOf($leaveApplication, $employee)) {
                    return $this->deny('Only the immediate supervisor or the office OIC can return this application.');
                }
                if ((int) $leaveApplication->status !== 2) {
                    return $this->deny('This application is not waiting on the supervisor.', 422);
                }
                $leaveApplication->hr_sign = null;
                $leaveApplication->status = 1;
                $leaveApplication->save();
                $this->markRead($leaveApplication, 'employee', [self::NOTIFY_SUPERVISOR]);
                Notification::create([
                    'empid' => $leaveApplication->empid,
                    'lapp_id' => $leaveApplication->id,
                    'category' => 2,
                    'utype' => 'hr',
                    'module' => 'leave',
                ]);
                break;

            case 3:
                if ($this->approvingRole() === null) {
                    return $this->deny('Only the Mayor or the Vice Mayor can return this application.');
                }
                if ((int) $leaveApplication->status !== 3) {
                    return $this->deny('This application is not waiting on final approval.', 422);
                }
                if ($employee) {
                    $this->applyCredits($leaveApplication, $employee, +1);
                }
                $leaveApplication->sup_sign = null;
                $leaveApplication->oic = null;
                $leaveApplication->status = 2;
                $leaveApplication->save();
                $this->markRead($leaveApplication, 'employee', [self::NOTIFY_APPROVER]);
                if ($employee) {
                    $this->notifySupervisor($leaveApplication, $employee);
                }
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave updated successfully.'
        ]);
    }

    public function leaveUndo(Request $request, $id = null)
    {
        $request->validate([
            'to' => 'required|integer|min:1|max:3',
        ]);

        if ($this->getGuard() !== 'web') {
            return $this->deny('Only HR can move an application back.');
        }

        $id = $id ?? $request->id;
        $leaveApplication = LeaveApplication::find($id);

        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.'
            ], 404);
        }

        // Moving back past the supervisor's signature would leave the credits
        // taken; that is what Return (by the Mayor) is for.
        if ((int) $leaveApplication->history !== 1 || (int) $leaveApplication->status >= 3) {
            return $this->deny('This application can no longer be moved back here.', 422);
        }

        switch ((int) $request->to) {
            case 1:
                $leaveApplication->emp_esign = 1;
                $leaveApplication->status = 1;
                break;
            case 2:
                $leaveApplication->status = 1;
                break;
            case 3:
                $leaveApplication->status = 2;
                break;
        }

        $leaveApplication->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave updated successfully.'
        ]);
    }

    /** The application with every signatory's name and e-signature, for the form. */
    private function formRecord($id)
    {
        // Left joins throughout: a missing supervisor or HR Head must not make
        // the form disappear.
        return LeaveApplication::with(['office:id,office_name,office_abbr'])
            ->join('employees', 'leave_applications.empid', '=', 'employees.emp_ID')
            ->leftJoin('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
            ->leftJoin('employees as oic', 'oic.id', '=', 'leave_applications.oic')
            ->leftJoin('employees as approver', 'approver.id', '=', 'leave_applications.approver')
            ->leftJoin('employees as hrhead', 'hrhead.id', '=', 'leave_applications.hr')
            ->select(
                'leave_applications.*',
                'leave_applications.id as lid',
                'employees.id as employid',
                'employees.lname',
                'employees.fname',
                'employees.mname',
                'employees.suffix',
                'employees.esign as employee_esign',

                'sup.lname as supervisor_lname',
                'sup.fname as supervisor_fname',
                'sup.mname as supervisor_mname',
                'sup.suffix as supervisor_suffix',
                'sup.prefix as supervisor_prefix',
                'sup.esign as supervisor_esign',

                'oic.lname as oic_lname',
                'oic.fname as oic_fname',
                'oic.mname as oic_mname',
                'oic.suffix as oic_suffix',
                'oic.prefix as oic_prefix',
                'oic.esign as oic_esign',

                'approver.lname as approver_lname',
                'approver.fname as approver_fname',
                'approver.mname as approver_mname',
                'approver.suffix as approver_suffix',
                'approver.prefix as approver_prefix',
                'approver.esign as approver_esign',

                'hrhead.lname as hr_lname',
                'hrhead.fname as hr_fname',
                'hrhead.mname as hr_mname',
                'hrhead.suffix as hr_suffix',
                'hrhead.prefix as hr_prefix',
                'hrhead.esign as hr_esign',
            )
            ->where('leave_applications.id', $id)
            ->first();
    }

    /** E-signatures are stored encrypted; the form needs them as images. */
    private function decryptSignatures(LeaveApplication $leaveApplication): void
    {
        foreach (['employee_esign', 'supervisor_esign', 'oic_esign', 'approver_esign', 'hr_esign'] as $field) {
            if (!$leaveApplication->{$field}) {
                continue;
            }
            try {
                $leaveApplication->{$field} = 'data:image/png;base64,' . base64_encode(Crypt::decrypt($leaveApplication->{$field}));
            } catch (\Throwable $e) {
                // A signature saved before encryption, or with another key:
                // leave the line blank rather than fail the whole form.
                $leaveApplication->{$field} = null;
            }
        }
    }

    public function genApplication($id)
    {
        $leaveApplication = $this->formRecord($id);
        if (!$leaveApplication) {
            return null;
        }
        $this->decryptSignatures($leaveApplication);

        $barcodeNumber = $leaveApplication->transnum ?: str_pad($id, 6, '0', STR_PAD_LEFT);
        $generator = new BarcodeGeneratorPNG();
        $barcodePath = storage_path('app/barcode_' . $id . '_' . mt_rand(1000, 9999) . '.png');

        // Generate the barcode with text in a single image
        $barcode = $generator->getBarcode($barcodeNumber, $generator::TYPE_CODE_128);
        $image = imagecreatefromstring($barcode);
        $fontPath = public_path('fonts/Code128.ttf');
        $textColor = imagecolorallocate($image, 0, 0, 0);
        imagettftext($image, 10, 0, (imagesx($image) - imagettfbbox(10, 0, $fontPath, $barcodeNumber)[2]) / 2, imagesy($image) - 10, $textColor, $fontPath, $barcodeNumber);
        imagepng($image, $barcodePath);
        imagedestroy($image);

        $customPaper = [0, 0, 595.28, 841.89];
        $pdf = \PDF::loadView('leaves.generate-leave', compact('leaveApplication', 'barcodePath'))->setPaper($customPaper, 'portrait')
            ->setOption('margin-top', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                }
            ]);

        $fileName = mt_rand(100000, 999999) . '_leave_application_' . $id . '.pdf';
        $filePath = 'Leaveapplication/' . $fileName;
        $storagePath = storage_path('app/public/' . $filePath);

        if (!empty($leaveApplication->gen_app)) {
            $oldFilePath = storage_path('app/public/' . $leaveApplication->gen_app);
            if (file_exists($oldFilePath) && !is_dir($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        if (!file_exists(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0777, true);
        }

        $pdf->save($storagePath);

        // Saved through a plain query: the joined record carries the decoded
        // signatures and names, which are not columns of this table.
        LeaveApplication::whereKey($id)->update(['gen_app' => $filePath]);

        if (file_exists($barcodePath)) {
            unlink($barcodePath);
        }

        return $filePath;
    }

    /** Who may read an application's form: HR, the applicant, and its signatories. */
    private function canView(LeaveApplication $leave): bool
    {
        $guard = $this->getGuard();
        if ($guard === 'web') {
            return true;
        }

        $me = $this->me();
        $applicant = Employee::where('emp_ID', $leave->empid)->first();

        return (int) $me->id === (int) $applicant?->id
            || in_array((int) $me->id, array_map('intval', [$leave->supervisor, $leave->oic, $leave->approver, $leave->hr]), true)
            || ($applicant && $this->isSupervisorOf($leave, $applicant))
            || Setting::first()?->isApprovingOfficial($me->id)
            || ($applicant && Office::whereKey($applicant->emp_dept)->where('office_head_id', $me->id)->exists());
    }

    public function previewLeave($id){
        $leaveApplication = $this->formRecord($id);

        if (!$leaveApplication) {
            return redirect()->back()->with('error', 'Leave application not found.');
        }

        if (!$this->canView($leaveApplication)) {
            abort(403, 'You cannot open this leave application.');
        }

        $this->decryptSignatures($leaveApplication);

        $customPaper = array(0, 0, 595.28, 841.89);
        $pdf = \PDF::loadView('leaves.generate-leave', compact('leaveApplication'))->setPaper($customPaper, 'portrait');

        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        $pdf->setCallbacks([
            'before_render' => function ($domPdf) {
                $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
            },
        ]);

        $pdf->render();

        return $pdf->stream();
    }

    public function historyRead($id = null){
        $guard = $this->getGuard();
        $authid = auth()->guard($guard)->user()->id;

        // HR opens a named employee's (the first one when none is named).
        $employee = $guard == "web"
            ? ($id ? Employee::find($id) : Employee::leaveEligible()->first())
            : Employee::find($authid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $empid = $employee->id;
        $emplalls = Employee::leaveEligible()->get();

        $leaveApplication = LeaveApplication::where('leave_applications.history', 2)
            ->where('leave_applications.empid', $employee->emp_ID)
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();

        $leaveApplication1 = [];

        if ($guard != "web") {
            // What this employee signed as supervisor, OIC or approving
            // official, with whose it was.
            $leaveApplication1 = LeaveApplication::where('leave_applications.history', 2)
            ->where(function ($q) use ($empid) {
                $q->where('leave_applications.supervisor', $empid)
                    ->orWhere('leave_applications.oic', $empid)
                    ->orWhere(function ($q) use ($empid) {
                        $q->where('leave_applications.approver', $empid)->whereNotNull('leave_applications.approver_sdate');
                    });
            })
            ->leftJoin('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
            ->select(
                'leave_applications.*',
                'emp.lname as employee_lname',
                'emp.fname as employee_fname',
                'emp.mname as employee_mname',
                'emp.suffix as employee_suffix',
            )
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();
        }

        return view('leaves.history', compact('guard', 'empid', 'employee', 'emplalls', 'leaveApplication', 'leaveApplication1'));
    }

    public function getPdfPath(Request $request)
    {
        $leave = LeaveApplication::find($request->input('id'));

        if ($leave && $leave->gen_app && $this->canView($leave)) {
            $filePath = 'public/' . $leave->gen_app;

            if (Storage::exists($filePath)) {
                return response()->json(['path' => Storage::url($filePath)]);
            }
        }

        return response()->json(['error' => 'PDF not found'], 404);
    }

    public function leaveReport(Request $request)
    {
        if ($this->getGuard() !== 'web') {
            abort(403);
        }

        $filingdate = trim((string) $request->input('date'));
        if ($filingdate === '') {
            return back()->with('error', 'Choose the dates for the leave report.');
        }

        $setting = Setting::join('employees as hr', 'hr.id', '=', 'settings.hr')
            ->join('employees as mayor', 'mayor.id', '=', 'settings.mayor')
            ->select(
                'settings.*',
                'hr.lname as hr_lname',
                'hr.fname as hr_fname',
                'hr.mname as hr_mname',
                'hr.suffix as hr_suffix',
                'mayor.lname as mayor_lname',
                'mayor.fname as mayor_fname',
                'mayor.mname as mayor_mname',
                'mayor.suffix as mayor_suffix'
            )
            ->first();

        // The report is signed by the HR Head and the Mayor.
        if (! $setting) {
            return back()->with('error', 'Choose the HR Head and the Mayor in Settings before printing the leave report.');
        }

        try {
            if (str_contains($filingdate, 'to')) {
                [$startDate, $endDate] = array_map('trim', explode('to', $filingdate));
                $startDateObj = Carbon::parse($startDate);
                $endDateObj   = Carbon::parse($endDate);
            } else {
                $startDateObj = $endDateObj = Carbon::parse($filingdate);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Those dates could not be read.');
        }

        if ($startDateObj->isSameDay($endDateObj)) {
            $formattedDateRange = $startDateObj->format('F j, Y');
        } elseif ($startDateObj->format('F Y') === $endDateObj->format('F Y')) {
            $formattedDateRange = $startDateObj->format('F j') . '-' . $endDateObj->format('j, Y');
        } else {
            $formattedDateRange = $startDateObj->format('F j, Y') . ' - ' . $endDateObj->format('F j, Y');
        }

        // The transmittal the Mayor ticks on paper: applications signed by the
        // supervisor and waiting on final approval (status 3), not refused.
        $applications = LeaveApplication::join('employees', 'leave_applications.empid', '=', 'employees.emp_ID')
            ->whereDate('leave_applications.created_at', '>=', $startDateObj->toDateString())
            ->whereDate('leave_applications.created_at', '<=', $endDateObj->toDateString())
            ->where('leave_applications.status', 3)
            ->where(fn ($q) => $q->where('leave_applications.remarks_stat', 0)->orWhereNull('leave_applications.remarks_stat'))
            ->orderBy('leave_applications.created_at', 'asc')
            ->select(
                'leave_applications.*',
                'employees.lname',
                'employees.fname',
                'employees.mname',
                'employees.suffix'
            )
            ->get();

        $customPaper = [0, 0, 612, 1008];

        $pdf = \PDF::loadView(
            'leaves.leave-report',
            compact('applications', 'formattedDateRange', 'setting')
        )->setPaper($customPaper, 'portrait');

        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        $pdf->setCallbacks([
            'before_render' => function ($domPdf) {
                $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
            },
        ]);

        return $pdf->stream();
    }

    public function leaveLive($id = null)
    {
        $guard = $this->getGuard();
        $empid = ($guard == "web") ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return response()->json(['error' => 'Employee record not found.'], 404);
        }

        return response()->json([
            'vl' => $employee->vl,
            'sl' => $employee->sl,
            'special_pl' => $employee->special_pl,
            'solo_pl' => $employee->solo_pl,
            'study_leave' => $employee->study_leave,
            'vawc_leave' => $employee->vawc_leave,
            'rehab_leave' => $employee->rehab_leave,
            'benefits_leave' => $employee->benefits_leave,
            'calamity_leave' => $employee->calamity_leave,
            'adopt_leave' => $employee->adopt_leave,
            'servcred_leave' => $employee->servcred_leave,
            'well_leave' => $employee->well_leave,
        ]);
    }

    public function eSign()
    {
        $guard = $this->getGuard();

        return view('leaves.esign', compact('guard'));
    }

    /**
     * HR takes an application out altogether. Any leave credits it already
     * took (once the supervisor signed) are given back first.
     */
    public function cancelLeave($id)
    {
        if ($this->getGuard() !== 'web') {
            return $this->deny('Only HR can cancel a leave application.');
        }

        $leave = LeaveApplication::find($id);

        if ($leave) {
            if ((int) $leave->history !== 1) {
                return $this->deny('A decided application is cancelled from History instead.', 422);
            }

            $employee = Employee::where('emp_ID', $leave->empid)->first();
            if ($employee && $this->creditsTaken($leave)) {
                $this->applyCredits($leave, $employee, +1);
            }

            $leave->delete();

            Notification::where('lapp_id', $id)
                ->where('module', '=', 'leave')
                ->delete();
        }

        return response()->json(['success' => true, 'message' => 'Leave application canceled.']);
    }
}
