<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HRIS - LGU Mabinay {{ isset($title) ? ' | '.$title : '' }}</title>
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <!-- fullCalendar -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fullcalendar/main.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/style.css') }}">
    <!-- HRIS modern theme (must load after AdminLTE + custom style) -->
    <link rel="stylesheet" href="{{ asset('css/hris-theme.css') }}">
    <!-- QR -->
    <script src="{{ asset('template/dist/js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/qrcode.min.js') }}"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('mabinay-logo.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
    .profile-image {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        margin-top: -7px;
        margin-right: 10px;
    }
    .img-circle1 {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 2px solid #ddd !important;
        display: block !important;
    }

    .nav-item.dropdown .dropdown-menu.notifications{
        width: 500px !important; /* Or whatever width you prefer */
        max-width: none !important; /* Ensure it doesn't get constrained by max-width */
    }
    .btn-success1 {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
    body.modal-open {
        overflow: hidden;   
    }
    .privacy-container h3 {
        margin-top: 1.5rem;
    }
    .privacy-container ul {
        padding-left: 20px;
    }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse layout-navbar-fixed text-sm">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-warning">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars text-success1"></i></a>
                </li>
            </ul>
            
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                @if($guard == "web")
                    @include('layouts.notif-admin')
                @else
                    @include('layouts.notif-employee')
                @endif

                @if(!empty($guard))
                    <li class="nav-item" id="interviewRatingNavItem" style="{{ ($activeInterviewRatingCount ?? 0) > 0 ? '' : 'display:none;' }}">
                        <a class="nav-link text-success1"
                           href="{{ route('interviewAssignments') }}"
                           id="interviewRatingNavLink"
                           title="Interview Ratings">
                            <i class="fas fa-comments"></i>
                            <span class="badge badge-danger navbar-badge" id="interviewRatingBadge">{{ $activeInterviewRatingCount ?? 0 }}</span>
                        </a>
                    </li>
                @endif
                
                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-success1" href="#" role="button" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @php
                            $profileUrl = asset('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                            $profilePath = public_path('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                        @endphp
                        <img src="{{ file_exists($profilePath) && isset(auth()->guard($guard)->user()->profile) ? $profileUrl : asset('Profile/Employee/default.png') }}" alt="User Image" class="profile-image">
                    </a>                    
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-power-off fa-xs"></i> Sign Out
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-light-warning elevation-2">
            <!-- Brand Logo -->
            <a href="{{ route('dashboard') }}" class="brand-link">
                <img src="{{ asset('mabinay-logo.png') }}" alt="Municipality of Mabinay Official Seal" class="brand-image">
                <span class="brand-text">
                    HRIS
                    <small>LGU Mabinay</small>
                </span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <hr class="sidebar-divider">
                <!-- Sidebar user panel (optional) -->
                <div class="user-panel mt-4 d-flex">
                    <div class="image">
                        @php
                            $profileUrl = asset('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                            $profilePath = public_path('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                        @endphp
                        <img src="{{ file_exists($profilePath) && auth()->guard($guard)->check() && auth()->guard($guard)->user()->profile ? $profileUrl : asset('Profile/Employee/default.png') }}" 
                             class="img-circle1 elevation-2" 
                             alt="User Image">
                    </div>                    
                    <div class="info ml-2" style="margin-top: -7px;">
                        <span class="d-block">
                            {{ ucwords(strtolower(auth()->guard($guard)->user()->fname)) }} {{ ucwords(strtolower(auth()->guard($guard)->user()->lname)) }}
                        </span>
                        <span class="d-block text-sm text-muted">
                            @if($guard == "employee")
                                {{ auth()->guard($guard)->user()->emp_status == 1 ? auth()->guard($guard)->user()->position : 'Employee' }}
                            @else
                                {{ ucfirst(auth()->guard($guard)->user()->role) }}
                            @endif
                        </span>
                    </div>
                </div>                
                <hr>
                <!-- Sidebar Menu -->
                @include('partials.control')
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper" style="padding-top: 20px;">
            <!-- Main content -->
            <div class="content">
                @yield('body')
            </div>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        
        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->

        @if($guard == "employee" && auth()->guard($guard)->user()->dpn == 0)
            <div class="modal fade show" id="dpnModal" tabindex="-1" aria-modal="true" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                    <div class="modal-content shadow">
                        <div class="modal-body px-4 py-3">
                            @include('data-privacy') 
                        </div>

                        <div class="modal-footer justify-content-between px-4 py-3">
                            <small class="text-muted">Municipality of Mabinay &copy; {{ now()->year }}</small>

                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('dataPrivacyNotice') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success px-4 mr-1">
                                        I Accept
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger px-4">
                                        Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.body.classList.add('modal-open');
            </script>
        @endif

        <div id="dataPrivacyModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="dataPrivacyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content shadow">
                    <div class="modal-body px-4 py-3">
                        @include('data-privacy') 
                    </div>

                    <div class="modal-footer justify-content-between px-4 py-3">
                        <small class="text-muted">Municipality of Mabinay &copy; {{ now()->year }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Footer -->
        <footer class="main-footer">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;">
                <div>
                    <strong>&copy; {{ now()->year }} Municipality of Mabinay.</strong> All rights reserved.
                    &nbsp;|&nbsp;
                    <a href="#" data-toggle="modal" data-target="#dataPrivacyModal">
                        Data Privacy Policy
                    </a>
                </div>
                <div class="d-none d-sm-inline">
                    Maintained and Managed by the <strong>MIS Office</strong>.
                </div>
            </div>
        </footer>


    </div>

@include('script.masterScript')
@include('script.officeScript')
@if(request()->is('pds/family-bg/*') || request()->is('pds/family-bg'))
    @include('script.familybgScript')
@endif
@if(request()->is('employees') || request()->is('employees/*'))
    @include('script.employeeScript')
@endif
@if(request()->is('user') || request()->is('user/*'))
    @include('script.userScript')
@endif
@if(request()->is('pds') || request()->is('pds/personal-info') || request()->is('pds/personal-info/*'))
    @include('script.personInfoScript')
@endif
@if(request()->is('pds/educ-bg/*') || request()->is('pds/educ-bg'))
    @include('script.educbgScript')
@endif
@if(request()->is('pds/eligibility/*') || request()->is('pds/eligibility') || isset($eligibilityedit))
    @include('script.eligibilityScript')
@endif
@if(request()->is('pds/work-experience/*') || request()->is('pds/work-experience') || isset($workexperienceedit))
    @include('script.WorkExperienceScript')
@endif
@if(request()->is('pds/voluntary-work/*') || request()->is('pds/voluntary-work-edit/*') || request()->is('pds/voluntary-work') || isset($workexperienceedit))
    @include('script.voluntaryWorksScript')
@endif
@if(request()->is('pds/learning-dev/*') || request()->is('pds/learning-dev-edit/*') || request()->is('pds/learning-dev') || isset($learningdevedit))
    @include('script.learningDevScript')
@endif
@if(request()->is('pds/other-info/*') || request()->is('pds/other-info-edit/*') || request()->is('pds/other-info'))
    @include('script.otherInfoScript')
@endif
@if(request()->is('pds/info-question/*') || request()->is('pds/info-question-edit/*') || request()->is('pds/info-question'))
    @include('script.infoquestionScript')
@endif
@if(request()->is('pds/references*'))
    @include('script.referenceScript')
@endif
@if(request()->is('pds/government-id*'))
    @include('script.govidScript')
@endif
@if(request()->is('leaves/*') || request()->is('leaves') || request()->is('leave*') || request()->is('leave/history') || request()->is('leave/history*'))
    @include('script.leaveCreditScript')
@endif
@if(request()->is('pending/*'))
    @include('script.pendingScript')
@endif
@if(request()->is('pds/signature/*') || request()->is('pds/signature'))
    @include('script.signatureScript')
@endif
@if(!empty($guard))
<script>
document.addEventListener('DOMContentLoaded', function () {
    let interviewRatingNavRunning = false;

    function refreshInterviewRatingNav() {
        if (interviewRatingNavRunning) {
            return;
        }

        interviewRatingNavRunning = true;
        fetch("{{ route('interviewAssignmentsStatus') }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        })
            .then(response => response.json())
            .then(function (data) {
                const count = parseInt(data.count || 0, 10);
                const item = document.getElementById('interviewRatingNavItem');
                const badge = document.getElementById('interviewRatingBadge');
                const link = document.getElementById('interviewRatingNavLink');

                if (!item || !badge || !link) {
                    return;
                }

                badge.textContent = count;
                link.setAttribute('href', "{{ route('interviewAssignments') }}");
                if (count > 0) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            })
            .catch(function () {})
            .finally(function () {
                interviewRatingNavRunning = false;
            });
    }

    refreshInterviewRatingNav();
    window.addEventListener('focus', refreshInterviewRatingNav);
    window.addEventListener('pageshow', refreshInterviewRatingNav);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refreshInterviewRatingNav();
        }
    });
    setInterval(refreshInterviewRatingNav, 1000);
});
</script>
@endif
</body>
</html>
