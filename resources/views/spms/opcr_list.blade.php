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
    .drive-sidebar-card {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        background: #ffffff;
    }
    .drive-nav-item {
        color: #495057;
        font-size: 14px;
        font-weight: 500;
        padding: 10px 18px;
        border: none;
        display: flex;
        align-items: center;
        text-decoration: none !important;
    }
    .drive-nav-item:hover {
        background-color: #f8f9fa;
        color: #16a085;
    }
    .drive-nav-item.active {
        background-color: #e8f4f8;
        color: #007bff;
        font-weight: 600;
        border-left: 4px solid #007bff;
    }
    .opcr-row-item {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
        text-decoration: none !important;
    }
    .opcr-row-item:hover {
        background-color: #f8fafc;
    }
    .badge-weight-red {
        color: #b91c1c;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    .breadcrumb-drive {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }
    .pdf-hover-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fca5a5;
        border-radius: 20px;
        padding: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease-in-out;
        white-space: nowrap;
        overflow: hidden;
        width: 34px;
        height: 34px;
        min-width: 34px;
    }
    .pdf-hover-btn .btn-text {
        max-width: 0;
        opacity: 0;
        margin-left: 0;
        transition: max-width 0.3s ease, opacity 0.25s ease, margin-left 0.25s ease;
        display: inline-block;
        white-space: nowrap;
        font-size: 12px;
    }
    .pdf-hover-btn:hover {
        width: auto;
        padding: 6px 12px;
        background-color: #dc2626;
        color: #ffffff;
        border-color: #dc2626;
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);
    }
    .pdf-hover-btn:hover .btn-text {
        max-width: 100px;
        opacity: 1;
        margin-left: 6px;
    }
</style>

<div class="container-fluid py-2">
    {{-- Breadcrumb Bar --}}
    <div class="d-flex align-items-center mb-3">
        <span class="breadcrumb-drive">
            <i class="fas fa-info-circle text-info mr-1"></i> Dashboard &nbsp;/&nbsp; Drive &nbsp;/&nbsp; OPCR
        </span>
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

    <div class="row">
        {{-- Left Navigation Sidebar --}}
        <div class="col-md-3 col-lg-3 mb-4">
            <div class="dropdown mb-3">
                <button class="btn btn-teal btn-block py-2 dropdown-toggle text-center shadow-sm" type="button" id="driveNewBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-plus mr-2"></i> New
                </button>
                <div class="dropdown-menu w-100 shadow border-0" aria-labelledby="driveNewBtn">
                    <a class="dropdown-item py-2" href="#" data-toggle="modal" data-target="#createOpcrModal"><i class="fas fa-folder text-warning mr-2"></i> New OPCR Document</a>
                    <a class="dropdown-item py-2" href="{{ route('spms.ipcr') }}"><i class="fas fa-folder text-warning mr-2"></i> My IPCR</a>
                </div>
            </div>

            <div class="drive-sidebar-card shadow-sm p-2">
                <a href="{{ route('spms.drive') }}" class="drive-nav-item rounded mb-1">
                    <i class="fas fa-th-large text-secondary mr-3"></i> My Drive
                </a>
                <a href="{{ route('spms.opcr') }}" class="drive-nav-item active rounded mb-1">
                    <i class="fas fa-file-alt text-primary mr-3"></i> OPCR Documents
                </a>
                <a href="{{ route('spms.ipcr') }}" class="drive-nav-item rounded">
                    <i class="fas fa-id-badge text-secondary mr-3"></i> My IPCR
                </a>
            </div>
        </div>

        {{-- Main OPCR List Content Area --}}
        <div class="col-md-9 col-lg-9">
            <div class="card shadow-sm border-0 p-3" style="border-radius: 10px; min-height: 420px; background: #ffffff;">
                {{-- Search Bar matching Screenshot 2 --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-building text-teal mr-2"></i> {{ $activeOffice->office_name ?? 'Office' }} OPCR Documents
                    </h6>
                    <form method="GET" action="{{ route('spms.opcr') }}" class="form-inline">
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <input type="text" name="search" class="form-control border-right-0" placeholder="Search" value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary bg-white border-left-0" type="submit">
                                    <i class="fas fa-search text-muted"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- OPCR List Table Matching Screenshot 2 --}}
                <div class="list-group list-group-flush border-top">
                    @forelse($opcrs as $opcr)
                        <div class="opcr-row-item list-group-item py-3 px-3">
                            <div class="row align-items-center">
                                {{-- Left User Icon & Name --}}
                                <div class="col-md-5 d-flex align-items-center">
                                    <a href="{{ route('spms.opcr.matrix', $opcr->id) }}" class="d-flex align-items-center text-decoration-none">
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mr-3" style="width: 38px; height: 38px; min-width: 38px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold text-dark mb-0 text-uppercase" style="font-size: 13px;">
                                                {{ $opcr->head ? ($opcr->head->fname . ' ' . $opcr->head->lname) : ($opcr->office->office_name ?? 'OFFICE HEAD') }}
                                            </h6>
                                            <small class="text-muted font-weight-bold">
                                                OPCR FOR {{ $opcr->year }} (Semester {{ $opcr->semester }})
                                            </small>
                                        </div>
                                    </a>
                                </div>

                                {{-- Weight Badges & Far-Right PDF Hover Button --}}
                                <div class="col-md-7 d-flex justify-content-end align-items-center flex-wrap">
                                    <a href="{{ route('spms.opcr.matrix', $opcr->id) }}" class="d-flex align-items-center text-decoration-none mr-3">
                                        <span class="badge-weight-red mr-4">CORE FUNCTIONS (60%)</span>
                                        <span class="badge-weight-red mr-4">STRATEGIC FUNCTIONS (20%)</span>
                                        <span class="badge-weight-red">SUPPORT FUNCTIONS (20%)</span>
                                    </a>
                                    <button type="button" class="pdf-hover-btn ml-2" data-toggle="modal" data-target="#printOpcrModal{{ $opcr->id }}" title="Preview &amp; Print OPCR Form (PDF)">
                                        <i class="fas fa-file-pdf"></i>
                                        <span class="btn-text">Print OPCR</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-3x text-warning mb-3 d-block"></i>
                            <p class="mb-2 font-weight-bold">No OPCR documents found for {{ $activeOffice->office_name ?? 'this office' }}.</p>
                            <button class="btn btn-sm btn-teal px-3 font-weight-bold" data-toggle="modal" data-target="#createOpcrModal">
                                <i class="fas fa-plus mr-1"></i> Create OPCR Document
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create OPCR Modal --}}
<div class="modal fade" id="createOpcrModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title font-weight-bold text-teal"><i class="fas fa-plus-circle mr-2"></i> Create OPCR Document</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('spms.opcr.create') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Office / Department:</label>
                        <select name="office_id" class="form-control custom-select" required>
                            @foreach($managedOffices as $off)
                                <option value="{{ $off->id }}" {{ ($activeOffice && $activeOffice->id == $off->id) ? 'selected' : '' }}>
                                    {{ $off->office_name }} ({{ $off->office_abbr }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Year:</label>
                        <select name="year" class="form-control custom-select" required>
                            @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Semester:</label>
                        <select name="semester" class="form-control custom-select" required>
                            <option value="1" {{ $semester == 1 ? 'selected' : '' }}>1st Half (Jan - Jun)</option>
                            <option value="2" {{ $semester == 2 ? 'selected' : '' }}>2nd Half (Jul - Dec)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal btn-sm font-weight-bold px-4">Create OPCR</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($opcrs as $opcr)
    <div class="modal fade" id="printOpcrModal{{ $opcr->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title font-weight-bold text-danger" style="font-size: 15px;">
                        <i class="fas fa-file-pdf text-danger mr-2"></i> OPCR Form Preview &bull; {{ $opcr->office->office_name }} ({{ $opcr->year }})
                    </h5>
                    <div>
                        <button type="button" onclick="printOpcrListIframe('opcrListIframe{{ $opcr->id }}')" class="btn btn-xs btn-outline-dark font-weight-bold mr-2">
                            <i class="fas fa-print mr-1"></i> Print Document
                        </button>
                        <a href="{{ route('spms.opcr.print', $opcr->id) }}" target="_blank" class="btn btn-xs btn-outline-teal font-weight-bold mr-2">
                            <i class="fas fa-external-link-alt mr-1"></i> Open in New Tab
                        </a>
                        <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body p-0 bg-dark text-center" style="overflow: hidden;">
                    <iframe id="opcrListIframe{{ $opcr->id }}" src="{{ route('spms.opcr.print', ['id' => $opcr->id, 'embed' => 1]) }}" style="width: 100%; height: 75vh; border: none;" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
<script>
    function printOpcrListIframe(iframeId) {
        var iframe = document.getElementById(iframeId);
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }
    }
</script>
@endpush
@endsection
