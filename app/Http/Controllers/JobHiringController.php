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

    /**
     * The public careers portal — no auth, applicants are not users.
     * Only positions that are Open and not past their closing date are shown;
     * everything else about a vacancy stays internal.
     */
    public function portal()
    {
        $jobs = JobHiring::where('status', 'Open')
            ->whereDate('expiration_at', '>=', now()->toDateString())
            ->orderBy('expiration_at')
            ->get();

        return view('career.portal', compact('jobs'));
    }

    /**
     * Nature of appointment, as an LGU uses it. The list previously offered
     * "Teaching / Non-Teaching", which is a university distinction and meant
     * nothing here.
     */
    public const APPOINTMENT_TYPES = [
        'Permanent'            => 'Permanent',
        'Casual'               => 'Casual',
        'Contractual'          => 'Contractual',
        'Co-terminous'         => 'Co-terminous',
        'Temporary'            => 'Temporary',
        'Job Order'            => 'Job Order',
        'Contract of Service'  => 'Contract of Service',
    ];

    public function jlist()
    {
        $guard = $this->getGuaard();
        $jobs = JobHiring::with(['positionDescription', 'comparativeAssessment'])
            ->withCount('applications')
            ->orderByDesc('id')
            ->get();

        return view("career.list", compact('jobs', 'guard') + [
            'descriptions' => \App\Models\PositionDescription::where('status', 'active')
                ->orderBy('position_title')->get(),
            'types'        => self::APPOINTMENT_TYPES,
        ]);
    }

    public function jCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'              => 'required',
            'title'             => 'required',
            // The standing DBM-CSC Form No. 1 for this plantilla item. Optional,
            // so a vacancy can still be posted before its description is written.
            'position_description_id' => 'nullable|exists:position_descriptions,id',
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
        $jobs = JobHiring::with(['positionDescription', 'comparativeAssessment'])
            ->withCount('applications')
            ->orderByDesc('id')
            ->get();
        $jEdit = JobHiring::find($id);

        if (!$jEdit) {
            return redirect()->back()->with('error', 'Job not found.');
        }

        return view("career.list", compact('jobs', 'jEdit', 'guard') + [
            'descriptions' => \App\Models\PositionDescription::where('status', 'active')
                ->orderBy('position_title')->get(),
            'types'        => self::APPOINTMENT_TYPES,
        ]);
    }

    public function jUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'              => 'required',
            'title'             => 'required',
            // The standing DBM-CSC Form No. 1 for this plantilla item. Optional,
            // so a vacancy can still be posted before its description is written.
            'position_description_id' => 'nullable|exists:position_descriptions,id',
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
