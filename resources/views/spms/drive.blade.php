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
    .folder-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        padding: 25px 15px;
        text-align: center;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        text-decoration: none !important;
        display: block;
        position: relative;
    }
    .folder-card:hover {
        box-shadow: 0 6px 15px rgba(0,0,0,0.08);
        transform: translateY(-2px);
        border-color: #cbd5e1;
    }
    .folder-card.locked {
        background: #f8fafc;
        border-style: dashed;
        cursor: not-allowed;
    }
    .folder-card.locked:hover {
        transform: none;
        box-shadow: none;
    }
    .folder-icon-img {
        width: 70px;
        height: 70px;
        margin-bottom: 12px;
    }
    .folder-title {
        font-weight: 700;
        color: #334155;
        font-size: 15px;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
            <i class="fas fa-info-circle text-info mr-1"></i> Dashboard &nbsp;/&nbsp; Drive
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
            {{-- + New Button --}}
            <div class="dropdown mb-3">
                <button class="btn btn-teal btn-block py-2 dropdown-toggle text-center shadow-sm" type="button" id="driveNewBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-plus mr-2"></i> New
                </button>
                <div class="dropdown-menu w-100 shadow border-0" aria-labelledby="driveNewBtn">
                    @if($isHead)
                        <a class="dropdown-item py-2" href="{{ route('spms.opcr') }}"><i class="fas fa-folder text-warning mr-2"></i> OPCR Document</a>
                    @else
                        <a class="dropdown-item py-2 text-muted" href="#" onclick="alert('Access Restricted: OPCR is reserved for Office Heads only.'); return false;"><i class="fas fa-lock text-warning mr-2"></i> OPCR Document (Locked)</a>
                    @endif
                    <a class="dropdown-item py-2" href="{{ route('spms.ipcr') }}"><i class="fas fa-folder text-warning mr-2"></i> My IPCR Matrix</a>
                </div>
            </div>

            {{-- Navigation Items Card --}}
            <div class="drive-sidebar-card shadow-sm p-2">
                <a href="{{ route('spms.drive') }}" class="drive-nav-item active rounded mb-1">
                    <i class="fas fa-th-large text-primary mr-3"></i> My Drive
                </a>

                @if($isHead)
                    <a href="{{ route('spms.opcr') }}" class="drive-nav-item rounded mb-1">
                        <i class="fas fa-file-alt text-secondary mr-3"></i> OPCR Documents
                    </a>
                @else
                    <a href="#" class="drive-nav-item rounded mb-1 text-muted" onclick="alert('Access Restricted: OPCR is reserved for Office Heads only.'); return false;" style="cursor: not-allowed;">
                        <i class="fas fa-lock text-warning mr-3"></i> OPCR Documents
                        <span class="badge badge-warning ml-auto small"><i class="fas fa-lock fa-xs"></i> Locked</span>
                    </a>
                @endif

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

        {{-- Main Content Area: Outer Card Container with Folder Grid --}}
        <div class="col-md-9 col-lg-9">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 10px; min-height: 420px; background: #ffffff;">
                <div class="row">
                    {{-- OPCR Folder (Locked for Regular Employees) --}}
                    <div class="col-sm-6 col-md-4 col-lg-4 mb-4">
                        @if($isHead)
                            <a href="{{ route('spms.opcr') }}" class="folder-card">
                                <svg class="folder-icon-img" viewBox="0 0 24 24" fill="#f59e0b" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.5 21a2.5 2.5 0 002.5-2.5V9a2.5 2.5 0 00-2.5-2.5h-7.172a2 2 0 01-1.414-.586l-1.828-1.828A2 2 0 007.172 3.5H4.5A2.5 2.5 0 002 6v12.5A2.5 2.5 0 004.5 21h15z"/>
                                </svg>
                                <h6 class="folder-title">OPCR</h6>
                            </a>
                        @else
                            <a href="#" class="folder-card locked opacity-75" onclick="alert('Access Restricted: OPCR is reserved for Office Heads only. Regular employees can only access IPCR.'); return false;">
                                <span class="badge badge-warning position-absolute" style="top: 10px; right: 10px;">
                                    <i class="fas fa-lock"></i> Locked
                                </span>
                                <svg class="folder-icon-img" viewBox="0 0 24 24" fill="#94a3b8" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.5 21a2.5 2.5 0 002.5-2.5V9a2.5 2.5 0 00-2.5-2.5h-7.172a2 2 0 01-1.414-.586l-1.828-1.828A2 2 0 007.172 3.5H4.5A2.5 2.5 0 002 6v12.5A2.5 2.5 0 004.5 21h15z"/>
                                </svg>
                                <h6 class="folder-title text-muted">OPCR <i class="fas fa-lock fa-xs text-warning ml-1"></i></h6>
                            </a>
                        @endif
                    </div>

                    {{-- DPCR Folder --}}
                    <div class="col-sm-6 col-md-4 col-lg-4 mb-4">
                        <a href="#" class="folder-card" onclick="alert('DPCR (Division Performance Commitment) Folder.'); return false;">
                            <svg class="folder-icon-img" viewBox="0 0 24 24" fill="#f59e0b" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19.5 21a2.5 2.5 0 002.5-2.5V9a2.5 2.5 0 00-2.5-2.5h-7.172a2 2 0 01-1.414-.586l-1.828-1.828A2 2 0 007.172 3.5H4.5A2.5 2.5 0 002 6v12.5A2.5 2.5 0 004.5 21h15z"/>
                            </svg>
                            <h6 class="folder-title">DPCR</h6>
                        </a>
                    </div>

                    {{-- IPCR Folder (Open for All Employees) --}}
                    <div class="col-sm-6 col-md-4 col-lg-4 mb-4">
                        <a href="{{ route('spms.ipcr') }}" class="folder-card">
                            <svg class="folder-icon-img" viewBox="0 0 24 24" fill="#f59e0b" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19.5 21a2.5 2.5 0 002.5-2.5V9a2.5 2.5 0 00-2.5-2.5h-7.172a2 2 0 01-1.414-.586l-1.828-1.828A2 2 0 007.172 3.5H4.5A2.5 2.5 0 002 6v12.5A2.5 2.5 0 004.5 21h15z"/>
                            </svg>
                            <h6 class="folder-title">IPCR</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
