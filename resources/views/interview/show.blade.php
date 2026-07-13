@extends('layouts.master')

@section('body')
<style>
    .interview-manage .card { border:0; border-radius:18px; box-shadow:0 9px 30px rgba(15,23,42,.07); }
    .interview-manage-head { align-items:center; display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; padding:20px; }
    .interview-manage-head h4 { font-weight:800; margin:0; }
    .cast-pill { border-radius:999px; display:inline-block; font-size:.72rem; font-weight:800; padding:4px 9px; text-transform:uppercase; }
    .cast-pill.on { background:#dcfce7; color:#166534; }
    .cast-pill.off { background:#f1f5f9; color:#64748b; }
    .progress-mini { background:#edf2f7; border-radius:999px; height:8px; overflow:hidden; }
    .progress-mini span { background:#16a34a; display:block; height:100%; }
    .panel-link { border-radius:999px; margin:2px; }
    .panel-pill { border:1px solid; border-radius:999px; display:inline-block; font-size:.78rem; font-weight:700; margin:2px; padding:5px 10px; }
    .panel-pill.done { background:#f0fdf4; border-color:#16a34a; color:#166534; }
    .panel-pill.pending { background:#fef2f2; border-color:#dc2626; color:#991b1b; }
    .panel-assignment { align-items:center; display:inline-flex; gap:2px; margin:2px; }
    .panel-assignment .panel-link,
    .panel-assignment .panel-pill { margin:0; }
    .panel-remove { border-radius:999px; height:28px; line-height:1; padding:0; width:28px; }
    .action-cell { min-width:190px; width:190px; }
    .action-stack { align-items:center; display:flex; flex-direction:row; gap:6px; justify-content:center; }
    .action-stack form { margin:0; }
    .action-stack .btn { min-width:74px; }
    .action-stack .panel-add-btn { min-width:74px; }
</style>

<div class="container-fluid interview-manage">
    <div class="card mb-3">
        <div class="interview-manage-head">
            <div>
                <small class="text-muted">Interview Assessment from ETE #{{ $interview->ete_id }}</small>
                <h4>{{ $interview->job->title ?? 'Position' }}</h4>
                @if($interview->job && $interview->job->plantilla_item_no)
                    <div class="text-muted small">{{ $interview->job->plantilla_item_no }}</div>
                @endif
                <div class="text-muted small"><i class="fas fa-building mr-1"></i>{{ $interview->eteEvaluation->office->office_name ?? 'Office not set' }}</div>
            </div>
            <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                @if(auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true))
                    <a href="{{ route('interviewConsolidatedScreen', $interview->id) }}" target="_blank" class="btn btn-warning">
                        <i class="fas fa-ranking-star"></i> Ranking
                    </a>
                    <a href="{{ route('interviewSummaryRatingPdf', $interview->id) }}" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Summary Rating
                    </a>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#panelMembersModal">
                        <i class="fas fa-users"></i> Panel
                    </button>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#panelProgressModal">
                        <i class="fas fa-chart-line"></i> Scoring Progress
                    </button>
                @endif
                <a href="{{ route('interviewEvaluationList') }}" class="btn btn-light border"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="font-weight-bold mb-0">Candidates</h5>
                    <small class="text-muted">Scores and report actions are available directly in each row.</small>
                </div>
                <span class="badge badge-light border p-2">{{ $eligibleApplicants->count() }} applicants</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Applicant</th>
                            <th>Contact</th>
                            <th class="text-center">Cast Status</th>
                            <th class="text-center">Panel Progress</th>
                            <th>Interview Panel</th>
                            <th class="text-center action-cell">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $orderedEligibleApplicants = $eligibleApplicants->sortBy(function ($applicant) {
                                return strtolower(trim(($applicant->last_name ?? '').' '.($applicant->first_name ?? '').' '.($applicant->middle_name ?? '')));
                            })->values();
                        @endphp

                        @forelse($orderedEligibleApplicants as $applicant)
                            @php
                                $row = $interview->applicants->firstWhere('application_id', $applicant->id);
                                $isCast = $row && $row->is_cast;
                                $ratings = $ratingsByApplication->get($applicant->id, collect());
                                $completedRatings = $completedRatingsByApplication->get($applicant->id, collect());
                                $assignedPanels = $panelEmployeesByApplication->get($applicant->id, collect());
                                $assignedPanelIds = $assignedPanels->pluck('id');
                                $availablePanelEmployees = $employees->whereNotIn('id', $assignedPanelIds);
                                $submitted = $completedRatings->count();
                                $panelCount = max(1, $assignedPanels->count());
                                $percent = min(100, round(($submitted / $panelCount) * 100));
                                $middleInitial = trim((string) $applicant->middle_name) !== ''
                                    ? strtoupper(substr(trim($applicant->middle_name), 0, 1)).'.'
                                    : '';
                                $displayName = trim(($applicant->last_name ?? '').', '.($applicant->first_name ?? '').' '.$middleInitial);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $displayName }}</strong>
                                    <small class="d-block text-muted">{{ $applicant->app_number }}</small>
                                </td>
                                <td>
                                    {{ $applicant->email }}
                                    <small class="d-block text-muted">{{ $applicant->mobile }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="cast-pill {{ $isCast ? 'on' : 'off' }}">{{ $isCast ? 'Cast' : 'Not cast' }}</span>
                                </td>
                                <td class="text-center">
                                    <strong>{{ $submitted }}/{{ $panelCount }}</strong>
                                    <div class="progress-mini mt-1"><span style="width: {{ $percent }}%"></span></div>
                                </td>
                                <td>
                                    @if($isCast)
                                        @foreach($assignedPanels as $panelEmployee)
                                            @php
                                                $panelRating = $ratings->firstWhere('panel_employee_id', $panelEmployee->id);
                                                $panelFinished = $panelRating && $completedRatings->contains('id', $panelRating->id);
                                            @endphp
                                            <div class="panel-assignment">
                                                <a href="{{ route('interviewRatingForm', ['id' => $interview->id, 'applicationId' => $applicant->id, 'panel_id' => $panelEmployee->id]) }}"
                                                   class="btn btn-sm {{ $panelFinished ? 'btn-outline-success' : 'btn-outline-danger' }} panel-link"
                                                   title="{{ $panelFinished ? 'Rating complete' : 'Not yet finished rating' }}">
                                                    <i class="fas {{ $panelFinished ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i> {{ $panelEmployee->lname ?? 'Panel' }}
                                                </a>
                                                @if($assignedPanels->count() > 1)
                                                    <button type="button"
                                                            class="btn btn-sm btn-light border text-danger panel-remove"
                                                            title="Remove panel for this applicant"
                                                            data-remove-panel-button
                                                            data-applicant-name="{{ e($displayName) }}"
                                                            data-panel-name="{{ e(trim(($panelEmployee->lname ?? '').', '.($panelEmployee->fname ?? ''))) }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('interviewCandidatePanelRemove', [$interview->id, $applicant->id, $panelEmployee->id]) }}" class="d-none">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        @foreach($assignedPanels as $panelEmployee)
                                            @php
                                                $panelRating = $ratings->firstWhere('panel_employee_id', $panelEmployee->id);
                                                $panelFinished = $panelRating && $completedRatings->contains('id', $panelRating->id);
                                            @endphp
                                            <div class="panel-assignment">
                                                <span class="panel-pill {{ $panelFinished ? 'done' : 'pending' }}"
                                                      title="{{ $panelFinished ? 'Rating complete' : 'Not yet finished rating' }}">
                                                    <i class="fas {{ $panelFinished ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i> {{ $panelEmployee->lname ?? 'Panel' }}
                                                </span>
                                                @if($assignedPanels->count() > 1)
                                                    <button type="button"
                                                            class="btn btn-sm btn-light border text-danger panel-remove"
                                                            title="Remove panel for this applicant"
                                                            data-remove-panel-button
                                                            data-applicant-name="{{ e($displayName) }}"
                                                            data-panel-name="{{ e(trim(($panelEmployee->lname ?? '').', '.($panelEmployee->fname ?? ''))) }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('interviewCandidatePanelRemove', [$interview->id, $applicant->id, $panelEmployee->id]) }}" class="d-none">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center action-cell">
                                    <div class="action-stack">
                                        @if($availablePanelEmployees->isNotEmpty())
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success panel-add-btn"
                                                    title="Add panel for this applicant"
                                                    data-toggle="modal"
                                                    data-target="#addPanelModal{{ $applicant->id }}">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        @endif
                                        @if($isCast)
                                            <form method="POST" action="{{ route('interviewCandidateUncast', [$interview->id, $applicant->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye-slash"></i> Uncast</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('interviewCandidateCast', [$interview->id, $applicant->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success"><i class="fas fa-bullhorn"></i> Cast</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No applicants with status Qualified / Ready for Interview.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true))
<div class="modal fade" id="panelMembersModal" tabindex="-1" role="dialog" aria-labelledby="panelMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="panelMembersModalLabel">
                    <i class="fas fa-users"></i> Interview Panel
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Click the crown to set a member as chairman.</p>
                <div>
                    @foreach($interview->panels as $panel)
                        <span class="d-inline-flex align-items-center mb-1 mr-1" style="gap:4px;">
                            <span class="badge {{ $panel->is_chairman ? 'badge-warning' : 'badge-info' }} p-2">
                                {{ $panel->employee->lname ?? '' }}, {{ $panel->employee->fname ?? '' }}
                                @if($panel->is_chairman)
                                    <i class="fas fa-crown ml-1" title="Chairman"></i>
                                @endif
                            </span>
                            @if(!$panel->is_chairman)
                                <form method="POST" action="{{ route('interviewPanelSetChairman', [$interview->id, $panel->emp_id]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Set Chairman">
                                        <i class="fas fa-crown"></i>
                                    </button>
                                </form>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="panelProgressModal" tabindex="-1" role="dialog" aria-labelledby="panelProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable panel-progress-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="panelProgressModalLabel">
                    <i class="fas fa-chart-line"></i> Scoring Progress
                    <span class="text-white-50" style="font-size:.8rem;">(all positions the cast applicant applied to)</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center justify-content-end mb-2">
                    <small class="text-muted" id="panelProgressUpdated"></small>
                </div>
                <div id="panelProgressBody">
                    <div class="progress-empty text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Loading progress…</div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="mr-auto small text-muted">
                    <i class="fas fa-check-circle text-success"></i> Finished
                    &nbsp;<i class="fas fa-hourglass-half text-danger"></i> Still scoring
                </span>
                <button type="button" class="btn btn-light border" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .panel-progress-dialog { max-width:90vw; margin:1.75rem auto; }
    .panel-progress-dialog .modal-content { height:90vh; }
    #panelProgressBody { display:grid; gap:12px; grid-template-columns:repeat(4, minmax(0, 1fr)); align-items:start; }
    #panelProgressBody .progress-empty,
    #panelProgressBody .progress-applicant-header { grid-column:1 / -1; }
    #panelProgressBody .progress-applicant-header { border-bottom:2px solid #16a34a; margin-bottom:2px; padding-bottom:6px; }
    #panelProgressBody .progress-card { border:1px solid #e5e7eb; border-radius:12px; padding:10px 12px; }
    #panelProgressBody .progress-card.done { border-color:#16a34a; background:#f0fdf4; }
    #panelProgressBody .panel-chip { border-radius:999px; display:inline-block; font-size:.68rem; font-weight:700; margin:2px 2px 0 0; padding:2px 8px; }
    #panelProgressBody .panel-chip.done { background:#dcfce7; color:#166534; }
    #panelProgressBody .panel-chip.pending { background:#fef2f2; color:#991b1b; }
    @media (max-width:1399.98px) { #panelProgressBody { grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    @media (max-width:991.98px)  { #panelProgressBody { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (max-width:575.98px)  { #panelProgressBody { grid-template-columns:1fr; } }
</style>
@endif

@foreach($orderedEligibleApplicants as $applicant)
    @php
        $assignedPanelEmployees = $panelEmployeesByApplication->get($applicant->id, collect());
        $assignedPanelIds = $assignedPanelEmployees->pluck('id');
        $availablePanelEmployees = $employees->whereNotIn('id', $assignedPanelIds);
    @endphp
    @if($availablePanelEmployees->isNotEmpty())
        <div class="modal fade" id="addPanelModal{{ $applicant->id }}" tabindex="-1" role="dialog" aria-labelledby="addPanelModalLabel{{ $applicant->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="POST" action="{{ route('interviewCandidatePanelAdd', [$interview->id, $applicant->id]) }}" class="modal-content">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="addPanelModalLabel{{ $applicant->id }}">
                            <i class="fas fa-user-plus"></i> Add Interview Panel
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <strong>{{ trim(($applicant->last_name ?? '').', '.($applicant->first_name ?? '')) }}</strong>
                            <small class="d-block text-muted">{{ $applicant->app_number }}</small>
                        </div>
                        <div class="form-group mb-0">
                            <label>Select Panel</label>
                            <select name="panel_employee_id" class="form-control panel-select" data-dropdown-parent="#addPanelModal{{ $applicant->id }}" required>
                                <option value="">Search panel employee</option>
                                @foreach($availablePanelEmployees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->lname }}, {{ $employee->fname }} {{ $employee->mname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Panel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endforeach

<script>
(function waitForPanelSelect() {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
        window.setTimeout(waitForPanelSelect, 100);
        return;
    }

    const $ = window.jQuery;

    $('.modal').on('shown.bs.modal', function () {
        const $select = $(this).find('.panel-select');

        if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
            return;
        }

        $select.select2({
            dropdownParent: $(this),
            width: '100%',
            placeholder: 'Search panel employee'
        });
    });

    $(document).on('click', '[data-remove-panel-button]', function () {
        const button = this;
        const form = button.nextElementSibling;
        const applicantName = button.dataset.applicantName || 'this applicant';
        const panelName = button.dataset.panelName || 'this panel member';

        if (!form) {
            return;
        }

        Swal.fire({
            title: 'Remove interview panel?',
            html: '<div class="text-left">' +
                '<p class="mb-2">This will remove <strong>' + panelName + '</strong> from <strong>' + applicantName + '</strong>.</p>' +
                '<p class="mb-0 text-danger font-weight-bold">Their rating for this applicant only will be deleted.</p>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove panel',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
})();
</script>

@if(auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('panelProgressModal');
    const body = document.getElementById('panelProgressBody');
    const updated = document.getElementById('panelProgressUpdated');
    if (!modal || !body) return;

    const endpoint = "{{ route('interviewPanelProgress', $interview->id) }}";
    let pollTimer = null;
    let fetching = false;

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Each position renders as its own card in the grid (4 per row).
    function renderPosition(pos) {
        const plantilla = pos.plantilla ? '<div class="text-muted small">' + esc(pos.plantilla) + '</div>' : '';

        if (!pos.setup) {
            return '<div class="progress-card">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                    '<div><strong>' + esc(pos.position) + '</strong>' + plantilla + '</div>' +
                    '<span class="badge badge-secondary">Not set up</span>' +
                '</div></div>';
        }

        const chips = (pos.panels || []).map(function (p) {
            const cls = p.finished ? 'done' : 'pending';
            const icon = p.finished ? 'fa-check-circle' : 'fa-hourglass-half';
            return '<span class="panel-chip ' + cls + '"><i class="fas ' + icon + '"></i> ' + esc(p.name) + '</span>';
        }).join('');

        const complete = pos.total > 0 && pos.completed === pos.total;
        const countCls = complete ? 'badge-success' : 'badge-danger';

        return '<div class="progress-card' + (complete ? ' done' : '') + '">' +
            '<div class="d-flex justify-content-between align-items-start">' +
                '<div><strong>' + esc(pos.position) + '</strong>' + plantilla + '</div>' +
                '<span class="badge ' + countCls + '">' + pos.completed + '/' + pos.total + ' rated</span>' +
            '</div>' +
            '<div class="mt-1">' + (chips || '<span class="text-muted small">No panel assigned.</span>') + '</div>' +
        '</div>';
    }

    function render(data) {
        const applicants = data.applicants || [];
        if (updated) {
            updated.textContent = data.generated_at ? ('Updated ' + data.generated_at) : '';
        }

        if (!applicants.length) {
            body.innerHTML = '<div class="progress-empty text-center text-muted py-4">No applicant is currently cast for this interview.</div>';
            return;
        }

        body.innerHTML = applicants.map(function (a) {
            const summaryCls = a.all_done ? 'badge-success' : 'badge-warning';

            // Full-width header for the cast applicant...
            const header = '<div class="progress-applicant-header">' +
                '<div class="d-flex justify-content-between align-items-center">' +
                    '<div><strong>' + esc(a.name) + '</strong>' +
                        (a.app_number ? '<div class="text-muted small">' + esc(a.app_number) + '</div>' : '') +
                    '</div>' +
                    '<span class="badge ' + summaryCls + '">' + a.done_positions + '/' + a.total_positions + ' positions done</span>' +
                '</div></div>';

            // ...followed by one card per position, laid out 4 per row.
            const positions = (a.positions || []).map(renderPosition).join('');

            return header + positions;
        }).join('');
    }

    function load() {
        if (fetching) return;
        fetching = true;
        fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {
                body.innerHTML = '<div class="progress-empty text-center text-danger py-4">Unable to load progress. Retrying…</div>';
            })
            .finally(function () { fetching = false; });
    }

    if (window.jQuery) {
        window.jQuery(modal)
            .on('shown.bs.modal', function () {
                load();
                window.clearInterval(pollTimer);
                pollTimer = window.setInterval(load, 1500);
            })
            .on('hidden.bs.modal', function () {
                window.clearInterval(pollTimer);
            });
    }
});
</script>
@endif
@endsection
