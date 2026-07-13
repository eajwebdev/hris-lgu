<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Employee;
use App\Models\FamilyBg;
use App\Models\EducBg;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;
use App\Models\LearningDev;
use App\Models\OtherInfo;
use App\Models\InfoQuestion;
use App\Models\PdsReference;
use App\Models\GovId;

class GovIdController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function columnStat($empid){
        $familyBg = FamilyBg::where('empid', $empid)->first();
        $educBg = EducBg::where('empid', $empid)->first();
        $eligibility = Eligibility::where('empid', $empid)->get();
        $workexperience = WorkExperience::where('empid', $empid)->get();
        $voluntaryworks = VoluntaryWork::where('empid', $empid)->get();
        $learningdev = LearningDev::where('empid', $empid)->get();
        $otherinfo = OtherInfo::where('empid', $empid)->first();
        $infoquestion = InfoQuestion::where('empid', $empid)->first();
        $references = PdsReference::where('empid', $empid)->first();
        $govids= GovId::where('empid', $empid)->first();
        
        $columnstatus = [
            'colfamstat' => $familyBg->famhasAnyValue(),
            'coleducstat' => $educBg->educhasAnyValue(),
            'eligibility' => $eligibility,
            'workexperience' => $workexperience,
            'voluntaryworks' => $voluntaryworks,
            'learningdev' => $learningdev,
            'colotherinfo' => $otherinfo->otherinfoAnyValue(),
            'colinfoquestion' => $infoquestion->infoquestionValue(),
            'colreferences' => $references->referencesValue(),
            'colgovids' => $govids->govidsValue(),
        ];

        return $columnstatus;
    }

    public function govids($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $govids = GovId::where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);

        return view("emp.government-id", compact('guard', 'empid', 'employee', 'govids', 'columnstatus'));
    }

    public function update(Request $request)
    {
        $empid = $request->input('empid'); 
        $columnWithSuffix = $request->input('column');
        $index = $request->input('index');    
        $value = $request->input('value');
    
        $column = preg_replace('/_\d+$/', '', $columnWithSuffix);
    
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $govids = GovId::where('empid', $employee->emp_ID)->first();
    
        if ($govids) {
            $currentValue = $govids->$column;
            $valuesArray = explode(',', $currentValue);
    
            if (isset($valuesArray[$index])) {
                $valuesArray[$index] = $value;
            } else {
                $valuesArray[$index] = $value;
            }
    
            $newValue = implode(',', $valuesArray);
    
            $govids->$column = $newValue;
    
            if ($column === 'question' && $value == '0') {
                $qdetailsArray = explode(',', $govids->qdetails);
    
                if (isset($qdetailsArray[$index])) {
                    $qdetailsArray[$index] = '';
                } else {
                    $qdetailsArray = array_pad($qdetailsArray, $index + 1, '');
                    $qdetailsArray[$index] = '';
                }
    
                $govids->qdetails = implode(',', $qdetailsArray);
            }
    
            $govids->save();
    
            return response()->json(['success' => true]);
        }
    
        return response()->json(['success' => false], 404);
    } 
}
