<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\Notification;
use Carbon\Carbon;

class LeaveCreditController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }  

    public function leavesRead($id = null){
        $emplalls = Employee::where('emp_status', 1)->get();
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;

        // Administrators have no employee record of their own, and the id in the
        // menu link may not exist, so fall back to the first active employee.
        $employee = Employee::find($empid) ?? $emplalls->first();

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'No active employees on record yet.');
        }

        $empid = $employee->id;

        $leaves = LeaveCredit::where('empid', $employee->emp_ID)
        ->join('users', 'leave_credits.add_by', '=', 'users.id')
        ->select('leave_credits.*', 'users.fname', 'users.mname', 'users.lname')
        ->orderBy('leave_credits.created_at', 'desc')
        ->get();    

        return view('leaves.emp-leaves', compact('leaves', 'guard', 'employee', 'emplalls', 'empid'));
    }
    
    public function leavesReadEmp(){
        $guard = $this->getGuard();
        $empid = auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $leaves = LeaveCredit::where('empid', $employee->emp_ID)
        ->join('users', 'leave_credits.add_by', '=', 'users.id')
        ->select('leave_credits.*', 'users.fname', 'users.mname', 'users.lname')
        ->orderBy('leave_credits.created_at', 'desc')
        ->get();    

        return view('leaves.emp-leaves', compact('leaves', 'guard', 'employee'));
    }

    public function leavesCreate(Request $request)
    {
        $authid = auth()->user()->id;
        $currentDate = Carbon::now()->format('Y-m');
        
        $request->validate([
            'empid' => 'required|exists:employees,id',
            'sl' => 'required|numeric|min:0',
            'vl' => 'required|numeric|min:0',
        ]);
    
        $employee = Employee::find($request->empid);
    
        if ($employee) {
            $employee->sl += $request->sl;
            $employee->vl += $request->vl;
            $employee->save();
    
            $records = LeaveCredit::where('empid', $employee->emp_ID)
                ->where('stat', 1)
                ->where('date', $request->date)
                ->get();
    
            $leavecredit = LeaveCredit::create([
                'empid' => $employee->emp_ID,
                'days' => $request->days,
                'earn_sl' => $request->sl,
                'earn_vl' => $request->vl,
                'remarks' => $request->remarks,
                'date' => $request->date ?? $currentDate,
                'add_by' => $authid,
                'stat' => $request->days ? 1 : 0,
            ]);

            Notification::create([
                'empid' => $employee->emp_ID,
                'lapp_id' => $leavecredit->id,
                'category' => 1,
                'utype' => 'employee',
                'module' => 'leavecreditadd',
                'status' => 1,
            ]);
        } else {
            return redirect()->back()->with('error', 'Employee not found.');
        }
    
        return redirect()->back()->with('success', 'Save successfully.');
    }

    public function leavescreditDeduct(Request $request)
    {
        $authid = auth()->user()->id;
        $currentDate = Carbon::now()->format('Y-m');
    
        $request->validate([
            'empid' => 'required|exists:employees,id',
            'sl' => 'required|numeric|min:0',
            'vl' => 'required|numeric|min:0',
        ]);
    
        $employee = Employee::find($request->empid);
    
        $leavecredit = LeaveCredit::create([
            'empid' => $employee->emp_ID,
            'days' => 0,
            'earn_sl' => $request->sl,
            'earn_vl' => $request->vl,
            'remarks' => $request->remarks,
            'date' => $request->date ?? $currentDate,
            'add_by' => $authid,
            'stat' => 1,
        ]);
    
        Employee::where('id', $request->empid)->update([
            'sl' => \DB::raw('ROUND(sl - ' . $request->sl . ', 3)'),
            'vl' => \DB::raw('ROUND(vl - ' . $request->vl . ', 3)'),
        ]);
    
        Notification::create([
            'empid' => $employee->emp_ID,
            'lapp_id' => $leavecredit->id,
            'category' => 1,
            'utype' => 'employee',
            'module' => 'leavecreditadd',
            'status' => 1,
        ]);
    
        return redirect()->back()->with('success', 'Save successfully.');
    }    
    
    public function leavesEdit(Request $request){
        $leavecredit = LeaveCredit::find($request->id);

        return response()->json([
            'data'=> $leavecredit,
        ]);
    }

    public function leavescreditDeductUpdate(Request $request){
        $authid = auth()->user()->id;
        $leavecread = LeaveCredit::find($request->lcid);

        $currentDate = Carbon::now()->format('Y-m');
        $request->validate([
            'empid' => 'required|exists:employees,id',
            'sl' => 'required|numeric|min:0',
            'vl' => 'required|numeric|min:0',
        ]);
    
        $employee = Employee::find($request->empid);

        if ($employee) {

            $employee->update([
                'sl' => ($employee->sl + $leavecread->earn_sl) - ($request->sl),
                'vl' => ($employee->vl + $leavecread->earn_vl) - ($request->vl),
            ]);            
            
            $employee->save();            
            
            LeaveCredit::where('id', $request->lcid)
            ->update([
                'days' => 0,
                'earn_sl' => $request->sl,
                'earn_vl' => $request->vl,
                'remarks' => $request->remarks,
                'date' => isset($request->date) ? $request->date : $currentDate,
                'add_by' => $authid,
                'stat' => 1,
            ]);

        } else {
            return redirect()->back()->with('error', 'Employee not found.');
        }
    
        return redirect()->back()->with('success', 'Save successfully.');
    }

    public function leavesUpdate(Request $request)
    {
        $authid = auth()->user()->id;
        $leavecread = LeaveCredit::find($request->lcid);

        $currentDate = Carbon::now()->format('Y-m');
        $request->validate([
            'empid' => 'required|exists:employees,id',
            'sl' => 'required|numeric|min:0',
            'vl' => 'required|numeric|min:0',
        ]);
    
        $employee = Employee::find($request->empid);
    
        if ($employee) {

            $employee->update([
                'sl' => ($employee->sl - $leavecread->earn_sl) + ($request->sl),
                'vl' => ($employee->vl - $leavecread->earn_vl) + ($request->vl),
            ]);            
            
            $employee->save();            
            
            LeaveCredit::where('id', $request->lcid)
            ->update([
                'days' => $request->days,
                'earn_sl' => $request->sl,
                'earn_vl' => $request->vl,
                'remarks' => $request->remarks,
                'date' => isset($request->date) ? $request->date : $currentDate,
                'add_by' => $authid,
                'stat' => ($request->days == null) ? 0 : 1,
            ]);

        } else {
            return redirect()->back()->with('error', 'Employee not found.');
        }
    
        return redirect()->back()->with('success', 'Save successfully.');
    }

    public function leavesDelete($id, $empid){
        $emp = LeaveCredit::find($id);
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

        if ($emp->stat == 1 && $emp->days == 0) {
            $employee->update([ 
                'sl' => $employee->sl + $emp->earn_sl,
                'vl' => $employee->vl + $emp->earn_vl,
            ]);
        } else {
            $employee->update([ 
                'sl' => $employee->sl - $emp->earn_sl,
                'vl' => $employee->vl - $emp->earn_vl,
            ]);
        }

        $emp->delete();

        return response()->json([
            'status'=>200,
            'sl' =>  $employee->sl,
            'vl' => $employee->vl,
            'message'=>"Deleted Successfully",
        ]);
    }
}
