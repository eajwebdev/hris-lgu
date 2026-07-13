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

class LearningDevController extends Controller
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

    public function learningdev($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $learningdev = LearningDev::where('empid', $employee->emp_ID)->get();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.learning-dev", compact('guard', 'empid', 'employee', 'learningdev', 'columnstatus'));
    }

    public function learningdevCreate(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'learning_dev' => 'required',
            'inc_date1' => 'required',
            'inc_date2' => 'required',
            'num_hours' => 'required',
            'types' => 'required',
            'conducted' => 'required',
            'attachment' => 'nullable|file|mimes:pdf', 
        ]);
    
        $attachmentPath = null;
    
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $originalName . '-' . $randomNumber . '.' . $extension;
    
            $attachmentPath = $attachment->storeAs('LearningDev', $newFileName, 'public');
        }
    
        $learningdev = LearningDev::create([
            'empid' => $request->input('empid'),
            'learning_dev' => $request->input('learning_dev'),
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'num_hours' => $request->input('num_hours'),
            'types' => $request->input('types'),
            'conducted' => $request->input('conducted'),
            'attachment' => $attachmentPath,
        ]);

        Notification::create([
            'empid' => $request->input('empid'),
            'lapp_id' => $learningdev->id,
            'category' => 1,
            'utype' => 'hr',
            'module' => 'pds',
        ]);
    
        return redirect()->back()->with('success', 'Added successfully!');
    } 

    public function learningdevEdit($id, $eid)
    {
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }
        $learningdev = LearningDev::where('empid', $employee->emp_ID)->get();
        $learningdevedit = LearningDev::where('id', $eid)->where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);
        return view('emp.learning-dev', compact('guard', 'empid', 'employee', 'learningdev', 'learningdevedit', 'columnstatus'));
    }

    public function learningdevUpdate(Request $request, $id)
    {
        $request->validate([
            'empid' => 'required',
            'learning_dev' => 'required',
            'inc_date1' => 'required',
            'inc_date2' => 'required',
            'num_hours' => 'required',
            'types' => 'required',
            'conducted' => 'required',
            'attachment' => 'nullable|file|mimes:pdf',
        ]);
    
        $learningdev = LearningDev::findOrFail($id);
    
        $attachmentPath = $learningdev->attachment;
    
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $originalName . '-' . $randomNumber . '.' . $extension;
    
            $attachmentPath = $attachment->storeAs('LearningDev', $newFileName, 'public');
    
            if ($learningdev->attachment && \Storage::disk('public')->exists($learningdev->attachment)) {
                \Storage::disk('public')->delete($learningdev->attachment);
            }
        }
    
        $learningdev->update([
            'learning_dev' => $request->input('learning_dev'),
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'num_hours' => $request->input('num_hours'),
            'types' => $request->input('types'),
            'conducted' => $request->input('conducted'),
            'attachment' => $attachmentPath,
            'status' => 0,
        ]);
    
        return redirect()->back()->with('success', 'Updated successfully!');
    }

    public function learningdevApprove($id){
        $learningdev = LearningDev::find($id);
        
        if ($learningdev) {
            $learningdev->status = 1;
            $learningdev->save();
            
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
    
    public function learningdevDelete($id)
    {
        $learningdev = LearningDev::find($id);
        
        if ($learningdev) {
            $learningdev->delete();
    
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
    
    public function learningdevCancel(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'remarks' => 'required',
        ]);

        LearningDev::where('id', $validated['id'])->update([
            'status' => 2,
            'remarks' => $validated['remarks']
        ]);

        return redirect()->back()->with('success', 'Successfully canceled.');
    }
}
