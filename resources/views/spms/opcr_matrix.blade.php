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
    .opcr-table-header th {
        font-size: 11px;
        text-transform: uppercase;
        vertical-align: middle !important;
        background-color: #f8fafc;
        color: #334155;
        font-weight: 700;
    }
    .opcr-category-row {
        background-color: #e2e8f0 !important;
        color: #1e293b !important;
        font-weight: 700;
        font-size: 12px;
        letter-spacing: 0.5px;
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
    {{-- Breadcrumb & Navigation Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="breadcrumb-drive">
            <i class="fas fa-info-circle text-info mr-1"></i> Dashboard &nbsp;/&nbsp; Drive &nbsp;/&nbsp; OPCR Matrix
        </span>
        <a href="{{ route('spms.opcr') }}" class="btn btn-outline-secondary btn-sm font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Back to OPCR Documents
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

    {{-- Header Banner & Office Head Badge --}}
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <button class="btn btn-teal font-weight-bold px-3 py-1 text-uppercase shadow-sm" style="border-radius: 20px; font-size: 13px;">
                <i class="fas fa-user-circle mr-1"></i> {{ $opcr->head ? ($opcr->head->fname . ' ' . $opcr->head->lname) : ($opcr->office->office_name ?? 'Office Head') }}
            </button>
            <span class="ml-2 font-weight-bold text-muted small">
                OPCR Matrix &bull; {{ $opcr->office->office_name }} ({{ $opcr->year }})
            </span>
        </div>
        <div class="col-md-6 text-right d-flex justify-content-end align-items-center">
            <span class="badge badge-light border text-dark px-3 py-2 mr-2 font-weight-bold">
                {{ $opcr->semester == 1 ? '1st Half' : '2nd Half' }}
            </span>
            <button class="btn btn-sm btn-teal font-weight-bold px-3 shadow-sm" data-toggle="modal" data-target="#addOpcrRowModal">
                <i class="fas fa-plus mr-1"></i> Add OPCR Item
            </button>
        </div>
    </div>

    {{-- FULL-WIDTH Matrix Table Card (Light Theme) --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px; background: #ffffff;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle text-center mb-0" style="font-size: 12px;">
                    <thead class="opcr-table-header text-center">
                        <tr>
                            <th rowspan="2" style="width: 15%; min-width: 160px;">MFO/PAPs</th>
                            <th rowspan="2" style="width: 18%; min-width: 180px;">Success Indicators<br><small class="text-muted">(Targets + Measures)</small></th>
                            <th rowspan="2" style="width: 5%;">Link to Source</th>
                            <th colspan="2" style="width: 16%;">Evidence</th>
                            <th rowspan="2" style="width: 8%;">Allotted Budget</th>
                            <th rowspan="2" style="width: 10%;">Division / Individuals Accountable</th>
                            <th colspan="4" style="width: 12%;">Rating Guide / Accomplishment</th>
                            <th rowspan="2" style="width: 12%;">Remarks / Accomplishment</th>
                            <th rowspan="2" style="width: 4%;">Actions</th>
                        </tr>
                        <tr>
                            <th style="font-size: 10px;">Individual Support Documents</th>
                            <th style="font-size: 10px;">Report of Supervisor / Other Offices</th>
                            <th style="width: 3%;">Q</th>
                            <th style="width: 3%;">E</th>
                            <th style="width: 3%;">T</th>
                            <th style="width: 3%;">A</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['Core Functions' => 'CORE FUNCTIONS (60%)', 'Strategic Functions' => 'STRATEGIC FUNCTIONS (20%)', 'Support Functions' => 'SUPPORT FUNCTIONS (20%)'] as $catKey => $catLabel)
                            <tr class="opcr-category-row text-left">
                                <td colspan="14" class="py-2 px-3">
                                    <i class="fas fa-folder text-warning mr-2"></i> {{ $catLabel }}
                                </td>
                            </tr>

                            @php
                                $categoryItems = $opcr->items->where('category', $catKey);
                            @endphp

                            @forelse($categoryItems as $item)
                                <tr>
                                    {{-- MFO / PAP --}}
                                    <td class="text-left font-weight-bold text-dark">{!! nl2br(e($item->mfo_pap)) !!}</td>

                                    {{-- Success Indicators --}}
                                    <td class="text-left text-muted">{!! nl2br(e($item->success_indicators)) !!}</td>

                                    {{-- Link to Source --}}
                                    <td class="align-middle text-center">
                                        @if($item->link_to_source)
                                            <a href="{{ $item->link_to_source }}" target="_blank" class="btn btn-xs btn-outline-info shadow-sm" title="Open Source Document: {{ $item->link_to_source }}">
                                                <i class="fas fa-globe mr-1"></i> Source
                                            </a>
                                        @else
                                            <span class="text-muted small" title="No link attached">
                                                <i class="fas fa-globe text-secondary opacity-50"></i>
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Evidence Support & Supervisor --}}
                                    <td class="align-middle">
                                        @php
                                            $evidenceItems = $item->ipcrItems->whereNotNull('evidence_file');
                                            $totalAssigned = $item->ipcrItems->count();
                                            $submittedCount = $evidenceItems->count();
                                        @endphp

                                        @if($submittedCount > 0)
                                            <button type="button" class="btn btn-xs btn-teal font-weight-bold shadow-sm" data-toggle="modal" data-target="#evidenceListModal{{ $item->id }}">
                                                <i class="fas fa-folder-open mr-1"></i> View Evidences ({{ $submittedCount }}/{{ $totalAssigned }})
                                            </button>

                                            {{-- Submissions List Modal --}}
                                            <div class="modal fade" id="evidenceListModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                                    <div class="modal-content shadow-lg border-0">
                                                        <div class="modal-header bg-white border-bottom py-2">
                                                            <h5 class="modal-title font-weight-bold text-teal" style="font-size: 15px;">
                                                                <i class="fas fa-file-alt mr-2"></i> Employee Evidence Submissions
                                                            </h5>
                                                            <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body p-3 bg-light">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered table-sm bg-white mb-0 text-left" style="font-size: 12px;">
                                                                    <thead class="bg-light text-dark font-weight-bold">
                                                                        <tr>
                                                                            <th>Employee Name</th>
                                                                            <th>Actual Accomplishment</th>
                                                                            <th>Attached File</th>
                                                                            <th class="text-center">Action</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($item->ipcrItems as $ipcrRow)
                                                                            <tr>
                                                                                <td class="font-weight-bold text-dark align-middle">
                                                                                    {{ $ipcrRow->employee->fname ?? 'Staff' }} {{ $ipcrRow->employee->lname ?? '' }}
                                                                                    <small class="d-block text-muted">{{ $ipcrRow->employee->position ?? 'Personnel' }}</small>
                                                                                </td>
                                                                                <td class="align-middle">
                                                                                    @if($ipcrRow->actual_accomplishment)
                                                                                        {!! nl2br(e($ipcrRow->actual_accomplishment)) !!}
                                                                                    @else
                                                                                        <span class="text-muted font-italic small">Pending submission</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td class="align-middle">
                                                                                    @if($ipcrRow->evidence_file)
                                                                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-paperclip mr-1"></i> {{ basename($ipcrRow->evidence_file) }}</span>
                                                                                    @else
                                                                                        <span class="badge badge-light border text-muted">No File</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td class="text-center align-middle">
                                                                                    @if($ipcrRow->evidence_file)
                                                                                        <button type="button" class="btn btn-xs btn-teal font-weight-bold" data-toggle="modal" data-target="#opcrEvModal{{ $ipcrRow->id }}">
                                                                                            <i class="fas fa-eye mr-1"></i> Preview
                                                                                        </button>
                                                                                    @else
                                                                                        <span class="text-muted small">-</span>
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Individual Preview Modals --}}
                                            @foreach($evidenceItems as $evItem)
                                                @php
                                                    $evExt = strtolower(pathinfo($evItem->evidence_file, PATHINFO_EXTENSION));
                                                    $evIsImg = in_array($evExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                                    $evIsPdf = ($evExt === 'pdf');
                                                @endphp
                                                <div class="modal fade" id="opcrEvModal{{ $evItem->id }}" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
                                                    <div class="modal-dialog modal-extra-large modal-dialog-centered">
                                                        <div class="modal-content shadow-lg border-0">
                                                            <div class="modal-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                                                                <h5 class="modal-title font-weight-bold text-teal" style="font-size: 15px;">
                                                                    <i class="fas fa-file-alt text-teal mr-2"></i> Evidence Attachment &bull; {{ $evItem->employee->fname ?? 'Staff' }} {{ $evItem->employee->lname ?? '' }}
                                                                </h5>
                                                                <div>
                                                                    <a href="{{ route('spms.evidence.view', ['id' => $evItem->id, 'download' => 1]) }}" class="btn btn-xs btn-outline-primary mr-2 font-weight-bold">
                                                                        <i class="fas fa-download mr-1"></i> Download File
                                                                    </a>
                                                                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-body p-3 bg-light text-center">
                                                                @if($evIsPdf)
                                                                    <object data="{{ route('spms.evidence.view', $evItem->id) }}#toolbar=0" type="application/pdf" style="width: 100%; height: 100%; min-height: calc(90vh - 100px); border: none; border-radius: 6px;">
                                                                        <embed src="{{ route('spms.evidence.view', $evItem->id) }}" type="application/pdf" style="width: 100%; height: 100%; min-height: calc(90vh - 100px);" />
                                                                    </object>
                                                                @elseif($evIsImg)
                                                                    <img src="{{ route('spms.evidence.view', $evItem->id) }}" class="img-fluid rounded shadow-sm d-block mx-auto" style="max-height: calc(90vh - 100px);" alt="Evidence Attachment">
                                                                @else
                                                                    <div class="py-5">
                                                                        <i class="fas fa-file-archive fa-4x text-secondary mb-3"></i>
                                                                        <h6 class="font-weight-bold text-dark mb-1">{{ basename($evItem->evidence_file) }}</h6>
                                                                        <p class="text-muted small">This file type cannot be previewed inline.</p>
                                                                        <a href="{{ route('spms.evidence.view', ['id' => $evItem->id, 'download' => 1]) }}" class="btn btn-teal font-weight-bold px-4 py-2 mt-2">
                                                                            <i class="fas fa-download mr-1"></i> Download Original File
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted small font-italic">No Evidence Attached</span>
                                        @endif
                                    </td>
                                    <td class="align-middle small">
                                        Summary Report
                                    </td>

                                    {{-- Allotted Budget --}}
                                    <td class="align-middle font-weight-bold">
                                        {{ $item->allotted_budget ?? '-' }}
                                    </td>

                                    {{-- Accountable Personnel Badges List --}}
                                    <td class="text-left align-middle bg-light" style="min-width: 150px;">
                                        <small class="font-weight-bold text-dark d-block mb-1">Assigned To:</small>
                                        @php
                                            $assignedEmps = $item->assignedEmployees;
                                            $empCount = $assignedEmps->count();
                                        @endphp

                                        @if($empCount > 0)
                                            @if($empCount <= 2)
                                                @foreach($assignedEmps as $emp)
                                                    <span class="badge badge-success d-inline-block text-left mb-1 px-2 py-1 shadow-sm" style="white-space: normal; font-size: 11px;">
                                                        &bull; {{ $emp->fname }} {{ $emp->lname }}
                                                    </span>
                                                @endforeach
                                            @else
                                                @foreach($assignedEmps->take(2) as $emp)
                                                    <span class="badge badge-success d-inline-block text-left mb-1 px-2 py-1 shadow-sm" style="white-space: normal; font-size: 11px;">
                                                        &bull; {{ $emp->fname }} {{ $emp->lname }}
                                                    </span>
                                                @endforeach
                                                <button type="button" class="btn btn-xs btn-outline-success font-weight-bold mb-1 shadow-sm" data-toggle="modal" data-target="#assignedEmpsModal{{ $item->id }}">
                                                    +{{ $empCount - 2 }} more
                                                </button>

                                                {{-- Assigned Personnel List Modal --}}
                                                <div class="modal fade" id="assignedEmpsModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content shadow-lg border-0">
                                                            <div class="modal-header bg-white border-bottom py-2">
                                                                <h5 class="modal-title font-weight-bold text-teal" style="font-size: 15px;">
                                                                    <i class="fas fa-users text-teal mr-2"></i> Assigned Personnel ({{ $empCount }} Total)
                                                                </h5>
                                                                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body p-3 bg-light text-left">
                                                                <p class="text-muted small mb-2 font-weight-bold">Objective: {{ Str::limit($item->mfo_pap, 80) }}</p>
                                                                <div class="list-group shadow-sm">
                                                                    @foreach($assignedEmps as $emp)
                                                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                                                            <div>
                                                                                <h6 class="font-weight-bold text-dark mb-0" style="font-size: 13px;">
                                                                                    <i class="fas fa-user-circle text-teal mr-2"></i>{{ $emp->fname }} {{ $emp->lname }}
                                                                                </h6>
                                                                                <small class="text-muted font-weight-bold">{{ $emp->position ?? 'Personnel' }}</small>
                                                                            </div>
                                                                            <span class="badge badge-success px-2 py-1">Assigned</span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted small font-italic">{{ $item->division_accountable ?? 'Unassigned' }}</span>
                                        @endif
                                    </td>

                                    {{-- Rating Q, E, T, A --}}
                                    <td class="align-middle">{{ $item->rating_q ?? '-' }}</td>
                                    <td class="align-middle">{{ $item->rating_e ?? '-' }}</td>
                                    <td class="align-middle">{{ $item->rating_t ?? '-' }}</td>
                                    <td class="align-middle font-weight-bold text-success">{{ $item->rating_ave ? number_format($item->rating_ave, 2) : '-' }}</td>

                                    {{-- Remarks --}}
                                    <td class="text-left small">{!! nl2br(e($item->remarks ?? '')) !!}</td>

                                    {{-- Row Actions (Edit, Delete, Cascade (+)) --}}
                                    <td class="align-middle">
                                        <div class="d-flex flex-column align-items-center">
                                            <button class="btn btn-xs btn-success mb-1 font-weight-bold"
                                                    title="Cascade / Assign Row Target"
                                                    data-toggle="modal"
                                                    data-target="#cascadeModal{{ $item->id }}">
                                                <i class="fas fa-plus fa-xs"></i>
                                            </button>
                                            <button class="btn btn-xs btn-info mb-1"
                                                    title="Edit Row"
                                                    data-toggle="modal"
                                                    data-target="#editModal{{ $item->id }}">
                                                <i class="fas fa-edit fa-xs"></i>
                                            </button>
                                            <form method="POST" action="{{ route('spms.opcr.item.delete', $item->id) }}" onsubmit="return confirm('Delete this OPCR row item and its cascaded assignments?')">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-danger" title="Delete Row">
                                                    <i class="fas fa-times fa-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Cascade Modal --}}
                                <div class="modal fade" id="cascadeModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content shadow-lg border-0">
                                            <div class="modal-header bg-white border-bottom py-2">
                                                <h5 class="modal-title font-weight-bold text-teal"><i class="fas fa-user-plus mr-2"></i> Assign OPCR Item</h5>
                                                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('spms.opcr.item.cascade') }}">
                                                @csrf
                                                <input type="hidden" name="opcr_item_id" value="{{ $item->id }}">

                                                <div class="modal-body text-left">
                                                    <div class="alert alert-info py-2 small mb-3">
                                                        <i class="fas fa-building mr-1"></i> <strong>Office Scoping Restriction:</strong> Only personnel belonging to <strong>{{ $opcr->office->office_name }}</strong> are listed.
                                                    </div>

                                                    <div class="form-group mb-2">
                                                        <label class="font-weight-bold text-dark">Description (MFO/PAP):</label>
                                                        <p class="text-muted small bg-light p-2 rounded border mb-0">{!! nl2br(e($item->mfo_pap)) !!}</p>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">Target / Measure:</label>
                                                        <p class="text-muted small bg-light p-2 rounded border mb-0">{!! nl2br(e($item->success_indicators)) !!}</p>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">Select Employee(s) ({{ $opcr->office->office_abbr }} Department):</label>
                                                        <select name="employee_ids[]" class="form-control select2" multiple required style="width: 100%;">
                                                            @foreach($officeEmployees as $emp)
                                                                <option value="{{ $emp->id }}" {{ $item->assignedEmployees->contains($emp->id) ? 'selected' : '' }}>
                                                                    {{ $emp->fname }} {{ $emp->lname }} ({{ $emp->position ?? 'Personnel' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="form-text text-muted">Hold Ctrl to select multiple employees.</small>
                                                    </div>
                                                </div>

                                                <div class="modal-footer bg-light py-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success btn-sm font-weight-bold px-3">
                                                        <i class="fas fa-check-circle mr-1"></i> Assign Target
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="editModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content shadow-lg border-0">
                                            <div class="modal-header bg-white border-bottom py-2">
                                                <h5 class="modal-title font-weight-bold text-teal"><i class="fas fa-edit mr-2"></i> Edit OPCR Row Item</h5>
                                                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('spms.opcr.item.store') }}">
                                                @csrf
                                                <input type="hidden" name="opcr_id" value="{{ $opcr->id }}">
                                                <input type="hidden" name="item_id" value="{{ $item->id }}">

                                                <div class="modal-body text-left">
                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">Category:</label>
                                                        <select name="category" class="form-control custom-select" required>
                                                            <option value="Core Functions" {{ $item->category == 'Core Functions' ? 'selected' : '' }}>Core Functions (60%)</option>
                                                            <option value="Strategic Functions" {{ $item->category == 'Strategic Functions' ? 'selected' : '' }}>Strategic Functions (20%)</option>
                                                            <option value="Support Functions" {{ $item->category == 'Support Functions' ? 'selected' : '' }}>Support Functions (20%)</option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">MFO / PAPs:</label>
                                                        <textarea name="mfo_pap" class="form-control" rows="3" required>{{ $item->mfo_pap }}</textarea>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">Success Indicators (Targets + Measures):</label>
                                                        <textarea name="success_indicators" class="form-control" rows="3" required>{{ $item->success_indicators }}</textarea>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group mb-3">
                                                                <label class="font-weight-bold text-dark">Allotted Budget:</label>
                                                                <input type="text" name="allotted_budget" class="form-control" value="{{ $item->allotted_budget }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-group mb-3">
                                                                <label class="font-weight-bold text-dark">Division / Accountable:</label>
                                                                <input type="text" name="division_accountable" class="form-control" value="{{ $item->division_accountable }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="modal-footer bg-light py-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-teal btn-sm font-weight-bold px-4">Update Item</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center py-3 text-muted small font-italic">
                                        No items added under {{ $catLabel }} yet.
                                    </td>
                                </tr>
                            @endforelse
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sign-off Footer Section --}}
        <div class="card-footer bg-white pt-4 pb-3 border-top">
            <div class="row text-center font-weight-bold text-dark" style="font-size: 12px;">
                <div class="col-md-3 mb-3">
                    <p class="text-muted mb-4 font-italic">Discussed with:</p>
                    <u class="d-block text-uppercase">{{ $opcr->head ? ($opcr->head->fname . ' ' . $opcr->head->lname) : 'OFFICE HEAD NAME' }}</u>
                    <small class="text-muted">Head, {{ $opcr->office->office_name }}</small>
                </div>
                <div class="col-md-3 mb-3">
                    <p class="text-muted mb-4 font-italic">Assessed by:</p>
                    <u class="d-block text-uppercase">MARC ALEXEI CAESAR B. BADAJOS</u>
                    <small class="text-muted">Vice President For Administration And Finance Office</small>
                </div>
                <div class="col-md-3 mb-3">
                    <p class="text-muted mb-4 font-italic">Reviewed by:</p>
                    <u class="d-block text-uppercase">ROSE ANN G. JOCSON, Ph.D.</u>
                    <small class="text-muted">Performance Management Team</small>
                </div>
                <div class="col-md-3 mb-3">
                    <p class="text-muted mb-4 font-italic">Final Rating by:</p>
                    <u class="d-block text-uppercase">ERNIE T. UY, RN, JD</u>
                    <small class="text-muted">Municipal Mayor / Head of Agency</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add OPCR Row Modal (Light Header) --}}
<div class="modal fade" id="addOpcrRowModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title font-weight-bold text-teal"><i class="fas fa-plus-circle mr-2"></i> Add New OPCR Row Item</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('spms.opcr.item.store') }}">
                @csrf
                <input type="hidden" name="opcr_id" value="{{ $opcr->id }}">

                <div class="modal-body text-left">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Category:</label>
                        <select name="category" class="form-control custom-select" required>
                            <option value="Core Functions">Core Functions (60%)</option>
                            <option value="Strategic Functions">Strategic Functions (20%)</option>
                            <option value="Support Functions">Support Functions (20%)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Major Final Output (MFO / PAPs):</label>
                        <textarea name="mfo_pap" class="form-control" rows="3" placeholder="Enter objective or deliverable description..." required></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Success Indicators (Targets + Measures):</label>
                        <textarea name="success_indicators" class="form-control" rows="3" placeholder="Enter target metrics and measures..." required></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Link to Source Document (URL):</label>
                        <input type="url" name="link_to_source" class="form-control" placeholder="https://mabinay.gov.ph/mandates/ordinance-2026">
                        <small class="text-muted">Optional web link to the official mandate, municipal ordinance, or reference document.</small>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Allotted Budget:</label>
                                <input type="text" name="allotted_budget" class="form-control" placeholder="e.g. All Office / 50,000">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Division / Individuals Accountable:</label>
                                <input type="text" name="division_accountable" class="form-control" placeholder="e.g. All Office / Staff">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal btn-sm font-weight-bold px-4">Save Row Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('[data-toggle="popover"]').popover();
    });
</script>
@endpush
