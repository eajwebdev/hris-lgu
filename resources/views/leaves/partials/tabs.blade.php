{{-- The three leave screens, for the header banner. --}}
@php
    $leaveTabs = [
        [$guard == 'web' ? 'Leave credits' : 'Application form',
         $guard == 'web' ? route('leavesRead', $employee->id) : route('leavesReadEmp'),
         'fa-id-card',
         request()->is('leave') || request()->is('leaves*')],
        ['Status',
         $guard == 'web' ? route('leaveStatus', $employee->id) : route('leaveStatus'),
         'fa-stamp',
         request()->is('leave/status') || request()->is('leave/status/*') || request()->is('leaves/status*')],
        ['History',
         $guard == 'web' ? route('historyRead', $employee->id) : route('historyRead'),
         'fa-clock-rotate-left',
         request()->is('leave/history*') || request()->is('leaves/history')],
    ];
@endphp
<nav aria-label="Leave" class="inline-flex flex-wrap gap-1 rounded-xl border border-cream/20 p-1">
    @foreach($leaveTabs as [$tabLabel, $tabUrl, $tabIcon, $tabCurrent])
        <a href="{{ $tabUrl }}" @if($tabCurrent) aria-current="page" @endif
           class="inline-flex h-9 items-center gap-2 rounded-lg px-4 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-sun-500 {{ $tabCurrent ? 'bg-cream text-forest-900' : 'text-cream/80 hover:bg-cream/12 hover:text-cream' }}">
            <i class="fas {{ $tabIcon }}"></i> {{ $tabLabel }}
        </a>
    @endforeach
</nav>
