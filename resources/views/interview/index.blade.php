@extends('layouts.master')

@section('body')
<style>
    .interview-page .card { border:0; border-radius:16px; box-shadow:0 8px 28px rgba(15,23,42,.07); }
    .interview-head { align-items:center; display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; padding:18px 20px; }
    .interview-head h1 { font-size:1.15rem; font-weight:800; margin:0; }
    .interview-page .table td, .interview-page .table th { vertical-align:middle; }
    .panel-chip { background:#eef7f2; border:1px solid #cfe8d8; border-radius:999px; color:#206a3b; display:inline-block; font-size:.76rem; margin:2px; padding:4px 8px; }
    .interview-page .modal-content { border:0; border-radius:16px; overflow:hidden; }
</style>

<div class="container-fluid interview-page">
    <div class="card card-info card-outline">
        <div class="interview-head">
            <h1><i class="fas fa-comments mr-1"></i> Interview Assessment</h1>
            <button class="btn btn-success" data-toggle="modal" data-target="#addInterviewModal">
                <i class="fas fa-plus"></i> Add Interview
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="example1" class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Position</th>
                            <th>ETE Source</th>
                            <th>Interview Date</th>
                            <th>Panels</th>
                            <th>Active Candidate</th>
                            <th class="text-center">Ratings</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($interviews as $interview)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $interview->job->title ?? 'N/A' }}</strong>
                                    @if($interview->job && $interview->job->plantilla_item_no)
                                        <small class="d-block text-muted">{{ $interview->job->plantilla_item_no }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light border">ETE #{{ $interview->ete_id }}</span>
                                    <small class="d-block text-muted">{{ $interview->eteEvaluation->office->office_name ?? '' }}</small>
                                </td>
                                <td>{{ $interview->interview_date ? $interview->interview_date->format('M. d, Y h:i A') : 'N/A' }}</td>
                                <td>
                                    @forelse($interview->panels as $panel)
                                        <span class="panel-chip">{{ $panel->employee->lname ?? '' }}, {{ $panel->employee->fname ?? '' }}</span>
                                    @empty
                                        <span class="text-muted">No panel</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if($interview->activeApplication)
                                        <strong>{{ trim($interview->activeApplication->first_name.' '.$interview->activeApplication->last_name) }}</strong>
                                        <small class="d-block text-muted">{{ $interview->activeApplication->app_number }}</small>
                                    @else
                                        <span class="badge badge-secondary">No cast candidate</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info p-2">{{ $interview->ratings->whereNotNull('submitted_at')->count() }} submitted</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('interviewEvaluationShow', $interview->id) }}" class="btn btn-sm btn-primary" title="Manage">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true))
                                        <a href="{{ route('interviewConsolidatedScreen', $interview->id) }}" target="_blank" class="btn btn-sm btn-warning" title="Ranking">
                                            <i class="fas fa-ranking-star"></i>
                                        </a>
                                        <a href="{{ route('interviewSummaryRatingPdf', $interview->id) }}" target="_blank" class="btn btn-sm btn-danger" title="Summary Rating of Applicants">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endif
                                    <form action="{{ route('interviewEvaluationDelete', $interview->id) }}"
                                          method="POST"
                                          class="d-inline-block interview-delete-form"
                                          data-interview-title="{{ $interview->job->title ?? 'Interview Assessment' }}">
                                        @csrf
                                        <button class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addInterviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('interviewEvaluationStore') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-comments mr-1"></i> Create Interview Assessment</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>ETE Select</label>
                                <select name="ete_id" class="form-control select2" required>
                                    <option value="">Select ETE Evaluation</option>
                                    @foreach($etes as $ete)
                                        <option value="{{ $ete->id }}">
                                            ETE #{{ $ete->id }} - {{ $ete->job->title ?? 'N/A' }}{{ $ete->job && $ete->job->plantilla_item_no ? ' - '.$ete->job->plantilla_item_no : '' }}{{ $ete->office ? ' - '.$ete->office->office_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Interview Date</label>
                                <input type="datetime-local" name="interview_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Interview Panel Employees</label>
                        <select name="panels[]" class="form-control select2" multiple required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->lname }}, {{ $employee->fname }} {{ $employee->mname }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Each selected employee gets a rating form when a candidate is cast.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Interview</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    $('#addInterviewModal').on('shown.bs.modal', function () {
        $('.select2').select2({
            dropdownParent: $('#addInterviewModal'),
            width: '100%',
            placeholder: 'Search...'
        });
    });

    $(document).on('submit', '.interview-delete-form', function (e) {
        e.preventDefault();
        const form = this;
        const title = $(form).data('interview-title') || 'Interview Assessment';

        Swal.fire({
            title: 'Delete interview assessment?',
            html: `Delete "<strong>${title}</strong>" and all connected panel ratings? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endsection
