@php
    $leaveCreditsRoute = $guard == "web" ? route('leavesRead', $employee->id) : route('leavesReadEmp');
    $statusRoute = $guard == "web" ? route('leaveStatus', $employee->id) : route('leaveStatus');
    $historyRoute = $guard == 'web' ? route('historyRead', $employee->id) : route('historyRead');

    $isLeaveCreditsActive = request()->is('leave') || request()->is('leaves*');
    $isStatusActive = request()->is('leave/status') || request()->is('leave/status/*') || request()->is('leaves/status*');
    $isHistoryActive = request()->is('leave/history*') || request()->is('leaves/history');
@endphp

<div class="page-tabs">
    <a href="{{ $leaveCreditsRoute }}" class="page-tab {{ $isLeaveCreditsActive ? 'active' : '' }}">
        <i class="fas fa-id-card"></i>
        <span>{{ ($guard == "web") ? 'LEAVE CREDITS' : 'APPLICATION FORM' }}</span>
    </a>

    <a href="{{ $statusRoute }}" class="page-tab {{ $isStatusActive ? 'active' : '' }}">
        <i class="fas fa-stamp"></i>
        <span>STATUS</span>
    </a>

    <a href="{{ $historyRoute }}" class="page-tab {{ $isHistoryActive ? 'active' : '' }}">
        <i class="fas fa-clock-rotate-left"></i>
        <span>HISTORY</span>
    </a>
</div>
