@extends('layouts.master')

@section('body')
<style>
    .rating-page .card { border:0; border-radius:18px; box-shadow:0 9px 30px rgba(15,23,42,.07); }
    .rating-hero { background:#f8faf9; border:1px solid #dfe7e2; border-radius:18px; padding:18px; }
    .rating-table th { background:#f8fafc; font-size:.78rem; text-transform:uppercase; }
    .rating-table td, .rating-table th { vertical-align:middle; }
    .score-choice { 
        align-items:center; 
        display:inline-flex; 
        justify-content:center;
        margin:0; 
        position:relative;
    }
    .score-choice input { 
        opacity:0;
        position:absolute;
        inset:0;
        cursor:pointer; 
        z-index:2;
    }
    .score-choice span { 
        align-items:center;
        background:#fff;
        border:1px solid #cfd8e3;
        border-radius:50%;
        color:#334155;
        display:inline-flex;
        font-size:.72rem; 
        font-weight:800;
        height:28px;
        justify-content:center;
        line-height:1;
        min-width:28px; 
        text-align:center; 
        transition:.15s ease;
        width:28px;
    }
    .score-choice:hover span {
        border-color:#7bbf99;
        color:#166534;
        transform:translateY(-1px);
    }
    .score-choice input:focus + span {
        box-shadow:0 0 0 3px rgba(25,135,84,.18);
    }
    .score-choice input:checked + span {
        background:#198754;
        border-color:#198754;
        box-shadow:0 6px 14px rgba(25,135,84,.25);
        color:#fff;
    }
    .trait-prompt { 
        color:#536171; 
        font-size:.72rem; 
        line-height:1.3; 
        margin-top:3px; 
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .level-grid { 
        display:grid; 
        gap:4px; 
        grid-template-columns:repeat(5,1fr); 
    }
    .level-card { 
        background:#fafbfc; 
        border:1px solid #e5e9f0; 
        border-radius:6px; 
        padding:6px 6px 8px; 
        min-height:60px;
    }
    .level-card strong { 
        color:#1f2937; 
        display:block; 
        font-size:.65rem; 
        margin-bottom:3px; 
        text-align:center;
    }
    .level-card small { 
        color:#5f6b7a; 
        display:block; 
        line-height:1.2; 
        min-height:28px; 
        font-size:.6rem;
        text-align:center;
        margin-bottom:4px;
    }
    .level-card .score-options { 
        display:flex; 
        flex-wrap:wrap;
        justify-content:center; 
        gap:5px;
    }
    .interview-instructions { 
        background:#f8fafc; 
        border:1px solid #d9e1ea; 
        border-radius:10px; 
        color:#475569; 
        font-size:.8rem; 
        line-height:1.4; 
        padding:10px 12px; 
        margin-bottom:12px;
    }
    .tab-pane { padding-top:12px; }
    .rating-page .nav-tabs .nav-link {
        background:#f8fafc;
        border-color:#d9e1ea #d9e1ea #dee2e6;
        color:#1f2937 !important;
        font-weight:700;
        padding:8px 16px;
        font-size:.85rem;
    }
    .rating-page .nav-tabs .nav-link.active {
        background:#fff;
        border-color:#d9e1ea #d9e1ea #fff;
        color:#166534 !important;
    }
    .rating-page .nav-tabs .nav-link i {
        color:inherit !important;
    }
    .total-box { background:#e8f6ee; border-radius:14px; color:#166534; font-weight:800; padding:12px 16px; text-align:center; }
    .autosave-status { color:#64748b; font-size:.72rem; min-height:18px; }
    .autosave-status.saving { color:#b7791f; }
    .autosave-status.saved { color:#166534; }
    .autosave-status.error { color:#b91c1c; }
    .position-switcher {
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-top:12px;
    }
    .position-switcher .position-link {
        align-items:center;
        background:#fff;
        border:1px solid #d9e1ea;
        border-radius:8px;
        color:#1f2937;
        display:flex;
        gap:10px;
        justify-content:space-between;
        min-width:210px;
        padding:8px 10px;
        text-align:left;
        text-decoration:none;
    }
    .position-switcher .position-link:hover {
        border-color:#7bbf99;
        color:#166534;
    }
    .position-switcher .position-link.active {
        background:#e8f6ee;
        border-color:#198754;
        box-shadow:inset 4px 0 0 #198754;
    }
    .position-switcher .position-link-body {
        min-width:0;
    }
    .position-switcher strong,
    .position-switcher small {
        display:block;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .position-switcher .position-status {
        flex-shrink:0;
        font-size:1.25rem;
        line-height:1;
    }
    .position-switcher .position-status.rated { color:#16a34a; }
    .position-switcher .position-status.draft { color:#d97706; }
    .position-switcher .position-status.pending { color:#dc2626; }
    .copy-rating-control {
        margin-bottom:10px;
    }
    .copy-rating-control select {
        border-radius:8px;
        min-height:38px;
    }
    
    /* Compact trait row */
    .trait-row td:first-child { 
        width:28%; 
        padding:8px 10px; 
    }
    .trait-row td:last-child { 
        padding:6px 8px; 
    }
    .trait-row .trait-label { 
        font-size:.78rem; 
        display:block; 
        margin-bottom:2px; 
    }
    
    /* Mobile optimizations */
    @media(max-width:767.98px) {
        .level-grid { 
            grid-template-columns:repeat(3,1fr); 
            gap:3px;
        }
        .level-card { 
            padding:4px 4px 6px; 
            min-height:50px;
        }
        .level-card strong { 
            font-size:.58rem; 
        }
        .level-card small { 
            font-size:.52rem; 
            min-height:20px;
        }
        .score-choice { 
            margin:0; 
        }
        .score-choice span { 
            font-size:.62rem; 
            height:24px;
            min-width:24px;
            width:24px;
        }
        .trait-row td:first-child { 
            width:35%; 
            padding:6px 6px; 
        }
        .trait-row td:last-child { 
            padding:4px 4px; 
        }
        .trait-row .trait-label { 
            font-size:.7rem; 
        }
        .trait-prompt { 
            font-size:.62rem; 
            -webkit-line-clamp:2;
        }
        .interview-instructions { 
            font-size:.7rem; 
            padding:8px 10px; 
        }
        .rating-page .nav-tabs .nav-link {
            padding:6px 10px;
            font-size:.75rem;
        }
        .total-box { 
            padding:8px 12px; 
            font-size:.85rem;
        }
        .total-box small { 
            font-size:.65rem; 
        }
        .position-switcher .position-link {
            min-width:100%;
        }
    }
    
    @media(max-width:400px) {
        .level-grid { 
            grid-template-columns:repeat(2,1fr); 
        }
        .trait-row td:first-child { 
            width:40%; 
        }
    }
</style>

@php
    $interviewScores = $rating->interview_scores ?? [];
    $potentialScores = $rating->potential_scores ?? [];
    $relatedPositions = $relatedPositions ?? collect();
    $copyableRatings = $copyableRatings ?? collect();
    $sourceInterviewId = $sourceInterviewId ?? $interview->id;
    $sourceApplicationId = $sourceApplicationId ?? $application->id;
@endphp

<div class="container-fluid rating-page">
    <form method="POST" action="{{ route('interviewRatingSave', [$interview->id, $application->id]) }}" id="interviewRatingForm">
        @csrf
        <input type="hidden" name="panel_employee_id" value="{{ $panelEmployee->id }}">
        <input type="hidden" name="source_interview_id" value="{{ $sourceInterviewId }}">
        <input type="hidden" name="source_application_id" value="{{ $sourceApplicationId }}">

        <div class="rating-hero mb-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <small class="text-muted">Interview Assessment Form</small>
                    <h4 class="font-weight-bold mb-1">{{ trim($application->first_name.' '.$application->middle_name.' '.$application->last_name) }}</h4>
                    {{-- <div class="text-muted small">{{ $application->app_number }}</div> --}}
                    <div class="text-muted small">{{ $interview->job->title ?? 'Position' }}</div>
                    @if($interview->job && $interview->job->plantilla_item_no)
                        <div class="text-muted small">{{ $interview->job->plantilla_item_no }}</div>
                    @endif
                    {{-- <div class="text-muted small">{{ $interview->eteEvaluation->office->office_name ?? '' }}</div> --}}

                    @if($relatedPositions->count() > 1)
                        <div class="position-switcher">
                            @foreach($relatedPositions as $positionItem)
                                @php
                                    $positionInterview = $positionItem['interview'];
                                    $positionApplication = $positionItem['application'];
                                    $positionRating = $positionItem['rating'];
                                    $isCurrentPosition = (int) $positionInterview->id === (int) $interview->id
                                        && (int) $positionApplication->id === (int) $application->id;
                                    $positionUrl = route('interviewRatingForm', [$positionInterview->id, $positionApplication->id]);
                                    $positionQuery = [
                                        'source_interview_id' => $sourceInterviewId,
                                        'source_application_id' => $sourceApplicationId,
                                    ];
                                    if (auth()->guard('web')->check()) {
                                        $positionQuery['panel_id'] = $panelEmployee->id;
                                    }
                                    $positionUrl .= '?'.http_build_query($positionQuery);
                                @endphp
                                @php
                                    $positionIsRated = $positionRating && $positionRating->submitted_at;
                                    $positionIsDraft = !$positionIsRated && $positionRating && ((float) $positionRating->total_score > 0 || !empty($positionRating->interview_scores) || !empty($positionRating->potential_scores) || !empty($positionRating->remarks));
                                @endphp
                                <a href="{{ $positionUrl }}" class="position-link {{ $isCurrentPosition ? 'active' : '' }}">
                                    <span class="position-link-body">
                                        <strong>{{ $positionInterview->job->title ?? $positionApplication->position ?? 'Position' }}</strong>
                                        <small class="text-muted">{{ $positionInterview->job->plantilla_item_no ?? 'No plantilla number' }}</small>
                                    </span>
                                    @if($positionIsRated)
                                        <span class="position-status rated" title="Rated"><i class="fas fa-check-circle"></i></span>
                                    @elseif($positionIsDraft)
                                        <span class="position-status draft" title="Draft in progress"><i class="fas fa-pen-nib"></i></span>
                                    @else
                                        <span class="position-status pending" title="Not yet rated"><i class="fas fa-times-circle"></i></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    @if($copyableRatings->isNotEmpty())
                        <div class="copy-rating-control">
                            <select id="copyInterviewRatingSelect" class="form-control form-control-sm">
                                <option value="">Copy rating from other position</option>
                                @foreach($copyableRatings as $copyableRating)
                                    @php
                                        $copyInterview = $copyableRating->interview;
                                        $copyApplication = $copyableRating->application;
                                        $copyJob = optional($copyInterview)->job;
                                        $copyLabel = $copyJob->title ?? $copyApplication->position ?? 'Position';
                                        $copyPlantilla = $copyJob && $copyJob->plantilla_item_no ? ' - '.$copyJob->plantilla_item_no : '';
                                    @endphp
                                    <option value="{{ $copyableRating->id }}"
                                            data-position="{{ $copyLabel }}{{ $copyPlantilla }}"
                                            data-score="{{ number_format($copyableRating->total_score, 2) }}">
                                        {{ $copyLabel }}{{ $copyPlantilla }} ({{ number_format($copyableRating->total_score, 2) }} pts)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="text-muted small">Rated by</div>
                    <strong>{{ trim($panelEmployee->fname.' '.$panelEmployee->mname.' '.$panelEmployee->lname) }}</strong>
                    <div>
                        <span class="badge badge-success mt-2" id="ratingSavedBadge" style="{{ $rating->submitted_at ? '' : 'display:none;' }}">
                            Saved {{ optional($rating->submitted_at)->format('M d, Y h:i A') }}
                        </span>
                    </div>
                    <div class="autosave-status mt-1" id="autosaveStatus"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="interviewRatingTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#interview-tab" role="tab">
                            <i class="fas fa-microphone-alt mr-1"></i> Interview Assessment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#potential-tab" role="tab">
                            <i class="fas fa-chart-line mr-1"></i> Potential Assessment
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="interview-tab" role="tabpanel">
                        <div class="interview-instructions">
                            <strong>Instructions:</strong> Rate the candidate's physical characteristics and personality traits using a 10-point scale. Select the number that corresponds to your rating for each item.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered rating-table">
                                <thead>
                                    <tr>
                                        <th style="width:25%;">Traits</th>
                                        <th>Rating Scale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interviewCriteria as $key => $criterion)
                                        <tr class="trait-row">
                                            <td>
                                                <span class="trait-label"><strong>{{ $loop->iteration }}.</strong> {{ $criterion['label'] }}</span>
                                                <div class="trait-prompt">{{ $criterion['prompt'] }}</div>
                                            </td>
                                            <td>
                                                <div class="level-grid">
                                                    @foreach($criterion['levels'] as $range => $description)
                                                        @php
                                                            [$start, $end] = array_map('intval', explode(' - ', $range));
                                                        @endphp
                                                        <div class="level-card">
                                                            <small>{{ $description }}</small>
                                                            <div class="score-options">
                                                                @for($score = $start; $score <= $end; $score++)
                                                                    <label class="score-choice">
                                                                        <input type="radio" name="interview_scores[{{ $key }}]" value="{{ $score }}" {{ (int)($interviewScores[$key] ?? 0) === $score ? 'checked' : '' }} required>
                                                                        <span>{{ $score }}</span>
                                                                    </label>
                                                                @endfor
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="potential-tab" role="tabpanel">
                        @foreach($potentialCriteria as $group => $items)
                            <h6 class="font-weight-bold mt-2">{{ $group }}</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered rating-table">
                                    <thead>
                                        <tr>
                                            <th style="width:55%;">Factor</th>
                                            <th>Rating (5 Excellent, 1 Poor)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $key => $label)
                                            <tr>
                                                <td>{{ $label }}</td>
                                                <td>
                                                    @for($score = 5; $score >= 1; $score--)
                                                        <label class="score-choice">
                                                            <input type="radio" name="potential_scores[{{ $key }}]" value="{{ $score }}" {{ (int)($potentialScores[$key] ?? 0) === $score ? 'checked' : '' }} required>
                                                            <span>{{ $score }}</span>
                                                        </label>
                                                    @endfor
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row align-items-end mt-3">
                    <div class="col-md-8">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes">{{ old('remarks', $rating->remarks) }}</textarea>
                    </div>
                    <div class="col-md-4 mt-3 mt-md-0">
                        <div class="total-box">
                            <small class="d-block">Current Total</small>
                            <span id="currentTotalScore">{{ number_format($rating->total_score, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@if($copyableRatings->isNotEmpty())
<div class="modal fade" id="copyInterviewRatingModal" tabindex="-1" role="dialog" aria-labelledby="copyInterviewRatingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="{{ route('interviewRatingCopy', [$interview->id, $application->id]) }}" class="modal-content">
            @csrf
            <input type="hidden" name="panel_employee_id" value="{{ $panelEmployee->id }}">
            <input type="hidden" name="source_interview_id" value="{{ $sourceInterviewId }}">
            <input type="hidden" name="source_application_id" value="{{ $sourceApplicationId }}">
            <input type="hidden" name="source_rating_id" id="copyInterviewSourceRatingId">

            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="copyInterviewRatingModalLabel">
                    <i class="fas fa-copy"></i> Copy Interview Rating Here?
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <strong id="copyInterviewPositionText">Previous position</strong>
                    <div class="text-muted small">Saved total score: <span id="copyInterviewScoreText">0.00</span> pts</div>
                </div>
                <p class="mb-0">
                    This will copy this panel member's saved interview and potential assessment scores into the current position. Current saved scores for this position will be overwritten.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Yes, Copy Rating
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('interviewRatingForm');
    const status = document.getElementById('autosaveStatus');
    const totalScore = document.getElementById('currentTotalScore');
    const savedBadge = document.getElementById('ratingSavedBadge');
    const currentUrl = window.location.href.split('#')[0];
    const fallbackUrl = "{{ auth()->guard('web')->check() ? route('interviewEvaluationShow', $interview->id) : route('interviewAssignments') }}";
    const currentActiveKey = "{{ $interview->id }}:{{ $application->id }}";
    const panelEmployeeId = "{{ $panelEmployee->id }}";
    const sourceInterviewId = "{{ $sourceInterviewId }}";
    const sourceApplicationId = "{{ $sourceApplicationId }}";
    const interviewKeys = @json(array_keys($interviewCriteria));
    const potentialKeys = @json(collect($potentialCriteria)->flatMap(fn ($items) => array_keys($items))->values());
    let autosaveTimer = null;
    let autosaveRunning = false;
    let autosaveQueued = false;
    let castCheckRunning = false;
    let pendingDirty = false;

    function setSaveStatus(text, state) {
        if (!status) return;
        status.textContent = text || '';
        status.className = 'autosave-status mt-1' + (state ? ' ' + state : '');
    }

    function selectedScore(name) {
        const selected = form.querySelector('input[name="' + name + '"]:checked');
        return selected ? parseInt(selected.value || '0', 10) : 0;
    }

    function updateCurrentTotal() {
        const interviewTotal = interviewKeys.reduce(function (sum, key) {
            return sum + selectedScore('interview_scores[' + key + ']');
        }, 0);
        const potentialTotal = potentialKeys.reduce(function (sum, key) {
            return sum + selectedScore('potential_scores[' + key + ']');
        }, 0);

        if (totalScore) {
            totalScore.textContent = (interviewTotal + potentialTotal).toFixed(2);
        }
    }

    function submitRating() {
        if (!form) return;
        if (autosaveRunning) {
            autosaveQueued = true;
            return;
        }

        const formData = new FormData(form);
        formData.append('autosave', '1');

        autosaveRunning = true;
        // This snapshot is now in flight; treat the form as clean unless a new edit
        // arrives while the request is running (which re-queues another save).
        pendingDirty = false;
        setSaveStatus('Saving...', 'saving');

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData,
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () {
                        throw new Error('Unable to save rating.');
                    }).then(function (payload) {
                        if (payload.redirect) {
                            window.location.replace(payload.redirect);
                            throw new Error('Redirecting...');
                        }
                        const message = payload.message || 'Please complete the required ratings.';
                        throw new Error(message);
                    });
                }

                return response.json();
            })
            .then(function (data) {
                if (totalScore && data.total_score) {
                    totalScore.textContent = data.total_score;
                }

                if (savedBadge && data.complete) {
                    savedBadge.textContent = 'Saved ' + data.saved_at;
                    savedBadge.style.display = '';
                }

                setSaveStatus(data.complete ? 'Saved automatically.' : 'Draft saved automatically.', 'saved');

                // Scores are persisted; only now is it safe to follow the active
                // assignment if the live cast has moved on to another applicant.
                if (data.redirect) {
                    window.location.replace(data.redirect);
                }
            })
            .catch(function (error) {
                // Save did not land — keep the form marked dirty so the realtime
                // cast-check won't navigate away and lose the unsaved scores.
                pendingDirty = true;
                setSaveStatus(error.message || 'Save failed. Please try again.', 'error');
            })
            .finally(function () {
                autosaveRunning = false;
                if (autosaveQueued) {
                    autosaveQueued = false;
                    submitRating();
                }
            });
    }

    function scheduleAutosave() {
        pendingDirty = true;
        updateCurrentTotal();
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(function () {
            submitRating();
        }, 450);
    }

    function hasUnsavedWork() {
        return pendingDirty || autosaveRunning || autosaveQueued;
    }

    if (form) {
        updateCurrentTotal();
        form.querySelectorAll('input[type="radio"]').forEach(function (input) {
            input.addEventListener('change', scheduleAutosave);
        });
        form.querySelectorAll('textarea').forEach(function (textarea) {
            textarea.addEventListener('input', scheduleAutosave);
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            updateCurrentTotal();
            submitRating();
        });
    }

    function checkCurrentCast() {
        if (castCheckRunning) {
            return;
        }

        // Never navigate away while the panelist has scores that aren't safely on the
        // server yet. Flush them first; the save response itself will redirect if the
        // live cast has genuinely moved on. This prevents realtime cast changes from
        // wiping in-progress panel scores.
        if (hasUnsavedWork()) {
            window.clearTimeout(autosaveTimer);
            submitRating();
            return;
        }

        castCheckRunning = true;
        fetch("{{ route('interviewRatingStatus', [$interview->id, $application->id]) }}?panel_id=" + encodeURIComponent(panelEmployeeId)
            + "&source_interview_id=" + encodeURIComponent(sourceInterviewId)
            + "&source_application_id=" + encodeURIComponent(sourceApplicationId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        })
            .then(response => response.json())
            .then(data => {
                const nextUrl = data.url || fallbackUrl;
                const activeKey = data.active_key || '';

                if (!data.source_active || !data.active || activeKey !== currentActiveKey) {
                    if (nextUrl.split('#')[0] === currentUrl) {
                        window.location.reload();
                        return;
                    }

                    window.location.replace(nextUrl);
                }
            })
            .catch(() => {})
            .finally(() => {
                castCheckRunning = false;
            });
    }

    checkCurrentCast();
    window.addEventListener('focus', checkCurrentCast);
    window.addEventListener('pageshow', checkCurrentCast);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            checkCurrentCast();
        }
    });
    setInterval(checkCurrentCast, 1000);

    // Last line of defense: if the page is being torn down (position switch, tab
    // close, browser back) while an edit is still unsaved, flush it with a beacon so
    // the panelist's scores are never lost. The form carries the CSRF token.
    function flushOnExit() {
        if (!form || !hasUnsavedWork() || !navigator.sendBeacon) {
            return;
        }
        const formData = new FormData(form);
        formData.append('autosave', '1');
        navigator.sendBeacon(form.action, formData);
        pendingDirty = false;
    }
    window.addEventListener('pagehide', flushOnExit);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            flushOnExit();
        }
    });

    const copySelect = document.getElementById('copyInterviewRatingSelect');
    if (copySelect) {
        copySelect.addEventListener('change', function () {
            const selected = copySelect.options[copySelect.selectedIndex];

            if (!selected || !selected.value) {
                return;
            }

            document.getElementById('copyInterviewSourceRatingId').value = selected.value;
            document.getElementById('copyInterviewPositionText').textContent = selected.dataset.position || 'Previous position';
            document.getElementById('copyInterviewScoreText').textContent = selected.dataset.score || '0.00';

            if (window.jQuery) {
                window.jQuery('#copyInterviewRatingModal').modal('show');
            }
        });

        if (window.jQuery) {
            window.jQuery('#copyInterviewRatingModal').on('hidden.bs.modal', function () {
                copySelect.value = '';
            });
        }
    }
});
</script>
@endsection
