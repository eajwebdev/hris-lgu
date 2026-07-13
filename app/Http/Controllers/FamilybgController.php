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

class FamilybgController extends Controller
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
    
    public function familybg($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $familyBg = FamilyBg::where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);

        return view("emp.family-bg", compact('guard', 'empid', 'employee', 'familyBg', 'columnstatus'));
    }

    public function updateChild(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'name_child' => 'required|array',
            'date_birth' => 'required|array',
        ]);

        $empid = $request->input('empid');        
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $familyBg = FamilyBg::where("empid", $employee->emp_ID)->first();
    
        if (!$familyBg) {
            return response()->json(['success' => false, 'message' => 'Record not found.']);
        }
    
        $nameChild = $request->input('name_child');
        $dateBirth = $request->input('date_birth');
        
        if (count($nameChild) !== count($dateBirth)) {
            return response()->json(['success' => false, 'message' => 'Mismatch between name_children and date_birth arrays length.']);
        }
    
        $nameChildString = implode(',', $nameChild);
        $dateBirthString = implode(',', $dateBirth);
    
        $familyBg->update([
            'name_child' => $nameChildString,
            'date_birth' => $dateBirthString,
        ]);
    
        return response()->json(['success' => true]);
    }
    
    public function familyBgUpdate(Request $request){
        $employee = Employee::find($request->id);
        $familybg = FamilyBg::where("empid", $employee->emp_ID)->first();
        $column = $request->column;
        $value = $request->value;

        $familybg->update([
            $column => $value,
        ]);
        
        return response()->json(['success' => true]);
    }

    public function familyBgUpdateArray(Request $request)
    {
        $request->validate([
            'names' => 'required|array', 
            'dates' => 'required|array',
            'names.*' => 'nullable|string',
            'dates.*' => 'nullable|date',
        ]);
        
        $empid = $request->input('empid'); 
        $familybg = FamilyBg::where("empid", $empid)->first();
    
        $names = $request->input('names');
        $dates = $request->input('dates');
    
        if (count($names) !== count($dates)) {
            return response()->json(['success' => false, 'message' => 'Mismatch between names and dates arrays length.']);
        }
        
        $familybg->update([
            'name_child' => implode(',', $names),
            'date_birth' => implode(',', $dates),
        ]);
        
        return response()->json(['success' => true]);
    }

}
