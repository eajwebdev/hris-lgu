@extends('layouts.master')

@section('body')
<style>
    .ete-rate-shell .card,
    .ete-rate-shell .alert {
        border-radius: 16px;
    }

    .ete-rate-shell > .card {
        border: 0;
        box-shadow: 0 8px 28px rgba(15, 23, 42, .07);
    }

    .ete-rate-header {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
        padding: 18px 20px;
    }

    .ete-rate-actions {
        align-items: center;
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .ete-copy-select {
        flex: 0 0 auto;
        min-width: 280px;
        width: auto;
    }

    .ete-active-banner {
        background: #f8fafc;
        border: 1px solid #dfe4ea;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 15px;
    }

    .ete-active-banner strong {
        display: block;
        font-size: 1rem;
    }

    .ete-rate-shell .form-control {
        border-radius: 9px;
        min-height: 42px;
    }

    .ete-rating-workspace { display: grid; gap: 18px; grid-template-columns: 300px minmax(0, 1fr); }
    .ete-candidate-queue { align-self: start; background: #f6f8f7; border: 1px solid #e3e9e5; border-radius: 16px; max-height: calc(100vh - 190px); overflow-y: auto; padding: 10px; position: sticky; top: 12px; }
    .ete-queue-title { align-items: center; display: flex; justify-content: space-between; padding: 8px 8px 12px; }
    .ete-candidate-link { align-items: center; background: #fff; border: 1px solid #e2e8e4; border-radius: 13px; color: #26352d; display: flex; gap: 10px; margin-bottom: 8px; padding: 11px; text-decoration: none !important; transition: .15s ease; }
    .ete-candidate-link:hover { border-color: #91c6a8; color: #176c43; transform: translateY(-1px); }
    .ete-candidate-link.active { background: #e9f7ef; border-color: #198754; box-shadow: inset 4px 0 0 #198754; }
    .ete-candidate-number { align-items: center; background: #eef2f0; border-radius: 50%; display: flex; flex: 0 0 34px; font-size: .75rem; font-weight: 800; height: 34px; justify-content: center; }
    .ete-candidate-meta { min-width: 0; }
    .ete-candidate-meta strong { display: block; font-size: .86rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ete-candidate-meta small { color: #718078; }
    .ete-status-dot { border-radius: 50%; flex: 0 0 9px; height: 9px; margin-left: auto; width: 9px; }
    .ete-status-dot.done { background: #21a366; box-shadow: 0 0 0 4px #dff4e8; }
    .ete-status-dot.pending { background: #aeb8b2; }
    .ete-form-panel > .card { border: 1px solid #e3e9e5; box-shadow: none; }
    .ete-candidate-nav { align-items: center; border-top: 1px solid #e7ece9; display: flex; justify-content: space-between; margin-top: 18px; padding-top: 16px; }

    @media (max-width: 767.98px) {
        .ete-rate-shell {
            padding-left: 8px;
            padding-right: 8px;
        }
        .ete-rate-header {
            align-items: flex-start;
            flex-direction: column;
        }
        .ete-rate-actions,
        .ete-copy-select,
        .ete-rate-actions .btn {
            width: 100%;
        }
        .ete-rate-actions {
            display: flex;
            flex-wrap: wrap;
        }
        .ete-rating-workspace { grid-template-columns: 1fr; }
        .ete-candidate-queue { display: flex; gap: 8px; max-height: none; overflow-x: auto; position: static; }
        .ete-queue-title { flex: 0 0 auto; }
        .ete-candidate-link { flex: 0 0 230px; margin-bottom: 0; }
        .ete-active-banner .badge {
            float: none !important;
            margin-top: 10px;
        }
        .ete-rate-shell .table-responsive {
            border: 0;
        }
        .ete-rate-shell .experience-table thead {
            display: none;
        }
        .ete-rate-shell .experience-table,
        .ete-rate-shell .experience-table tbody,
        .ete-rate-shell .experience-table tr,
        .ete-rate-shell .experience-table td {
            display: block;
            width: 100%;
        }
        .ete-rate-shell .experience-table tr {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 10px;
            padding: 10px;
        }
        .ete-rate-shell .experience-table td {
            border: 0;
            padding: 5px 0;
        }
        .ete-rate-shell .experience-table td::before {
            color: #6c757d;
            content: attr(data-label);
            display: block;
            font-size: .75rem;
            font-weight: 700;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .ete-rate-shell .rating-form button[type="submit"] {
            width: 100%;
        }
        .ete-rate-shell .save-status {
            display: block;
            margin: 10px 0 0 !important;
            text-align: center;
        }
    }
</style>

<div class="container-fluid ete-rate-shell">

    <div class="card card-info card-outline">

        <div class="ete-rate-header">
            <h3 class="card-title">
                <i class="fas fa-star"></i>
                Admin ETE Rating - {{ $ete->job->title ?? '' }}
                @if($ete->job && $ete->job->plantilla_item_no)
                    <small class="d-block text-muted">{{ $ete->job->plantilla_item_no }}</small>
                @endif
                <small class="d-block text-muted mt-1"><i class="fas fa-building mr-1"></i>{{ $ete->office->office_name ?? 'Department/Office not set' }}</small>
            </h3>

            <div class="ete-rate-actions">
                @if($copyableRatings->isNotEmpty())
                    <select id="ete-copy-rating-select" class="form-control form-control-sm ete-copy-select">
                        <option value="">Copy rating from previous position</option>
                        @foreach($copyableRatings as $copyableRating)
                            @php
                                $copyJob = optional($copyableRating->eteEvaluation)->job;
                                $positionLabel = $copyJob->title ?? optional($copyableRating->application)->position ?? 'Position';
                                $plantillaLabel = $copyJob && $copyJob->plantilla_item_no ? ' - '.$copyJob->plantilla_item_no : '';
                                $copyLabel = $positionLabel.$plantillaLabel;
                            @endphp
                            <option value="{{ $copyableRating->id }}"
                                    data-position="{{ $copyLabel }}"
                                    data-score="{{ number_format($copyableRating->total_score, 2) }}">
                                {{ $copyLabel }} ({{ number_format($copyableRating->total_score, 2) }} pts)
                            </option>
                        @endforeach
                    </select>
                @endif

                <a href="{{ route('eteEvaluationShow', $ete->id) }}" class="btn btn-light border"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="card-body">
            <div class="ete-rating-workspace">
                <aside class="ete-candidate-queue">
                    <div class="ete-queue-title">
                        <strong>Candidate Queue</strong>
                        <span class="badge badge-light border">{{ $candidateRatings->count() }}</span>
                    </div>
                    @foreach($candidateRatings as $candidateRating)
                        @php $candidate = $candidateRating->application; @endphp
                        <a class="ete-candidate-link {{ (int) $selectedRating->application_id === (int) $candidateRating->application_id ? 'active' : '' }}"
                           href="{{ route('eteAdminRating', ['id' => $ete->id, 'application_id' => $candidateRating->application_id]) }}">
                            <span class="ete-candidate-number">{{ $loop->iteration }}</span>
                            <span class="ete-candidate-meta">
                                <strong>{{ trim(($candidate->first_name ?? '').' '.($candidate->middle_name ?? '').' '.($candidate->last_name ?? '')) }}</strong>
                                <small>{{ $candidate->app_number ?? '' }} - {{ number_format($candidateRating->total_score, 2) }} pts</small>
                            </span>
                            <span class="ete-status-dot {{ $candidateRating->is_completed ? 'done' : 'pending' }}" title="{{ $candidateRating->is_completed ? 'Rated' : 'Pending' }}"></span>
                        </a>
                    @endforeach
                </aside>

                <main class="ete-form-panel">
                    <div class="alert alert-info"><i class="fas fa-user-shield"></i> Rate the selected candidate, save, then move to the next candidate. Evaluators are used only on generated report pages.</div>

            @foreach($ratings as $rating)
                @php
                    $app = $rating->application;

                    $savedExperience = [];

                    if (!empty($rating->experience_year_ratings)) {
                        $savedExperience = is_array($rating->experience_year_ratings)
                            ? $rating->experience_year_ratings
                            : json_decode($rating->experience_year_ratings, true);
                    }
                @endphp

                <div class="rating-panel mb-4"
                     id="rating-panel-{{ $app->id }}">

                    <div class="card">

                        <div class="card-header bg-success text-white">
                            <strong>
                                Candidate:
                                {{ $app->first_name }}
                                {{ $app->middle_name }}
                                {{ $app->last_name }}
                            </strong>
                            <br>
                            <small>{{ $app->app_number }}</small>
                        </div>

                        <div class="card-body py-2">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <label class="mb-0">Present Position</label>
                                    <input type="text"
                                           name="present_position"
                                           form="rating-form-{{ $rating->id }}"
                                           class="form-control"
                                           value="{{ $rating->present_position ?: $app->position }}"
                                           placeholder="Enter present position"
                                           maxlength="255">
                                </div>

                                <div class="col-sm-6">
                                    <label class="mb-0">Office/Department</label>
                                    <div class="form-control-plaintext">{{ $rating->college_department ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">

                            <form id="rating-form-{{ $rating->id }}" class="rating-form" data-id="{{ $rating->id }}">
                                @csrf

                                <input type="hidden" name="evaluate_id" value="{{ $rating->id }}">

                                {{-- Evaluation date follows the ETE schedule. --}}
                                <input type="hidden"
                                       name="evaluation_date"
                                       value="{{ optional($rating->evaluation_date ?? $ete->evaluation_date)->format('Y-m-d') }}">

                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Minimum Requirements (70 points)</strong>
                                        <small class="text-muted d-block">Each requirement marked Met earns 17.5 points.</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach([
                                                'education_met' => 'Education',
                                                'experience_met' => 'Experience',
                                                'eligibility_met' => 'Eligibility',
                                                'training_met' => 'Training',
                                            ] as $field => $label)
                                                <div class="col-sm-6 col-lg-3 mb-2">
                                                    <div class="border rounded p-2 h-100">
                                                        <strong class="d-block mb-2">{{ $label }}</strong>
                                                        <label class="mr-3 mb-0">
                                                            <input type="radio" name="{{ $field }}" value="1"
                                                                {{ $rating->{$field} === true ? 'checked' : '' }}> Met
                                                        </label>
                                                        <label class="mb-0">
                                                            <input type="radio" name="{{ $field }}" value="0"
                                                                {{ $rating->{$field} === false ? 'checked' : '' }}> Not met
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="col-6 col-lg-3">
                                        <div class="form-group">
                                            <label>Minimum</label>
                                            <input type="number"
                                                   class="form-control minimum-score font-weight-bold"
                                                   value="{{ $rating->minimum_requirement_score }}"
                                                   readonly>
                                            <small class="text-muted">Maximum: 70</small>
                                        </div>
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <div class="form-group">
                                            <label>Education Score</label>
                                            <input type="number"
                                                   name="education_score"
                                                   class="form-control education-score font-weight-bold"
                                                   value="{{ $rating->education_score }}"
                                                   readonly>
                                            <small class="text-muted">Maximum: 10</small>
                                        </div>
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <div class="form-group">
                                            <label>Training Score</label>
                                            <input type="number"
                                                   name="training_score"
                                                   class="form-control training-score font-weight-bold"
                                                   value="{{ $rating->training_score }}"
                                                   readonly>
                                            <small class="text-muted">Maximum: 5</small>
                                        </div>
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <div class="form-group">
                                            <label>Experience Score</label>
                                            <input type="number"
                                                   name="experience_score_display"
                                                   class="form-control experience-score font-weight-bold"
                                                   value="{{ $rating->experience_score }}"
                                                   readonly>
                                            <small class="text-muted">Auto-computed, Max: 15</small>
                                        </div>
                                    </div>

                                </div>

                                <hr>

                                @php
                                    $savedEducation = $rating->education_ratings ?? [];
                                    $savedTraining = $rating->training_ratings ?? [];
                                    $educationItems = [
                                        'additional_four_year_course' => ['Additional 4-year course completed', 2],
                                        'masteral_1_18' => ['1-18 masteral units', 1],
                                        'masteral_19_30' => ['19-30 masteral units', 2],
                                        'masters_degree' => ["Master's degree completed", 4],
                                        'doctoral_1_18' => ['1-18 doctoral units', 5],
                                        'doctoral_19_36' => ['19-36 doctoral units', 6],
                                        'doctoral_degree' => ['Doctoral degree completed', 10],
                                    ];
                                @endphp

                                <div class="row">
                                    <div class="col-lg-6">
                                        <h6 class="font-weight-bold"><i class="fas fa-graduation-cap"></i> Education Credits</h6>
                                        <div class="list-group mb-3">
                                            @foreach($educationItems as $key => [$label, $credit])
                                                <label class="list-group-item mb-0">
                                                    <input type="hidden" name="education_ratings[{{ $key }}]" value="0">
                                                    <input type="checkbox"
                                                           class="education-credit-item mr-2"
                                                           name="education_ratings[{{ $key }}]"
                                                           value="1"
                                                           data-credit="{{ $credit }}"
                                                           {{ !empty($savedEducation[$key]) ? 'checked' : '' }}>
                                                    {{ $label }}
                                                    <span class="badge badge-success float-right">{{ $credit }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <h6 class="font-weight-bold"><i class="fas fa-chalkboard-teacher"></i> Training Credits</h6>
                                        <div class="list-group mb-3">
                                            <label class="list-group-item mb-0">
                                                <input type="hidden" name="training_ratings[scholarship_grant]" value="0">
                                                <input type="checkbox"
                                                       class="training-credit-item mr-2"
                                                       name="training_ratings[scholarship_grant]"
                                                       value="1"
                                                       data-credit="3"
                                                       {{ !empty($savedTraining['scholarship_grant']) ? 'checked' : '' }}>
                                                Relevant study or scholarship grant
                                                <span class="badge badge-success float-right">3</span>
                                            </label>
                                            <label class="list-group-item mb-0">
                                                <input type="hidden" name="training_ratings[leadership_seminar]" value="0">
                                                <input type="checkbox"
                                                       class="training-credit-item mr-2"
                                                       name="training_ratings[leadership_seminar]"
                                                       value="1"
                                                       data-credit="2"
                                                       {{ !empty($savedTraining['leadership_seminar']) ? 'checked' : '' }}>
                                                Comparable leadership seminar
                                                <span class="badge badge-success float-right">2</span>
                                            </label>
                                            <div class="list-group-item">
                                                <label>Relevant in-service training hours</label>
                                                <input type="number"
                                                       class="form-control training-hours"
                                                       name="training_ratings[relevant_hours]"
                                                       min="0"
                                                       step="1"
                                                       value="{{ $savedTraining['relevant_hours'] ?? 0 }}">
                                                <small class="text-muted">1 point for every completed 50 hours.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="font-weight-bold">
                                    <i class="fas fa-briefcase"></i>
                                    Experience Rating
                                </h6>

                                <div class="table-responsive">
                                    @php $levelOneYears = array_slice(array_reverse($years), 0, 5); @endphp
                                    <div class="alert alert-light border py-2 small">
                                        <strong>Formula:</strong> Credit = (Length of Experience in months / 12) × Level.
                                        The five oldest years use Level 1; every newer year uses Level 2.
                                        Experience equals total credit (maximum 15).
                                    </div>
                                    <table class="table table-bordered table-sm bg-white experience-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 15%;">Year</th>
                                                <th style="width: 30%;">Length (months)</th>
                                                <th style="width: 25%;">Level of Experience</th>
                                                <th style="width: 30%;">Credit</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach($years as $year)
                                                <tr>
                                                    <td data-label="Year" class="align-middle font-weight-bold">
                                                        {{ $year }}
                                                    </td>

                                                    <td data-label="Length of Experience">
                                                        <input type="number"
                                                               name="experience_years[{{ $year }}][length]"
                                                               class="form-control form-control-sm experience-length"
                                                               min="0" max="12" step="0.01"
                                                               value="{{ $savedExperience[$year]['length'] ?? '' }}"
                                                               placeholder="Months (0-12)">
                                                    </td>

                                                    <td data-label="Level of Experience">
                                                        <input type="number"
                                                               name="experience_years[{{ $year }}][level]"
                                                               class="form-control form-control-sm"
                                                               value="{{ in_array($year, $levelOneYears) ? 1 : 2 }}" readonly>
                                                    </td>

                                                    <td data-label="Credit">
                                                        <input type="number"
                                                               name="experience_years[{{ $year }}][credit]"
                                                               class="form-control form-control-sm experience-credit"
                                                               value="{{ $savedExperience[$year]['credit'] ?? 0 }}" readonly>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">

                                    <div class="col-md-4 offset-md-8">
                                        <div class="form-group">
                                            <label>Total Rating (out of 100)</label>
                                            <input type="number"
                                                   class="form-control total-score font-weight-bold"
                                                   value="{{ $rating->total_score }}"
                                                   readonly>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="remarks"
                                              class="form-control"
                                              rows="3"
                                              placeholder="Remarks...">{{ $rating->remarks }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Rating
                                </button>

                                <span class="save-status text-success ml-2">
                                    <i class="fas fa-check-circle"></i> Ready
                                </span>

                            </form>

                        </div>

                    </div>

                </div>
            @endforeach

                    <div class="ete-candidate-nav">
                        @if($previousRating)
                            <a class="btn btn-light border" href="{{ route('eteAdminRating', ['id' => $ete->id, 'application_id' => $previousRating->application_id]) }}"><i class="fas fa-arrow-left"></i> Previous</a>
                        @else
                            <span></span>
                        @endif
                        <span class="text-muted small">Candidate {{ $candidateRatings->search(fn ($item) => $item->id === $selectedRating->id) + 1 }} of {{ $candidateRatings->count() }}</span>
                        @if($nextRating)
                            <a class="btn btn-success" href="{{ route('eteAdminRating', ['id' => $ete->id, 'application_id' => $nextRating->application_id]) }}">Next Candidate <i class="fas fa-arrow-right"></i></a>
                        @elseif(auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true))
                            <a class="btn btn-warning" href="{{ route('eteConsolidatedScreen', $ete->id) }}">View Ranking <i class="fas fa-ranking-star"></i></a>
                        @else
                            <span></span>
                        @endif
                    </div>
                </main>
            </div>

        </div>

    </div>

</div>

@if($copyableRatings->isNotEmpty())
<div class="modal fade" id="eteCopyRatingModal" tabindex="-1" role="dialog" aria-labelledby="eteCopyRatingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="{{ route('eteCopyPreviousRating', $ete->id) }}" class="modal-content">
            @csrf
            <input type="hidden" name="target_rating_id" value="{{ $selectedRating->id }}">
            <input type="hidden" name="source_rating_id" id="eteCopySourceRatingId">

            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="eteCopyRatingModalLabel">
                    <i class="fas fa-copy"></i> Copy Rating Here?
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <strong id="eteCopyPositionText">Previous position</strong>
                    <div class="text-muted small">Saved total score: <span id="eteCopyScoreText">0.00</span> pts</div>
                </div>
                <p class="mb-0">
                    This will copy the selected previous ETE rating into the current applicant form and overwrite the current saved scores.
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {

    function computeFormTotal(form) {
        let minimum = 0;

        $.each(['education_met', 'experience_met', 'eligibility_met', 'training_met'], function (_, field) {
            let selected = form.find('input[name="' + field + '"]:checked');
            if (selected.length && selected.val() === '1') minimum += 17.5;
        });

        let education = 0;
        form.find('.education-credit-item:checked').each(function () {
            education += parseFloat($(this).data('credit')) || 0;
        });
        education = Math.min(10, education);

        let training = 0;
        form.find('.training-credit-item:checked').each(function () {
            training += parseFloat($(this).data('credit')) || 0;
        });
        training += Math.floor((parseFloat(form.find('.training-hours').val()) || 0) / 50);
        training = Math.min(5, training);

        let experience = 0;
        const experienceRows = form.find('.experience-length');
        experienceRows.each(function (index) {
            const input = $(this);
            const rawMonths = parseFloat(input.val()) || 0;
            const months = Math.min(12, Math.max(0, rawMonths));
            if (input.val() !== '' && rawMonths !== months) input.val(months);
            const row = $(this).closest('tr');
            const experienceLevel = parseFloat(row.find('input[name$="[level]"]').val()) || 1;
            const credit = (months / 12) * experienceLevel;
            row.find('.experience-credit').val(credit.toFixed(2));
            experience += credit;
        });

        // Total experience score must not exceed 15
        if (experience > 15) {
            experience = 15;
        }

        let total = minimum + education + training + experience;

        form.find('.minimum-score').val(minimum.toFixed(2));
        form.find('.education-score').val(education.toFixed(2));
        form.find('.training-score').val(training.toFixed(2));
        form.find('.experience-score').val(experience.toFixed(2));
        form.find('.total-score').val(total.toFixed(2));
    }

    function setStatus(form, icon, text, className) {
        form.find('.save-status')
            .removeClass('text-success text-muted text-danger')
            .addClass(className)
            .html('<i class="fas ' + icon + '"></i> ' + text);
    }

    function saveForm(form, silent) {
        let btn = form.find('button[type="submit"]');

        computeFormTotal(form);
        setStatus(form, 'fa-spinner fa-spin', silent ? 'Autosaving...' : 'Saving...', 'text-muted');

        if (!silent) {
            btn.prop('disabled', true)
               .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        }

        $.ajax({
            url: "{{ route('eteRatingUpdateAjax') }}",
            type: "POST",
            data: form.serialize(),
            success: function (response) {
                form.find('.minimum-score').val(response.minimum_requirement_score);
                form.find('input[name="education_score"]').val(response.education_score);
                form.find('input[name="training_score"]').val(response.training_score);
                form.find('.experience-score').val(response.experience_score);
                form.find('.total-score').val(response.total_score);
                $.each(response.experience_rows || {}, function (year, row) {
                    const lengthInput = form.find('[name="experience_years[' + year + '][length]"]');
                    lengthInput.closest('tr').find('input[name$="[level]"]').val(row.level);
                    lengthInput.closest('tr').find('.experience-credit').val(Number(row.credit).toFixed(2));
                });
                setStatus(form, 'fa-check-circle', 'Saved', 'text-success');
            },
            error: function () {
                setStatus(form, 'fa-exclamation-circle', 'Unable to save', 'text-danger');

                if (!silent) {
                    alert('Unable to save rating. Please check the form.');
                }
            },
            complete: function () {
                if (!silent) {
                    btn.prop('disabled', false)
                       .html('<i class="fas fa-save"></i> Save Rating');
                }
            }
        });
    }

    function scheduleAutosave(form) {
        clearTimeout(form.data('autosaveTimer'));
        setStatus(form, 'fa-clock', 'Pending autosave...', 'text-muted');

        form.data('autosaveTimer', setTimeout(function () {
            saveForm(form, true);
        }, 250));
    }

    $(document).on('input change', '.education-credit-item, .training-credit-item, .training-hours, .experience-length, .rating-form input[type="radio"]', function () {
        let form = $(this).closest('form');
        computeFormTotal(form);
        scheduleAutosave(form);
    });

    $(document).on('change input', '.rating-form input[type="text"], .rating-form input[type="date"], .rating-form textarea, .rating-form input[name$="[level]"]', function () {
        scheduleAutosave($(this).closest('form'));
    });

    $(document).on('change input', 'input[name="present_position"]', function () {
        const form = $('#' + $(this).attr('form'));
        scheduleAutosave(form);
    });

    $(document).on('submit', '.rating-form', function (e) {
        e.preventDefault();

        let form = $(this);
        clearTimeout(form.data('autosaveTimer'));
        saveForm(form, false);
    });

    $('.rating-form').each(function () { computeFormTotal($(this)); });

    $('#ete-copy-rating-select').on('change', function () {
        const selected = $(this).find('option:selected');
        const sourceRatingId = selected.val();

        if (!sourceRatingId) {
            return;
        }

        $('#eteCopySourceRatingId').val(sourceRatingId);
        $('#eteCopyPositionText').text(selected.data('position') || 'Previous position');
        $('#eteCopyScoreText').text(selected.data('score') || '0.00');
        $('#eteCopyRatingModal').modal('show');
    });

    $('#eteCopyRatingModal').on('hidden.bs.modal', function () {
        $('#ete-copy-rating-select').val('');
    });

});
</script>
@endsection
