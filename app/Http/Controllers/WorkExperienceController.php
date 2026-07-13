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
use App\Models\Notification;

class WorkExperienceController extends Controller
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
    
    public function workexperience($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $workexperience = WorkExperience::where('empid', $employee->emp_ID)->get();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.work-experience", compact('guard', 'empid', 'employee', 'workexperience', 'columnstatus'));
    }

    public function workexperienceCreate(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'inc_date1' => 'required',
            'inc_date2' => 'nullable',
            'position' => 'required',
            'department' => 'required',
            'sg_grade' => 'nullable',
            'salary' => 'nullable',
            'stat_app' => 'nullable',
            'service' => 'required',
            'attachment' => 'nullable|file|mimes:pdf',
            'supervisor' => 'nullable', // Validate as an array for accomplishments
            'actual_summary' => 'nullable|string',
        ]);
    
        $attachmentPath = null;
    
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $originalName . '-' . $randomNumber . '.' . $extension;
    
            $attachmentPath = $attachment->storeAs('WorkExperience', $newFileName, 'public');
        }
    
        // Prepare the list of accomplishments as a semicolon-separated string
        $listAccom = $request->input('list_accom', []); // Default to an empty array if not provided
        $listAccomString = implode(';', array_map('trim', $listAccom)); // Join values with semicolon
    
        $workexperience = WorkExperience::create([
            'empid' => $request->input('empid'),
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'position' => $request->input('position'),
            'department' => $request->input('department'),
            'sg_grade' => $request->input('sg_grade'),
            'salary' => $request->input('salary'),
            'stat_app' => $request->input('stat_app'),
            'service' => $request->input('service'),
            'attachment' => $attachmentPath,
            'list_accom' => $listAccomString,
            'actual_summary' => $request->input('actual_summary'),
        ]);
    
        Notification::create([
            'empid' => $request->input('empid'),
            'lapp_id' => $workexperience->id,
            'category' => 2,
            'utype' => 'hr',
            'module' => 'pds',
        ]);
    
        return redirect()->back()->with('success', 'Added successfully!');
    }      

    public function workexperienceEdit($id, $eid)
    {
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $workexperience = WorkExperience::where('empid', $employee->emp_ID)->get();
        $workexperienceedit = WorkExperience::where('id', $eid)->where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);
        return view('emp.work-experience', compact('guard', 'empid', 'employee', 'workexperience', 'workexperienceedit', 'columnstatus'));
    }

    public function workexperienceUpdate(Request $request, $id)
    {
        $request->validate([
            'inc_date1' => 'required',
            'inc_date2' => 'nullable',
            'position' => 'required',
            'department' => 'required',
            'sg_grade' => 'nullable',
            'salary' => 'nullable',
            'stat_app' => 'nullable',
            'service' => 'required',
            'supervisor' => 'nullable',
            'attachment' => 'nullable|file|mimes:pdf',
            'actual_summary' => 'nullable|string',
        ]);

        // $salary = $request->input('salary');
        // if (!is_null($salary)) {
        //     $salary = str_replace(',', '', $salary);
        // }

        $workexperience = WorkExperience::findOrFail($id);

        $attachmentPath = $workexperience->attachment;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $randomNumber . '_' . $originalName . '.' . $extension;
            $attachmentPath = $attachment->storeAs('WorkExperience', $newFileName, 'public');

            if ($workexperience->attachment && \Storage::disk('public')->exists($workexperience->attachment)) {
                \Storage::disk('public')->delete($workexperience->attachment);
            }
        }

        // Process List of Accomplishments
        $listAccomplishments = $request->input('list_accom', []);
        $formattedAccomplishments = implode(';', array_map('trim', $listAccomplishments));

        $workexperience->update([
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'position' => $request->input('position'),
            'department' => $request->input('department'),
            'sg_grade' => $request->input('sg_grade'),
            'salary' => $request->input('salary'),
            'stat_app' => $request->input('stat_app'),
            'service' => $request->input('service'),
            'supervisor' => $request->input('supervisor'),
            'attachment' => $attachmentPath,
            'list_accom' => $formattedAccomplishments,
            'actual_summary' => $request->input('actual_summary'),
        ]);

        return redirect()->back()->with('success', 'Work Experience updated successfully!');
    }   

    public function expApprove($id){
        $workexperience = WorkExperience::find($id);
        
        if ($workexperience) {
            $workexperience->status = 1;
            $workexperience->save();

            Notification::create([
                'empid' => $workexperience->empid,
                'lapp_id' => $id,
                'category' => 2,
                'utype' => 'employee',
                'module' => 'pds',
            ]);

            return response()->json([
                'status' => 200,
                'message' => "Approved Successfully",
            ]);

        } else {
            return response()->json([
                'status' => 404,
                'message' => "Record not found",
            ]);
        }
    }

    public function workDelete($id)
    {
        $workexperience = WorkExperience::find($id);
        
        if ($workexperience) {
            $filePath = public_path('storage/' . $workexperience->attachment);
    
            $workexperience->delete();
    
            return response()->json([
                'status' => 200,
                'message' => "Deleted Successfully",
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "Record not found",
            ]);
        }
    }
    
    public function workexperienceCancel(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'remarks' => 'required',
        ]);

        WorkExperience::where('id', $validated['id'])->update([
            'status' => 2,
            'remarks' => $validated['remarks']
        ]);

        return redirect()->back()->with('success', 'Successfully canceled.');
    }
}
