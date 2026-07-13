<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Employee;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;
use App\Models\LearningDev;
use App\Models\LeaveApplication;

class PendingController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function readPending($type, $cat = null) {
        $guard = $this->getGuard();

        $leaveappCount = LeaveApplication::where('emp_esign', '=', 0)->where('history', 1)->where('status', 1)->count('empid');
        $eliCount = Eligibility::where('status', 0)->count();
        $workexpCount = WorkExperience::where('status', 0)->count();
        $learDevCount = LearningDev::where('status', 0)->count();
        $volWorkCount = VoluntaryWork::where('status', 0)->count();

        $employees = [];
        
        switch ($type) {
            case '1':
                $employees = LeaveApplication::join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
                ->join('employees as hr', 'hr.id', '=', 'leave_applications.hr')
                ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
                ->leftJoin('employees as approver', 'approver.id', '=', 'leave_applications.approver')
                ->select(
                    'leave_applications.*',
                    'emp.emp_ID as empid',
                    'emp.id as employid',
                    'emp.lname as employee_lname',
                    'emp.fname as employee_fname',
                    'emp.mname as employee_mname',
                    'emp.suffix as employee_suffix',
                    'hr.lname as hr_lname',
                    'hr.fname as hr_fname',
                    'hr.mname as hr_mname',
                    'hr.suffix as hr_suffix',
                    'sup.lname as supervisor_lname',
                    'sup.fname as supervisor_fname',
                    'sup.mname as supervisor_mname',
                    'sup.suffix as supervisor_suffix',
                    'approver.lname as approver_lname',
                    'approver.fname as approver_fname',
                    'approver.mname as approver_mname',
                    'approver.suffix as approver_suffix',
                );
        
                // Add a filter for $cat
                if ($cat !== null && in_array($cat, [1, 0.1, 0.2])) {
                    if($cat == 0.1){
                        $employees = $employees->where('leave_applications.emp_esign', '=', 0);
                    }
                    if($cat == 0.2){
                        $employees = $employees->where('leave_applications.emp_esign', '=', 1);
                    }
                    if($cat == 1){
                        $employees = $employees->where('leave_applications.emp_esign', '=', 2);
                    }
                    $employees = $employees->where('leave_applications.status', '=', 1);
                    $employees = $employees->where('leave_applications.history', 1);
                }
                elseif ($cat !== null && in_array($cat, [2, 3])) {
                    $employees = $employees->where('leave_applications.status', '=', $cat);
                    $employees = $employees->where('leave_applications.history', 1);
                }
                elseif ($cat == 4) {
                    $employees = $employees->where('leave_applications.status', '=', $cat);
                    $employees = $employees->where('leave_applications.history', '=', 2);
                }
                elseif ($cat == 5) {
                    $employees = $employees->where('leave_applications.history', 2);
                    $employees = $employees->where('leave_applications.remarks_stat', '!=', 0);
                }else{
                    $employees = $employees->where('leave_applications.history', '!=', 2);
                }
                
                $employees = $employees
                    ->orderByRaw('CASE WHEN leave_applications.emp_esign = 0 THEN 1 ELSE 0 END DESC')
                    ->orderByRaw('CASE 
                                    WHEN leave_applications.status = 2 THEN 1 
                                    WHEN leave_applications.status = 1 THEN 2 
                                    WHEN leave_applications.status = 3 THEN 3 
                                    WHEN leave_applications.status = 4 THEN 4 
                                END ASC')
                    ->get();
                       
                break;            
    
            case '2':
                $empids = Eligibility::where('status', 0)
                    ->get()->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '3':
                $empids = WorkExperience::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '4':
                $empids = VoluntaryWork::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '5':
                $empids = LearningDev::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
                
            default:
                return redirect()->route('');
        }

        if($type != 1){
            $employees = Employee::whereIn('emp_ID', $empids)->get();
        }

        return view('pending.index', compact('guard', 'cat', 'type', 'employees', 'eliCount', 'workexpCount', 'learDevCount', 'volWorkCount', 'leaveappCount'));
    }

}
