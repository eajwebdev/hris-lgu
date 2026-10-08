{{-- Calendar | Reports switch, for the header banner of both Events screens. --}}
@php
    $eventTabs = [
        ['Calendar', route('eventIndex'), 'fa-calendar-days', request()->is('event')],
        ['Reports', route('showReport'), 'fa-file-pdf', request()->is('event/reports*')],
    ];
@endphp
<nav aria-label="Events" class="inline-flex gap-1 rounded-xl border border-cream/20 p-1">
    @foreach($eventTabs as [$tabLabel, $tabUrl, $tabIcon, $tabCurrent])
        <a href="{{ $tabUrl }}" @if($tabCurrent) aria-current="page" @endif
           class="inline-flex h-9 items-center gap-2 rounded-lg px-4 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-sun-500 {{ $tabCurrent ? 'bg-cream text-forest-900' : 'text-cream/80 hover:bg-cream/12 hover:text-cream' }}">
            <i class="fas {{ $tabIcon }}"></i> {{ $tabLabel }}
        </a>
    @endforeach
</nav>
