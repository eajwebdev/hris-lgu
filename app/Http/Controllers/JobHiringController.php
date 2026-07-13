<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\JobHiring;

class JobHiringController extends Controller
{
    public function getGuaard()
    {
        if (\Auth::guard('web')->check()) {
            return 'web';
        } elseif (\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function jlist()
    {
        $guard = $this->getGuaard();
        $jobs = JobHiring::all();

        return view("career.list", compact('jobs', 'guard'));
    }

    public function jCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'             => 'required',
            'title'             => 'required',
            'plantilla_item_no' => 'required|unique:job_hirings',
            'salary'            => 'required|numeric',
            'assignment'        => 'nullable',
            'education'         => 'required',
            'eligibility'       => 'required',
            'training'          => 'nullable',
            'experience'        => 'nullable',
            'competency'        => 'nullable',
            'posted_at'         => 'required',
            'expiration_at'     => 'required',
            'status'            => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        JobHiring::create($request->all());

        return redirect()->back()->with('success', 'Job created successfully.');
    }

    public function jEdit($id)
    {
        $guard = $this->getGuaard();
        $jobs = JobHiring::all();
        $jEdit = JobHiring::find($id);

        if (!$jEdit) {
            return redirect()->back()->with('error', 'Job not found.');
        }

        return view("career.list", compact('jobs', 'jEdit', 'guard'));
    }

    public function jUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'             => 'required',
            'title'             => 'required',
            'plantilla_item_no' => 'required',
            'salary'            => 'required|numeric',
            'assignment'        => 'nullable',
            'education'         => 'required',
            'eligibility'       => 'required',
            'training'          => 'nullable',
            'experience'        => 'nullable',
            'competency'        => 'nullable',
            'posted_at'         => 'required',
            'expiration_at'     => 'required',
            'status'            => 'required',
        ]); 

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $job = JobHiring::find($request->input('id'));

        if (!$job) {
            return redirect()->back()->withErrors(['error' => 'Job not found']);
        }

        $job->update($request->all());

        return redirect()->back()->with('success', 'Job updated successfully.');
    }

    public function jDelete(Request $request)
    {
        $job = JobHiring::find($request->id);

        if (!$job) {
            return response()->json([
                'status' => 404,
                'message' => 'Job not found',
            ]);
        }

        $job->delete();

        return response()->json([
            'status' => 200,
            'id' => $job->id,
        ]);
    }

    public function apply(Request $request, $id)
    {
        $job = JobHiring::find($id);

        if (!$job) {
            return redirect()->back()->with('error', 'Job not found.');
        }

        // here you can handle saving applicant data later
        return redirect()->back()->with('success', 'Your application has been submitted for ' . $job->title);
    }
}
