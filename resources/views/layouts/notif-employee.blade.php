<!-- Job Application Notifications -->
@if(in_array(auth()->guard('employee')->user()->org_email, ['cbaligyan@cpsu.edu.ph','janetoledo@cpsu.edu.ph','wbantigue@cpsu.edu.ph']))
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
@php
    $initials = function ($name) {
        $words = preg_split('/\s+/', trim((string) $name));
        $letters = collect($words)->filter()->take(2)->map(fn ($word) => strtoupper(substr($word, 0, 1)))->implode('');
        return $letters ?: 'HR';
    };

    $id = auth()->guard($guard)->user()->id; // Get the authenticated user's ID
    $employee = \App\Models\Employee::find($id); // Fetch the employee record using the ID

    $notifications1 = collect($notifications1); 

    if ($employee) {
        $notifications1 = $notifications1
            ->where('notifempid', $employee->emp_ID) // Filter by employee ID
            ->where('notifstat', 0) // Filter by notification status
            ->sortByDesc('notif_created_at'); // Sort by descending creation date
            
        $notificationsCount1 = $notifications1->count();
    } else {
        $notifications1 = collect(); // Empty collection
        $notificationsCount1 = 0; // Default count
    }
@endphp

<li class="nav-item dropdown">
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
        <span class="badge badge-warning navbar-badge">{{ ($notificationsCount1 != 0) ? $notificationsCount1 : '' }}</span>
    </a>
    <div class="dropdown-menu notifications dropdown-notification dropdown-menu-lg dropdown-menu-right" style="left: inherit; right: 0; max-height: 400px; overflow-y: auto;">
        <div class="dropdown-item dropdown-header d-flex justify-content-between align-items-center">
            <span>{{ ($notificationsCount1 != 0) ? $notificationsCount1 : 'No' }} Notifications</span>
            @if($notificationsCount1 > 0)
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
        
            @foreach ($notifications1 as $notif)
                @php 
                    $timeDifference = $notif->notif_created_at 
                        ? \Carbon\Carbon::parse($notif->notif_created_at)->timezone('Asia/Manila')->diffForHumans() 
                        : ''; 
                    $remarks = null;
                @endphp
            
                @switch($notif->module)
                    @case('leave')
                    @switch($notif->category)
                            @case(1)
                                @php
                                    $remarks = "Your application for " . strtolower($leaveTypes[$notif->leave_type] ?? '') . " (Application No: #{$notif->transnum}) has been reviewed by HR and is awaiting your signature.";
                                @endphp
                            @break
                                @case(2)
                                @php
                                    $remarks = "Your application for " . strtolower($leaveTypes[$notif->leave_type] ?? '') . " (Application No: #{$notif->transnum}) has been approved.";
                                @endphp
                            @break
                        @endswitch
                        <a href="{{ route('leaveStatus') }}" class="dropdown-item d-flex align-items-center">
                            <div class="mr-3">
                                <span class="notification-initials">HR</span>
                            </div>
                            <div>
                                <p class="mb-0">
                                    {{ $remarks }}
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
                            @case(1.1)
                                @php
                                    $action = ($notif->category == 1) ? 'approved' : 'declined';
                                    $remarks = "Your submitted eligibility, <b>$notif->eligibilities_careereligible</b> has been $action by HR.";
                                    $profile = $notif->pds_emp_eligi_profile;
                                    $route = route('eligibility');
                                @endphp
                                @break
                            @case(2)
                            @case(2.1)
                                @php
                                    $action = ($notif->category == 2) ? 'approved' : 'declined';
                                    $remarks = "Your submitted work experience at <b>$notif->work_experiences_department</b> has been $action by HR.";
                                    $profile = $notif->pds_emp_workexp_profile;
                                    $route = route('work-experience');
                                    @endphp
                                @break
                            @case(3)
                            @case(3.1)
                                @php
                                    $action = ($notif->category == 3) ? 'approved' : 'declined';
                                    $remarks = "Your submitted new voluntary works at <b>$notif->voluntary_works_org_name</b> has been $action by HR.";
                                    $profile = $notif->pds_emp_volworks_profile;
                                    $route = route('voluntary-work');
                                    @endphp
                                @break
                            @case(4)
                            @case(4.1)
                                @php
                                    $action = ($notif->category == 4) ? 'approved' : 'declined';
                                    $remarks = "Your submitted new Learning and Development at <b>$notif->learning_devs_learning_dev</b> has been $action by HR.";
                                    $profile = $notif->pds_emp_learndev_profile;
                                    $route = route('learning-dev');
                                    @endphp
                                @break
                            @break
                        @endswitch
            
                        <a href="{{ $route }}" class="dropdown-item d-flex align-items-center">
                            <div class="mr-3">
                                <span class="notification-initials">HR</span>
                            </div>
                            <div>
                                <p class="mb-0">
                                    {!! $remarks !!}
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
