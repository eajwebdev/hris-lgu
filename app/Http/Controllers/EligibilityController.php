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

class EligibilityController extends Controller
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

    public function eligibility($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $eligibility = Eligibility::where('empid', $employee->emp_ID)->get();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.eligibility", compact('guard', 'empid', 'employee', 'eligibility', 'columnstatus'));
    }
    
    public function eligibilityEdit($id = null, $eid){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $eligibility = Eligibility::where('empid', $employee->emp_ID)->get();
        $eligibilityedit = Eligibility::where('id', $eid)->where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.eligibility", compact('guard', 'empid', 'employee', 'eligibility', 'eligibilityedit', 'columnstatus'));
    }

    public function eligibilityCreate(Request $request)
    {
        $request->validate([
            'careereligible' => 'nullable',
            'rating' => 'nullable',
            'date_exam' => 'required',
            'place_exam' => 'required',
            'number' => 'nullable',
            'date_valid' => 'nullable',
            'attachment' => 'required|file|mimes:pdf',
        ]);
        
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $originalName . '-' . $randomNumber . '.' . $extension;
            $attachmentPath = $attachment->storeAs('Eligibility', $newFileName, 'public');
        }
    
        $eligibility = Eligibility::create([
            'empid' => $request->input('empid'),
            'careereligible' => $request->input('careereligible'),
            'rating' => $request->input('rating'),
            'date_exam' => $request->input('date_exam'),
            'place_exam' => $request->input('place_exam'),
            'number' => $request->input('number'),
            'date_valid' => $request->input('date_valid'),
            'attachment' => $attachmentPath,
        ]);
        
        Notification::create([
            'empid' => $request->input('empid'),
            'lapp_id' => $eligibility->id,
            'category' => 1,
            'utype' => 'hr',
            'module' => 'pds',
        ]);
    
        return redirect()->back()->with('success', 'Eligibility submitted successfully.');
    }    

    public function eligibilityUpdate(Request $request, $id)
    {
        $request->validate([
            'careereligible' => 'required',
            'rating' => 'nullable',
            'date_exam' => 'required',
            'place_exam' => 'required',
            'number' => 'nullable',
            'date_valid' => 'nullable',
            'attachment' => 'nullable|file|mimes:pdf',
        ]);

        $eligibility = Eligibility::findOrFail($id);

        $attachmentPath = $eligibility->attachment;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $randomNumber . '_' . $originalName . '.' . $extension;
            $attachmentPath = $attachment->storeAs('Eligibility', $newFileName, 'public');
            
            if ($eligibility->attachment && \Storage::disk('public')->exists($eligibility->attachment)) {
                \Storage::disk('public')->delete($eligibility->attachment);
            }
        }

        $eligibility->update([
            'careereligible' => $request->input('careereligible'),
            'rating' => $request->input('rating'),
            'date_exam' => $request->input('date_exam'),
            'place_exam' => $request->input('place_exam'),
            'number' => $request->input('number'),
            'date_valid' => $request->input('date_valid'),
            'attachment' => $attachmentPath,
            'status' => 0,
        ]);

        return redirect()->back()->with('success', 'Eligibility updated successfully.');
    }

    public function eliCancel(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'remarks' => 'required',
        ]);
        
        Eligibility::where('id', $validated['id'])->update([
            'status' => 2,
            'remarks' => $validated['remarks']
        ]);

        return redirect()->back()->with('success', 'Successfully canceled.');
    }

    public function eliDelete($id)
    {
        $eligible = Eligibility::find($id);
        
        if ($eligible) {
            $filePath = public_path('storage/' . $eligible->attachment);
    
            if (file_exists($filePath)) {
                unlink($filePath);
            }
    
            $eligible->delete();
    
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

    public function eliApprove($id)
    {
        $eligible = Eligibility::find($id);
        
        if ($eligible) {
            $eligible->status = 1;
            $eligible->save();

            Notification::create([
                'empid' => $eligible->empid,
                'lapp_id' => $id,
                'category' => 1,
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

    
}
