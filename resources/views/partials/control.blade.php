<nav style="margin-right: -30px; !important">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link text-success1 {{ request()->is('dashboard') || request()->is('myaccount') || request()->is('pending/*') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
            </a>
        </li>
        @if(auth()->guard($guard)->user()->role !== "employee")
            <li class="nav-item">
                <a href="{{ route('emp_list') }}" class="nav-link text-success1 {{ request()->is('employees') || request()->is('employees/*') || request()->is('tirdeness*') || request()->is('pds/*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-users"></i>
                    <p>Employees</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('readTiredness') }}" class="nav-link text-success1 {{ request()->is('tardiness*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-hourglass-start"></i>
                    <p>Tardiness</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('officeList') }}" class="nav-link text-success1 {{ request()->is('office*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-building"></i>
                    <p>Offices</p>
                </a>
            </li>  
            <li class="nav-item">
                <a href="#" class="nav-link text-success1 {{ request()->is('payslip') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-file-invoice"></i>
                    <p>PAYSLIP</p>
                </a>
            </li>
        @endif

        @if(auth()->guard($guard)->user()->role == "employee")
            <li class="nav-item">
                <a href="{{ route('empPDS') }}" class="nav-link text-success1 {{ request()->is('pds') || request()->is('pds/*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-clipboard"></i>
                    <p>PDS</p>
                </a>
            </li>
        @endif

        @if($guard == "web")
            <li class="nav-item">
                <a href="{{ route('leavesRead') }}" class="nav-link text-success1 {{ request()->is('leave') || request()->is('leave/*') || request()->is('leaves*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-calendar-check"></i>
                    <p>LEAVE</p>
                </a>
            </li>
        @else
            @if(auth()->guard($guard)->user()->emp_status == 1)
                <li class="nav-item">
                    <a href="{{ route('leavesReadEmp') }}" class="nav-link text-success1 {{ request()->is('leave') || request()->is('leave/*') ? 'active' : '' }}">
                        <i class="pt-1 nav-icon fas fa-calendar-check"></i>
                        <p>LEAVE</p>
                    </a>
                </li>
            @endif
        @endif

        <li class="nav-item">
            <a href="{{ route('dtr-read') }}" class="nav-link text-success1 {{ request()->is('dtr') || request()->is('dtr/*') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-clock"></i>
                <p>DTR</p>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="{{ route('eventIndex') }}" class="nav-link text-success1 {{ request()->is('event*') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-calendar"></i>
                <p>Events</p>
            </a>
        </li>

        @if($guard == "web")
        <li class="nav-item has-treeview {{ request()->is('career*') || request()->is('applications*') || request()->is('ete*') || request()->is('interview*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link text-success1 {{ request()->is('career*') || request()->is('applications*') || request()->is('ete*') || request()->is('interview*') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-briefcase"></i>
                <p>
                    Careers
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="{{ route('jlist') }}" class="nav-link text-success1">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Job Openings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('appList') }}" class="nav-link text-success1">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Applications</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('eteEvaluationList') }}" class="nav-link text-success1">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ETE Evaluation</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('interviewEvaluationList') }}" class="nav-link text-success1">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Interview Assessment</p>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        @if(auth()->guard($guard)->user()->role == "Administrator")

            <li class="nav-item">
                <a href="{{ route('ulist') }}" class="nav-link text-success1 {{ request()->is('user*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-user-cog"></i>
                    <p>Users</p>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="{{ route('settings') }}" class="nav-link text-success1 {{ request()->is('settings') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-cogs"></i>
                    <p>Settings</p>
                </a>
            </li>
        
        @endif
    </ul>
</nav>
