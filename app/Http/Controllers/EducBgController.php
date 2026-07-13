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

class EducBgController extends Controller
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

    public function educbg($id = null)
    {
        $guard = $this->getGuard();
        $empid = $id ?? auth()->guard($guard)->user()->id;
        $employee = Employee::findOrFail($empid);
        $educBg = EducBg::where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);

        return view("emp.educational-bg", compact('guard', 'empid', 'employee', 'educBg', 'columnstatus'));
    }

    public function updateEducChild(Request $request)
    {
        $request->validate([
            'empid' => 'required|exists:employees,emp_ID',
            'schools' => 'required|array',
            'degrees' => 'required|array',
            'periods' => 'required|array',
            'levels' => 'required|array',
            'years' => 'required|array',
            'honors' => 'required|array',
            'schools.*' => 'nullable|string|max:255',
            'degrees.*' => 'nullable|string|max:255',
            'periods.*' => 'nullable|string|max:255',
            'levels.*' => 'nullable|string|max:255',
            'years.*' => 'nullable',
            'honors.*' => 'nullable|string|max:255',
        ]);
    
        $empid = $request->input('empid');
        $educBg = EducBg::where('empid', $empid)->first();
    
        if (!$educBg) {
            return response()->json(['success' => false, 'message' => 'Record not found.']);
        }
    
        $schools = $request->input('schools');
        $degrees = $request->input('degrees');
        $periods = $request->input('periods');
        $levels = $request->input('levels');
        $years = $request->input('years');
        $honors = $request->input('honors');
    
        if (count($schools) !== count($degrees) || count($schools) !== count($periods) || count($schools) !== count($levels) || count($schools) !== count($years) || count($schools) !== count($honors)) {
            return response()->json(['success' => false, 'message' => 'Mismatch between array lengths.']);
        }
    
        $educBg->update([
            'coll_school' => implode(',', $schools),
            'coll_course' => implode(',', $degrees),
            'coll_period' => implode(',', $periods),
            'coll_level' => implode(',', $levels),
            'coll_grad' => implode(',', $years),
            'coll_honor' => implode(',', $honors),
        ]);
    
        return response()->json(['success' => true]);
    }    

    public function educBgUpdate(Request $request)
    {
        $employee = Employee::find($request->id);
        $educBg = EducBg::where("empid", $employee->emp_ID)->first();
    
        if (!$educBg) {
            return response()->json(['success' => false, 'message' => 'Education background record not found.']);
        }
    
        $column = $request->column; 
        $value = $request->value; 
    
        $educBg->update([
            $column => $value,
        ]);
    
        return response()->json(['success' => true]);
    }    
    
    public function educBgUpdateArray(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'schools' => 'array', // Remove required to allow empty arrays
            'degrees' => 'array',
            'periods' => 'array',
            'levels' => 'array',
            'years' => 'array',
            'honors' => 'array',
            'schools.*' => 'nullable|string|max:255',
            'degrees.*' => 'nullable|string|max:255',
            'periods.*' => 'nullable|string|max:255',
            'levels.*' => 'nullable|string|max:255',
            'years.*' => 'nullable|string|max:255',
            'honors.*' => 'nullable|string|max:255',
        ]);
    
        $empid = $request->input('empid');
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $educBg = EducBg::where('empid', $employee->emp_ID)->first();
    
        if (!$educBg) {
            return response()->json(['success' => false, 'message' => 'Education background record not found.']);
        }
    
        // Retrieve input arrays, default to empty arrays if not provided
        $schools = $request->input('schools', []);
        $degrees = $request->input('degrees', []);
        $periods = $request->input('periods', []);
        $levels = $request->input('levels', []);
        $years = $request->input('years', []);
        $honors = $request->input('honors', []);
    
        // Pad arrays to match lengths
        $maxLength = max(count($schools), count($degrees), count($periods), count($levels), count($years), count($honors));
    
        $schools = array_pad($schools, $maxLength, '');
        $degrees = array_pad($degrees, $maxLength, '');
        $periods = array_pad($periods, $maxLength, '');
        $levels = array_pad($levels, $maxLength, '');
        $years = array_pad($years, $maxLength, '');
        $honors = array_pad($honors, $maxLength, '');
    
        // Update the record with imploded array values
        $educBg->update([
            'coll_school' => implode(',', $schools),
            'coll_course' => implode(',', $degrees),
            'coll_period' => implode(',', $periods),
            'coll_level' => implode(',', $levels),
            'coll_grad' => implode(',', $years),
            'coll_honor' => implode(',', $honors),
        ]);
    
        return response()->json(['success' => true]);
    }  
    
    public function graduateStudiesUpdate(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'grad_schools' => 'array',
            'grad_courses' => 'array',
            'grad_periods' => 'array',
            'grad_levels' => 'array',
            'grad_years' => 'array',
            'grad_honors' => 'array',
            'grad_schools.*' => 'nullable|string|max:255',
            'grad_courses.*' => 'nullable|string|max:255',
            'grad_periods.*' => 'nullable|string|max:255',
            'grad_levels.*' => 'nullable|string|max:255',
            'grad_years.*' => 'nullable',
            'grad_honors.*' => 'nullable|string|max:255',
        ]);
    
        $empid = $request->input('empid');
        $educBg = EducBg::where('empid', $empid)->first();
    
        if (!$educBg) {
            return response()->json(['success' => false, 'message' => 'Graduate studies record not found.']);
        }
    
        // Retrieve input arrays, default to empty arrays if not provided
        $gradSchools = $request->input('grad_schools', []);
        $gradCourses = $request->input('grad_courses', []);
        $gradPeriods = $request->input('grad_periods', []);
        $gradLevels = $request->input('grad_levels', []);
        $gradYears = $request->input('grad_years', []);
        $gradHonors = $request->input('grad_honors', []);
    
        // Check that all arrays are of the same length
        if (count($gradSchools) !== count($gradCourses) || 
            count($gradSchools) !== count($gradPeriods) || 
            count($gradSchools) !== count($gradLevels) || 
            count($gradSchools) !== count($gradYears) || 
            count($gradSchools) !== count($gradHonors)) {
            return response()->json(['success' => false, 'message' => 'Mismatch between array lengths.']);
        }
    
        // Pad arrays to match lengths if necessary
        $maxLength = max(count($gradSchools), count($gradCourses), count($gradPeriods), count($gradLevels), count($gradYears), count($gradHonors));
    
        $gradSchools = array_pad($gradSchools, $maxLength, '');
        $gradCourses = array_pad($gradCourses, $maxLength, '');
        $gradPeriods = array_pad($gradPeriods, $maxLength, '');
        $gradLevels = array_pad($gradLevels, $maxLength, '');
        $gradYears = array_pad($gradYears, $maxLength, '');
        $gradHonors = array_pad($gradHonors, $maxLength, '');
    
        // Update the graduate studies fields by imploding arrays
        $educBg->update([
            'grad_school' => implode(',', $gradSchools),
            'grad_course' => implode(',', $gradCourses),
            'grad_period' => implode(',', $gradPeriods),
            'grad_level' => implode(',', $gradLevels),
            'grad_grad' => implode(',', $gradYears),
            'grad_honor' => implode(',', $gradHonors),
        ]);
    
        return response()->json(['success' => true, 'message' => 'Graduate studies updated successfully.']);
    }    

    public function educBgUpdateGraduateArray(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'grad_schools' => 'array', // Remove required to allow empty arrays
            'grad_courses' => 'array',
            'grad_periods' => 'array',
            'grad_levels' => 'array',
            'grad_years' => 'array',
            'grad_honors' => 'array',
            'grad_schools.*' => 'nullable|string|max:255',
            'grad_courses.*' => 'nullable|string|max:255',
            'grad_periods.*' => 'nullable|string|max:255',
            'grad_levels.*' => 'nullable|string|max:255',
            'grad_years.*' => 'nullable',
            'grad_honors.*' => 'nullable|string|max:255',
        ]);
    
        $empid = $request->input('empid');
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $educBg = EducBg::where('empid', $employee->emp_ID)->first();
    
        if (!$educBg) {
            return response()->json(['success' => false, 'message' => 'Education background record not found.']);
        }
    
        // Retrieve input arrays, default to empty arrays if not provided
        $gradSchools = $request->input('grad_schools', []);
        $gradCourses = $request->input('grad_courses', []);
        $gradPeriods = $request->input('grad_periods', []);
        $gradLevels = $request->input('grad_levels', []);
        $gradYears = $request->input('grad_years', []);
        $gradHonors = $request->input('grad_honors', []);
    
        // Pad arrays to match lengths
        $maxLength = max(count($gradSchools), count($gradCourses), count($gradPeriods), count($gradLevels), count($gradYears), count($gradHonors));
    
        $gradSchools = array_pad($gradSchools, $maxLength, '');
        $gradCourses = array_pad($gradCourses, $maxLength, '');
        $gradPeriods = array_pad($gradPeriods, $maxLength, '');
        $gradLevels = array_pad($gradLevels, $maxLength, '');
        $gradYears = array_pad($gradYears, $maxLength, '');
        $gradHonors = array_pad($gradHonors, $maxLength, '');
    
        // Update the record with imploded array values
        $educBg->update([
            'grad_school' => implode(',', $gradSchools),
            'grad_course' => implode(',', $gradCourses),
            'grad_period' => implode(',', $gradPeriods),
            'grad_level' => implode(',', $gradLevels),
            'grad_grad' => implode(',', $gradYears),
            'grad_honor' => implode(',', $gradHonors),
        ]);
    
        return response()->json(['success' => true]);
    }
    
}
