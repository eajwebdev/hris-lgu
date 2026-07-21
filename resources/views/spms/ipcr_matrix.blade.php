@extends('layouts.master')

@section('body')
<style>
    .btn-teal {
        background-color: #16a085;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        font-weight: 600;
    }
    .btn-teal:hover {
        background-color: #13876f;
        color: #ffffff;
    }
    .breadcrumb-drive {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }
    .table-light-header th {
        background-color: #f8fafc !important;
        color: #334155 !important;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
    }
    .modal-extra-large {
        max-width: 95vw !important;
        width: 95vw !important;
    }
    .modal-extra-large .modal-content {
        height: 90vh !important;
    }
    .modal-extra-large .modal-body {
        height: calc(90vh - 60px) !important;
        overflow-y: auto;
    }
</style>

<div class="container-fluid py-2">
    {{-- Breadcrumb Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="breadcrumb-drive">
            <i class="fas fa-info-circle text-info mr-1"></i> Dashboard &nbsp;/&nbsp; Drive &nbsp;/&nbsp; IPCR Matrix
        </span>
        <a href="{{ route('spms.drive') }}" class="btn btn-outline-secondary btn-sm font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Back to My Drive
        </a>
    </div>

    {{-- Flash Messages --}}
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

    {{-- Header & Employee Info --}}
    <div class="card shadow-sm border-0 mb-3 p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="font-weight-bold text-dark mb-1">
                    <i class="fas fa-id-badge text-teal mr-2"></i>IPCR Matrix &bull; {{ $employee->fname }} {{ $employee->lname }}
                </h6>
                <small class="text-muted font-weight-bold">
                    Position: {{ $employee->position ?? 'Personnel' }} &bull; Department: {{ $office->office_name ?? 'LGU' }}
                </small>
            </div>
            <div>
                <span class="badge badge-light border text-dark px-3 py-2 font-weight-bold">
                    Year {{ $year }} ({{ $semester == 1 ? '1st Half: Jan-Jun' : '2nd Half: Jul-Dec' }})
                </span>
            </div>
        </div>
    </div>

    {{-- FULL-WIDTH IPCR Matrix Table Card (Light Theme) --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px; background: #ffffff;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-list-check text-teal mr-2"></i> My Assigned Objectives &amp; Accomplishments
            </h6>
            <div>
                <span class="badge badge-success px-3 py-2 font-weight-bold mr-2">Status: {{ $ipcr->status }}</span>
                <button type="button" class="btn btn-sm btn-teal font-weight-bold shadow-sm" data-toggle="modal" data-target="#addCustomIpcrModal">
                    <i class="fas fa-plus mr-1"></i> Add Custom Objective
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light-header text-center">
                        <tr>
                            <th style="width: 4%">#</th>
                            <th style="width: 12%">Category</th>
                            <th style="width: 25%">Major Final Output (MFO / PAPs)</th>
                            <th style="width: 25%">Success Indicators (Targets + Measures)</th>
                            <th style="width: 20%">Actual Accomplishment &amp; Evidence</th>
                            <th style="width: 8%">Rating (Q/E/T/Ave)</th>
                            <th style="width: 6%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ipcr->items as $index => $item)
                            <tr>
                                <td class="text-center font-weight-bold">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge {{ $item->category == 'Core Functions' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $item->category }}
                                    </span>
                                    @if($item->opcr_item_id)
                                        <small class="d-block text-teal font-weight-bold mt-1">
                                            <i class="fas fa-sitemap mr-1"></i> Cascaded from OPCR Row #{{ $item->opcr_item_id }}
                                        </small>
                                    @endif
                                </td>
                                <td class="font-weight-bold text-dark">{!! nl2br(e($item->mfo_pap)) !!}</td>
                                <td class="text-muted">{!! nl2br(e($item->success_indicators)) !!}</td>
                                <td>
                                    @if($item->actual_accomplishment)
                                        <p class="mb-1 text-dark">{!! nl2br(e($item->actual_accomplishment)) !!}</p>
                                    @else
                                        <span class="text-muted font-italic small">No accomplishment entered yet.</span>
                                    @endif

                                    @if($item->evidence_file)
                                        @php
                                            $ext = strtolower(pathinfo($item->evidence_file, PATHINFO_EXTENSION));
                                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                            $isPdf = ($ext === 'pdf');
                                        @endphp
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-xs btn-teal font-weight-bold shadow-sm" data-toggle="modal" data-target="#viewEvidenceModal{{ $item->id }}">
                                                <i class="fas fa-eye mr-1"></i> View Attachment
                                            </button>
                                        </div>

                                        {{-- Inline Document Preview Modal --}}
                                        <div class="modal fade" id="viewEvidenceModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-extra-large modal-dialog-centered">
                                                <div class="modal-content shadow-lg border-0">
                                                    <div class="modal-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                                                        <h5 class="modal-title font-weight-bold text-teal" style="font-size: 15px;">
                                                            <i class="fas fa-file-alt text-teal mr-2"></i> Evidence Attachment &bull; {{ basename($item->evidence_file) }}
                                                        </h5>
                                                        <div>
                                                            <a href="{{ route('spms.evidence.view', ['id' => $item->id, 'download' => 1]) }}" class="btn btn-xs btn-outline-primary mr-2 font-weight-bold">
                                                                <i class="fas fa-download mr-1"></i> Download File
                                                            </a>
                                                            <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="modal-body p-3 bg-light text-center">
                                                        @if($isPdf)
                                                            <object data="{{ route('spms.evidence.view', $item->id) }}#toolbar=0" type="application/pdf" style="width: 100%; height: 100%; min-height: calc(90vh - 100px); border: none; border-radius: 6px;">
                                                                <embed src="{{ route('spms.evidence.view', $item->id) }}" type="application/pdf" style="width: 100%; height: 100%; min-height: calc(90vh - 100px);" />
                                                            </object>
                                                        @elseif($isImage)
                                                            <img src="{{ route('spms.evidence.view', $item->id) }}" class="img-fluid rounded shadow-sm d-block mx-auto" style="max-height: calc(90vh - 100px);" alt="Evidence Attachment">
                                                        @else
                                                            <div class="py-5">
                                                                <i class="fas fa-file-archive fa-4x text-secondary mb-3"></i>
                                                                <h6 class="font-weight-bold text-dark mb-1">{{ basename($item->evidence_file) }}</h6>
                                                                <p class="text-muted small">This file type cannot be previewed inline.</p>
                                                                <a href="{{ route('spms.evidence.view', ['id' => $item->id, 'download' => 1]) }}" class="btn btn-teal font-weight-bold px-4 py-2 mt-2">
                                                                    <i class="fas fa-download mr-1"></i> Download Original File
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->rating_ave)
                                        <span class="badge badge-success px-2 py-1 font-weight-bold d-block mb-1">
                                            Average: {{ number_format($item->rating_ave, 2) }}
                                        </span>
                                        <small class="text-muted">
                                            Q:{{ $item->rating_q ?? '-' }} | E:{{ $item->rating_e ?? '-' }} | T:{{ $item->rating_t ?? '-' }}
                                        </small>
                                    @else
                                        <span class="text-muted small">Not Rated</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <button class="btn btn-xs btn-teal font-weight-bold px-2 mb-1"
                                            title="Update Accomplishment & Upload Evidence"
                                            data-toggle="modal"
                                            data-target="#updateAccomplishmentModal{{ $item->id }}">
                                        <i class="fas fa-upload mr-1"></i> Submit
                                    </button>

                                    @if($isHead)
                                        <button class="btn btn-xs btn-warning font-weight-bold px-2"
                                                title="Rate Employee Target"
                                                data-toggle="modal"
                                                data-target="#rateModal{{ $item->id }}">
                                            <i class="fas fa-star mr-1"></i> Rate
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- Accomplishment & Evidence Modal (Light Header) --}}
                            <div class="modal fade" id="updateAccomplishmentModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content shadow-lg border-0">
                                        <div class="modal-header bg-white border-bottom py-2">
                                            <h5 class="modal-title font-weight-bold text-teal"><i class="fas fa-edit mr-2"></i> Submit Accomplishment &amp; Evidence</h5>
                                            <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="POST" action="{{ route('spms.ipcr.accomplishment.submit') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="ipcr_item_id" value="{{ $item->id }}">

                                            <div class="modal-body text-left">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-dark">Objective (MFO / PAP):</label>
                                                    <p class="text-muted small bg-light p-2 rounded border mb-0">{!! nl2br(e($item->mfo_pap)) !!}</p>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-dark">Actual Accomplishment Description:</label>
                                                    <textarea name="actual_accomplishment" class="form-control" rows="3" placeholder="Describe actual achievements, percentages, or deliverables..." required>{{ $item->actual_accomplishment }}</textarea>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-dark">Upload Supporting Evidence File (PDF, DOC, JPG, PNG, ZIP):</label>
                                                    <input type="file" name="evidence_file" class="form-control-file border p-1 rounded w-100">
                                                    <small class="form-text text-muted">Max file size: 10MB.</small>
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light py-2">
                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-teal btn-sm font-weight-bold px-3">
                                                    <i class="fas fa-save mr-1"></i> Save &amp; Submit
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Office Head Rating Modal (Light Header) --}}
                            @if($isHead)
                                <div class="modal fade" id="rateModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content shadow-lg border-0">
                                            <div class="modal-header bg-white border-bottom py-2">
                                                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-star text-warning mr-2"></i> Evaluate Employee IPCR Item</h5>
                                                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('spms.ipcr.item.rate') }}">
                                                @csrf
                                                <input type="hidden" name="ipcr_item_id" value="{{ $item->id }}">

                                                <div class="modal-body text-left">
                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">Employee Accomplishment:</label>
                                                        <p class="text-muted small bg-light p-2 rounded border mb-0">{!! nl2br(e($item->actual_accomplishment ?? 'No accomplishment description provided')) !!}</p>
                                                    </div>

                                                    <h6 class="font-weight-bold text-dark mb-2">Rating (1 to 5 Scale):</h6>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <label class="small font-weight-bold">Quality (Q):</label>
                                                            <input type="number" step="0.1" min="1" max="5" name="rating_q" class="form-control form-control-sm" value="{{ $item->rating_q }}">
                                                        </div>
                                                        <div class="col-4">
                                                            <label class="small font-weight-bold">Efficiency (E):</label>
                                                            <input type="number" step="0.1" min="1" max="5" name="rating_e" class="form-control form-control-sm" value="{{ $item->rating_e }}">
                                                        </div>
                                                        <div class="col-4">
                                                            <label class="small font-weight-bold">Timeliness (T):</label>
                                                            <input type="number" step="0.1" min="1" max="5" name="rating_t" class="form-control form-control-sm" value="{{ $item->rating_t }}">
                                                        </div>
                                                    </div>

                                                    <div class="form-group mt-3 mb-0">
                                                        <label class="font-weight-bold text-dark">Remarks:</label>
                                                        <textarea name="remarks" class="form-control" rows="2">{{ $item->remarks }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="modal-footer bg-light py-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning btn-sm font-weight-bold px-3">Save Rating</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x text-secondary mb-3 d-block"></i>
                                    <p class="mb-0 font-weight-bold">No objectives assigned to you for this period yet.</p>
                                    <small>Your Office Head can cascade specific OPCR row targets directly to your IPCR.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Custom IPCR Objective Modal --}}
<div class="modal fade" id="addCustomIpcrModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title font-weight-bold text-teal"><i class="fas fa-plus-circle mr-2"></i> Add Custom IPCR Objective / Routine Duty</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('spms.ipcr.item.store') }}">
                @csrf
                <input type="hidden" name="ipcr_id" value="{{ $ipcr->id }}">

                <div class="modal-body text-left">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Category:</label>
                        <select name="category" class="form-control custom-select" required>
                            <option value="Core Functions">Core Functions (60%)</option>
                            <option value="Strategic Functions">Strategic Functions (20%)</option>
                            <option value="Support Functions" selected>Support Functions (20%) - Routine/Administrative</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Major Final Output (MFO / PAPs):</label>
                        <textarea name="mfo_pap" class="form-control" rows="3" placeholder="Enter custom deliverable description or daily routine duty..." required></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Success Indicators (Targets + Measures):</label>
                        <textarea name="success_indicators" class="form-control" rows="3" placeholder="Enter target metrics and measures..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal btn-sm font-weight-bold px-4">Save Custom Objective</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
