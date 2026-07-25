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
    .sortable-ghost {
        background-color: #e6fffa !important;
        border: 2px dashed #16a085 !important;
        opacity: 0.5;
    }
    .sortable-chosen {
        background: #f0fdfa !important;
        box-shadow: 0 4px 14px rgba(22, 160, 133, 0.25) !important;
    }
    .sortable-drag {
        opacity: 0.9;
    }
    .ipcr-sortable-row {
        cursor: grab;
        transition: background-color 0.15s ease;
    }
    .ipcr-sortable-row:active {
        cursor: grabbing;
    }
    .drag-handle {
        cursor: grab !important;
    }
    .drag-handle:active {
        cursor: grabbing !important;
    }
    @media print {
        /* Default print: Hide navigation, sidebar, and buttons */
        .main-header, .main-sidebar, .breadcrumb-drive, .btn, .alert, footer, .no-print {
            display: none !important;
        }
        body:not(.modal-open) .modal {
            display: none !important;
        }
        body, .content-wrapper, .container-fluid {
            background: #ffffff !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .card {
            border: 1px solid #000000 !important;
            box-shadow: none !important;
        }
        .table-bordered, .table-bordered th, .table-bordered td {
            border: 1px solid #000000 !important;
            color: #000000 !important;
        }

        /* Modal active print mode: Print ONLY the active modal & iframe content */
        body.modal-open .container-fluid > *:not(.modal) {
            display: none !important;
        }
        body.modal-open .modal.show {
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 99999 !important;
            background: #ffffff !important;
            display: block !important;
        }
        body.modal-open .modal-dialog {
            max-width: 100vw !important;
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
        }
        body.modal-open .modal-content {
            height: 100vh !important;
            border: none !important;
            box-shadow: none !important;
        }
        body.modal-open .modal-header {
            background: #ffffff !important;
            color: #000000 !important;
        }
        body.modal-open .modal-header .btn,
        body.modal-open .modal-header .close {
            display: none !important;
        }
    }
</style>

@php
    $currentSysYear = (int)date('Y');
    $currentSysSemester = (int)date('n') <= 6 ? 1 : 2;
    $isEditablePeriod = ($year == $currentSysYear && $semester == $currentSysSemester);
@endphp

<div class="container-fluid py-2">
    {{-- Breadcrumb Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="breadcrumb-drive">
            <i class="fas fa-info-circle text-info mr-1"></i> Dashboard &nbsp;/&nbsp; Drive &nbsp;/&nbsp; IPCR
        </span>
        <a href="{{ route('spms.ipcr') }}" class="btn btn-outline-secondary btn-sm font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Back to IPCR Documents
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
                    <i class="fas fa-id-badge text-teal mr-2"></i>IPCR &bull; {{ $employee->fname }} {{ $employee->lname }}
                </h6>
                <small class="text-muted font-weight-bold">
                    Position: {{ $employee->position ?? 'Personnel' }} &bull; Department: {{ $office->office_name ?? 'LGU' }}
                </small>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle font-weight-bold px-3 py-2 shadow-sm bg-white text-dark" type="button" id="periodDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-calendar-alt text-teal mr-1"></i> Year {{ $year }} ({{ $semester == 1 ? '1st Half: Jan-Jun' : '2nd Half: Jul-Dec' }})
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow border-0" aria-labelledby="periodDropdown">
                    <h6 class="dropdown-header text-uppercase font-weight-bold text-muted small">Select Rating Period</h6>
                    <a class="dropdown-item py-2 {{ $semester == 1 ? 'active font-weight-bold' : '' }}" href="{{ route('spms.ipcr.matrix', ['id' => $employee->id, 'semester' => 1, 'year' => $year]) }}">
                        <i class="fas fa-calendar-check mr-2 {{ $semester == 1 ? 'text-white' : 'text-teal' }}"></i> 1st Half (Jan - Jun {{ $year }})
                    </a>
                    <a class="dropdown-item py-2 {{ $semester == 2 ? 'active font-weight-bold' : '' }}" href="{{ route('spms.ipcr.matrix', ['id' => $employee->id, 'semester' => 2, 'year' => $year]) }}">
                        <i class="fas fa-calendar-check mr-2 {{ $semester == 2 ? 'text-white' : 'text-teal' }}"></i> 2nd Half (Jul - Dec {{ $year }})
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header text-uppercase font-weight-bold text-muted small">Switch Year</h6>
                    @foreach(range(2026, max(2026, (int)date('Y'))) as $y)
                        <a class="dropdown-item py-1 small {{ $year == $y ? 'font-weight-bold text-teal' : '' }}" href="{{ route('spms.ipcr.matrix', ['id' => $employee->id, 'semester' => $semester, 'year' => $y]) }}">
                            <i class="fas fa-history mr-2 text-secondary"></i> Year {{ $y }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- FULL-WIDTH IPCR Matrix Table Card (Light Theme) --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px; background: #ffffff;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-list-check text-teal mr-2"></i> My Assigned Objectives &amp; Accomplishments
            </h6>
            <div class="ml-auto d-flex align-items-center">
                <span class="badge badge-success px-3 py-2 font-weight-bold mr-2">Status: {{ $ipcr->status }}</span>
                <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold shadow-sm mr-2" data-toggle="modal" data-target="#previewCosRatingModal" title="Preview & Print Performance Rating Form (PDF)">
                    <i class="fas fa-file-pdf mr-1"></i> Print Rating Form (PDF)
                </button>
                @if($isEditablePeriod)
                    <div class="dropdown d-inline mr-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle font-weight-bold shadow-sm" type="button" id="ipcrTemplateDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-cog text-info mr-1"></i> Template & Options
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow border-0" aria-labelledby="ipcrTemplateDropdown">
                            @if(!empty($isJoOrCos) && $isJoOrCos)
                                <button type="button" class="dropdown-item py-2" data-toggle="modal" data-target="#loadCosTemplateModal" title="Load standard Job Order / Contract of Service rating form template">
                                    <i class="fas fa-file-invoice text-info mr-2"></i> Load COS / JO Rating Template
                                </button>
                            @else
                                <button type="button" class="dropdown-item py-2" data-toggle="modal" data-target="#loadCosTemplateModal" title="Load official LGU Mabinay IPCR form template">
                                    <i class="fas fa-file-excel text-success mr-2"></i> Load Official IPCR Template
                                </button>
                            @endif
                            @if($ipcr->items->count() > 0)
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('spms.ipcr.clear', $ipcr->id) }}" class="d-inline">
                                    @csrf
                                    <button type="button" class="dropdown-item text-danger py-2 btn-delete-confirm"
                                            data-title="Clear All IPCR Rows?"
                                            data-text="Are you sure you want to delete ALL row items from this IPCR? This action cannot be undone."
                                            title="Remove all IPCR rows">
                                        <i class="fas fa-trash-alt text-danger mr-2"></i> Clear All IPCR Rows
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-teal font-weight-bold shadow-sm" data-toggle="modal" data-target="#addCustomIpcrModal">
                        <i class="fas fa-plus mr-1"></i> Add Custom Objective
                    </button>
                @else
                    <span class="badge badge-secondary px-3 py-2 font-weight-bold shadow-sm" title="Past rating periods are locked for viewing only">
                        <i class="fas fa-lock mr-1"></i> Read-Only (Past Period)
                    </span>
                @endif
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
                    @foreach(['Core Functions' => 'CORE FUNCTIONS (60%)', 'Strategic Functions' => 'STRATEGIC FUNCTIONS (20%)', 'Support Functions' => 'SUPPORT FUNCTIONS (20%)'] as $catKey => $catLabel)
                        <tbody class="bg-light">
                            <tr class="table-secondary font-weight-bold text-left">
                                <td colspan="7" class="py-2 px-3">
                                    <i class="fas fa-folder text-warning mr-2"></i> {{ $catLabel }}
                                    @if($isEditablePeriod)
                                        <small class="text-muted font-weight-normal ml-2 font-italic">(Drag rows below to reorder within this function)</small>
                                    @endif
                                </td>
                            </tr>
                        </tbody>

                        @php
                            $categoryItems = $ipcr->items->where('category', $catKey);
                        @endphp

                        <tbody class="{{ $isEditablePeriod ? 'ipcr-sortable-body' : '' }}" data-category="{{ $catKey }}" data-reorderurl="{{ route('spms.ipcr.item.reorder') }}">
                            @forelse($categoryItems as $index => $item)
                                <tr class="{{ $isEditablePeriod ? 'ipcr-sortable-row' : '' }}" data-id="{{ $item->id }}">
                                    <td class="text-center font-weight-bold align-middle">
                                        @if($isEditablePeriod)
                                            <i class="fas fa-grip-vertical text-secondary mr-1 drag-handle no-print" style="cursor: grab;" title="Drag to reorder within {{ $catKey }}"></i>
                                        @endif
                                        {{ $loop->iteration }}
                                    </td>
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
                                    <td class="align-middle">
                                        @if($item->actual_accomplishment)
                                            <p class="mb-1 text-dark">{!! nl2br(e($item->actual_accomplishment)) !!}</p>
                                        @else
                                            <span class="text-muted font-italic small d-block mb-1">No accomplishment entered yet.</span>
                                        @endif

                                        @if($item->evidence_file)
                                            @if($item->is_evidence_url)
                                                <button type="button" class="btn btn-xs btn-outline-teal font-weight-bold shadow-sm mt-1" data-toggle="modal" data-target="#viewEvidenceUrlModal{{ $item->id }}">
                                                    <i class="fab fa-google-drive mr-1"></i> View Evidence Document
                                                </button>
                                            @else
                                                <a href="{{ asset('storage/' . $item->evidence_file) }}" target="_blank" class="btn btn-xs btn-outline-info font-weight-bold shadow-sm mt-1">
                                                    <i class="fas fa-paperclip mr-1"></i> View Attachment
                                                </a>
                                            @endif
                                        @elseif($isEditablePeriod && $guard === 'employee' && $item->employee_id == $user->id)
                                            <button type="button" class="btn btn-xs btn-teal font-weight-bold shadow-sm mt-1" data-toggle="modal" data-target="#editAccomplishmentModal{{ $item->id }}">
                                                <i class="fas fa-plus-circle mr-1"></i> Add Accomplishment &amp; Link
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Rating Column --}}
                                    <td class="text-center align-middle">
                                        @php
                                            $itemRating = $item->rating_ave ?? $item->rating_average;
                                        @endphp
                                        @if($itemRating)
                                            <span class="badge badge-success font-weight-bold" style="font-size: 13px;">
                                                {{ number_format($itemRating, 2) }}
                                            </span>
                                            <small class="d-block text-muted mt-1" style="font-size: 10px;">
                                                Q:{{ $item->rating_q ?? '-' }} | E:{{ $item->rating_e ?? '-' }} | T:{{ $item->rating_t ?? '-' }}
                                            </small>
                                        @else
                                            <span class="text-muted font-italic small">Unrated</span>
                                        @endif
                                    </td>

                                    {{-- Actions Column --}}
                                    <td class="text-center align-middle">
                                        @if($isEditablePeriod)
                                            @if($guard === 'employee' && $item->employee_id == $user->id)
                                                <button type="button" class="btn btn-xs btn-teal font-weight-bold shadow-sm mb-1 mr-1" data-toggle="modal" data-target="#editAccomplishmentModal{{ $item->id }}" title="Submit or Edit Accomplishment">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            @endif

                                            <button type="button" class="btn btn-xs btn-warning font-weight-bold shadow-sm mb-1 mr-1" data-toggle="modal" data-target="#rateIpcrItemModal{{ $item->id }}" title="Rate accomplishment">
                                                <i class="fas fa-star"></i>
                                            </button>

                                            @if(($guard === 'employee' && $item->employee_id == $user->id) || $isHead || $guard === 'web')
                                                <form method="POST" action="{{ route('spms.ipcr.item.delete', $item->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="button" class="btn btn-xs btn-outline-danger font-weight-bold shadow-sm mb-1 btn-delete-confirm" data-title="Delete Objective?" data-text="Are you sure you want to delete this IPCR objective?" title="Delete objective">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="badge badge-light border text-muted px-2 py-1" title="Past rating periods are read-only">
                                                <i class="fas fa-lock fa-xs mr-1"></i> Locked
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Large Evidence Link Preview Modal --}}
                                @if($item->evidence_file && $item->is_evidence_url)
                                    @php
                                        $iframeUrl = $item->evidence_file;
                                        if (preg_match('/drive\.google\.com\/file\/d\/([^\/]+)/i', $item->evidence_file, $matches)) {
                                            $iframeUrl = "https://drive.google.com/file/d/" . $matches[1] . "/preview";
                                        }
                                    @endphp
                                    <div class="modal fade" id="viewEvidenceUrlModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-extra-large modal-dialog-centered">
                                            <div class="modal-content shadow-lg border-0">
                                                <div class="modal-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                                                    <h5 class="modal-title font-weight-bold text-teal" style="font-size: 15px;">
                                                        <i class="fab fa-google-drive text-teal mr-2"></i> Evidence Document Preview
                                                    </h5>
                                                    <div>
                                                        <button type="button" onclick="window.print()" class="btn btn-xs btn-outline-dark font-weight-bold mr-2">
                                                            <i class="fas fa-print mr-1"></i> Print Document
                                                        </button>
                                                        <a href="{{ $item->evidence_file }}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-outline-teal font-weight-bold mr-2">
                                                            <i class="fas fa-external-link-alt mr-1"></i> Open in New Tab
                                                        </a>
                                                        <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="modal-body p-0 bg-dark text-center" style="overflow: hidden;">
                                                    <iframe id="evidenceIframe{{ $item->id }}" src="{{ $iframeUrl }}" style="width: 100%; height: 100%; border: none;" allow="autoplay; encrypted-media" loading="lazy"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Accomplishment Edit Modal for Ratee --}}
                                @if($guard === 'employee' && $item->employee_id == $user->id)
                                    <div class="modal fade" id="editAccomplishmentModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content shadow-lg border-0">
                                                <div class="modal-header bg-white border-bottom py-2">
                                                    <h5 class="modal-title font-weight-bold text-teal" style="font-size: 15px;"><i class="fas fa-file-upload mr-2"></i> Submit Accomplishment &amp; Evidence</h5>
                                                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form method="POST" action="{{ route('spms.ipcr.accomplishment.submit') }}" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="ipcr_item_id" value="{{ $item->id }}">

                                                    <div class="modal-body text-left">
                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold text-dark">Actual Accomplishment Description:</label>
                                                            <textarea name="actual_accomplishment" class="form-control" rows="3" placeholder="Describe your actual performance, target output achieved..." required>{{ $item->actual_accomplishment }}</textarea>
                                                        </div>

                                                        <div class="form-group mb-0">
                                                            <label class="font-weight-bold text-dark"><i class="fab fa-google-drive text-teal mr-1"></i> Evidence Google Drive / Web Link:</label>
                                                            <input type="url" name="evidence_file" class="form-control" value="{{ $item->evidence_file }}" placeholder="https://drive.google.com/file/d/.../view?usp=sharing">
                                                            <small class="form-text text-muted">Paste your Google Drive or web share link here.</small>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer bg-light py-2">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-teal btn-sm font-weight-bold px-4">Save Accomplishment &amp; Evidence</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Rating Modal --}}
                                <div class="modal fade" id="rateIpcrItemModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content shadow-lg border-0">
                                                <div class="modal-header bg-white border-bottom py-2">
                                                    <h5 class="modal-title font-weight-bold text-dark" style="font-size: 15px;"><i class="fas fa-star text-warning mr-2"></i> Rate Employee Accomplishment</h5>
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
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted small font-italic">
                                        No items assigned under {{ $catLabel }} yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>

        {{-- Sign-off Footer Section (Official CSC IPCR Signatories) --}}
        @php
            $rateeDefaultName = $employee->fname . ' ' . $employee->lname;
            $rateeDefaultPos = $employee->position ?? 'Personnel';
            $resolvedHead = isset($officeHead) && $officeHead ? $officeHead : ($ipcr->office?->head ?: ($office?->head ?? null));
            $assessedDefaultName = $resolvedHead ? ($resolvedHead->fname . ' ' . $resolvedHead->lname) : 'OFFICE HEAD NAME';
            $assessedDefaultPos = $resolvedHead?->position ?: ('Head, ' . ($office->office_name ?? $ipcr->office?->office_name ?? 'Department'));
            $approvedDefaultName = 'LUCRECIA C. NICOLAS, MAEd';
            $approvedDefaultPos = 'MGDH-I (GSO)/HRMO-Designate';
        @endphp
        <div class="card-footer bg-white pt-4 pb-3 border-top">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="font-weight-bold text-teal" style="font-size: 13px;">
                    <i class="fas fa-file-signature mr-1"></i> Official IPCR Signatories &amp; Approvals
                </span>
                @if($isEditablePeriod)
                    <button type="button" class="btn btn-xs btn-outline-teal font-weight-bold shadow-sm" data-toggle="modal" data-target="#editIpcrSignatoriesModal">
                        <i class="fas fa-user-edit mr-1"></i> Edit Signatories
                    </button>
                @endif
            </div>

            <div class="row text-dark" style="font-size: 12px;">
                {{-- Column 1: Ratee / Employee --}}
                <div class="col-md-4 mb-3 border-right">
                    <p class="font-weight-bold text-muted mb-4">Discussed with (Ratee):</p>
                    <u class="d-block font-weight-bold text-uppercase" style="font-size: 13px;">
                        {{ $ipcr->ratee_name ?? $rateeDefaultName }}
                    </u>
                    <small class="text-muted d-block font-weight-bold">
                        {{ $ipcr->ratee_position ?? $rateeDefaultPos }}
                    </small>
                    <small class="text-muted mt-3 d-block">Date: ________________________</small>
                </div>

                {{-- Column 2: Assessed by (Supervisor) --}}
                <div class="col-md-4 mb-3 border-right">
                    <p class="font-weight-bold text-muted mb-4">Assessed by (Supervisor):</p>
                    <u class="d-block font-weight-bold text-uppercase" style="font-size: 13px;">
                        {{ $ipcr->assessed_by_name ?? $assessedDefaultName }}
                    </u>
                    <small class="text-muted d-block font-weight-bold">
                        {{ $ipcr->assessed_by_position ?? $assessedDefaultPos }}
                    </small>
                    <small class="text-muted mt-3 d-block">Date: ________________________</small>
                </div>

                {{-- Column 3: Final Rating by --}}
                <div class="col-md-4 mb-3">
                    <p class="font-weight-bold text-muted mb-4">Final Rating by:</p>
                    <u class="d-block font-weight-bold text-uppercase" style="font-size: 13px;">
                        {{ $ipcr->approved_by_name ?? $approvedDefaultName }}
                    </u>
                    <small class="text-muted d-block font-weight-bold">
                        {{ $ipcr->approved_by_position ?? $approvedDefaultPos }}
                    </small>
                    <small class="text-muted mt-3 d-block">Date: ________________________</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit IPCR Signatories Modal --}}
<div class="modal fade" id="editIpcrSignatoriesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title font-weight-bold text-teal"><i class="fas fa-user-edit mr-2"></i> Edit IPCR Footer Signatories</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('spms.ipcr.signatories', $ipcr->id) }}">
                @csrf
                <div class="modal-body text-left">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Ratee / Employee (Name &amp; Title):</label>
                                <input type="text" name="ratee_name" class="form-control" value="{{ $ipcr->ratee_name ?? $rateeDefaultName }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Ratee Position:</label>
                                <input type="text" name="ratee_position" class="form-control" value="{{ $ipcr->ratee_position ?? $rateeDefaultPos }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Assessed By / Supervisor (Name):</label>
                                <input type="text" name="assessed_by_name" class="form-control" value="{{ $ipcr->assessed_by_name ?? $assessedDefaultName }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Supervisor Position:</label>
                                <input type="text" name="assessed_by_position" class="form-control" value="{{ $ipcr->assessed_by_position ?? $assessedDefaultPos }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Final Rating By (Name &amp; Title):</label>
                                <input type="text" name="approved_by_name" class="form-control" value="{{ $ipcr->approved_by_name ?? $approvedDefaultName }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Final Rating By (Position/Office):</label>
                                <input type="text" name="approved_by_position" class="form-control" value="{{ $ipcr->approved_by_position ?? $approvedDefaultPos }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal btn-sm font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Save Signatories
                    </button>
                </div>
            </form>
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

{{-- Load Performance Rating Form Template Modal --}}
<div class="modal fade" id="loadCosTemplateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title font-weight-bold text-info">
                    @if(!empty($isJoOrCos) && $isJoOrCos)
                        <i class="fas fa-file-invoice text-info mr-2"></i> Load Contract of Service (COS) / Job Order Performance Rating Form
                    @else
                        <i class="fas fa-file-excel text-success mr-2"></i> Load Official LGU Mabinay IPCR Form Template
                    @endif
                </h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('spms.ipcr.template.cos') }}">
                @csrf
                <input type="hidden" name="ipcr_id" value="{{ $ipcr->id }}">

                <div class="modal-body text-left">
                    @if(!empty($isJoOrCos) && $isJoOrCos)
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fas fa-info-circle mr-1"></i> <strong>Job Order / COS Rating Form Standard:</strong> Loads default <strong>Task Descriptions</strong>, <strong>Support Functions</strong>, and <strong>Work Ethics</strong> (Punctuality, Integrity, Teamwork, Professionalism, Adaptability) tailored for Contract of Service personnel.
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">Select Position Rating Template:</label>

                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="templateGenServices" name="template_type" value="general_services" class="custom-control-input" checked>
                                <label class="custom-control-label font-weight-bold text-dark" for="templateGenServices">
                                    General Services Office / Maintenance &amp; Utility Personnel (COS / JO)
                                </label>
                                <small class="d-block text-muted">Includes Hallway cleanliness, Garbage gathering &amp; segregation, Daily routine tasks, Flag ceremony, LCE activities, &amp; Work Ethics evaluation.</small>
                            </div>

                            <div class="custom-control custom-radio">
                                <input type="radio" id="templateAdminSupport" name="template_type" value="admin_support" class="custom-control-input">
                                <label class="custom-control-label font-weight-bold text-dark" for="templateAdminSupport">
                                    Administrative &amp; Clerical Support Personnel (COS / JO)
                                </label>
                                <small class="d-block text-muted">Includes Document encoding &amp; filing, Records routing, Client assistance, Departmental support, &amp; Work Ethics evaluation.</small>
                            </div>
                        </div>

                        <div class="card border bg-light p-3 mb-0">
                            <h6 class="font-weight-bold text-dark mb-2" style="font-size: 13px;">Included Rating Categories &amp; Work Ethics Indicators:</h6>
                            <ul class="text-muted small mb-0 pl-3">
                                <li><strong>Core Functions:</strong> Primary daily task descriptions &amp; operational deliverables.</li>
                                <li><strong>Support Functions:</strong> Department assignments, Flag Ceremony, &amp; LCE sanctioned activities.</li>
                                <li><strong>Work Ethics Evaluation:</strong> Punctuality &amp; attendance, Responsibility, Integrity, Teamwork, Professionalism, Time Management, Continuous Improvement, Respect, Adaptability, and Customer Service.</li>
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-success py-2 small mb-3">
                            <i class="fas fa-file-excel mr-1"></i> <strong>Official LGU Mabinay IPCR Form Standard:</strong> Loads official <strong>MFO/PAPs</strong>, <strong>Subcategories</strong>, and <strong>Success Indicators</strong> from the standard LGU Mabinay IPCR form.
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">Select IPCR Template:</label>

                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="templateOfficialRegular" name="template_type" value="official_regular" class="custom-control-input" checked>
                                <label class="custom-control-label font-weight-bold text-dark text-teal" for="templateOfficialRegular">
                                    <i class="fas fa-check-circle text-success mr-1"></i> Official LGU Mabinay IPCR Form (Regular &amp; Permanent Employees)
                                </label>
                                <small class="d-block text-muted">Loads official MFO/PAPs &amp; success indicators (Policy Implementation, Operations, Public Engagement, ARTA, HR &amp; Financial Management).</small>
                            </div>
                        </div>

                        <div class="card border bg-light p-3 mb-0">
                            <h6 class="font-weight-bold text-dark mb-2" style="font-size: 13px;">Included Functional Deliverables:</h6>
                            <ul class="text-muted small mb-0 pl-3">
                                <li><strong>Core Functions (90% Weight):</strong> Policy &amp; Program Implementation, Operational Management, Service Delivery &amp; Public Engagement, Personnel Management, Strategic Planning, Financial Resource Management.</li>
                                <li><strong>Support Functions (10% Weight):</strong> Compliance &amp; Regulation, Human Resource Management, Department Meetings, Trainings, and Flag Ceremonies.</li>
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info btn-sm font-weight-bold px-4">
                        <i class="fas fa-download mr-1"></i> Load Rating Template Items
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-delete-confirm');
        if (btn) {
            e.preventDefault();
            var form = btn.closest('form');
            var title = btn.getAttribute('data-title') || 'Confirm Deletion';
            var text = btn.getAttribute('data-text') || 'Are you sure you want to delete this item?';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash mr-1"></i> Yes, Delete It',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-danger font-weight-bold px-3 py-2 mr-2',
                        cancelButton: 'btn btn-secondary font-weight-bold px-3 py-2'
                    },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm(text)) {
                    form.submit();
                }
            }
        }
    });

    function printModalIframe(iframeId) {
        var iframe = document.getElementById(iframeId);
        if (iframe) {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                window.open(iframe.src, '_blank');
            }
        }
    }

    function printCosIframe(iframeId) {
        var iframe = document.getElementById(iframeId);
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.ipcr-sortable-body').forEach(function (tbody) {
            var reorderUrl = tbody.getAttribute('data-reorderurl');

            if (typeof Sortable !== 'undefined') {
                Sortable.create(tbody, {
                    animation: 150,
                    draggable: 'tr.ipcr-sortable-row',
                    filter: 'button, a, input, select, textarea, .btn, [data-toggle]',
                    preventOnFilter: false,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function () {
                        var itemIds = [];
                        tbody.querySelectorAll('tr.ipcr-sortable-row').forEach(function (row) {
                            itemIds.push(row.getAttribute('data-id'));
                        });

                        if (itemIds.length && reorderUrl) {
                            fetch(reorderUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ order: itemIds })
                            })
                            .then(function (res) { return res.json(); })
                            .catch(function (err) {
                                console.error('Reorder failed:', err);
                            });
                        }
                    }
                });
            }
        });
    });
</script>

{{-- Performance Rating Form Preview Modal --}}
<div class="modal fade" id="previewCosRatingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h5 class="modal-title font-weight-bold text-danger" style="font-size: 15px;">
                    <i class="fas fa-file-pdf text-danger mr-2"></i> Performance Rating Form Preview &bull; {{ $employee->fname }} {{ $employee->lname }}
                </h5>
                <div>
                    <button type="button" onclick="printCosIframe('matrixCosIframe')" class="btn btn-xs btn-outline-dark font-weight-bold mr-2">
                        <i class="fas fa-print mr-1"></i> Print Document
                    </button>
                    <a href="{{ route('spms.ipcr.print.cos', ['id' => $employee->id, 'semester' => $semester, 'year' => $year]) }}" target="_blank" class="btn btn-xs btn-outline-teal font-weight-bold mr-2">
                        <i class="fas fa-external-link-alt mr-1"></i> Open in New Tab
                    </a>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0 bg-dark text-center" style="overflow: hidden;">
                <iframe id="matrixCosIframe" src="{{ route('spms.ipcr.print.cos', ['id' => $employee->id, 'semester' => $semester, 'year' => $year, 'embed' => 1]) }}" style="width: 100%; height: 75vh; border: none;" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</div>
@endpush

