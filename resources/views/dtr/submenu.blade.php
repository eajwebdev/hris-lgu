{{-- DTR | Logs switch, for the header banner of both screens. --}}
@php
    $dtrTabs = [
        ['DTR', route('dtr-read'), 'fa-clock', request()->is('dtr')],
        ['Logs', route('dtrLogs'), 'fa-file-lines', request()->is('dtr/dtr-logs')],
    ];
@endphp
<nav aria-label="Daily time record" class="inline-flex gap-1 rounded-xl border border-cream/20 p-1">
    @foreach($dtrTabs as [$tabLabel, $tabUrl, $tabIcon, $tabCurrent])
        <a href="{{ $tabUrl }}" @if($tabCurrent) aria-current="page" @endif
           class="inline-flex h-9 items-center gap-2 rounded-lg px-4 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-sun-500 {{ $tabCurrent ? 'bg-cream text-forest-900' : 'text-cream/80 hover:bg-cream/12 hover:text-cream' }}">
            <i class="fas {{ $tabIcon }}"></i> {{ $tabLabel }}
        </a>
    @endforeach
</nav>
