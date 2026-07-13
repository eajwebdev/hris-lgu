<!-- Job Application Notifications -->
@if(auth()->user()->username === 'hrisadmin@cpsu.edu.ph') 
<li class="nav-item dropdown">
    <a class="nav-link" href="#" data-toggle="dropdown" title="Job Applications">
        <i class="fas fa-envelope text-success1"></i>
        <span class="badge badge-danger navbar-badge">{{ $jobapplication->count() }}</span>
    </a>

    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">
           {{ $jobapplication->count() }} New Applications
        </span>

        <div class="dropdown-divider"></div>

        @foreach($jobapplication->take(6) as $jobs)
            <a href="{{ route('viewApplication', $jobs->id) }}" target="_blank" class="dropdown-item" style="white-space: normal;">
                <div class="d-flex align-items-center w-100">

                    <div style="flex:1; min-width:0; padding-right:12px; line-height:1.3; word-break:break-word;">
                        <strong>
                            {{ $jobs->first_name }}
                            {{ !empty($jobs->middle_name) ? strtoupper(substr($jobs->middle_name, 0, 1)).'.' : '' }}
                            {{ $jobs->last_name }}
                        </strong>

                        is applying for
                        <strong>{{ $jobs->title }}</strong>

                        <br>

                        <small class="text-muted d-block text-truncate">
                            {{ $jobs->email }}
                        </small>
                    </div>

                    <div style="flex:0 0 28px; width:28px; text-align:center; align-self:center;">
                        @if($jobs->checked == 1)
                            <i class="fas fa-check-circle text-success fa-lg" title="Reviewed"></i>
                        @else
                            <i class="fas fa-check-circle text-secondary fa-lg" title="Pending"></i>
                        @endif
                    </div>

                </div>
            </a>

            <div class="dropdown-divider"></div>
        @endforeach
        <a href="{{ route('viewAllApplication') }}" class="dropdown-item dropdown-footer">
            <i class="fas fa-list mr-1"></i> View All Applications
        </a>
    </div>
</li>
@endif
<li class="nav-item dropdown">
    @php
        $initials = function ($name) {
            $words = preg_split('/\s+/', trim((string) $name));
            $letters = collect($words)->filter()->take(2)->map(fn ($word) => strtoupper(substr($word, 0, 1)))->implode('');
            return $letters ?: 'NA';
        };
    @endphp
    <style>
        .notification-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eaf7f0;
            color: #187744;
            font-weight: 700;
            border: 1px solid #cfe8d9;
        }
        .notification-mark-all {
            border: 0;
            background: transparent;
            color: #187744;
            font-size: 12px;
            padding: 0;
        }
    </style>
    <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
        <i class="fas fa-bell text-success1"></i>
        <span class="badge badge-warning navbar-badge">{{ ($notificationsCount != 0) ? $notificationsCount : '' }}</span>
    </a>
    <div class="dropdown-menu notifications dropdown-notification dropdown-menu-lg dropdown-menu-right" style="left: inherit; right: 0; max-height: 400px; overflow-y: auto;">
        <div class="dropdown-item dropdown-header d-flex justify-content-between align-items-center">
            <span>{{ ($notificationsCount != 0) ? $notificationsCount : 'No' }} Notifications</span>
            @if($notificationsCount > 0)
                <form method="POST" action="{{ route('notifications.markAllRead') }}">
                    @csrf
                    <button type="submit" class="notification-mark-all">Mark all as read</button>
                </form>
            @endif
        </div>
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
                                <span class="notification-initials">{{ $initials($notif->leave_emp_fullname) }}</span>
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
                                <span class="notification-initials">{{ $initials($fullname) }}</span>
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
