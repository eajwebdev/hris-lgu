@extends('layouts.master')

@section('body')
<div class="container-fluid">
    {{-- Header Banner --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card bg-gradient-success text-white shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 font-weight-bold">
                                <i class="fas fa-id-badge mr-2"></i>INDIVIDUAL PERFORMANCE COMMITMENT &amp; REVIEW (IPCRF)
                            </h4>
                            <p class="mb-0 text-white-50">
                                Strategic Performance Management System &bull; Municipality of Mabinay
                            </p>
                        </div>
                        <span class="badge badge-light px-3 py-2 text-success font-weight-bold">
                            IPCR Module
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Commitment & Ratee Box --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light border-left-success py-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-1 text-dark font-italic">
                        "I, <strong>{{ $employee->fname }} {{ $employee->mname }} {{ $employee->lname }}</strong>, 
                        <strong>{{ $employee->position ?? 'Personnel' }}</strong>, of <strong>{{ $office->office_name ?? 'LGU Mabinay' }}</strong>, 
                        commit to deliver and agree to be rated on the attainment of targets in accordance with indicated measures for the period 
                        <strong>{{ $year }} ({{ $semester == 1 ? '1st Semester: Jan - Jun' : '2nd Semester: Jul - Dec' }})</strong>."
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-user-check mr-1"></i> Ratee: {{ $employee->fname }} {{ $employee->lname }}
                    </span>
                    <p class="text-muted small mb-0 mt-1">Department: {{ $office->office_name ?? 'LGU' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- IPCR Targets Table --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title font-weight-bold text-success mb-0">
                <i class="fas fa-list-check mr-2"></i> Individual Targets &amp; Accomplishments
            </h5>
            <div>
                <span class="badge badge-info px-3 py-2">Status: {{ $ipcr->status }}</span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="bg-light text-center text-dark font-weight-bold">
                        <tr>
                            <th style="width: 4%">#</th>
                            <th style="width: 12%">Category</th>
                            <th style="width: 22%">MFO / PAP</th>
                            <th style="width: 22%">Success Indicators</th>
                            <th style="width: 22%">Actual Accomplishment</th>
                            <th style="width: 10%">Rating (Q/E/T/Ave)</th>
                            <th style="width: 8%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ipcr->targets as $index => $t)
                            <tr>
                                <td class="text-center font-weight-bold">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge {{ $t->category == 'Core Functions' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $t->category }}
                                    </span>
                                    @if($t->opcr_target_id)
                                        <small class="d-block text-success font-weight-bold mt-1">
                                            <i class="fas fa-sitemap mr-1"></i> Cascaded from OPCR
                                        </small>
                                    @endif
                                </td>
                                <td class="font-weight-bold text-dark">{!! nl2br(e($t->mfo_pap)) !!}</td>
                                <td class="text-muted">{!! nl2br(e($t->success_indicators)) !!}</td>
                                <td>
                                    @if($t->actual_accomplishment)
                                        <p class="mb-0 text-dark">{!! nl2br(e($t->actual_accomplishment)) !!}</p>
                                    @else
                                        <span class="text-muted font-italic small">No accomplishment entered yet</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($t->rating_ave)
                                        <div class="badge badge-success px-2 py-1 font-weight-bold">
                                            Average: {{ number_format($t->rating_ave, 2) }}
                                        </div>
                                        <small class="d-block text-muted mt-1">
                                            Q:{{ $t->rating_q ?? '-' }} | E:{{ $t->rating_e ?? '-' }} | T:{{ $t->rating_t ?? '-' }}
                                        </small>
                                    @else
                                        <span class="text-muted small">Not Rated</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-success font-weight-bold"
                                            data-toggle="modal"
                                            data-target="#editIpcrModal{{ $t->id }}">
                                        <i class="fas fa-edit mr-1"></i> Update
                                    </button>
                                </td>
                            </tr>

                            {{-- Edit IPCR Target Modal --}}
                            <div class="modal fade" id="editIpcrModal{{ $t->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content shadow-lg">
                                        <div class="modal-header bg-success text-white py-2">
                                            <h5 class="modal-title font-weight-bold text-white">
                                                <i class="fas fa-edit mr-2"></i> Update Actual Accomplishment
                                            </h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="POST" action="{{ route('spms.ipcr.target.update') }}">
                                            @csrf
                                            <input type="hidden" name="target_id" value="{{ $t->id }}">

                                            <div class="modal-body text-left">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-dark">MFO / PAP:</label>
                                                    <p class="text-muted small bg-light p-2 rounded border mb-0">{!! nl2br(e($t->mfo_pap)) !!}</p>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-dark">Actual Accomplishment:</label>
                                                    <textarea name="actual_accomplishment" class="form-control" rows="3" placeholder="State actual achievements against target indicators..." required>{{ $t->actual_accomplishment }}</textarea>
                                                </div>

                                                @if($isHead)
                                                    <hr>
                                                    <h6 class="font-weight-bold text-success mb-2"><i class="fas fa-star mr-1"></i> Office Head Rating (1 - 5 Scale):</h6>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <label class="small font-weight-bold">Quality (Q):</label>
                                                            <input type="number" step="0.1" min="1" max="5" name="rating_q" class="form-control form-control-sm" value="{{ $t->rating_q }}">
                                                        </div>
                                                        <div class="col-4">
                                                            <label class="small font-weight-bold">Efficiency (E):</label>
                                                            <input type="number" step="0.1" min="1" max="5" name="rating_e" class="form-control form-control-sm" value="{{ $t->rating_e }}">
                                                        </div>
                                                        <div class="col-4">
                                                            <label class="small font-weight-bold">Timeliness (T):</label>
                                                            <input type="number" step="0.1" min="1" max="5" name="rating_t" class="form-control form-control-sm" value="{{ $t->rating_t }}">
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="modal-footer bg-light py-2">
                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success btn-sm font-weight-bold px-3">
                                                    <i class="fas fa-save mr-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                                    No IPCR targets assigned to you for this period yet. Your Office Head can cascade targets directly from the OPCR module.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
