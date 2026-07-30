@php
    $role      = auth()->guard($guard)->user()->role;
    $isStaff   = $role !== 'employee';           // HR / administrators
    $isAdmin   = $role === 'Administrator';
    $isWeb     = $guard === 'web';
    $user      = auth()->guard($guard)->user();
    $isHead    = $isStaff || ($user && method_exists($user, 'isOfficeHead') && $user->isOfficeHead());

    $careersOpen = request()->is('career*') || request()->is('applications*')
                || request()->is('ete*') || request()->is('interview*');
    $spmsOpen    = request()->is('spms*');
@endphp

<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        {{-- ---------------------------------------------------------- Main --}}
        <li class="nav-header">Main</li>

        <li class="nav-item">
            <a href="{{ route('dashboard') }}" title="Dashboard"
               class="nav-link {{ request()->is('dashboard') || request()->is('myaccount') || request()->is('pending/*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-gauge-high"></i>
                <p>Dashboard</p>
            </a>
        </li>

        @if($isStaff)
            {{-- ---------------------------------------------------- Personnel --}}
            <li class="nav-header">Personnel</li>

            <li class="nav-item">
                <a href="{{ route('emp_list') }}" title="Employees"
                   class="nav-link {{ request()->is('employees') || request()->is('employees/*') || request()->is('tirdeness*') || request()->is('pds/*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-users"></i>
                    <p>Employees</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('officeList') }}" title="Offices"
                   class="nav-link {{ request()->is('office*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-building"></i>
                    <p>Offices</p>
                </a>
            </li>
        @endif

        @if($role === 'employee')
            <li class="nav-header">My Records</li>

            <li class="nav-item">
                <a href="{{ route('empPDS') }}" title="Personal Data Sheet"
                   class="nav-link {{ request()->is('pds') || request()->is('pds/*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-id-card"></i>
                    <p>PDS</p>
                </a>
            </li>
        @endif

        {{-- ------------------------------------------------- Time and leave --}}
        <li class="nav-header">Time &amp; Leave</li>

        <li class="nav-item">
            <a href="{{ route('dtr-read') }}" title="Daily Time Record"
               class="nav-link {{ request()->is('dtr') || request()->is('dtr/*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-clock"></i>
                <p>DTR</p>
            </a>
        </li>

        @if($isWeb)
            <li class="nav-item">
                <a href="{{ route('leavesRead') }}" title="Leave"
                   class="nav-link {{ request()->is('leave') || request()->is('leave/*') || request()->is('leaves*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-calendar-check"></i>
                    <p>Leave</p>
                </a>
            </li>
        {{-- Casual employees file leave too, so this is no longer
             `emp_status == 1`. The eligible statuses live in config/leave.php;
             isLeaveEligible() is the same check the dashboard panel and HR's
             employee pickers use, so the nav link can never again disagree with
             what the rest of the app will let the person do. --}}
        @elseif(auth()->guard($guard)->user()->isLeaveEligible())
            <li class="nav-item">
                <a href="{{ route('leavesReadEmp') }}" title="Leave"
                   class="nav-link {{ request()->is('leave') || request()->is('leave/*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-calendar-check"></i>
                    <p>Leave</p>
                </a>
            </li>
        @endif

        @if($isStaff)
            <li class="nav-item">
                <a href="{{ route('readTiredness') }}" title="Tardiness"
                   class="nav-link {{ request()->is('tardiness*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-hourglass-half"></i>
                    <p>Tardiness</p>
                </a>
            </li>
        @endif

        @if(\App\Http\Middleware\EnsureFaceRegistrar::allows())
            <li class="nav-item">
                <a href="{{ route('attendanceMonitor') }}" title="Face Attendance"
                   class="nav-link {{ request()->is('attendance-admin*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-street-view"></i>
                    <p>Face Attendance</p>
                </a>
            </li>
        @endif

        @if(\App\Http\Middleware\EnsureFaceRegistrar::allows())
            <li class="nav-item">
                <a href="{{ route('eventIndex') }}" title="Events"
                   class="nav-link {{ request()->is('event*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-calendar-days"></i>
                    <p>Events</p>
                </a>
            </li>
        @endif

        {{-- ------------------------------------------------ SPMS / Performance --}}
        <li class="nav-header">Performance</li>

        <li class="nav-item">
            <a href="{{ route('spms.drive') }}" title="SPMS"
               class="nav-link {{ $spmsOpen ? 'active' : '' }}">
                <i class="nav-icon fas fa-folder"></i>
                <p>SPMS</p>
            </a>
        </li>

        @if($isWeb)
            {{-- -------------------------------------------------- Recruitment --}}
            <li class="nav-header">Recruitment</li>

            <li class="nav-item has-treeview {{ $careersOpen ? 'menu-open' : '' }}">
                <a href="#" title="Careers" class="nav-link {{ $careersOpen ? 'active' : '' }}">
                    <i class="nav-icon fas fa-briefcase"></i>
                    <p>
                        Careers
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    {{-- One entry, not two. A vacancy is published from the position
                         it belongs to, so "Job Openings" no longer exists separately. --}}
                    <li class="nav-item">
                        <a href="{{ route('positionDescriptionList') }}" class="nav-link {{ request()->is('position-descriptions*') || request()->is('career') ? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Positions &amp; Vacancies</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('appList') }}" class="nav-link {{ request()->is('career/applications*') ? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Applications</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('interviewEvaluationList') }}" class="nav-link {{ request()->is('interview*') ? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Interview Assessment</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('psbMembers') }}" class="nav-link {{ request()->is('psb*') ? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Selection Board</p>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        @if($isAdmin)
            {{-- ---------------------------------------------- Administration --}}
            <li class="nav-header">Administration</li>

            <li class="nav-item">
                <a href="{{ route('ulist') }}" title="Users"
                   class="nav-link {{ request()->is('user*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-user-shield"></i>
                    <p>Users</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('settings') }}" title="Settings"
                   class="nav-link {{ request()->is('settings') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-sliders"></i>
                    <p>Settings</p>
                </a>
            </li>
        @endif
    </ul>
</nav>
