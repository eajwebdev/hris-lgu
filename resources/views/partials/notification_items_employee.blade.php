{{-- Notification rows for an employee's own top bar list.

     Drawn inside two shells, so every element carries two sets of classes: the
     Bootstrap ones for the AdminLTE top bar, and Tailwind utilities for
     layouts/app-topbar. Each page loads one stylesheet or the other. Where a
     Tailwind name also exists in Bootstrap or AdminLTE (px-4, border, text-xs)
     it is avoided or written as an arbitrary value, so the old pages do not
     pick up a stray rule. --}}
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
            <a data-notification-id="{{ $notif->id }}" href="{{ route('leaveStatus') }}" class="dropdown-item d-flex align-items-center flex items-center px-[1rem] py-[.75rem] text-ink transition-colors hover:bg-paper">
                <div class="mr-3 shrink-0">
                    <span class="notification-initials grid size-10 place-items-center rounded-full bg-forest-100 text-[13px] font-semibold text-forest-800 ring-1 ring-forest-600/20">HR</span>
                </div>
                <div class="min-w-0">
                    <p class="mb-0 leading-snug">
                        {{ $remarks }}
                    </p>
                    <span class="{{ $notif->notifstat == 0 ? 'text-primary font-weight-bold font-semibold text-forest-700' : 'text-muted text-ink/50' }} text-sm">
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

            <a data-notification-id="{{ $notif->id }}" href="{{ $route }}" class="dropdown-item d-flex align-items-center flex items-center px-[1rem] py-[.75rem] text-ink transition-colors hover:bg-paper">
                <div class="mr-3 shrink-0">
                    <span class="notification-initials grid size-10 place-items-center rounded-full bg-forest-100 text-[13px] font-semibold text-forest-800 ring-1 ring-forest-600/20">HR</span>
                </div>
                <div class="min-w-0">
                    <p class="mb-0 leading-snug">
                        {!! $remarks !!}
                    </p>
                    <span class="{{ $notif->notifstat == 0 ? 'text-primary font-weight-bold font-semibold text-forest-700' : 'text-muted text-ink/50' }} text-sm">
                        {{ $timeDifference }}
                    </span>
                </div>
            </a>
        @break
    @endswitch

    <div class="dropdown-divider border-t border-line"></div>
@endforeach
