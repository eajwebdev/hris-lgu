<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ComparativeAssessment;
use App\Models\ComparativeAssessmentRow;
use App\Models\Employee;
use App\Models\InterviewEvaluation;
use App\Models\InterviewRating;
use App\Models\JobHiring;
use App\Models\PsbMember;
use App\Services\PsbScoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Personnel Selection Board — Comparative Assessment Form.
 *
 * This is the consolidation sheet for a vacancy, and the document the board
 * signs. Two of the six preliminary components are already measured by the
 * panel interview, so those are filled in automatically:
 *
 *   Potential               <- the panel's average interview score
 *   Psychosocial attributes <- the interview's potential rubric
 *
 * The rest are scored on the sheet itself, which is how the printed form
 * works: HR keys the performance rating (from the IPCR, for internal
 * candidates) and the education, training and experience points.
 *
 * The two automatic columns are rescaled onto their own weight by PsbScoring,
 * so the six columns always total 100 no matter what scale the feeder used.
 */
class ComparativeAssessmentController extends Controller
{
    public function __construct(private PsbScoring $psb)
    {
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->guard('web')->check(), 403);
    }

    public function show($jid)
    {
        $this->authorizeAdmin();

        $job = JobHiring::with('positionDescription')->findOrFail($jid);
        $assessment = ComparativeAssessment::with(['rows', 'boardMembers'])->where('jid', $jid)->first();

        return view('psb.assessment', [
            'guard'      => 'web',
            'job'        => $job,
            'assessment' => $assessment,
            'weights'    => PsbScoring::ASSESSMENT_WEIGHTS,
            'labels'     => PsbScoring::ASSESSMENT_LABELS,
            // This sheet's own board once it has one; otherwise the standing
            // board, which is what it will be seeded from.
            'board'      => $assessment?->boardMembers->isNotEmpty()
                ? $assessment->boardMembers
                : PsbMember::board(),
            'employees'  => Employee::select('id', 'fname', 'lname')
                ->orderBy('lname')->orderBy('fname')->get(),
        ]);
    }

    /**
     * Create or refresh the sheet from the applicants and their evaluations.
     *
     * Figures a user has already adjusted by hand are preserved: rebuilding
     * after a late interview must not silently discard a keyed performance
     * rating. Only the columns that have a feeder result are overwritten.
     */
    public function build(Request $request, $jid)
    {
        $this->authorizeAdmin();

        $job = JobHiring::findOrFail($jid);

        $assessment = ComparativeAssessment::firstOrNew(['jid' => $job->id]);
        if (! $assessment->exists) {
            $assessment->created_by = auth()->guard('web')->id();
        }
        $assessment->fill([
            'position_to_be_filled' => $job->title,
            'item_no'               => $job->plantilla_item_no,
            'location'              => $job->assignment,
            'date_posted'           => $job->posted_at,
            'rate_per_month'        => $job->salary,
        ]);
        $assessment->save();

        $interview = InterviewEvaluation::where('jid', $job->id)->latest('id')->first();

        $assessment->interview_id = $interview?->id;
        $assessment->save();

        // Take a copy of the standing board the first time only. A rebuild
        // after a late interview must not undo names edited on this sheet.
        $assessment->seedBoardFrom(PsbMember::board());

        // Candidates who were not disqualified.
        $applications = Application::where('jid', $job->id)
            ->whereNotIn('status', [4])   // 4 = disqualified
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        DB::transaction(function () use ($applications, $assessment, $interview) {
            foreach ($applications as $i => $app) {
                $row = ComparativeAssessmentRow::firstOrNew([
                    'comparative_assessment_id' => $assessment->id,
                    'application_id'            => $app->id,
                ]);

                $row->candidate_name = trim(collect([
                    $app->last_name, $app->first_name, $app->middle_name,
                ])->filter()->implode(', '));
                $row->civil_service_eligibility = $row->civil_service_eligibility ?: $app->eligibility;
                $row->sort_order = $i;

                $this->applyInterviewScores($row, $interview, $app);

                $row->recalculate()->save();
            }

            $this->reRank($assessment);
        });

        return redirect()->route('psbAssessment', $assessment->jid)
            ->with('success', 'Comparative assessment built from '.$applications->count().' candidate(s).');
    }


    /**
     * Potential and psychosocial attributes, from the panel interview.
     *
     * The panel is averaged, never summed — see PsbScoring::panelAverage.
     */
    private function applyInterviewScores(ComparativeAssessmentRow $row, ?InterviewEvaluation $interview, Application $app): void
    {
        if (! $interview) {
            return;
        }

        $ratings = InterviewRating::where('interview_id', $interview->id)
            ->where('application_id', $app->id)
            ->whereNotNull('submitted_at')
            ->get();

        if ($ratings->isEmpty()) {
            return;
        }

        $interviewAverage = $this->psb->panelAverage($ratings->pluck('interview_total')->all());
        $potentialAverage = $this->psb->panelAverage($ratings->pluck('potential_total')->all());

        // Interview total is already on a 100-point scale (PSB weights).
        $row->potential_points = $this->psb->rescale($interviewAverage, 100, 10);

        // The potential rubric is 20 statements rated 1-5, so 100 raw maximum.
        $row->psychosocial_points = $this->psb->rescale($potentialAverage, 100, 10);
    }

    /** Persist hand edits, then recompute totals and ranks. */
    public function save(Request $request, $id)
    {
        $this->authorizeAdmin();

        $assessment = ComparativeAssessment::with('rows')->findOrFail($id);

        abort_if($assessment->isFinalised(), 403, 'This assessment has been finalised and can no longer be edited.');

        $data = $request->validate([
            'position_to_be_filled' => 'nullable|string|max:255',
            'item_no'               => 'nullable|string|max:100',
            'location'              => 'nullable|string|max:255',
            'date_posted'           => 'nullable|date',
            'date_published'        => 'nullable|date',
            'rate_per_month'        => 'nullable|string|max:100',
            'further_assessment_label' => 'nullable|string|max:255',

            'rows'                        => 'nullable|array',
            'rows.*.present_position'     => 'nullable|string|max:255',
            'rows.*.salary_grade'         => 'nullable|string|max:50',
            'rows.*.appointment_status'   => 'nullable|string|max:100',
            'rows.*.civil_service_eligibility' => 'nullable|string|max:255',
            'rows.*.performance_rating'   => 'nullable|numeric|min:0|max:35',
            'rows.*.education_points'     => 'nullable|numeric|min:0|max:15',
            'rows.*.training_points'      => 'nullable|numeric|min:0|max:10',
            'rows.*.experience_points'    => 'nullable|numeric|min:0|max:20',
            'rows.*.potential_points'     => 'nullable|numeric|min:0|max:10',
            'rows.*.psychosocial_points'  => 'nullable|numeric|min:0|max:10',
            'rows.*.further_assessment'   => 'nullable|numeric|min:0',
            'rows.*.remarks'              => 'nullable|string',

            // The board on this sheet. Free text: it records who signed this
            // particular assessment, which need not be the standing board.
            'board'               => 'nullable|array',
            'board.*.id'          => 'nullable|integer',
            'board.*.name'        => 'nullable|string|max:255',
            'board.*.credentials' => 'nullable|string|max:100',
            'board.*.role'        => 'nullable|in:Chairperson,Vice-Chairperson,Member',
        ]);

        DB::transaction(function () use ($data, $request, $assessment) {
            $assessment->fill(collect($data)->except('rows')->all());
            $assessment->save();

            $label = $data['further_assessment_label'] ?? null;

            foreach ($request->input('rows', []) as $rowId => $values) {
                $row = $assessment->rows->firstWhere('id', (int) $rowId);
                if (! $row) {
                    continue;
                }

                $row->fill($values);
                $row->further_assessment_label = $label;
                $row->recalculate()->save();
            }

            $this->saveBoard($assessment, $request->input('board', []));

            $this->reRank($assessment);
        });

        return redirect()->route('psbAssessment', $assessment->jid)
            ->with('success', 'Comparative assessment saved.');
    }

    /**
     * Replace this sheet's signatory block with what was submitted.
     *
     * Written straight to the sheet's own board rows — never to psb_members and
     * never to the employee record — so a substitute signatory on one hiring
     * leaves the standing board and everyone's personnel file untouched.
     */
    private function saveBoard(ComparativeAssessment $assessment, array $submitted): void
    {
        $keep = [];

        foreach (array_values($submitted) as $i => $entry) {
            if (trim((string) ($entry['name'] ?? '')) === '') {
                continue;
            }

            $member = $assessment->boardMembers()->updateOrCreate(
                ['id' => $entry['id'] ?? null],
                [
                    'name'        => $entry['name'],
                    'credentials' => $entry['credentials'] ?? null,
                    'role'        => $entry['role'] ?? 'Member',
                    'sort_order'  => $i,
                ]
            );

            $keep[] = $member->id;
        }

        $assessment->boardMembers()->whereNotIn('id', $keep ?: [0])->delete();
    }

    /**
     * Rank by overall points, highest first, ties sharing a rank.
     * Always derived — never keyed — so the ranking cannot contradict the
     * points printed beside it.
     */
    private function reRank(ComparativeAssessment $assessment): void
    {
        $rows = $assessment->rows()->get();

        $ranks = $this->psb->rank($rows->pluck('overall_points', 'id')->map(fn ($v) => (float) $v)->all());

        foreach ($rows as $row) {
            $row->rank = $ranks[$row->id] ?? null;
            $row->saveQuietly();
        }
    }

    public function finalise($id)
    {
        $this->authorizeAdmin();

        $assessment = ComparativeAssessment::findOrFail($id);
        $assessment->finalised_at = now();
        $assessment->save();

        return redirect()->route('psbAssessment', $assessment->jid)
            ->with('success', 'Assessment finalised. It is now read-only and ready to print.');
    }

    public function print($id)
    {
        $this->authorizeAdmin();

        $assessment = ComparativeAssessment::with(['rows', 'job', 'boardMembers'])->findOrFail($id);

        return view('psb.assessment-print', [
            'assessment' => $assessment,
            // The names that signed this sheet. Falls back to the standing
            // board only for sheets built before per-hiring boards existed.
            'board'      => $assessment->boardMembers->isNotEmpty()
                ? $assessment->boardMembers
                : PsbMember::board(),
            'weights'    => PsbScoring::ASSESSMENT_WEIGHTS,
            'labels'     => PsbScoring::ASSESSMENT_LABELS,
        ]);
    }

    // ------------------------------------------------------- board membership

    public function members()
    {
        $this->authorizeAdmin();

        return view('psb.members', [
            'guard'     => 'web',
            'members'   => PsbMember::orderBy('sort_order')->get(),
            'employees' => Employee::select('id', 'emp_ID', 'fname', 'lname')
                ->orderBy('lname')->orderBy('fname')->get(),
        ]);
    }

    public function membersSave(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'members'               => 'nullable|array',
            'members.*.id'          => 'nullable|integer',
            'members.*.name'        => 'nullable|string|max:255',
            'members.*.credentials' => 'nullable|string|max:100',
            'members.*.role'        => 'nullable|in:Chairperson,Vice-Chairperson,Member',
            'members.*.employee_id' => 'nullable|integer|exists:employees,id',
            'members.*.active'      => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data) {
            $keep = [];

            foreach (array_values($data['members'] ?? []) as $i => $row) {
                if (trim((string) ($row['name'] ?? '')) === '') {
                    continue;
                }

                $member = PsbMember::updateOrCreate(
                    ['id' => $row['id'] ?? null],
                    [
                        'name'        => $row['name'],
                        'credentials' => $row['credentials'] ?? null,
                        'role'        => $row['role'] ?? 'Member',
                        'employee_id' => $row['employee_id'] ?? null,
                        'active'      => (bool) ($row['active'] ?? false),
                        'sort_order'  => $i,
                    ]
                );

                $keep[] = $member->id;
            }

            PsbMember::whereNotIn('id', $keep ?: [0])->delete();
        });

        return redirect()->route('psbMembers')->with('success', 'Personnel Selection Board updated.');
    }
}
