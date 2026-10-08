{{--
    Drive | OPCR | IPCR, for the header banner of the SPMS screens.

    OPCR belongs to office heads and HR ($isHead). Everyone else sees it
    locked; SpmsController::opcrList turns them away as well, so the lock here
    is the explanation, not the access check.
--}}
@php
    $spmsTabs = [
        ['Drive', route('spms.drive'), 'fa-folder-open', request()->is('spms'), true],
        ['OPCR', route('spms.opcr'), 'fa-building', request()->is('spms/opcr*'), $isHead],
        ['IPCR', route('spms.ipcr'), 'fa-id-badge', request()->is('spms/ipcr*'), true],
    ];
    $spmsTab = 'inline-flex h-9 items-center gap-2 rounded-lg px-4 font-medium';
@endphp
<nav aria-label="SPMS" class="inline-flex gap-1 rounded-xl border border-cream/20 p-1">
    @foreach($spmsTabs as [$tabLabel, $tabUrl, $tabIcon, $tabCurrent, $tabOpen])
        @if($tabOpen)
            <a href="{{ $tabUrl }}" @if($tabCurrent) aria-current="page" @endif
               class="{{ $spmsTab }} transition-colors focus-visible:outline-2 focus-visible:outline-sun-500 {{ $tabCurrent ? 'bg-cream text-forest-900' : 'text-cream/80 hover:bg-cream/12 hover:text-cream' }}">
                <i class="fas {{ $tabIcon }}"></i> {{ $tabLabel }}
            </a>
        @else
            <span aria-disabled="true" title="Reserved for office heads" class="{{ $spmsTab }} cursor-not-allowed text-cream/40">
                <i class="fas fa-lock"></i> {{ $tabLabel }}
            </span>
        @endif
    @endforeach
</nav>
