@extends('layouts.master')

@section('body')
<style>
    .ete-manage { --ete-green:#198754; --ete-soft:#f5f8f6; }
    .ete-manage .card { border:0; border-radius:18px; box-shadow:0 9px 30px rgba(15,23,42,.07); }
    .ete-head { align-items:center; display:flex; flex-wrap:wrap; gap:14px; justify-content:space-between; padding:20px; }
    .ete-head h4 { font-weight:800; }
    .report-person { background:#f8faf9; border:1px solid #dfe7e2; border-radius:999px; display:inline-block; margin:3px; padding:6px 11px; }
    .candidate-list { display:grid; gap:11px; }
    .candidate-row { align-items:center; background:#fff; border:1px solid #e4e9e6; border-radius:16px; display:grid; gap:14px; grid-template-columns:minmax(220px,1.4fr) repeat(4,minmax(78px,.55fr)) auto; padding:14px 16px; transition:.16s ease; }
    .candidate-row:hover { border-color:#9bcbb0; box-shadow:0 9px 22px rgba(25,135,84,.09); transform:translateY(-1px); }
    .candidate-main { align-items:center; display:flex; gap:12px; min-width:0; }
    .candidate-avatar { align-items:center; background:linear-gradient(145deg,#edf5f0,#dcebe2); border-radius:13px; color:#3f7959; display:flex; flex:0 0 46px; font-weight:800; height:46px; justify-content:center; }
    .candidate-name { min-width:0; }
    .candidate-name strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .candidate-name small { color:#7a8780; }
    .status-pill { border-radius:999px; display:inline-block; font-size:.68rem; font-weight:800; margin-left:6px; padding:3px 7px; text-transform:uppercase; }
    .status-pill.done { background:#ddf4e7; color:#187747; }
    .status-pill.pending { background:#eef0ef; color:#68716c; }
    .score-box { background:var(--ete-soft); border-radius:11px; padding:8px 6px; text-align:center; }
    .score-box small { color:#78847d; display:block; font-size:.63rem; font-weight:700; text-transform:uppercase; }
    .score-box strong { color:#23332a; display:block; font-size:1rem; }
    .score-box.total { background:#e8f6ee; }
    .score-box.total strong { color:var(--ete-green); font-size:1.12rem; }
    .candidate-actions { display:flex; gap:7px; justify-content:flex-end; }
    .candidate-actions .btn { align-items:center; border-radius:10px; display:inline-flex; height:38px; justify-content:center; width:40px; }
    .list-labels { color:#748078; display:grid; font-size:.7rem; font-weight:800; gap:14px; grid-template-columns:minmax(220px,1.4fr) repeat(4,minmax(78px,.55fr)) auto; padding:0 16px; text-transform:uppercase; }
    .list-labels span:not(:first-child) { text-align:center; }
    .empty-state { color:#748078; padding:45px 20px; text-align:center; }
    @media(max-width:991.98px) {
        .list-labels { display:none; }
        .candidate-row { grid-template-columns:repeat(4,1fr); }
        .candidate-main { grid-column:1/-1; }
        .candidate-actions { grid-column:1/-1; }
        .candidate-actions .btn { flex:1; width:auto; }
    }
    @media(max-width:575.98px) {
        .ete-manage { padding-left:8px; padding-right:8px; }
        .ete-head { align-items:stretch; flex-direction:column; }
        .ete-head .btn { width:100%; }
        .candidate-row { gap:8px; padding:12px; }
        .score-box { padding:7px 2px; }
        .score-box small { font-size:.55rem; }
    }
</style>

<div class="container-fluid ete-manage">
    <div class="card mb-3">
        <div class="ete-head">
            <div>
                <small class="text-muted">Admin ETE Evaluation</small>
                <h4 class="mb-0">{{ $ete->job->title ?? 'Position' }}</h4>
                @if($ete->job && $ete->job->plantilla_item_no)
                    <div class="text-muted small">{{ $ete->job->plantilla_item_no }}</div>
                @endif
                <div class="text-muted small mt-1"><i class="fas fa-building mr-1"></i>{{ $ete->office->office_name ?? 'Department/Office not set' }}</div>
            </div>
            <div>
                <a href="{{ route('eteEvaluationList') }}" class="btn btn-light border"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="{{ route('eteAdminRating', $ete->id) }}" class="btn btn-success"><i class="fas fa-list-check"></i> Rating Queue</a>
                @if(auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true))
                    <a href="{{ route('eteConsolidatedScreen', $ete->id) }}" target="_blank" class="btn btn-warning"><i class="fas fa-ranking-star"></i> Ranking</a>
                @endif
            </div>
        </div>
        <div class="border-top p-3">
            <strong>Official report pages: {{ $ete->evaluators->count() }}</strong>
            <div class="text-muted small mb-2">One identical rated form is generated for each report evaluator.</div>
            @foreach($ete->evaluators as $panel)
                <span class="report-person"><i class="fas fa-file-signature mr-1"></i>{{ $panel->employee->lname ?? '' }}, {{ $panel->employee->fname ?? '' }}</span>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div><h5 class="font-weight-bold mb-0">Candidates</h5><small class="text-muted">Scores and report actions are available directly in each row.</small></div>
                <span class="badge badge-light border p-2">{{ $applicants->count() }} total</span>
            </div>

            <div class="list-labels mb-2">
                <span>Candidate</span><span>Education</span><span>Training</span><span>Experience</span><span>Total</span><span>Actions</span>
            </div>

            <div class="candidate-list">
                @php
                    $orderedApplicants = $applicants->sortBy(function ($applicant) {
                        return strtolower(trim(($applicant->last_name ?? '').' '.($applicant->first_name ?? '').' '.($applicant->middle_name ?? '')));
                    })->values();
                @endphp

                @forelse($orderedApplicants as $applicant)
                    @php
                        $rating = $ratingsByApplication->get($applicant->id);
                        $isRated = $rating && (
                            $rating->education_met !== null || $rating->experience_met !== null ||
                            $rating->eligibility_met !== null || $rating->training_met !== null ||
                            (float) $rating->total_score > 0 || !empty($rating->remarks)
                        );
                        $middleInitial = trim((string) $applicant->middle_name) !== ''
                            ? strtoupper(substr(trim($applicant->middle_name), 0, 1)).'.'
                            : '';
                        $fullName = trim(($applicant->last_name ?? '').', '.($applicant->first_name ?? '').' '.$middleInitial);
                    @endphp
                    <article class="candidate-row">
                        <div class="candidate-main">
                            <span class="candidate-avatar">{{ strtoupper(substr($applicant->first_name ?? 'C', 0, 1).substr($applicant->last_name ?? '', 0, 1)) }}</span>
                            <div class="candidate-name">
                                <strong>{{ $fullName }}</strong>
                                <small>{{ $applicant->app_number }}</small>
                                <span class="status-pill {{ $isRated ? 'done' : 'pending' }}">{{ $isRated ? 'Rated' : 'Pending' }}</span>
                            </div>
                        </div>
                        <div class="score-box"><small>Education</small><strong>{{ number_format($rating->education_score ?? 0, 2) }}</strong></div>
                        <div class="score-box"><small>Training</small><strong>{{ number_format($rating->training_score ?? 0, 2) }}</strong></div>
                        <div class="score-box"><small>Experience</small><strong>{{ number_format($rating->experience_score ?? 0, 2) }}</strong></div>
                        <div class="score-box total"><small>Total</small><strong>{{ number_format($rating->total_score ?? 0, 2) }}</strong></div>
                        <div class="candidate-actions">
                            <a href="{{ route('eteAdminRating', ['id' => $ete->id, 'application_id' => $applicant->id]) }}"
                               class="btn btn-outline-success" title="Rate {{ $fullName }}" aria-label="Rate {{ $fullName }}">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="{{ route('eteApplicantEvaluationPdf', [$ete->id, $applicant->id]) }}" target="_blank"
                               class="btn btn-outline-danger" title="Generate Official ETE PDF" aria-label="Generate Official ETE PDF for {{ $fullName }}">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-outline-primary ete-schedule-btn"
                                    data-app-id="{{ $applicant->id }}"
                                    data-app-name="{{ $fullName }}"
                                    data-app-number="{{ $applicant->app_number }}"
                                    data-toggle="modal"
                                    data-target="#eteScheduleModal"
                                    title="Set Interview Schedule"
                                    aria-label="Set Interview Schedule for {{ $fullName }}">
                                <i class="fas fa-calendar-check"></i>
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><i class="fas fa-users fa-2x mb-3"></i><div>No candidates available.</div></div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eteScheduleModal" tabindex="-1" role="dialog" aria-labelledby="eteScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow rounded">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="eteScheduleModalLabel">
                    <i class="fas fa-calendar-check mr-2"></i> Set Interview Schedule
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" action="{{ route('updateStatus') }}">
                @csrf
                <input type="hidden" name="id" id="eteScheduleAppId">
                <input type="hidden" name="status" value="2">

                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <strong id="eteScheduleApplicantName">Applicant</strong>
                        <div class="text-muted small" id="eteScheduleApplicantNumber"></div>
                    </div>

                    <div class="form-group">
                        <label for="ete_interview_datetime">Interview Schedule <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="ete_interview_datetime" name="interview_datetime" class="form-control" required>
                        <small class="form-text text-muted">This will mark the applicant as Qualified / Ready for Interview and send the interview email.</small>
                    </div>

                    <div class="form-group">
                        <label for="ete_venue">Venue <span class="text-danger">*</span></label>
                        <textarea id="ete_venue" name="venue" class="form-control" rows="2" required>Conference Room, Admin Building/Bidding Room/Accreditation/ Mini Hotel</textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Confirm & Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ete-schedule-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('eteScheduleAppId').value = this.dataset.appId || '';
            document.getElementById('eteScheduleApplicantName').textContent = this.dataset.appName || 'Applicant';
            document.getElementById('eteScheduleApplicantNumber').textContent = this.dataset.appNumber || '';
        });
    });
});
</script>
@endsection
