@php
    $leaveCreditsRoute = $guard == "web" ? route('leavesRead', $employee->id) : route('leavesReadEmp');
    $statusRoute = $guard == "web" ? route('leaveStatus', $employee->id) : route('leaveStatus');
    $historyRoute = $guard == 'web' ? route('historyRead', $employee->id) : route('historyRead');

    $isLeaveCreditsActive = request()->is('leave') || request()->is('leaves*');
    $isStatusActive = request()->is('leave/status') || request()->is('leave/status/*') || request()->is('leaves/status*');
    $isHistoryActive = request()->is('leave/history*') || request()->is('leaves/history');
@endphp

<div class="row">
    <div class="col-md-4">
        <a href="{{ $leaveCreditsRoute }}" class="nav-link mb-1 {{ $isLeaveCreditsActive ? 'bg-default' : 'bg-secondary' }}" style="border-radius: 5px;">
            <i class="pr-2 fas fa-id-card" style="width: 20px; margin-left: 3px;"></i> 
            <span class="text-light text-bold">{{ ($guard == "web") ? 'LEAVE CREDITS' : 'APPLICATION FORM'}}</span> 
            <span class="float-right pt-1 badge badge-light">0</span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ $statusRoute }}" class="nav-link mb-1 {{ $isStatusActive ? 'bg-default' : 'bg-secondary' }}" style="border-radius: 5px;">
            <i class="pr-2 fas fa-stamp text-light" style="width: 20px; margin-left: 3px;"></i> 
            <span class="text-light text-bold">STATUS</span> 
            <span class="float-right pt-1 badge badge-light">0</span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ $historyRoute }}" class="nav-link mb-1 {{ $isHistoryActive ? 'bg-default' : 'bg-secondary' }}" style="border-radius: 5px;">
            <i class="pr-2 fas fa-history" style="width: 20px; margin-left: 3px;"></i> 
            <span class="text-light text-bold">HISTORY</span> 
            <span class="float-right pt-1 badge badge-light">0</span>
        </a>
    </div>
</div>
