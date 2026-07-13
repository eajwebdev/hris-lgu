<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Employee;
use App\Models\EteApplicantRating;
use App\Models\EteEvaluation;
use App\Models\InterviewApplicant;
use App\Models\InterviewEvaluation;
use App\Models\InterviewPanel;
use App\Models\InterviewRating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterviewEvaluationController extends Controller
{
    private array $interviewCriteria = [
        'voice_speech' => [
            'label' => 'Voice and speech',
            'prompt' => 'Is his/her voice inviting or pleasant? Can you easily hear what he/she says? Is his/her speech clear and distinct? Is his/her voice resonant and well-modulated?',
            'levels' => [
                '1 - 2' => 'Irritating or indistinct',
                '3 - 4' => 'Understandable but rather unpleasant',
                '5 - 6' => 'Neither conspicuously pleasant or unpleasant',
                '7 - 8' => 'Definitely pleasant and distinct',
                '9 - 10' => 'Exceptionally clear and pleasing',
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'prompt' => 'Does he/she look like a well set-up, healthy, energetic person? Is he/she well-groomed or is he/she unattractive in appearance?',
            'levels' => [
                '1 - 2' => 'Unpleasant or unsuitable',
                '3 - 4' => 'Creates a rather unfavorable impression',
                '5 - 6' => 'Suitable, acceptable',
                '7 - 8' => 'Creates a distinctly favorable impression',
                '9 - 10' => 'Impressive, commands admiration',
            ],
        ],
        'alertness' => [
            'label' => 'Alertness',
            'prompt' => 'Does he/she readily grasp the meaning of a question? Is he/she slow to comprehend?',
            'levels' => [
                '1 - 2' => 'Slow in grasping obvious questions; often misunderstands meaning of questions',
                '3 - 4' => 'Slow to understand subtle points; requires explanation',
                '5 - 6' => 'Nearly grasps ideas',
                '7 - 8' => 'Rather quick in grasping questions and new ideas',
                '9 - 10' => 'Exceptionally keen and quick to understand',
            ],
        ],
        'present_ideas' => [
            'label' => 'Ability to present ideas',
            'prompt' => 'Does he/she speak logically and convincingly or does he/she tend to be vague, confused or illogical?',
            'levels' => [
                '1 - 2' => 'Confused and illogical',
                '3 - 4' => 'Tends to present ideas in a haphazard manner',
                '5 - 6' => 'Usually gets his/her ideas across well',
                '7 - 8' => 'Shows superior ability to express him/herself',
                '9 - 10' => 'Unusually logical, clear and convincing',
            ],
        ],
        'judgment' => [
            'label' => 'Judgment',
            'prompt' => 'Does he/she impress you as a person whose judgment would be dependable even under stress? Or is he/she hasty, erratic, biased, swayed by his/her feelings?',
            'levels' => [
                '1 - 2' => 'Confused and illogical',
                '3 - 4' => 'Tends to present ideas in a haphazard manner',
                '5 - 6' => 'Usually gets his/her ideas across well',
                '7 - 8' => 'Shows superior ability to express him/herself',
                '9 - 10' => 'Unusually logical, clear and convincing',
            ],
        ],
        'emotional_stability' => [
            'label' => 'Emotional stability',
            'prompt' => 'Is he/she emotionally mature? Is he/she touchy, sensitive to criticism, easily upset? Is he/she irritated or impatient when things go wrong? Or does he/she keep an even keel?',
            'levels' => [
                '1 - 2' => 'Over sensitive; easily disconcerted',
                '3 - 4' => 'Occasionally impatient or irritated',
                '5 - 6' => 'Well-poised most of the time',
                '7 - 8' => 'Superior self-command',
                '9 - 10' => 'Exceptional poise, calmness, and good humor under stress',
            ],
        ],
        'self_confidence' => [
            'label' => 'Self-confidence',
            'prompt' => 'Does he/she seem to be uncertain of him/herself, hesitant, lacking in assurance, easily bluffed? Or is he/she wholesomely self-confident and assured?',
            'levels' => [
                '1 - 2' => 'Timid, hesitant, easily influenced',
                '3 - 4' => 'Appears to be over self-conscious',
                '5 - 6' => 'Moderately confident of him/herself',
                '7 - 8' => 'Wholesomely self-confident',
                '9 - 10' => 'Shows superior self-assurance',
            ],
        ],
    ];

    private array $potentialCriteria = [
        'Human Relations' => [
            'adjust_personalities' => 'Ability to adjust to personalities, rank, and informal groups',
            'internalize_changes' => 'Internalizes work changes with ease and vigor',
            'respond_superiors' => 'Responds to requests, demands, and expectations',
            'appraise_work_problems' => 'Appraises work problems, causes, and corrective steps',
            'maintain_point_of_view' => 'Maintains individual point of view despite behavior differences',
            'peer_respect' => 'Has respect and acceptance of peers',
            'resolve_peer_conflict' => 'Helps peers resolve conflict',
            'public_cordiality' => 'Cordial and respectful with clientele/public',
            'client_assistance' => 'Shows enthusiasm in advising and assisting clients',
        ],
        'Leadership' => [
            'encourage_participation' => 'Encourages participation in problem-solving and decision-making',
            'influence_others' => 'Influences thinking, attitude, and behavior of peers',
            'external_group_leadership' => 'Leads ad hoc external groups to complete tasks/projects',
            'working_group_responsibility' => 'Assumes responsibility as leader/chair of a working group',
        ],
        'Personal Qualifications and Attributes' => [
            'critical_standards' => 'Intellectually critical of existing standards, systems, and policies',
            'initiative_programs' => 'Takes initiative to develop beneficial programs, systems, and procedures',
            'stress_tolerance' => 'Has high tolerance for tension, change, and conflict',
            'control_emotions' => 'Controls anger and negative emotions',
            'accept_criticism' => 'Accepts criticism objectively',
            'recommend_solutions' => 'Recommends solutions when help is sought',
            'quick_decisions' => 'Acts quickly and makes the best possible immediate decision',
        ],
    ];

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->guard('web')->check(), 403);
    }

    private function authorizeRankingAdmin(): void
    {
        $user = auth()->guard('web')->user();
        if (!$user || !in_array($user->role, ['Administrator', 'HR Administrator'], true)) {
            redirect()->route('dashboard')->with('error1', 'Only administrators can view rankings.')->send();
            exit;
        }
    }

    private function guard()
    {
        return auth()->guard('web')->check() ? 'web' : (auth()->guard('employee')->check() ? 'employee' : null);
    }

    private function employeeHasPhd(Employee $employee): bool
    {
        $haystack = strtolower($employee->prefix . ' ' . $employee->title_prefix);

        return str_contains($haystack, 'phd') || str_contains($haystack, 'ph.d');
    }

    private function currentPanelEmployeeId(): ?int
    {
        if (auth()->guard('employee')->check()) {
            return (int) auth()->guard('employee')->user()->id;
        }

        if (!auth()->guard('web')->check()) {
            return null;
        }

        $user = auth()->guard('web')->user();

        if (!empty($user->emp_ID)) {
            $employeeId = Employee::where('emp_ID', $user->emp_ID)->value('id');
            if ($employeeId) {
                return (int) $employeeId;
            }
        }

        if (!empty($user->username)) {
            $employeeId = Employee::where('username', $user->username)
                ->orWhere('org_email', $user->username)
                ->value('id');

            if ($employeeId) {
                return (int) $employeeId;
            }
        }

        if (!empty($user->fname) && !empty($user->lname)) {
            $employeeId = Employee::whereRaw('LOWER(fname) = ?', [strtolower($user->fname)])
                ->whereRaw('LOWER(lname) = ?', [strtolower($user->lname)])
                ->value('id');

            if ($employeeId) {
                return (int) $employeeId;
            }
        }

        return null;
    }

    private function employeeName($employee): string
    {
        return trim(collect([
            $employee->prefix ?? null,
            $employee->fname ?? null,
            $employee->mname ?? null,
            $employee->lname ?? null,
            $employee->suffix ?? null,
        ])->filter()->implode(' '));
    }

    private function applicantName($application): string
    {
        return trim(collect([
            $application->first_name ?? null,
            $application->middle_name ?? null,
            $application->last_name ?? null,
        ])->filter()->implode(' '));
    }

    private function eligibleApplicants(InterviewEvaluation $interview)
    {
        return Application::where('jid', $interview->jid)
            ->where('status', 2)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function syncApplicantRows(InterviewEvaluation $interview): void
    {
        $this->eligibleApplicants($interview)->each(function ($application) use ($interview) {
            InterviewApplicant::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $application->id,
            ]);
        });
    }

    private function createRatingRows(InterviewEvaluation $interview, int $applicationId): void
    {
        $interview->loadMissing('panels');

        $interview->panels->each(function ($panel) use ($interview, $applicationId) {
            InterviewRating::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $applicationId,
                'panel_employee_id' => $panel->emp_id,
            ]);
        });
    }

    private function ensureDefaultApplicantPanels(InterviewEvaluation $interview, int $applicationId): void
    {
        if (InterviewRating::where('interview_id', $interview->id)
            ->where('application_id', $applicationId)
            ->exists()) {
            return;
        }

        $this->createRatingRows($interview, $applicationId);
    }

    private function assignedPanelIdsForApplication(InterviewEvaluation $interview, int $applicationId)
    {
        $assignedPanelIds = InterviewRating::where('interview_id', $interview->id)
            ->where('application_id', $applicationId)
            ->pluck('panel_employee_id')
            ->filter()
            ->unique()
            ->values();

        if ($assignedPanelIds->isNotEmpty()) {
            return $assignedPanelIds;
        }

        return $interview->panels()
            ->pluck('emp_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function assignedPanelEmployeesForApplication(InterviewEvaluation $interview, int $applicationId)
    {
        $panelIds = $this->assignedPanelIdsForApplication($interview, $applicationId);

        if ($panelIds->isEmpty()) {
            return collect();
        }

        return Employee::whereIn('id', $panelIds)
            ->orderBy('lname')
            ->orderBy('fname')
            ->get()
            ->sortBy(fn ($employee) => $panelIds->search($employee->id))
            ->values();
    }

    private function isCurrentCastForPanel(InterviewEvaluation $interview, int $applicationId, int $employeeId): bool
    {
        return (int) $interview->active_application_id === (int) $applicationId
            && $this->assignedPanelIdsForApplication($interview, $applicationId)->contains((int) $employeeId)
            && $interview->applicants()
                ->where('application_id', $applicationId)
                ->where('is_cast', true)
                ->exists();
    }

    private function sourceCastMatchesApplication(Application $application, int $employeeId, ?int $sourceInterviewId, ?int $sourceApplicationId): bool
    {
        if (!$sourceInterviewId || !$sourceApplicationId) {
            return false;
        }

        $sourceInterview = InterviewEvaluation::find($sourceInterviewId);
        $sourceApplication = $sourceInterview
            ? Application::where('jid', $sourceInterview->jid)->where('status', 2)->find($sourceApplicationId)
            : null;

        if (!$sourceInterview || !$sourceApplication) {
            return false;
        }

        $sourceEmail = strtolower(trim((string) $sourceApplication->email));
        $targetEmail = strtolower(trim((string) $application->email));

        return $sourceEmail !== ''
            && $sourceEmail === $targetEmail
            && $this->isCurrentCastForPanel($sourceInterview, (int) $sourceApplication->id, $employeeId);
    }

    private function canRateApplicationForPanel(
        InterviewEvaluation $interview,
        Application $application,
        int $employeeId,
        ?int $sourceInterviewId = null,
        ?int $sourceApplicationId = null
    ): bool
    {
        if ((int) $application->jid !== (int) $interview->jid || (int) $application->status !== 2) {
            return false;
        }

        if (!$this->assignedPanelIdsForApplication($interview, (int) $application->id)->contains((int) $employeeId)) {
            return false;
        }

        return $this->isCurrentCastForPanel($interview, (int) $application->id, $employeeId)
            || $this->sourceCastMatchesApplication($application, $employeeId, $sourceInterviewId, $sourceApplicationId);
    }

    private function activeInterviewsForPanel(int $employeeId)
    {
        return InterviewEvaluation::with(['job', 'eteEvaluation.office', 'activeApplication'])
            ->whereNotNull('active_application_id')
            ->whereHas('applicants', function ($query) {
                $query->where('is_cast', true)
                    ->whereColumn('interview_applicants.application_id', 'interview_evaluations.active_application_id');
            })
            ->whereHas('ratings', function ($query) use ($employeeId) {
                $query->where('panel_employee_id', $employeeId)
                    ->whereColumn('interview_ratings.application_id', 'interview_evaluations.active_application_id');
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    private function activeAssignmentKeyForPanel(int $employeeId): string
    {
        return $this->activeInterviewsForPanel($employeeId)
            ->map(fn ($interview) => $interview->id . ':' . $interview->active_application_id . ':' . optional($interview->updated_at)->timestamp)
            ->implode('|');
    }

    private function relatedQualifiedPositions(Application $application, int $employeeId)
    {
        $email = strtolower(trim((string) $application->email));

        if ($email === '') {
            return collect();
        }

        $applications = Application::whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 2)
            ->orderBy('jid')
            ->get()
            ->values();

        if ($applications->isEmpty()) {
            return collect();
        }

        $interviews = InterviewEvaluation::with(['job', 'ratings' => function ($query) use ($employeeId) {
                $query->where('panel_employee_id', $employeeId);
            }])
            ->whereIn('jid', $applications->pluck('jid')->unique())
            ->latest()
            ->get()
            ->unique('jid')
            ->keyBy('jid');

        return $applications->map(function ($relatedApplication) use ($interviews, $employeeId) {
            $relatedInterview = $interviews->get($relatedApplication->jid);

            if (!$relatedInterview) {
                return null;
            }

            if (!$this->assignedPanelIdsForApplication($relatedInterview, (int) $relatedApplication->id)->contains((int) $employeeId)) {
                return null;
            }

            $rating = $relatedInterview->ratings
                ->firstWhere('application_id', $relatedApplication->id);

            return [
                'interview' => $relatedInterview,
                'application' => $relatedApplication,
                'rating' => $rating,
            ];
        })->filter()
            ->unique(function ($item) {
                $job = $item['interview']->job;

                return strtolower(trim(collect([
                    $job->plantilla_item_no ?? null,
                    $job->title ?? $item['application']->position ?? null,
                ])->filter()->implode('|')));
            })
            ->values();
    }

    private function ratingStarted(?InterviewRating $rating): bool
    {
        return $rating && (
            (float) $rating->total_score > 0
            || !empty($rating->interview_scores)
            || !empty($rating->potential_scores)
            || !empty($rating->remarks)
        );
    }

    private function copyablePanelRatings(Application $application, InterviewRating $currentRating, int $employeeId)
    {
        $email = strtolower(trim((string) $application->email));

        if ($email === '') {
            return collect();
        }

        return InterviewRating::with(['interview.job', 'application'])
            ->where('id', '!=', $currentRating->id)
            ->where('panel_employee_id', $employeeId)
            ->whereHas('application', function ($query) use ($email) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->latest('updated_at')
            ->get()
            ->filter(fn ($rating) => $this->ratingStarted($rating)
                && (int) optional($rating->application)->jid !== (int) $application->jid)
            ->values();
    }

    private function sumScores(array $scores, array $allowedKeys, int $min, int $max): float
    {
        return collect($allowedKeys)->sum(function ($key) use ($scores, $min, $max) {
            $score = (int) ($scores[$key] ?? 0);
            return max($min, min($max, $score));
        });
    }

    private function sumProvidedScores(array $scores, array $allowedKeys, int $min, int $max): float
    {
        return collect($allowedKeys)->sum(function ($key) use ($scores, $min, $max) {
            if (!array_key_exists($key, $scores) || $scores[$key] === null || $scores[$key] === '') {
                return 0;
            }

            $score = (int) $scores[$key];
            return max($min, min($max, $score));
        });
    }

    private function maxInterviewScore(): int
    {
        return count($this->interviewCriteria) * 10;
    }

    private function maxPotentialScore(): int
    {
        return collect($this->potentialCriteria)->flatten()->count() * 5;
    }

    private function potentialCriteriaKeys(): array
    {
        return collect($this->potentialCriteria)
            ->flatMap(fn ($items) => array_keys($items))
            ->values()
            ->all();
    }

    private function ratingComplete(?InterviewRating $rating): bool
    {
        if (!$rating) {
            return false;
        }

        $interviewScores = $rating->interview_scores ?? [];
        $potentialScores = $rating->potential_scores ?? [];

        return collect(array_keys($this->interviewCriteria))->every(function ($key) use ($interviewScores) {
                return array_key_exists($key, $interviewScores)
                    && $interviewScores[$key] !== null
                    && $interviewScores[$key] !== '';
            })
            && collect($this->potentialCriteriaKeys())->every(function ($key) use ($potentialScores) {
                return array_key_exists($key, $potentialScores)
                    && $potentialScores[$key] !== null
                    && $potentialScores[$key] !== '';
            });
    }

    private function weightedScore(float $score, int $maxScore, float $weight): float
    {
        if ($maxScore <= 0) {
            return 0;
        }

        return ($score / $maxScore) * $weight;
    }

    private function rankingRows(InterviewEvaluation $interview)
    {
        $ratingsByApplication = $interview->ratings->groupBy('application_id');
        $eteRatings = EteApplicantRating::where('ete_id', $interview->ete_id)
            ->whereIn('application_id', $interview->applicants->pluck('application_id'))
            ->get()
            ->keyBy('application_id');

        return $interview->applicants
            ->filter(fn ($row) => $row->application)
            ->map(function ($row) use ($interview, $ratingsByApplication, $eteRatings) {
                $ratings = $ratingsByApplication->get($row->application_id, collect());
                $panelCount = max(1, $this->assignedPanelIdsForApplication($interview, (int) $row->application_id)->count());
                $startedRatings = $ratings->filter(function ($rating) {
                    return (float) $rating->total_score > 0
                        || !empty($rating->interview_scores)
                        || !empty($rating->potential_scores)
                        || !empty($rating->remarks);
                });
                $startedCount = $startedRatings->count();
                $submittedRatings = $ratings->filter(fn ($rating) => $this->ratingComplete($rating));
                $submittedCount = $submittedRatings->count();
                $interviewTotal = $submittedCount ? (float) $submittedRatings->avg('interview_total') : 0;
                $potentialTotal = $submittedCount ? (float) $submittedRatings->avg('potential_total') : 0;
                $totalScore = $submittedCount ? (float) $submittedRatings->avg('total_score') : 0;
                $eteTotal = (float) optional($eteRatings->get($row->application_id))->total_score;
                $qualificationScore = $eteTotal * 0.5;
                $interviewScore = $this->weightedScore($interviewTotal, $this->maxInterviewScore(), 25);
                $potentialScore = $this->weightedScore($potentialTotal, $this->maxPotentialScore(), 25);
                $finalScore = $qualificationScore + $potentialScore + $interviewScore;

                return [
                    'application_id' => $row->application_id,
                    'app_number' => $row->application->app_number,
                    'name' => $this->applicantName($row->application),
                    'qualification_score_raw' => $qualificationScore,
                    'potential_score_raw' => $potentialScore,
                    'interview_score_raw' => $interviewScore,
                    'final_score_raw' => $finalScore,
                    'raw_total_score' => number_format($totalScore, 2),
                    'raw_total_score_raw' => $totalScore,
                    'total_raw' => $finalScore,
                    'qualification_score' => number_format($qualificationScore, 2),
                    'interview_score' => number_format($interviewScore, 2),
                    'potential_score' => number_format($potentialScore, 2),
                    'raw_interview_score' => number_format($interviewTotal, 2),
                    'raw_potential_score' => number_format($potentialTotal, 2),
                    'weighted_interview_score' => number_format($interviewScore, 2),
                    'weighted_potential_score' => number_format($potentialScore, 2),
                    'total_score' => number_format($finalScore, 2),
                    'final_score' => number_format($finalScore, 2),
                    'started_count' => $startedCount,
                    'submitted_count' => $submittedCount,
                    'panel_count' => $panelCount,
                    'completed' => $submittedCount >= $panelCount,
                    'is_active' => (int) $interview->active_application_id === (int) $row->application_id && (bool) $row->is_cast,
                    'remarks' => '',
                ];
            })
            ->sortByDesc('final_score_raw')
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });
    }

    public function index()
    {
        $this->authorizeAdmin();

        $interviews = InterviewEvaluation::with([
            'eteEvaluation.job',
            'job',
            'panels.employee',
            'activeApplication',
            'applicants.application',
            'ratings',
        ])->latest()->get();

        $etes = EteEvaluation::with(['job', 'office'])->latest()->get();
        $employees = Employee::orderBy('lname')->orderBy('fname')->get();

        return view('interview.index', compact('interviews', 'etes', 'employees'));
    }

    public function assignments()
    {
        $employeeId = $this->currentPanelEmployeeId();
        if (!$employeeId) {
            $ratings = collect();
            return view('interview.assignments', compact('ratings'));
        }

        $activeInterviews = $this->activeInterviewsForPanel((int) $employeeId);

        $ratings = $activeInterviews->map(function ($interview) use ($employeeId) {
            return InterviewRating::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $interview->active_application_id,
                'panel_employee_id' => $employeeId,
            ])->load(['interview.job', 'interview.eteEvaluation.office', 'application']);
        });

        if ($ratings->isNotEmpty()) {
            $rating = $ratings->first();
            $interview = $rating->interview->load(['job', 'eteEvaluation.office', 'panels.employee', 'activeApplication']);
            $application = $rating->application;
            $panelEmployee = Employee::findOrFail($employeeId);
            $relatedPositions = $this->relatedQualifiedPositions($application, (int) $employeeId);
            $copyableRatings = $this->copyablePanelRatings($application, $rating, (int) $employeeId);
            $sourceInterviewId = (int) $interview->id;
            $sourceApplicationId = (int) $application->id;

            return view('interview.rate', [
                'interview' => $interview,
                'application' => $application,
                'rating' => $rating,
                'panelEmployee' => $panelEmployee,
                'interviewCriteria' => $this->interviewCriteria,
                'potentialCriteria' => $this->potentialCriteria,
                'relatedPositions' => $relatedPositions,
                'copyableRatings' => $copyableRatings,
                'sourceInterviewId' => $sourceInterviewId,
                'sourceApplicationId' => $sourceApplicationId,
            ]);
        }

        $assignmentKey = $this->activeAssignmentKeyForPanel((int) $employeeId);

        return view('interview.assignments', compact('ratings', 'assignmentKey'));
    }

    public function assignmentStatus()
    {
        $employeeId = $this->currentPanelEmployeeId();
        if (!$employeeId) {
            return response()->json([
                'count' => 0,
                'url' => route('interviewAssignments'),
                'assignment_key' => '',
            ]);
        }

        $activeInterviews = $this->activeInterviewsForPanel((int) $employeeId);
        $activeInterview = $activeInterviews->first();

        return response()->json([
            'count' => $activeInterviews->count(),
            'url' => route('interviewAssignments'),
            'active_form_url' => $activeInterview
                ? route('interviewRatingForm', [$activeInterview->id, $activeInterview->active_application_id])
                : null,
            'active_key' => $activeInterview
                ? $activeInterview->id . ':' . $activeInterview->active_application_id
                : '',
            'assignment_key' => $activeInterviews
                ->map(fn ($interview) => $interview->id . ':' . $interview->active_application_id . ':' . optional($interview->updated_at)->timestamp)
                ->implode('|'),
        ]);
    }

    public function ratingStatus($id, $applicationId)
    {
        $employeeId = $this->currentPanelEmployeeId();
        $statusUrl = auth()->guard('web')->check()
            ? route('interviewEvaluationShow', $id)
            : route('interviewAssignments');

        if (auth()->guard('web')->check()) {
            $employeeId = request()->integer('panel_id') ?: $employeeId;
        }

        if (!$employeeId) {
            return response()->json([
                'active' => false,
                'url' => $statusUrl,
            ]);
        }

        $interview = InterviewEvaluation::find($id);
        $application = $interview
            ? Application::where('jid', $interview->jid)->where('status', 2)->find($applicationId)
            : null;
        $sourceInterviewId = request()->integer('source_interview_id') ?: null;
        $sourceApplicationId = request()->integer('source_application_id') ?: null;
        $sourceInterview = $sourceInterviewId ? InterviewEvaluation::find($sourceInterviewId) : null;
        $sourceActive = false;

        if ($sourceInterview && $sourceApplicationId) {
            $sourceActive = $this->isCurrentCastForPanel($sourceInterview, (int) $sourceApplicationId, (int) $employeeId);
        }

        $currentCastActive = $interview && $application
            ? $this->isCurrentCastForPanel($interview, (int) $application->id, (int) $employeeId)
            : false;
        $active = $interview && $application
            ? $this->canRateApplicationForPanel($interview, $application, (int) $employeeId, $sourceInterviewId, $sourceApplicationId)
            : false;
        $activeInterview = $this->activeInterviewsForPanel((int) $employeeId)->first();

        return response()->json([
            'active' => $active,
            'current_cast_active' => $currentCastActive,
            'source_active' => $sourceInterviewId && $sourceApplicationId ? $sourceActive : $currentCastActive,
            'url' => $statusUrl,
            'active_key' => $active
                ? $id . ':' . $applicationId
                : ($activeInterview
                ? $activeInterview->id . ':' . $activeInterview->active_application_id
                : ''),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'ete_id' => 'required|exists:ete_evaluations,id',
            'interview_date' => 'nullable|date',
            'panels' => 'required|array|min:1',
            'panels.*' => 'exists:employees,id',
        ]);

        DB::transaction(function () use ($request) {
            $ete = EteEvaluation::findOrFail($request->ete_id);
            $qualifiedApplicants = Application::where('jid', $ete->jid)->where('status', 2)->count();

            if ($qualifiedApplicants === 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'ete_id' => 'Cannot create interview assessment. No applicants are currently Qualified / Ready for Interview for this ETE position.',
                ]);
            }

            $interview = InterviewEvaluation::create([
                'ete_id' => $ete->id,
                'jid' => $ete->jid,
                'interview_date' => $request->interview_date ? Carbon::parse($request->interview_date) : now(),
            ]);

            collect($request->panels)->unique()->each(fn ($empId) => InterviewPanel::create([
                'interview_id' => $interview->id,
                'emp_id' => $empId,
            ]));

            $this->syncApplicantRows($interview);
        });

        return back()->with('success', 'Interview evaluation created successfully.');
    }

    public function show($id)
    {
        $this->authorizeAdmin();

        $interview = InterviewEvaluation::with(['eteEvaluation.office', 'job', 'panels.employee', 'activeApplication'])->findOrFail($id);
        $this->syncApplicantRows($interview);
        $eligibleApplicants = $this->eligibleApplicants($interview);
        $eligibleApplicants->each(fn ($applicant) => $this->ensureDefaultApplicantPanels($interview, (int) $applicant->id));
        $interview->load(['applicants.application', 'ratings.panelEmployee']);
        $ratingsByApplication = $interview->ratings->groupBy('application_id');
        $completedRatingsByApplication = $ratingsByApplication->map(function ($ratings) {
            return $ratings->filter(fn ($rating) => $this->ratingComplete($rating));
        });
        $panelEmployeesByApplication = $eligibleApplicants->mapWithKeys(function ($applicant) use ($interview) {
            return [$applicant->id => $this->assignedPanelEmployeesForApplication($interview, (int) $applicant->id)];
        });
        $employees = Employee::orderBy('lname')
            ->orderBy('fname')
            ->get();

        return view('interview.show', compact('interview', 'eligibleApplicants', 'ratingsByApplication', 'completedRatingsByApplication', 'panelEmployeesByApplication', 'employees'));
    }

    public function cast($id, $applicationId)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($id, $applicationId) {
            $interview = InterviewEvaluation::with('panels')->findOrFail($id);
            $application = Application::where('jid', $interview->jid)->where('status', 2)->findOrFail($applicationId);

            InterviewApplicant::where('interview_id', $interview->id)
                ->where('is_cast', true)
                ->update(['is_cast' => false, 'uncasted_at' => now()]);

            InterviewApplicant::updateOrCreate(
                ['interview_id' => $interview->id, 'application_id' => $application->id],
                ['is_cast' => true, 'casted_at' => now(), 'uncasted_at' => null]
            );

            $interview->update(['active_application_id' => $application->id]);
            $this->ensureDefaultApplicantPanels($interview, $application->id);
        });

        return back()->with('success', 'Candidate cast to interview panel.');
    }

    public function uncast($id, $applicationId)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($id, $applicationId) {
            $interview = InterviewEvaluation::findOrFail($id);
            InterviewApplicant::where('interview_id', $interview->id)
                ->where('application_id', $applicationId)
                ->update(['is_cast' => false, 'uncasted_at' => now()]);

            if ((int) $interview->active_application_id === (int) $applicationId) {
                $interview->update(['active_application_id' => null]);
            }

        });

        return back()->with('success', 'Candidate uncast successfully.');
    }

    public function addApplicantPanel(Request $request, $id, $applicationId)
    {
        $this->authorizeAdmin();

        $request->validate([
            'panel_employee_id' => 'required|exists:employees,id',
        ]);

        DB::transaction(function () use ($request, $id, $applicationId) {
            $interview = InterviewEvaluation::with('panels')->findOrFail($id);
            $application = Application::where('jid', $interview->jid)
                ->where('status', 2)
                ->findOrFail($applicationId);
            $employeeId = (int) $request->panel_employee_id;

            InterviewApplicant::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $application->id,
            ]);

            $this->ensureDefaultApplicantPanels($interview, (int) $application->id);

            InterviewRating::firstOrCreate([
                'interview_id' => $interview->id,
                'application_id' => $application->id,
                'panel_employee_id' => $employeeId,
            ]);
        });

        return back()->with('success', 'Interview panel added for this applicant.');
    }

    public function removeApplicantPanel($id, $applicationId, $employeeId)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($id, $applicationId, $employeeId) {
            $interview = InterviewEvaluation::with('panels')->findOrFail($id);
            $application = Application::where('jid', $interview->jid)
                ->where('status', 2)
                ->findOrFail($applicationId);

            $this->ensureDefaultApplicantPanels($interview, (int) $application->id);
            $assignedPanelIds = $this->assignedPanelIdsForApplication($interview, (int) $application->id);

            if (!$assignedPanelIds->contains((int) $employeeId)) {
                return;
            }

            if ($assignedPanelIds->count() <= 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'panel_employee_id' => 'At least one panel member is required for an applicant.',
                ]);
            }

            InterviewRating::where('interview_id', $interview->id)
                ->where('application_id', $application->id)
                ->where('panel_employee_id', (int) $employeeId)
                ->delete();
        });

        return back()->with('success', 'Interview panel removed for this applicant. Its rating for this applicant was also removed.');
    }

    public function setPanelChairman($id, $employeeId)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($id, $employeeId) {
            $interview = InterviewEvaluation::with('panels')->findOrFail($id);

            $panel = $interview->panels->firstWhere('emp_id', (int) $employeeId);
            abort_if(!$panel, 404, 'Panel member not found for this interview.');

            InterviewPanel::where('interview_id', $interview->id)
                ->where('is_chairman', true)
                ->update(['is_chairman' => false]);

            $panel->update(['is_chairman' => true]);
        });

        return back()->with('success', 'Chairman set for this interview panel.');
    }

    public function rate($id, $applicationId = null)
    {
        $guard = $this->guard();
        abort_unless($guard, 403);

        $interview = InterviewEvaluation::with(['job', 'eteEvaluation.office', 'panels.employee', 'activeApplication'])->findOrFail($id);
        $applicationId = $applicationId ?: $interview->active_application_id;
        abort_unless($applicationId, 404, 'No cast candidate is active for this interview.');

        $application = Application::where('jid', $interview->jid)->findOrFail($applicationId);
        $employeeId = $this->currentPanelEmployeeId();

        if ($guard === 'web') {
            $employeeId = request()->integer('panel_id') ?: $employeeId ?: optional($interview->panels->first())->emp_id;
        }
        $sourceInterviewId = request()->integer('source_interview_id') ?: null;
        $sourceApplicationId = request()->integer('source_application_id') ?: null;

        abort_unless(
            $employeeId && $this->assignedPanelIdsForApplication($interview, (int) $application->id)->contains((int) $employeeId),
            403,
            'You are not part of this interview panel.'
        );

        if (!$sourceInterviewId && !$sourceApplicationId && $this->isCurrentCastForPanel($interview, (int) $application->id, (int) $employeeId)) {
            $sourceInterviewId = (int) $interview->id;
            $sourceApplicationId = (int) $application->id;
        }

        if (!$this->canRateApplicationForPanel($interview, $application, (int) $employeeId, $sourceInterviewId, $sourceApplicationId)) {
            $redirectRoute = $guard === 'web' ? 'interviewEvaluationShow' : 'interviewAssignments';

            return redirect()->route($redirectRoute, $guard === 'web' ? [$interview->id] : [])
                ->with('error', 'This applicant is not currently cast for your interview panel.');
        }

        InterviewApplicant::firstOrCreate([
            'interview_id' => $interview->id,
            'application_id' => $application->id,
        ]);

        $rating = InterviewRating::firstOrCreate([
            'interview_id' => $interview->id,
            'application_id' => $application->id,
            'panel_employee_id' => $employeeId,
        ]);

        $panelEmployee = Employee::findOrFail($employeeId);
        $relatedPositions = $this->relatedQualifiedPositions($application, (int) $employeeId);
        $copyableRatings = $this->copyablePanelRatings($application, $rating, (int) $employeeId);

        return view('interview.rate', [
            'interview' => $interview,
            'application' => $application,
            'rating' => $rating,
            'panelEmployee' => $panelEmployee,
            'interviewCriteria' => $this->interviewCriteria,
            'potentialCriteria' => $this->potentialCriteria,
            'relatedPositions' => $relatedPositions,
            'copyableRatings' => $copyableRatings,
            'sourceInterviewId' => $sourceInterviewId,
            'sourceApplicationId' => $sourceApplicationId,
        ]);
    }

    public function saveRating(Request $request, $id, $applicationId)
    {
        $guard = $this->guard();
        abort_unless($guard, 403);

        $interview = InterviewEvaluation::with('panels')->findOrFail($id);
        $application = Application::where('jid', $interview->jid)->findOrFail($applicationId);
        $employeeId = $this->currentPanelEmployeeId();
        if ($guard === 'web') {
            $employeeId = $request->integer('panel_employee_id') ?: $employeeId;
        }
        $sourceInterviewId = $request->integer('source_interview_id') ?: null;
        $sourceApplicationId = $request->integer('source_application_id') ?: null;
        abort_unless(
            $employeeId && $this->assignedPanelIdsForApplication($interview, (int) $application->id)->contains((int) $employeeId),
            403
        );

        // A panelist's scores live in their own isolated rating row, keyed by
        // (interview_id, application_id, panel_employee_id). We must NEVER discard an
        // in-progress score just because the live "cast" moved to another applicant
        // mid-scoring — that race was silently deleting panel scores when an applicant
        // was being rated across several positions. Persist first, redirect after.
        $stillRateable = $this->canRateApplicationForPanel($interview, $application, (int) $employeeId, $sourceInterviewId, $sourceApplicationId);

        InterviewApplicant::firstOrCreate([
            'interview_id' => $interview->id,
            'application_id' => $application->id,
        ]);

        $interviewKeys = array_keys($this->interviewCriteria);
        $potentialKeys = $this->potentialCriteriaKeys();

        $isAutosave = $request->boolean('autosave');
        $rules = [
            'panel_employee_id' => 'nullable|exists:employees,id',
            'remarks' => 'nullable|string|max:2000',
            'autosave' => 'nullable|boolean',
            'interview_scores' => $isAutosave ? 'nullable|array' : 'required|array',
            'potential_scores' => $isAutosave ? 'nullable|array' : 'required|array',
        ];

        foreach ($interviewKeys as $key) {
            $rules["interview_scores.$key"] = ($isAutosave ? 'nullable' : 'required') . '|integer|min:1|max:10';
        }

        foreach ($potentialKeys as $key) {
            $rules["potential_scores.$key"] = ($isAutosave ? 'nullable' : 'required') . '|integer|min:1|max:5';
        }

        $validated = $request->validate($rules);
        $rating = null;
        $isComplete = false;

        DB::transaction(function () use (
            $interview,
            $application,
            $employeeId,
            $validated,
            $interviewKeys,
            $potentialKeys,
            $request,
            &$rating,
            &$isComplete
        ) {
            $keys = [
                'interview_id' => $interview->id,
                'application_id' => $application->id,
                'panel_employee_id' => $employeeId,
            ];
            $existingRating = InterviewRating::where($keys)->lockForUpdate()->first();
            $interviewScores = array_replace(
                $existingRating->interview_scores ?? [],
                $validated['interview_scores'] ?? []
            );
            $potentialScores = array_replace(
                $existingRating->potential_scores ?? [],
                $validated['potential_scores'] ?? []
            );
            $isComplete = collect($interviewKeys)->every(fn ($key) => isset($interviewScores[$key]) && $interviewScores[$key] !== '')
                && collect($potentialKeys)->every(fn ($key) => isset($potentialScores[$key]) && $potentialScores[$key] !== '');
            $interviewTotal = $isComplete
                ? $this->sumScores($interviewScores, $interviewKeys, 1, 10)
                : $this->sumProvidedScores($interviewScores, $interviewKeys, 1, 10);
            $potentialTotal = $isComplete
                ? $this->sumScores($potentialScores, $potentialKeys, 1, 5)
                : $this->sumProvidedScores($potentialScores, $potentialKeys, 1, 5);

            $rating = $existingRating ?: new InterviewRating($keys);
            $rating->fill([
                'interview_scores' => $interviewScores,
                'potential_scores' => $potentialScores,
                'interview_total' => $interviewTotal,
                'potential_total' => $potentialTotal,
                'total_score' => $interviewTotal + $potentialTotal,
                'remarks' => $request->remarks,
                // Preserve the original submission time instead of churning it on
                // every autosave; only stamp a fresh time the first time it completes.
                'submitted_at' => $isComplete ? (optional($existingRating)->submitted_at ?? now()) : null,
            ]);
            $rating->save();
        }, 3);

        // The scores are now safely persisted. If the live cast has since moved on to
        // another applicant, hand the panelist a redirect so the UI follows the active
        // assignment — but only after their work has been saved.
        $redirectUrl = $stillRateable
            ? null
            : ($guard === 'web'
                ? route('interviewEvaluationShow', $interview->id)
                : route('interviewAssignments'));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'complete' => $isComplete,
                'interview_total' => number_format($rating->interview_total, 2),
                'potential_total' => number_format($rating->potential_total, 2),
                'total_score' => number_format($rating->total_score, 2),
                'saved_at' => now()->format('M d, Y h:i A'),
                'message' => $isComplete ? 'Saved' : 'Draft saved',
                'redirect' => $redirectUrl,
            ]);
        }

        if ($redirectUrl) {
            return redirect()->to($redirectUrl)
                ->with('success', 'Interview assessment saved.');
        }

        return back()->with('success', 'Interview assessment saved.');
    }

    public function copyPreviousRating(Request $request, $id, $applicationId)
    {
        $guard = $this->guard();
        abort_unless($guard, 403);

        $request->validate([
            'source_rating_id' => 'required|exists:interview_ratings,id',
            'panel_employee_id' => 'nullable|exists:employees,id',
        ]);

        $interview = InterviewEvaluation::with('panels')->findOrFail($id);
        $application = Application::where('jid', $interview->jid)
            ->where('status', 2)
            ->findOrFail($applicationId);

        $employeeId = $this->currentPanelEmployeeId();
        if ($guard === 'web') {
            $employeeId = $request->integer('panel_employee_id') ?: $employeeId;
        }
        $sourceInterviewId = $request->integer('source_interview_id') ?: null;
        $sourceApplicationId = $request->integer('source_application_id') ?: null;

        abort_unless(
            $employeeId && $this->assignedPanelIdsForApplication($interview, (int) $application->id)->contains((int) $employeeId),
            403
        );
        abort_unless($this->canRateApplicationForPanel($interview, $application, (int) $employeeId, $sourceInterviewId, $sourceApplicationId), 403);

        $sourceRating = InterviewRating::with('application')->findOrFail($request->source_rating_id);
        abort_if((int) $sourceRating->panel_employee_id !== (int) $employeeId, 422, 'Selected rating belongs to another panel member.');
        abort_if((int) $sourceRating->interview_id === (int) $interview->id && (int) $sourceRating->application_id === (int) $application->id, 422, 'Please select a rating from another position.');
        abort_if((int) optional($sourceRating->application)->jid === (int) $application->jid, 422, 'Please select a rating from another position.');

        $targetEmail = strtolower(trim((string) $application->email));
        $sourceEmail = strtolower(trim((string) optional($sourceRating->application)->email));
        abort_if($targetEmail === '' || $targetEmail !== $sourceEmail, 422, 'Selected rating does not belong to the same applicant email.');
        abort_if(!$this->ratingStarted($sourceRating), 422, 'Selected rating has no saved score to copy.');

        InterviewApplicant::firstOrCreate([
            'interview_id' => $interview->id,
            'application_id' => $application->id,
        ]);

        $targetRating = InterviewRating::firstOrCreate([
            'interview_id' => $interview->id,
            'application_id' => $application->id,
            'panel_employee_id' => $employeeId,
        ]);

        $targetRating->update([
            'interview_scores' => $sourceRating->interview_scores,
            'potential_scores' => $sourceRating->potential_scores,
            'interview_total' => $sourceRating->interview_total,
            'potential_total' => $sourceRating->potential_total,
            'total_score' => $sourceRating->total_score,
            'remarks' => $sourceRating->remarks,
            'submitted_at' => $sourceRating->submitted_at ? now() : null,
        ]);

        $params = [$interview->id, $application->id];
        $url = route('interviewRatingForm', $params);
        if ($guard === 'web') {
            $url .= '?panel_id='.(int) $employeeId;
        }

        return redirect()->to($url)->with('success', 'Previous interview rating copied to this position.');
    }

    public function consolidatedScreen($id)
    {
        $this->authorizeRankingAdmin();
        $interview = InterviewEvaluation::with(['job', 'eteEvaluation.office'])->findOrFail($id);
        $this->syncApplicantRows($interview);

        return view('interview.consolidated-screen', compact('interview'));
    }

    public function consolidatedData($id)
    {
        $this->authorizeRankingAdmin();

        $interview = InterviewEvaluation::with([
            'job',
            'panels',
            'applicants.application',
            'ratings.application',
        ])->findOrFail($id);

        $data = $this->rankingRows($interview);

        return response()->json(['success' => true, 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function summaryRatingPdf($id)
    {
        $this->authorizeRankingAdmin();

        $interview = InterviewEvaluation::with([
            'job',
            'eteEvaluation.office',
            'panels.employee',
            'applicants.application',
            'ratings.application',
        ])->findOrFail($id);
        $this->syncApplicantRows($interview);
        $interview->load(['applicants.application', 'ratings.application']);

        $rows = $this->rankingRows($interview)->take(5)->values();
        $panelistData = $interview->panels
            ->filter(fn ($panel) => $panel->employee)
            ->map(function ($panel) {
                $name = trim(preg_replace('/\s+/', ' ', $panel->employee->fname . ' ' . $panel->employee->mname . ' ' . $panel->employee->lname));

                if ($this->employeeHasPhd($panel->employee)) {
                    $name .= ', PhD';
                }

                return [
                    'name' => $name,
                    'position' => $panel->employee->position,
                    'is_chairman' => (bool) $panel->is_chairman,
                ];
            })
            ->values();
        $chairman = $panelistData->firstWhere('is_chairman', true);
        $panelists = $panelistData->reject(fn ($panelist) => $panelist['is_chairman'])->values();
        $fileName = 'summary-rating-applicants-' . $interview->id . '.pdf';
        $longBondPaper = [0, 0, 612, 936];

        return \PDF::loadView('interview.summary-rating-pdf', compact('interview', 'rows', 'panelists', 'chairman'))
            ->setPaper($longBondPaper, 'portrait')
            ->stream($fileName);
    }

    /**
     * Realtime scoring overview for the panel-progress modal.
     *
     * Scoped to the applicant currently cast on this interview: cross-references,
     * by email, every qualified position that cast applicant applied to, and reports
     * which panel members have finished scoring each position and which are pending.
     */
    public function panelProgress($id)
    {
        $this->authorizeAdmin();

        $interview = InterviewEvaluation::findOrFail($id);
        $this->syncApplicantRows($interview);

        // Only the applicant currently cast on this interview is shown.
        $activeId = $interview->active_application_id;
        $baseApplicants = $activeId
            ? Application::where('jid', $interview->jid)
                ->where('status', 2)
                ->where('id', $activeId)
                ->get()
            : collect();

        $emails = $baseApplicants
            ->map(fn ($applicant) => strtolower(trim((string) $applicant->email)))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return response()->json([
                'success' => true,
                'applicants' => [],
                'generated_at' => now()->format('h:i:s A'),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        // Every qualified application belonging to these applicants, across all positions.
        $applications = Application::where('status', 2)
            ->whereIn(DB::raw('LOWER(TRIM(email))'), $emails->all())
            ->get();

        $jids = $applications->pluck('jid')->filter()->unique()->values();

        // Latest interview per position (jid) with its panels and ratings.
        $interviewsByJid = InterviewEvaluation::with(['job', 'panels.employee', 'ratings'])
            ->whereIn('jid', $jids)
            ->orderByDesc('id')
            ->get()
            ->unique('jid')
            ->keyBy('jid');

        // Preload every employee that could appear as a panelist (avoids N+1).
        $panelEmployeeIds = collect();
        foreach ($interviewsByJid as $relatedInterview) {
            $panelEmployeeIds = $panelEmployeeIds
                ->merge($relatedInterview->panels->pluck('emp_id'))
                ->merge($relatedInterview->ratings->pluck('panel_employee_id'));
        }
        $employees = Employee::whereIn('id', $panelEmployeeIds->filter()->unique()->values()->all())
            ->get()
            ->keyBy('id');

        $applicationsByEmail = $applications->groupBy(fn ($application) => strtolower(trim((string) $application->email)));

        $applicantsPayload = $baseApplicants->map(function ($base) use ($applicationsByEmail, $interviewsByJid, $employees) {
            $email = strtolower(trim((string) $base->email));
            $apps = $applicationsByEmail->get($email, collect())
                ->unique('jid')
                ->sortBy('jid')
                ->values();

            $positions = $apps->map(function ($app) use ($interviewsByJid, $employees) {
                $relatedInterview = $interviewsByJid->get($app->jid);

                if (!$relatedInterview) {
                    return [
                        'position' => $app->position ?? 'Position',
                        'plantilla' => null,
                        'setup' => false,
                        'completed' => 0,
                        'total' => 0,
                        'fully_done' => false,
                        'panels' => [],
                    ];
                }

                $ratingsForApp = $relatedInterview->ratings->where('application_id', $app->id);
                $assignedIds = $ratingsForApp->pluck('panel_employee_id')->filter()->unique()->values();
                if ($assignedIds->isEmpty()) {
                    $assignedIds = $relatedInterview->panels->pluck('emp_id')->filter()->unique()->values();
                }

                $panels = $assignedIds->map(function ($empId) use ($ratingsForApp, $employees) {
                    $employee = $employees->get($empId);
                    $rating = $ratingsForApp->firstWhere('panel_employee_id', $empId);

                    return [
                        'name' => $employee
                            ? trim(($employee->lname ?? '') . ', ' . ($employee->fname ?? ''))
                            : ('Panel #' . $empId),
                        'finished' => $this->ratingComplete($rating),
                    ];
                })->values();

                $completed = $panels->where('finished', true)->count();
                $total = $panels->count();

                return [
                    'position' => $relatedInterview->job->title ?? $app->position ?? 'Position',
                    'plantilla' => $relatedInterview->job->plantilla_item_no ?? null,
                    'setup' => true,
                    'completed' => $completed,
                    'total' => $total,
                    'fully_done' => $total > 0 && $completed === $total,
                    'panels' => $panels,
                ];
            })->values();

            $totalPositions = $positions->count();
            $donePositions = $positions->where('fully_done', true)->count();

            return [
                'name' => $this->applicantName($base),
                'app_number' => $base->app_number,
                'total_positions' => $totalPositions,
                'done_positions' => $donePositions,
                'all_done' => $totalPositions > 0 && $donePositions === $totalPositions,
                'positions' => $positions,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'applicants' => $applicantsPayload,
            'generated_at' => now()->format('h:i:s A'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();
        InterviewEvaluation::findOrFail($id)->delete();

        return back()->with('success', 'Interview evaluation deleted.');
    }
}
