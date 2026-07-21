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
                    <a class="dropdown-item py-2" href="{{ route('spms.ipcr') }}"><i class="fas fa-folder text-warning mr-2"></i> My IPCR Matrix</a>
                </div>
            </div>

            <div class="drive-sidebar-card shadow-sm p-2">
                <a href="{{ route('spms.drive') }}" class="drive-nav-item rounded mb-1">
                    <i class="fas fa-th-large text-secondary mr-3"></i> My Drive
                </a>
                <a href="{{ route('spms.opcr') }}" class="drive-nav-item active rounded mb-1">
                    <i class="fas fa-file-alt text-primary mr-3"></i> OPCR Documents
                </a>
                <a href="{{ route('spms.ipcr') }}" class="drive-nav-item rounded mb-3">
                    <i class="fas fa-id-badge text-secondary mr-3"></i> My IPCR
                </a>

                <div class="dropdown-divider my-2"></div>

                <a href="#" class="drive-nav-item rounded mb-1">
                    <i class="fas fa-user text-secondary mr-3"></i> PMT
                </a>
                <a href="#" class="drive-nav-item rounded mb-1">
                    <i class="fas fa-user text-secondary mr-3"></i> Personnel
                </a>

                <div class="dropdown-divider my-2"></div>

                <a href="#" class="drive-nav-item rounded">
                    <i class="fas fa-cog text-secondary mr-3"></i> MFO Settings
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
                        <a href="{{ route('spms.opcr.matrix', $opcr->id) }}" class="opcr-row-item list-group-item py-3 px-3">
                            <div class="row align-items-center">
                                {{-- Left User Icon & Name --}}
                                <div class="col-md-4 d-flex align-items-center">
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
                                </div>

                                {{-- Weight Badges Matching Screenshot 2 --}}
                                <div class="col-md-8 d-flex justify-content-end align-items-center flex-wrap">
                                    <span class="badge-weight-red mr-4">CORE FUNCTIONS (60%)</span>
                                    <span class="badge-weight-red mr-4">STRATEGIC FUNCTIONS (20%)</span>
                                    <span class="badge-weight-red mr-2">SUPPORT FUNCTIONS (20%)</span>
                                </div>
                            </div>
                        </a>
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
@endsection
