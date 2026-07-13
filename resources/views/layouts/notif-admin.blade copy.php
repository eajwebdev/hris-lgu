<!-- Search Form -->
<li class="nav-item">
    <a class="nav-link" data-widget="navbar-search" href="#" role="button">
        <i class="fas fa-search text-success1"></i>
    </a>
    <div class="navbar-search-block" style="width: 92%">
        <form class="form-inline">
            <div class="input-group">
                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                        <i class="fas fa-times text-success1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</li>

<li class="nav-item dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
        <i class="fas fa-bell text-success1"></i>
        <span class="badge badge-warning navbar-badge">{{ ($notificationsCount != 0) ? $notificationsCount : '' }}</span>
    </a>
    <div class="dropdown-menu notifications dropdown-notification dropdown-menu-lg dropdown-menu-right" style="left: inherit; right: 0; max-height: 400px; overflow-y: auto;">
        <span class="dropdown-item dropdown-header">{{ ($notificationsCount != 0) ? $notificationsCount : '' }} Notifications</span>
        <div class="dropdown-divider"></div>
        <div id="notifications-container">
            @php 
                $leaveTypes = [
                    1 => 'Vacation Leave',
                    2 => 'Mandatory/Forced Leave',
                    3 => 'Sick Leave',
                    4 => 'Maternity Leave',
                    5 => 'Paternity Leave',
                    6 => 'Special Privilege Leave',
                    7 => 'Solo Parent Leave',
                    8 => 'Study Leave',
                    9 => '10-Day VAWC Leave',
                    10 => 'Rehabilitation Privilege',
                    11 => 'Special Leave Benefits for Women',
                    12 => 'Special Emergency (Calamity) Leave',
                    13 => 'Adoption Leave',
                    14 => 'Others'
                ];
            @endphp
        
            @foreach ($notifications as $notif)
                @php 
                    $timeDifference = $notif->notif_created_at 
                        ? \Carbon\Carbon::parse($notif->notif_created_at)->timezone('Asia/Manila')->diffForHumans() 
                        : ''; 
                    $remarks = null;
                @endphp
            
                @switch($notif->module)
                    @case('leave')
                        @php
                            $action = $notif->category == 1 ? "is applying for" : "is awaiting approval for";
                            $remarks = "{$action} " . strtolower($leaveTypes[$notif->leave_type] ?? '') . " (Application No: #{$notif->transnum})";
                        @endphp
                        <a href="{{ route('leaveStatus', $notif->leave_emp_id) }}" class="dropdown-item d-flex align-items-center">
                            <div class="mr-3">
                                <img src="{{ file_exists(public_path('Profile/Employee/' . $notif->leave_emp_profile)) ? asset('Profile/Employee/' . $notif->leave_emp_profile) : 
                                asset('Profile/Employee/default.png') }}" class="img-circle" alt="User Image" width="40" height="40">
                            </div>                            
                            <div>
                                <p class="mb-0">
                                    <strong>{{ ucwords(strtolower($notif->leave_emp_fullname)) }}</strong> {{ $remarks }}
                                </p>
                                <span class="{{ $notif->notifstat == 0 ? 'text-primary font-weight-bold' : 'text-muted' }} text-sm">
                                    {{ $timeDifference }}
                                </span>
                            </div>
                        </a>
                        @break
                    
                    @case('pds')
                        @switch($notif->category)
                            @case(1)
                                    @php
                                        $lappid = $notif->lapp_id ?? 0;
                                        $menid = $notif->pds_emp_eligi_id ?? 0;
                                        $remarks = "has submitted new eligibility.";
                                        $profile = $notif->pds_emp_eligi_profile;
                                        $fullname = $notif->pds_emp_eligi_fullname;
                                        $menu = "eligibility";
                                    @endphp
                                @break
                            @case(2)
                                    @php
                                        $lappid = $notif->lapp_id ?? 0;
                                        $menid = $notif->pds_emp_workexp_id ?? 0;
                                        $remarks = "has submitted new work experience.";
                                        $profile = $notif->pds_emp_workexp_profile;
                                        $fullname = $notif->pds_emp_workexp_fullname;
                                        $menu = "work-experience";
                                    @endphp
                                @break
                            @case(3)
                                    @php
                                        $lappid = $notif->lapp_id ?? 0;
                                        $menid = $notif->pds_emp_volworks_id ?? 0;
                                        $remarks = "has submitted new voluntary works.";
                                        $profile = $notif->pds_emp_volworks_profile;
                                        $fullname = $notif->pds_emp_volworks_fullname;
                                        $menu = "voluntary-work";
                                    @endphp
                                @break
                            @case(4)
                                    @php
                                        $lappid = $notif->lapp_id ?? 0;
                                        $menid = $notif->pds_emp_learndev_id ?? 0;
                                        $remarks = "has submitted new Learning and Development.";
                                        $profile = $notif->pds_emp_learndev_profile;
                                        $fullname = $notif->pds_emp_learndev_fullname;
                                        $menu = "learning-dev";
                                    @endphp
                                @break
                            @break
                        @endswitch
            
                        <a href="{{ route('updateNotif', ['menid' => $menid, 'lappid' => $lappid, 'menu' => $menu]) }}" class="dropdown-item d-flex align-items-center">
                            <div class="mr-3">
                                @php
                                    $profileUrl = asset('Profile/Employee/' . $profile);
                                    $profilePath = public_path('Profile/Employee/' . $profile);
                                @endphp
                                <img src="{{ file_exists($profilePath) && $profile ? $profileUrl : asset('Profile/Employee/default.png') }}" 
                                     class="img-circle" alt="User Image" width="40" height="40">
                            </div>
                            <div>
                                <p class="mb-0">
                                    <strong>{{ ucwords(strtolower($fullname)) }}</strong> {{ $remarks }}
                                </p>
                                <span class="{{ $notif->notifstat == 0 ? 'text-primary font-weight-bold' : 'text-muted' }} text-sm">
                                    {{ $timeDifference }}
                                </span>
                            </div>
                        </a>                        
                    @break
                @endswitch
            
                <div class="dropdown-divider"></div>
            @endforeach
    
        </div>
        {{-- <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a> --}}
    </div>
</li>