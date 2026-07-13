<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\Office;
use App\Models\Setting;
use Carbon\Carbon;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class LeaveApplicationController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }  

    public function LeaveAppCreate(Request $request)
    {
        $validatedData = $request->validate([
            'empid' => 'required|exists:employees,emp_ID',
            'date_range' => 'required|string',
        ]);

        $checkleave = LeaveApplication::where('empid', $request->empid)->where('history', 1)->whereNotIn('status', [3, 4])->get();

        if ($checkleave->isNotEmpty()) {
            return redirect()->back()->with('error', 'One Leave Application at a time');
        }
    
        $leaveDetails = array_filter($request->input('leave_detail'));
        $firstDetail = reset($leaveDetails);
        
        $setting = Setting::first();
        $employee = Employee::where('emp_ID', $request->empid)->first();
    
        $supemp = Employee::find($employee->supervisor);
        // The Mayor is the default approver; if the Vice Mayor signs instead,
        // the approver fields are overwritten at approval time.
        $approver = Employee::find($setting->mayor);
        $hremp = Employee::find($setting->hr);
        $purpose = $request->leave_purpose;
    
        if (is_null($employee->supervisor) || $employee->supervisor == 0) {
            return redirect()->back()->with(['error' => 'No Supervisor Assigned']);
        }

        $lastTransnum = LeaveApplication::orderBy('id', 'desc')->first();
        $newTransnum = $lastTransnum ? intval($lastTransnum->transnum) + 1 : 1;
        $transnum = str_pad($newTransnum, 6, '0', STR_PAD_LEFT);
    
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
            'date_filing' => $request->date_filing . ' ' . \Carbon\Carbon::now('Asia/Manila')->format('H:i:s'),
            'salary' => $employee->emp_salary,
            'commutation' => ($purpose == 7 || $purpose == 8) ? 2 : 1,
            'supervisor' => $employee->supervisor,
            'sup_prefix' => $supemp->prefix,
            'approver' => $setting->mayor,
            'approver_prefix' => $approver?->prefix,
            'approver_role' => 'Mayor',
            'hr' => $setting->hr,
            'hr_prefix' => $hremp->prefix,
            'department' => $employee->emp_dept,
        ]);

        $leaveTypes = [
            1 => 'Vacation Leave',
            2 => 'Mandatory/Forced Leave',
            3 => 'Sick Leave',
            4 => 'Maternity Leave',
            5 => 'Paternity Leave',
            6 => 'Special Privilege Leave',
            7 => 'Solo Parent Leave',
            8 => 'Study Leave',
            9 => '10-Day VAWC Leave',
            10 => 'Rehabilitation Privilege',
            11 => 'Special Leave Benefits for Women',
            12 => 'Special Emergency (Calamity) Leave',
            13 => 'Adoption Leave',
            14 => 'Vacation Service Credit',
            15 => 'Wellness Leave'
        ];
        
        Notification::create([
            'empid' => $request->empid,
            'lapp_id' => $leaveApplication->id,
            'category' => 1,
            'utype' => 'hr',
            'module' => 'leave',
        ]);
        
        // $this->genApplication($leaveApplication->id);
        
        return redirect()->back()->with('success', 'Submitted successfully');
    }

    // public function leavesnotif($id){
    //     Notification::where('id', $id)->update(['status' => 1]);

    //     $this->leaveStatus($notification->lapp_id);
    // }

    public function leaveStatus($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

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
        // dd($oic);
        $isOfficeHead = Office::where('office_head_id', $employee->id)->first();

        $leavesapp = LeaveApplication::where('empid', $employee->emp_ID)
        ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
        ->join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
        ->join('employees as hr', 'hr.id', '=', 'leave_applications.hr')
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

        // dd($leavesapp);

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
            'mayor.suffix as mayor_suffix',
        )
        ->first();

        $leavesapphead = LeaveApplication::join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
            ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
            ->join('employees as hr', 'hr.id', '=', 'leave_applications.hr');
            
        // if ($setting->mayor !== auth()->guard($guard)->user()->id) {
        //     $leavesapphead->where('leave_applications.supervisor', auth()->guard($guard)->user()->id);
        // }else{
        //     $leavesapphead->whereIn('leave_applications.status', [3]);
        // }

        // The Mayor and the Vice Mayor both see applications awaiting final approval.
        if (!$setting->isApprovingOfficial(auth()->guard($guard)->user()->id)) {
            if ($oic == null) {
                $leavesapphead->where('leave_applications.supervisor', auth()->guard($guard)->user()->id);
            }else{
                $leavesapphead->where('leave_applications.empid', '!=', $employee->emp_ID);
            }
        }else{
            $leavesapphead->whereIn('leave_applications.status', [3]);
        }
        
        $leavesapphead = $leavesapphead->select(
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
        )
        ->orderBy('leave_applications.id', 'desc')
        ->where('leave_applications.history', 1)
        ->get();
        
        // dd($leavesapphead);
        if($guard == 'web'){
            $leavesapphead = [];
        }
        
        $emplalls = Employee::where('emp_status', 1)->get();

        return view("leaves.status", compact('guard', 'setting', 'employee', 'leavesapp', 'isOfficeHead', 'leavesapphead', 'oic', 'emplalls', 'empid'));
    }

    public function leaveWpay(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'day_wpay' => 'required',
            'holiday' => 'required',
        ]);
    
        $leaveApplication = LeaveApplication::find($request->id);
        $leavetype = $leaveApplication->leave_type;
        $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
    
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
    
        // $leaveApplication->hr_sdate = Carbon::now();
        $leaveApplication->holiday = $request->holiday;
        
        $leaveApplication->days -= $request->holiday;

        $leaveApplication->day_wpay = $request->day_wpay;

        $daysdeduct = $leaveApplication->days - $request->day_wpay;
        
        if ($leavetype == 3) {
            $employee->sl = $employee->sl ?? 0;
            $employee->vl = $employee->vl ?? 0;
            
            if ($daysdeduct > $employee->sl) {
                $remainingDays = $daysdeduct - $employee->sl;
                
                if ($remainingDays > $employee->vl) {
                    return response()->json(['error' => 'Insufficient leave credits'], 400);
                }

                $leaveApplication->less_sl = $employee->sl;
                $leaveApplication->less_vl = $remainingDays;
            }else{
                $leaveApplication->less_sl = $daysdeduct;
                $leaveApplication->less_vl = 0;
            }
        }

        if($leavetype == 1 || $leavetype == 2) {
            if ($daysdeduct > $employee->vl) {
                return response()->json(['error' => 'Insufficient leave credits'], 400);
            }
            $leaveApplication->less_sl = 0;
            $leaveApplication->less_vl = $daysdeduct;
        }

        if($leavetype == 6) {
            if ($daysdeduct > $employee->special_pl) {
                return response()->json(['error' => 'Insufficient leave credits'], 400);
            }
        }

        if($leavetype == 14) {
            if ($daysdeduct > $employee->servcred_leave) {
                return response()->json(['error' => 'Insufficient leave credits'], 400);
            }
        }

        if($leavetype == 15) {
            if ($daysdeduct > $employee->well_leave) {
                return response()->json(['error' => 'Insufficient leave credits'], 400);
            }
        }
        
        $originalPath = $leaveApplication->gen_app;
        
        if (file_exists(public_path($originalPath)) && !is_dir(public_path($originalPath))) {
            unlink(public_path($originalPath));
        }
        
        $leaveApplication->emp_esign = 1;
        $leaveApplication->as_of = Carbon::now();
        $leaveApplication->save();
        
        Notification::where('lapp_id', $leaveApplication->id)->where('category', 1)->where('module', '=', 'leave')->where('utype', '=', 'hr')->update(['status' => 1]);

        Notification::create([
            'empid' => $leaveApplication->empid,
            'lapp_id' => $leaveApplication->id,
            'category' => 1,
            'utype' => 'employee',
            'module' => 'leave',
        ]);

        // $this->genApplication($leaveApplication->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Leave approved successfully.',
            'datetime' => now(),
            'withpay' =>  $daysdeduct,
            'withoutpay' => $request->day_wpay,
        ]);
    }
    
    // public function leaveApprove(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required|integer|exists:leave_applications,id',
    //         'by' => 'required|integer|min:0|max:3',
    //         'day_wpay' => 'nullable|numeric',
    //         // 'file' => 'required|file|mimes:pdf'
    //     ]);
        
    //     $leaveApplication = LeaveApplication::find($request->id);
    //     $currdate = Carbon::now('Asia/Manila')->toDateTimeString();
    //     $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');
    
    //     $status = 1;
    //     switch ($request->by) {
    //         case 0:
    //             $emp_esign = 2;
    //             break;
    //         case 1:
    //             $status = 2;
    //             break;
    //         case 2:
    //             $status = 3;
    //             break;
    //         case 3:
    //             $status = 4;
    //             break;
    //     }
    
    //     if ($request->hasFile('file')) {
    //         // Delete the old file if it exists
    //         if ($leaveApplication->gen_app && Storage::exists('public/' . $leaveApplication->gen_app)) {
    //             Storage::delete('public/' . $leaveApplication->gen_app);
    //         }
            
    //         $storagePath = 'public/Leaveapplication';
            
    //         // Generate a new random filename
    //         $randomNumber = mt_rand(100000, 999999);
    //         $fileName = $randomNumber . '_leave_application_' . $leaveApplication->id . '.pdf';
        
    //         // Store the new file
    //         $newFilePath = $request->file('file')->storeAs($storagePath, $fileName);
        
    //         // Save the new filename in the database
    //         $leaveApplication->gen_app = str_replace('public/', '', $newFilePath);
    //         $leaveApplication->save();
    //     }
        
    //     $leave = [
    //         1 => 'vl',
    //         2 => 'vl',
    //         3 => 'sl'
    //     ];

    //     $leaveTypes = [
    //         1 => 'Vacation Leave',
    //         2 => 'Mandatory/Forced Leave',
    //         3 => 'Sick Leave',
    //         4 => 'Maternity Leave',
    //         5 => 'Paternity Leave',
    //         6 => 'Special Privilege Leave',
    //         7 => 'Solo Parent Leave',
    //         8 => 'Study Leave',
    //         9 => '10-Day VAWC Leave',
    //         10 => 'Rehabilitation Privilege',
    //         11 => 'Special Leave Benefits for Women',
    //         12 => 'Special Emergency (Calamity) Leave',
    //         13 => 'Adoption Leave',
    //         14 => 'Vacation Service Credit'
    //     ];
        
    //     if($request->by == 0){
    //         $leaveApplication->emp_esign = $emp_esign;
    //         $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
    //         $employeeName = ucwords(strtolower($employee->fname . ' ' . $employee->lname));

    //         Notification::create([
    //             'empid' => $leaveApplication->empid,
    //             'lapp_id' => $leaveApplication->id,
    //             'category' => 2,
    //             'utype' => 'hr',
    //             'module' => 'leave',
    //         ]);

    //         Notification::where('lapp_id', $leaveApplication->id)->where('category', 1)->where('module', '=', 'leave')->where('utype', '=', 'employee')->update(['status' => 1]);
    //     }
        
    //     if($request->by == 1){
    //         $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
    //         $leaveApplication->hr_sdate = Carbon::now();

    //         Notification::create([
    //             'empid' => $leaveApplication->empid,
    //             'lapp_id' => $leaveApplication->id,
    //             'esign_id' => $employee->supervisor ?? 0,
    //             'category' => 3,
    //             'utype' => 'supervisor',
    //             'module' => 'leave',
    //         ]);
            
    //         Notification::where('lapp_id', $leaveApplication->id)->where('category', 2)->where('module', '=', 'leave')->where('utype', '=', 'hr')->update(['status' => 1]);
    //     }

    //     if($request->by == 2){
    //         $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
    //         $leaveApplication->sup_sdate= Carbon::now();

    //         $daysdeduct = ($leaveApplication->days ?? 0) - ($leaveApplication->day_wpay ?? 0);

    //         $employee->vl = $employee->vl ?? 0;
    //         $employee->sl = $employee->sl ?? 0;
    
    //         if (in_array($leaveApplication->leave_type, [1, 2])){
    //             $employee->vl -= $leaveApplication->less_vl;
    //         }if($leaveApplication->leave_type == 3){
    //             $employee->vl -= $leaveApplication->less_vl;
    //             $employee->sl -= $leaveApplication->less_sl;
    //         }if($leaveApplication->leave_type == 6){
    //             $employee->special_pl -= ($leaveApplication->days - $leaveApplication->day_wpay);
    //         }if($leaveApplication->leave_type == 14){
    //             $employee->servcred_leave -= ($leaveApplication->days - $leaveApplication->day_wpay);
    //         }
        
    //         $employee->save();
        
    //         $leaveApplication->save();

    //         Notification::create([
    //             'empid' => $leaveApplication->empid,
    //             'lapp_id' => $leaveApplication->id,
    //             'esign_id' => $employee->supervisor ?? 0,
    //             'category' => 4,
    //             'utype' => 'approver',
    //             'module' => 'leave',
    //         ]);

    //         Notification::where('lapp_id', $leaveApplication->id)->where('category', 3)->where('module', '=', 'leave')->where('utype', '=', 'supervisor')->update(['status' => 1]);
    //     }

    //     if ($request->by == 3) {
    //         $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
    //         $leaveApplication->approver_sdate = Carbon::now();

    //         // $daysdeduct = ($leaveApplication->days ?? 0) - ($leaveApplication->day_wpay ?? 0);

    //         // $employee->vl = $employee->vl ?? 0;
    //         // $employee->sl = $employee->sl ?? 0;
    
    //         // if (in_array($leaveApplication->leave_type, [1, 2])){
    //         //     $employee->vl -= $leaveApplication->less_vl;
    //         // }if($leaveApplication->leave_type == 3){
    //         //     $employee->vl -= $leaveApplication->less_vl;
    //         //     $employee->sl -= $leaveApplication->less_sl;
    //         // }if($leaveApplication->leave_type == 6){
    //         //     $employee->special_pl -= ($leaveApplication->days - $leaveApplication->day_wpay);
    //         // }if($leaveApplication->leave_type == 14){
    //         //     $employee->servcred_leave -= ($leaveApplication->days - $leaveApplication->day_wpay);
    //         // }
        
    //         // $employee->save();
        
    //         $leaveApplication->history = 2;
    //         $leaveApplication->save();

    //         Notification::create([
    //             'empid' => $leaveApplication->empid,
    //             'lapp_id' => $leaveApplication->id,
    //             'category' => 2,
    //             'utype' => 'employee',
    //             'module' => 'leave',
    //         ]);

    //         Notification::where('lapp_id', $leaveApplication->id)->where('category', 4)->where('module', '=', 'leave')->where('utype', '=', 'approver')->update(['status' => 1]);
    //     }        
        
    //     $leaveApplication->status = $status ?? 1;
    //     $leaveApplication->save();
    
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Leave approved successfully.',
    //         'datetime' => $currdate1,
    //     ]);
    // }

    public function leaveApprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:leave_applications,id',
            'by' => 'required|integer|min:0|max:3',
            'day_wpay' => 'nullable|numeric',
        ]);

        $guard = $this->getGuard();
        $authid = auth()->guard($guard)->user()->id;
        
        $leaveApplication = LeaveApplication::find($request->id);
        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');

        $status = 1;
        switch ($request->by) {
            case 0:
                $emp_esign = 2;
                break;
            case 1:
                $status = 2;
                break;
            case 2:
                $status = 3;
                break;
            case 3:
                $status = 4;
                break;
        }

        $leave = [
            1 => 'vl',
            2 => 'vl',
            3 => 'sl'
        ];

        $leaveTypes = [
            1 => 'Vacation Leave',
            2 => 'Mandatory/Forced Leave',
            3 => 'Sick Leave',
            4 => 'Maternity Leave',
            5 => 'Paternity Leave',
            6 => 'Special Privilege Leave',
            7 => 'Solo Parent Leave',
            8 => 'Study Leave',
            9 => '10-Day VAWC Leave',
            10 => 'Rehabilitation Privilege',
            11 => 'Special Leave Benefits for Women',
            12 => 'Special Emergency (Calamity) Leave',
            13 => 'Adoption Leave',
            14 => 'Vacation Service Credit',
            15 => 'Wellness Leave'
        ];
        
        if($request->by == 0){
            $leaveApplication->emp_esign = $emp_esign;
            $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();

            Notification::create([
                'empid' => $leaveApplication->empid,
                'lapp_id' => $leaveApplication->id,
                'category' => 2,
                'utype' => 'hr',
                'module' => 'leave',
            ]);

            Notification::where('lapp_id', $leaveApplication->id)
                ->where('category', 1)
                ->where('module', 'leave')
                ->where('utype', 'employee')
                ->update(['status' => 1]);
        }

        if($request->by == 1){
            $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
            $leaveApplication->hr_sdate = Carbon::now();
            $leaveApplication->hr_sign = 2;

            Notification::create([
                'empid' => $leaveApplication->empid,
                'lapp_id' => $leaveApplication->id,
                'esign_id' => $employee->supervisor ?? 0,
                'category' => 3,
                'utype' => 'supervisor',
                'module' => 'leave',
            ]);

            Notification::where('lapp_id', $leaveApplication->id)
                ->where('category', 2)
                ->where('module', 'leave')
                ->where('utype', 'hr')
                ->update(['status' => 1]);
        }

        if($request->by == 2){
            $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
            $leaveApplication->sup_sdate = Carbon::now();
            $leaveApplication->hr_sign = 2;

            $officeoic = Office::find($employee->emp_dept);

            if($officeoic && $officeoic->oic_id && $authid !== $employee->supervisor && $authid == $officeoic->oic_id){
                $leaveApplication->oic = $authid;
            }else{
                $leaveApplication->sup_sign = 2;
            }

            $employee->vl = $employee->vl ?? 0;
            $employee->sl = $employee->sl ?? 0;

            if (in_array($leaveApplication->leave_type, [1, 2])){
                $employee->vl -= $leaveApplication->less_vl;
            }
            if($leaveApplication->leave_type == 3){
                $employee->vl -= $leaveApplication->less_vl;
                $employee->sl -= $leaveApplication->less_sl;
            }
            if($leaveApplication->leave_type == 6){
                $employee->special_pl -= ($leaveApplication->days - $leaveApplication->day_wpay);
            }
            if($leaveApplication->leave_type == 14){
                $employee->servcred_leave -= ($leaveApplication->days - $leaveApplication->day_wpay);
            }
            if($leaveApplication->leave_type == 15){
                $employee->well_leave -= ($leaveApplication->days - $leaveApplication->day_wpay);
            }

            $employee->save();
            $leaveApplication->save();

            Notification::create([
                'empid' => $leaveApplication->empid,
                'lapp_id' => $leaveApplication->id,
                'esign_id' => $employee->supervisor ?? 0,
                'category' => 4,
                'utype' => 'approver',
                'module' => 'leave',
            ]);

            Notification::where('lapp_id', $leaveApplication->id)
                ->where('category', 3)
                ->where('module', 'leave')
                ->where('utype', 'supervisor')
                ->update(['status' => 1]);
        }

        if ($request->by == 3) {
            $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
            $leaveApplication->approver_sdate = Carbon::now();
            $leaveApplication->approver_sign = 2;
            $leaveApplication->history = 2;
            $leaveApplication->save();

            Notification::create([
                'empid' => $leaveApplication->empid,
                'lapp_id' => $leaveApplication->id,
                'category' => 2,
                'utype' => 'employee',
                'module' => 'leave',
            ]);

            Notification::where('lapp_id', $leaveApplication->id)
                ->where('category', 4)
                ->where('module', 'leave')
                ->where('utype', 'approver')
                ->update(['status' => 1]);
        }

        $leaveApplication->status = $status ?? 1;
        $leaveApplication->save();

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

        $guard = $this->getGuard();
        $setting = Setting::first();
        $signatoryId = auth()->guard($guard)->user()->id;
        $role = $setting->approvingRole($signatoryId);

        // Either the Mayor or the Vice Mayor may sign, whoever is available.
        if ($role === null) {
            return response()->json([
                'success' => false,
                'message' => 'Only the Mayor or the Vice Mayor may approve a leave application.',
            ], 403);
        }

        $leaveApplication = LeaveApplication::find($request->id);
        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');

        // Final approval. Record who actually signed — the form prints their title.
        $signatory = Employee::find($signatoryId);
        $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
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
        $this->genApplication($leaveApplication->id);

        // Notify employee about final approval
        Notification::create([
            'empid'   => $leaveApplication->empid,
            'lapp_id' => $leaveApplication->id,
            'category'=> 2,
            'utype'   => 'employee',
            'module'  => 'leave',
        ]);

        // Mark the approver notification as read
        Notification::where('lapp_id', $leaveApplication->id)
            ->where('category', 4)
            ->where('module', 'leave')
            ->where('utype', 'approver')
            ->update(['status' => 1]);

        return response()->json([
            'success'  => true,
            'message'  => 'Leave approved by the ' . $role . ' successfully.',
            'datetime' => $currdate1,
        ]);
    }

    public function leaveDisapprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'by' => 'required|integer',
            'remarks' => 'required|string',
            'day_wpay' => 'nullable|integer',
        ]);
    
        $leaveApplication = LeaveApplication::find($request->id);
        $currdate = Carbon::now('Asia/Manila')->toDateTimeString();
        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');
        if ($leaveApplication) {
            $leaveApplication->remarks_stat = $request->by;
            
            if ($request->by == 1) {
                $leaveApplication->hr_sdate = $currdate;
            }
    
            if ($request->by == 2) {
                $leaveApplication->sup_sdate = $currdate;
                $leaveApplication->remarks_details = $request->remarks;
                $leaveApplication->status = 3;
                $leaveApplication->history = 2;
            }
            
            if ($request->by == 3) {
                $leaveApplication->approver_sdate = $currdate;
                $leaveApplication->remarks_details1 = $request->remarks;
                $leaveApplication->history = 2;
            }

            if ($request->by == 4) {
                $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
                if (in_array($leaveApplication->leave_type, [1, 2, 3])){
                    $employee->vl += $leaveApplication->less_vl ?? 0;
                    $employee->sl += $leaveApplication->less_sl ?? 0;
                }
                if($leaveApplication->leave_type == 6){
                    $employee->special_pl += ($leaveApplication->days - $leaveApplication->day_wpay);
                }
                if($leaveApplication->leave_type == 14){
                    $employee->servcred_leave += ($leaveApplication->days - $leaveApplication->day_wpay);
                }
                if($leaveApplication->leave_type == 15){
                    $employee->well_leave += ($leaveApplication->days - $leaveApplication->day_wpay);
                }

                $employee->save();
                
                $leaveApplication->remarks_stat = 4;
                $leaveApplication->remarks_details2 = $request->remarks;
                $leaveApplication->history = 2;
            }

            $leaveApplication->save();

            $this->genApplication($leaveApplication->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Leave disapproved successfully.'
            ]);
        }

        Notification::create([
            'empid' => $leaveApplication->empid,
            'lapp_id' => $leaveApplication->id,
            'category' => 5,
            'utype' => 'hr',
            'module' => 'employee',
            'status' => 1,
        ]);

        Notification::where('lapp_id', $leaveApplication->id)->where('module', '=', 'leave')->update(['status' => 1]);
        
        return response()->json([
            'success' => false,
            'message' => 'Leave application not found.',
            'datetime' => $currdate1,
        ], 404);
    }

    public function leaveReturn(Request $request, $id = null)
    {
        $validatedData = $request->validate([
            'to' => 'required|integer|min:0|max:3',
        ]);
        
        if ($id === null) {
            $id = $request->id;
        }
    
        $leaveApplication = LeaveApplication::find($id);
        $originalPath = $leaveApplication->gen_app;
    
        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.'
            ], 404);
        }
    
        switch ($request->to) {
            case 1:
                $leaveApplication->emp_esign = 1;
                if (file_exists(public_path($originalPath)) && !is_dir(public_path($originalPath))) {
                    unlink(public_path($originalPath));
                }
                $this->genApplication($id);
                break;
                $leaveApplication->emp_esign = 1;
            case 2:
                $leaveApplication->hr_sign = null;
                $leaveApplication->status = 1;
                break;
            case 3:
                $leaveApplication->sup_sign = null;
                $leaveApplication->status = 2;
                
                $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
                $employee->vl = $employee->vl ?? 0;
                $employee->sl = $employee->sl ?? 0;
                
                if (in_array($leaveApplication->leave_type, [1, 2])){
                    $employee->vl += $leaveApplication->less_vl;
                }if($leaveApplication->leave_type == 3){
                    $employee->vl += $leaveApplication->less_vl;
                    $employee->sl += $leaveApplication->less_sl;
                }if($leaveApplication->leave_type == 6){
                    $employee->special_pl += ($leaveApplication->days - $leaveApplication->day_wpay);
                }if($leaveApplication->leave_type == 14){
                    $employee->servcred_leave += ($leaveApplication->days - $leaveApplication->day_wpay);
                }if($leaveApplication->leave_type == 15){
                    $employee->well_leave += ($leaveApplication->days - $leaveApplication->day_wpay);
                }
                
                $employee->save();

                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status value.'
                ], 400);
        }
        
        $leaveApplication->save();
    
        return response()->json([
            'success' => true,
            'message' => 'Leave updated successfully.'
        ]);
    }    

    public function leaveUndo(Request $request, $id = null)
    {
        $validatedData = $request->validate([
            'to' => 'required|integer|min:0|max:3',
        ]);
        
        if ($id === null) {
            $id = $request->id;
        }
    
        $leaveApplication = LeaveApplication::find($id);
        $originalPath = $leaveApplication->gen_app;
    
        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.'
            ], 404);
        }
    
        switch ($request->to) {
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
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status value.'
                ], 400);
        }
        
        $leaveApplication->save();
    
        return response()->json([
            'success' => true,
            'message' => 'Leave updated successfully.'
        ]);
    } 

    public function genApplication($id)
    {
        // oic and approver are optional — an inner join here would silently drop
        // the application and leave the PDF ungenerated.
        $leaveApplication = LeaveApplication::with(['office:id,office_name,office_abbr'])
            ->join('employees', 'leave_applications.empid', '=', 'employees.emp_ID')
            ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
            ->leftJoin('employees as oic', 'oic.id', '=', 'leave_applications.oic')
            ->leftJoin('employees as approver', 'approver.id', '=', 'leave_applications.approver')
            ->join('employees as hr', 'hr.id', '=', 'leave_applications.hr')
            ->select('leave_applications.*', 
                'leave_applications.id as lid', 
                'employees.lname', 'employees.fname', 'employees.mname', 'employees.suffix',             
                'sup.lname as supervisor_lname', 'sup.fname as supervisor_fname', 'sup.mname as supervisor_mname', 
                'sup.suffix as supervisor_suffix', 'sup.prefix as supervisor_prefix',
                'oic.lname as oic_lname', 'oic.fname as oic_fname', 'oic.mname as oic_mname', 
                'oic.suffix as oic_suffix', 'oic.prefix as oic_prefix',
                'hr.lname as hr_lname', 'hr.fname as hr_fname', 'hr.mname as hr_mname', 
                'hr.suffix as hr_suffix',
                'approver.lname as approver_lname', 'approver.fname as approver_fname', 'approver.mname as approver_mname', 
                'approver.suffix as approver_suffix', 'approver.prefix as approver_prefix'
            )
            ->where('leave_applications.id', $id)->first();
       
        $barcodeNumber = $leaveApplication->transnum;
        $generator = new BarcodeGeneratorPNG();
        $barcodePath = public_path($barcodeNumber . '.png');
        
        // Generate the barcode with text in a single image
        $barcode = $generator->getBarcode($barcodeNumber, $generator::TYPE_CODE_128);
        $image = imagecreatefromstring($barcode);
        $fontPath = public_path('fonts/Code128.ttf');
        $textColor = imagecolorallocate($image, 0, 0, 0);
        
        // Add text below the barcode
        imagettftext($image, 10, 0, (imagesx($image) - imagettfbbox(10, 0, $fontPath, $barcodeNumber)[2]) / 2, imagesy($image) - 10, $textColor, $fontPath, $barcodeNumber);
        
        // Save the image
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
    
        $randomNumber = mt_rand(100000, 999999);
        $fileName = $randomNumber . '_leave_application_' . $id . '.pdf';
        
        $filePath = 'Leaveapplication/' . $fileName;
        $storagePath = storage_path('app/public/' . $filePath);
        
        if (!empty($leaveApplication->gen_app)) {
            $oldFilePath = storage_path('app/public/' . $leaveApplication->gen_app);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        if (!file_exists(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0777, true);
        }
        
        $pdf->save($storagePath);
        
        $leaveApplication->gen_app = $filePath;
        $leaveApplication->save();

        if (file_exists($barcodePath)) {
            unlink($barcodePath);
        }
        
        return $filePath;
    }    
    
    public function previewLeave($id){
        $guard = $this->getGuard();
        $leaveApplication = LeaveApplication::with(['office:id,office_name,office_abbr'])
            ->join('employees', 'leave_applications.empid', '=', 'employees.emp_ID')
            ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
            ->leftjoin('employees as oic', 'oic.id', '=', 'leave_applications.oic')
            ->leftJoin('employees as approver', 'approver.id', '=', 'leave_applications.approver')
            ->join('employees as hrhead', 'hrhead.id', '=', 'leave_applications.hr') // <--- add this
            ->select(
                'leave_applications.*', 
                'leave_applications.id as lid', 
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

        if (!$leaveApplication) {
            return redirect()->back()->with('error', 'Leave application not found.');
        }

        if ($leaveApplication->employee_esign) {
            $decrypted = Crypt::decrypt($leaveApplication->employee_esign);
            $leaveApplication->employee_esign = 'data:image/png;base64,' . base64_encode($decrypted);
        }

        if ($leaveApplication->supervisor_esign) {
            $decrypted = Crypt::decrypt($leaveApplication->supervisor_esign);
            $leaveApplication->supervisor_esign = 'data:image/png;base64,' . base64_encode($decrypted);
        }

        if ($leaveApplication->oic_esign) {
            $decrypted = Crypt::decrypt($leaveApplication->oic_esign);
            $leaveApplication->oic_esign = 'data:image/png;base64,' . base64_encode($decrypted);
        }

        if ($leaveApplication->approver_esign) {
            $decrypted = Crypt::decrypt($leaveApplication->approver_esign);
            $leaveApplication->approver_esign = 'data:image/png;base64,' . base64_encode($decrypted);
        }

        if ($leaveApplication->hr_esign) {
            $decrypted = Crypt::decrypt($leaveApplication->hr_esign);
            $leaveApplication->hr_esign = 'data:image/png;base64,' . base64_encode($decrypted);
        }
    
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
        
        $empid = ($guard == "web") ? $id : $authid;
        
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $emplalls = Employee::where('emp_status', 1)->get();
        
        $settings = Setting::join('employees as hr', 'hr.id', '=', 'settings.hr')
        ->join('employees as mayor', 'mayor.id', '=', 'settings.mayor')
        ->select(
            'settings.*', 
            'hr.id as hrid', 
            'mayor.id as mayorid', 
        )
        ->first();

        

        if($guard == "web"){

            $leaveApplication = LeaveApplication::where('leave_applications.history', 2)
            ->where('leave_applications.empid', $employee->emp_ID)
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();

            $leaveApplication1 = [];

        }else{

            $leaveApplication = LeaveApplication::where('leave_applications.history', 2)
            ->where('leave_applications.empid', $employee->emp_ID)
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();

            $leaveApplication1 = LeaveApplication::where('leave_applications.history', 2)
            ->where('leave_applications.supervisor', $empid)
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();

        }

        return view('leaves.history', compact('guard', 'empid', 'employee', 'emplalls', 'leaveApplication', 'leaveApplication1'));
    }

    public function getPdfPath(Request $request)
    {
        $leaveId = $request->input('id');
        $leave = LeaveApplication::find($leaveId);
        
        if ($leave && $leave->gen_app) {
            // Ensure the file path starts with 'public/'
            $filePath = 'public/' . $leave->gen_app;
    
            if (Storage::exists($filePath)) {
                // Return only the relative path
                return response()->json(['path' => Storage::url($filePath)]);
            }
        }
        
        return response()->json(['error' => 'PDF not found'], 404);
    }

public function leaveReport(Request $request)
{
    $filingdate = trim($request->input('date'));

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

    // ===============================
    // DATE RANGE (e.g., 2026-02-01 to 2026-02-28)
    // ===============================
    if (str_contains($filingdate, 'to')) {

        [$startDate, $endDate] = array_map('trim', explode('to', $filingdate));

        $startDateObj = Carbon::parse($startDate);
        $endDateObj   = Carbon::parse($endDate);

        // Format label
        if ($startDateObj->format('F') === $endDateObj->format('F')) {
            $formattedDateRange = $startDateObj->format('F j') . '-' . $endDateObj->format('j, Y');
        } else {
            $formattedDateRange = $startDateObj->format('F j, Y') . ' - ' . $endDateObj->format('F j, Y');
        }

        $applications = LeaveApplication::join(
                'employees',
                'leave_applications.empid',
                '=',
                'employees.emp_ID'
            )
            ->whereDate('leave_applications.created_at', '>=', $startDateObj->toDateString())
            ->whereDate('leave_applications.created_at', '<=', $endDateObj->toDateString())
            ->whereIn('leave_applications.status', [3])
            ->where('leave_applications.remarks_stat', 0)
            ->orderBy('leave_applications.created_at', 'asc')
            ->select(
                'leave_applications.*',
                'employees.lname',
                'employees.fname',
                'employees.mname',
                'employees.suffix'
            )
            ->get();

    } 
    // ===============================
    // SINGLE DATE
    // ===============================
    else {

        $dateObj = Carbon::parse($filingdate);

        $formattedDateRange = $dateObj->format('F j, Y');

        $applications = LeaveApplication::join(
                'employees',
                'leave_applications.empid',
                '=',
                'employees.emp_ID'
            )
            ->whereDate('leave_applications.created_at', $dateObj->toDateString())
            ->whereIn('leave_applications.status', [3])
            ->where('leave_applications.remarks_stat', 0)
            ->orderBy('leave_applications.created_at', 'asc')
            ->select(
                'leave_applications.*',
                'employees.lname',
                'employees.fname',
                'employees.mname',
                'employees.suffix'
            )
            ->get();
    }

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
            $domPdf->getCanvas()->page_text(
                10,
                10,
                "Page {PAGE_NUM} of {PAGE_COUNT}",
                null,
                10,
                [0, 0, 0]
            );
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
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
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

    public function uploadAndSign(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:2048',
        ]);

        $filePath = public_path('Uploads/dtr.pdf');

        $client = new Client();

        $response = $client->post('https://api.example.com/esign', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => 'dtr.pdf',
                ],
                [
                    'name' => 'user_id',
                    // 'contents' => $guard->id,
                ],
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() == 200) {
            return redirect()->back()->with('success', 'Document signed successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to sign the document.');
        }
    }

    public function cancelLeave($id)
    {
        $leave = LeaveApplication::find($id);
    
        if ($leave) {
            // if($leave->status == 3){
            //     $employee = Employee::where('emp_ID', $leave->empid)->first();
            //     if($leave->type == 1){
            //         $employee->vl += $employee->vl ?? 0;
            //     }
            //     if($leave->type == 2){
            //         $employee->sl += $employee->sl ?? 0;
            //     }
            //     if($leave->type == 3){
            //         $employee->sl += $employee->sl ?? 0;
            //     }
                
            // }

            $empid = $leave->empid;
    
            $leave->delete();
    
            Notification::where('lapp_id', $id)
                ->where('empid', $empid)
                ->where('module', '=', 'leave')
                ->delete();
        }
    
        return response()->json(['success' => true, 'message' => 'Leave application canceled.']);
    }
}
