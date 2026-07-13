<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Employee;
use App\Models\EteApplicantRating;
use App\Models\EteEvaluation;
use App\Models\Evaluator;
use App\Models\JobHiring;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EteEvaluationController extends Controller
{
    private function authorizeEteAdmin()
    {
        abort_unless(auth()->guard('web')->check(), 403, 'Only HR administrators can manage ETE evaluations.');
    }

    private function authorizeRankingAdmin()
    {
        $user = auth()->guard('web')->user();
        if (!$user || !in_array($user->role, ['Administrator', 'HR Administrator'], true)) {
            redirect()->route('dashboard')->with('error1', 'Only administrators can view rankings.')->send();
            exit;
        }
    }

    private function experienceYears($value)
    {
        if (empty($value)) {
            return [];
        }

        if (str_contains($value, '-')) {
            [$from, $to] = array_map('intval', explode('-', $value, 2));
            return $from <= $to ? range($to, $from) : [];
        }

        $years = array_filter(array_map('trim', explode(',', $value)));
        rsort($years, SORT_NUMERIC);
        return $years;
    }

    private function applicantName($app)
    {
        return trim(collect([$app->first_name, $app->middle_name, $app->last_name])->filter()->implode(' '));
    }

    private function syncMinimumRequirementScore(EteApplicantRating $rating)
    {
        $minimumScore = collect([
            $rating->education_met,
            $rating->experience_met,
            $rating->eligibility_met,
            $rating->training_met,
        ])->filter(fn ($value) => $value === true)->count() * 17.5;
        $totalScore = $minimumScore
            + (float) $rating->education_score
            + (float) $rating->training_score
            + (float) $rating->experience_score;

        if ((float) $rating->minimum_requirement_score !== $minimumScore
            || (float) $rating->total_score !== $totalScore) {
            $rating->update([
                'minimum_requirement_score' => $minimumScore,
                'total_score' => $totalScore,
            ]);
        }
    }

    private function syncApplicationRating(EteEvaluation $ete, Application $application)
    {
        abort_if((int) $application->jid !== (int) $ete->jid, 422, 'Applicant does not belong to this ETE position.');
        $ete->loadMissing(['job', 'office']);

        $rating = EteApplicantRating::firstOrCreate(
            ['ete_id' => $ete->id, 'application_id' => $application->id],
            [
                'jid' => $ete->jid,
                'evaluation_date' => optional($ete->evaluation_date)->toDateString(),
                'present_position' => $application->position,
                'college_department' => optional($ete->office)->office_name,
                'education_score' => 0,
                'training_score' => 0,
                'experience_score' => 0,
                'total_score' => 0,
                'created_by' => auth()->guard('web')->id(),
            ]
        );

        if (!$rating->college_department && $ete->office) {
            $rating->update(['college_department' => $ete->office->office_name]);
        }

        $this->syncMinimumRequirementScore($rating);

        return $rating;
    }

    private function syncReviewingApplicants(EteEvaluation $ete)
    {
        Application::where('jid', $ete->jid)->where('status', 1)->get()
            ->each(fn ($application) => $this->syncApplicationRating($ete, $application));
    }

    private function ratingCompleted($rating)
    {
        return $rating && (
            $rating->education_met !== null || $rating->experience_met !== null ||
            $rating->eligibility_met !== null || $rating->training_met !== null ||
            (float) $rating->total_score > 0 || !empty($rating->remarks)
        );
    }

    private function previousApplicantRatings(EteApplicantRating $currentRating)
    {
        $currentRating->loadMissing('application');
        $email = strtolower(trim((string) optional($currentRating->application)->email));

        if ($email === '') {
            return collect();
        }

        return EteApplicantRating::with(['application', 'eteEvaluation.job'])
            ->where('id', '!=', $currentRating->id)
            ->where('jid', '!=', $currentRating->jid)
            ->whereHas('application', function ($query) use ($email) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->latest('updated_at')
            ->get()
            ->filter(fn ($rating) => $this->ratingCompleted($rating))
            ->values();
    }

    public function eteEvaluationList()
    {
        $this->authorizeEteAdmin();
        $eteEvaluations = EteEvaluation::with(['job', 'office', 'evaluators.employee', 'applicantRatings.application'])
            ->latest()->get();
        $jobs = JobHiring::orderBy('title')->get();
        $employees = Employee::orderBy('lname')->get();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
            ->orderBy('office_name')
            ->get();

        return view('ete.index', compact('eteEvaluations', 'jobs', 'employees', 'offices'));
    }

    public function eteEvaluationStore(Request $request)
    {
        $this->authorizeEteAdmin();
        $request->validate([
            'jid' => 'required|exists:job_hirings,id',
            'off_id' => 'required|exists:payroll.offices,id',
            'evaluators' => 'required|array|min:1',
            'evaluators.*' => 'exists:employees,id',
            'evaluation_date' => 'required|date',
            'experience_years' => 'required|string',
        ]);

        $applications = Application::where('jid', $request->jid)->where('status', 1)->get();
        if ($applications->isEmpty()) {
            return back()->with('error', 'No applicants with status Reviewing found for this position.');
        }

        DB::transaction(function () use ($request, $applications) {
            $ete = EteEvaluation::create([
                'jid' => $request->jid,
                'off_id' => $request->off_id,
                'evaluation_date' => Carbon::parse($request->evaluation_date),
                'experience_years' => $request->experience_years,
                'active_application_id' => null,
            ]);

            collect($request->evaluators)->unique()->each(fn ($empId) => Evaluator::create([
                'ete_id' => $ete->id,
                'emp_id' => $empId,
            ]));

            $applications->each(fn ($application) => $this->syncApplicationRating($ete, $application));
        });

        return back()->with('success', 'ETE evaluation created successfully.');
    }

    public function eteEvaluationShow($id)
    {
        $this->authorizeEteAdmin();
        $ete = EteEvaluation::with(['job', 'office', 'evaluators.employee'])->findOrFail($id);
        $this->syncReviewingApplicants($ete);
        $ete->load('applicantRatings.application');
        $alphabeticalRatings = $ete->applicantRatings
            ->filter(fn ($rating) => $rating->application)
            ->sortBy(function ($rating) {
                $application = $rating->application;

                return strtolower(trim(
                    ($application->last_name ?? '').' '.
                    ($application->first_name ?? '').' '.
                    ($application->middle_name ?? '')
                ));
            })
            ->values();
        $applicants = $alphabeticalRatings->pluck('application')->filter();
        $ratingsByApplication = $ete->applicantRatings->keyBy('application_id');

        return view('ete.show', compact('ete', 'applicants', 'ratingsByApplication'));
    }

    public function adminRating($id)
    {
        $this->authorizeEteAdmin();
        $ete = EteEvaluation::with(['job', 'office'])->findOrFail($id);
        $this->syncReviewingApplicants($ete);
        $candidateRatings = $ete->applicantRatings()->with('application')->orderBy('application_id')->get();
        abort_if($candidateRatings->isEmpty(), 404, 'No candidates are available for this ETE evaluation.');

        $selectedApplicationId = request()->integer('application_id');
        $selectedIndex = $selectedApplicationId
            ? $candidateRatings->search(fn ($item) => (int) $item->application_id === $selectedApplicationId)
            : 0;
        abort_if($selectedIndex === false, 404, 'Candidate does not belong to this ETE evaluation.');

        $selectedRating = $candidateRatings->get($selectedIndex);
        $ratings = collect([$selectedRating]);
        $previousRating = $selectedIndex > 0 ? $candidateRatings->get($selectedIndex - 1) : null;
        $nextRating = $selectedIndex < $candidateRatings->count() - 1 ? $candidateRatings->get($selectedIndex + 1) : null;
        $candidateRatings->each(function ($candidateRating) {
            $candidateRating->is_completed = $this->ratingCompleted($candidateRating);
        });
        $years = $this->experienceYears($ete->experience_years);
        $copyableRatings = $this->previousApplicantRatings($selectedRating);

        return view('ete.evaluator-rate', compact(
            'ete', 'ratings', 'candidateRatings', 'selectedRating', 'previousRating', 'nextRating', 'years', 'copyableRatings'
        ));
    }

    public function eteEvaluationDelete($id)
    {
        $this->authorizeEteAdmin();
        DB::transaction(function () use ($id) {
            $ete = EteEvaluation::findOrFail($id);
            $ete->evaluators()->delete();
            $ete->employeeEvaluates()->delete();
            $ete->applicantRatings()->delete();
            $ete->delete();
        });

        return redirect()->route('eteEvaluationList')->with('success', 'ETE evaluation deleted successfully.');
    }

    public function eteRatingUpdateAjax(Request $request)
    {
        $this->authorizeEteAdmin();
        $request->validate([
            'evaluate_id' => 'required|exists:ete_applicant_ratings,id',
            'evaluation_date' => 'nullable|date',
            'present_position' => 'nullable|string|max:255',
            'college_department' => 'nullable|string|max:255',
            'education_met' => 'nullable|boolean',
            'experience_met' => 'nullable|boolean',
            'eligibility_met' => 'nullable|boolean',
            'training_met' => 'nullable|boolean',
            'education_ratings' => 'nullable|array',
            'training_ratings' => 'nullable|array',
            'training_ratings.relevant_hours' => 'nullable|numeric|min:0|max:10000',
            'experience_years' => 'nullable|array',
            'experience_years.*.length' => 'nullable|numeric|min:0|max:12',
            'remarks' => 'nullable|string',
        ]);

        $rating = EteApplicantRating::with('eteEvaluation')->findOrFail($request->evaluate_id);
        $educationCredits = [
            'additional_four_year_course' => 2, 'masteral_1_18' => 1,
            'masteral_19_30' => 2, 'masters_degree' => 4,
            'doctoral_1_18' => 5, 'doctoral_19_36' => 6, 'doctoral_degree' => 10,
        ];
        $educationRatings = [];
        $educationScore = 0;
        foreach ($educationCredits as $key => $credit) {
            $educationRatings[$key] = $request->boolean("education_ratings.$key");
            $educationScore += $educationRatings[$key] ? $credit : 0;
        }
        $educationScore = min(10, $educationScore);

        $trainingRatings = [
            'scholarship_grant' => $request->boolean('training_ratings.scholarship_grant'),
            'leadership_seminar' => $request->boolean('training_ratings.leadership_seminar'),
            'relevant_hours' => max(0, (float) $request->input('training_ratings.relevant_hours', 0)),
        ];
        $trainingScore = min(5,
            ($trainingRatings['scholarship_grant'] ? 3 : 0) +
            ($trainingRatings['leadership_seminar'] ? 2 : 0) +
            floor($trainingRatings['relevant_hours'] / 50)
        );

        $submittedExperienceRows = $request->input('experience_years', []);
        $orderedYears = $this->experienceYears(optional($rating->eteEvaluation)->experience_years);
        $levelOneYears = array_slice(array_reverse($orderedYears), 0, 5);
        $experienceRows = [];
        $experienceScore = 0;
        foreach ($orderedYears as $year) {
            $row = $submittedExperienceRows[$year] ?? [];
            $months = max(0, min(12, (float) ($row['length'] ?? 0)));
            $experienceLevel = in_array($year, $levelOneYears) ? 1 : 2;
            $credit = round(($months / 12) * $experienceLevel, 2);
            $experienceRows[$year] = ['length' => $months, 'level' => $experienceLevel, 'credit' => $credit];
            $experienceScore += $credit;
        }
        $experienceScore = min(15, round($experienceScore, 2));

        $requirements = collect(['education_met', 'experience_met', 'eligibility_met', 'training_met'])
            ->mapWithKeys(fn ($field) => [$field => $request->filled($field) ? $request->boolean($field) : null]);
        $minimumScore = $requirements->filter(fn ($value) => $value === true)->count() * 17.5;
        $total = $minimumScore + $educationScore + $trainingScore + $experienceScore;

        $rating->update(array_merge($requirements->all(), [
            'evaluation_date' => $request->evaluation_date,
            'present_position' => $request->present_position,
            'college_department' => $request->college_department,
            'minimum_requirement_score' => $minimumScore,
            'education_score' => $educationScore,
            'education_ratings' => $educationRatings,
            'training_score' => $trainingScore,
            'training_ratings' => $trainingRatings,
            'experience_score' => $experienceScore,
            'experience_year_ratings' => $experienceRows,
            'total_score' => $total,
            'remarks' => $request->remarks,
            'updated_by' => auth()->guard('web')->id(),
        ]));

        return response()->json([
            'success' => true,
            'minimum_requirement_score' => number_format($minimumScore, 2),
            'education_score' => number_format($educationScore, 2),
            'training_score' => number_format($trainingScore, 2),
            'experience_score' => number_format($experienceScore, 2),
            'experience_rows' => $experienceRows,
            'total_score' => number_format($total, 2),
        ]);
    }

    public function copyPreviousRating(Request $request, $id)
    {
        $this->authorizeEteAdmin();
        $request->validate([
            'target_rating_id' => 'required|exists:ete_applicant_ratings,id',
            'source_rating_id' => 'required|exists:ete_applicant_ratings,id',
        ]);

        $ete = EteEvaluation::findOrFail($id);
        $targetRating = EteApplicantRating::with('application')
            ->where('ete_id', $ete->id)
            ->findOrFail($request->target_rating_id);
        $sourceRating = EteApplicantRating::with('application')->findOrFail($request->source_rating_id);

        $targetEmail = strtolower(trim((string) optional($targetRating->application)->email));
        $sourceEmail = strtolower(trim((string) optional($sourceRating->application)->email));

        abort_if($targetEmail === '' || $targetEmail !== $sourceEmail, 422, 'Selected previous rating does not belong to the same applicant email.');
        abort_if((int) $targetRating->jid === (int) $sourceRating->jid, 422, 'Please select a rating from another position.');
        abort_if(!$this->ratingCompleted($sourceRating), 422, 'Selected previous rating has no saved score to copy.');

        $targetRating->update([
            'education_met' => $sourceRating->education_met,
            'experience_met' => $sourceRating->experience_met,
            'eligibility_met' => $sourceRating->eligibility_met,
            'training_met' => $sourceRating->training_met,
            'minimum_requirement_score' => $sourceRating->minimum_requirement_score,
            'education_score' => $sourceRating->education_score,
            'education_ratings' => $sourceRating->education_ratings,
            'training_score' => $sourceRating->training_score,
            'training_ratings' => $sourceRating->training_ratings,
            'experience_score' => $sourceRating->experience_score,
            'experience_year_ratings' => $sourceRating->experience_year_ratings,
            'total_score' => $sourceRating->total_score,
            'remarks' => $sourceRating->remarks,
            'updated_by' => auth()->guard('web')->id(),
        ]);

        return redirect()
            ->route('eteAdminRating', ['id' => $ete->id, 'application_id' => $targetRating->application_id])
            ->with('success', 'Previous ETE rating copied to this applicant.');
    }

    public function applicantEvaluationPdf($id, $applicationId)
    {
        $this->authorizeEteAdmin();
        $ete = EteEvaluation::with(['job', 'evaluators.employee'])->findOrFail($id);
        $application = Application::where('jid', $ete->jid)->findOrFail($applicationId);
        $rating = $this->syncApplicationRating($ete, $application);

        $reportEvaluators = $ete->evaluators->map(function ($panel) {
            $panel->signature_data = null;
            if ($panel->employee && $panel->employee->esign) {
                try {
                    $panel->signature_data = 'data:image/png;base64,' . base64_encode(Crypt::decrypt($panel->employee->esign));
                } catch (\Throwable $exception) {
                    $panel->signature_data = null;
                }
            }
            return $panel;
        });

        $years = $this->experienceYears($ete->experience_years);
        $fileName = 'ETE-' . ($application->app_number ?: $application->id) . '.pdf';
        return \PDF::loadView('ete.applicant-evaluation-pdf', compact(
            'ete', 'application', 'rating', 'reportEvaluators', 'years'
        ))->setPaper('legal', 'portrait')->stream($fileName);
    }

    public function selectedApplicantConsolidated($id)
    {
        $this->authorizeEteAdmin();
        $ete = EteEvaluation::with('evaluators.employee')->findOrFail($id);
        $rating = $ete->applicantRatings()->with('application')
            ->where('application_id', request()->integer('application_id'))->first();
        if (!$rating || !$rating->application) {
            return response()->json(['success' => false, 'message' => 'Applicant not found.']);
        }

        return response()->json([
            'success' => true,
            'application_id' => $rating->application_id,
            'app_number' => $rating->application->app_number,
            'name' => $this->applicantName($rating->application),
            'education_score' => number_format($rating->education_score, 2),
            'training_score' => number_format($rating->training_score, 2),
            'experience_score' => number_format($rating->experience_score, 2),
            'total_score' => number_format($rating->total_score, 2),
            'completed' => $this->ratingCompleted($rating),
            'report_page_count' => $ete->evaluators->count(),
        ]);
    }

    public function consolidatedScreen($id)
    {
        $this->authorizeRankingAdmin();
        $ete = EteEvaluation::with('job')->findOrFail($id);
        $this->syncReviewingApplicants($ete);
        return view('ete.consolidated-screen', compact('ete'));
    }

    public function consolidatedData($id)
    {
        $this->authorizeRankingAdmin();
        $ete = EteEvaluation::with('applicantRatings.application')->findOrFail($id);
        $data = $ete->applicantRatings->filter(fn ($rating) => $rating->application)
            ->sortByDesc('total_score')->values()->map(function ($rating, $index) {
                $requirementsMet = collect([
                    $rating->education_met,
                    $rating->experience_met,
                    $rating->eligibility_met,
                    $rating->training_met,
                ])->filter(fn ($value) => $value === true)->count();

                return [
                    'application_id' => $rating->application_id,
                    'app_number' => $rating->application->app_number,
                    'name' => $this->applicantName($rating->application),
                    'education_score' => number_format($rating->education_score, 2),
                    'training_score' => number_format($rating->training_score, 2),
                    'experience_score' => number_format($rating->experience_score, 2),
                    'minimum_score' => number_format($rating->minimum_requirement_score, 2),
                    'requirements_met' => $requirementsMet,
                    'total_score' => number_format($rating->total_score, 2),
                    'total_raw' => (float) $rating->total_score,
                    'completed' => $this->ratingCompleted($rating),
                    'rank' => $index + 1,
                ];
            });

        return response()->json(['success' => true, 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
