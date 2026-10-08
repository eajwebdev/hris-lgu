@extends('layouts.app')

@php
    // The punch monitor (AttendanceAdminController::monitor): one day at a
    // time, everyone who clocked through the attendance portal, with where
    // each punch came from.

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $badge = 'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap';

    // How a punch's location turned out: key => [badge colours, icon].
    // 'far' and 'none' are the two HR needs to follow up.
    $places = [
        'none'    => ['bg-sun-100 text-sun-700', 'fa-location-crosshairs'],
        'far'     => ['bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300', 'fa-location-arrow'],
        'near'    => ['bg-forest-100 text-forest-800', 'fa-location-dot'],
        'unknown' => ['bg-line/60 text-ink/70', 'fa-location-dot'],
    ];

    $day = \Carbon\Carbon::parse($date);
    $today = now()->toDateString();
    $isToday = $date === $today;
    $busyDays = $busyDays ?? collect();

    $total = $logs->count();
    $clockIns = $logs->where('action', '!=', 'out')->count();
    $people = $logs->pluck('emp_ID')->unique()->count();
    $shareOf = fn ($count) => $total ? round($count / $total * 100) : 0;

    // Punches by hour, for the first tile: the working day, stretched to take
    // in anything earlier or later than it.
    $byHour = $logs->countBy(fn ($log) => (int) $log->created_at->format('G'));
    $firstHour = min(6, $byHour->keys()->min() ?? 6);
    $lastHour = max(18, $byHour->keys()->max() ?? 18);
    $hours = collect(range($firstHour, $lastHour))->map(fn ($hour) => [
        'label' => \Carbon\Carbon::createFromTime($hour)->format('g A'),
        'value' => $byHour[$hour] ?? 0,
    ])->all();

    $hourPeak = max(1, collect($hours)->max('value'));

    // The fortnight ending on this day, one series per tile.
    $fortnight = collect($fortnight ?? []);
    $over = fn ($key) => $fortnight->map(fn ($point) => ['label' => $point['label'], 'value' => $point[$key]])->all();

    // Where the day's punches came from, largest concern first:
    // key => [label, how many, bar colour].
    $unknown = $logs->filter(fn ($log) => $log->lat !== null && $log->out_of_range === null)->count();
    $breakdown = [
        'far'     => ['Out of range', $flagged, 'bg-red-600'],
        'none'    => ['No location shared', $unlocated, 'bg-sun-500'],
        'near'    => ['At a station', $total - $flagged - $unlocated - $unknown, 'bg-forest-600 dark:bg-forest-500'],
        'unknown' => ['Not compared', $unknown, 'bg-ink/30'],
    ];

    $dayButton = 'grid size-10 place-items-center transition-colors hover:bg-cream/12 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-sun-500';
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Face Attendance</h1>
            <p class="mt-1 text-cream/70">Punches made through the attendance portal on {{ $day->format('l, F j, Y') }}.</p>
        </div>

        {{-- Day picker: a step back, the day itself (which opens a calendar),
             a step forward. Every control is a link to ?date=..., so the page
             for that day is one click or one middle-click away. Days with a
             dot have punches on them. --}}
        <div class="relative" id="dayPicker" data-selected="{{ $date }}" data-today="{{ $today }}"
             data-url="{{ route('attendanceMonitor') }}" data-busy='@json($busyDays)'>
            <div class="inline-flex overflow-hidden rounded-xl border border-cream/25">
                <a href="{{ route('attendanceMonitor', ['date' => $day->copy()->subDay()->toDateString()]) }}" title="Previous day" class="{{ $dayButton }}">
                    <i class="fas fa-angle-left"></i><span class="sr-only">Previous day</span>
                </a>
                <button type="button" data-day-toggle aria-haspopup="dialog" aria-expanded="false"
                        class="inline-flex h-10 cursor-pointer items-center gap-2.5 border-x border-cream/25 px-4 font-medium transition-colors hover:bg-cream/12 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-sun-500 aria-expanded:bg-cream/15">
                    <i class="fas fa-calendar-day text-cream/70"></i>
                    {{ $isToday ? 'Today' : $day->format('D, M j, Y') }}
                    <i class="fas fa-angle-down text-xs text-cream/60"></i>
                </button>
                @if($isToday)
                    <span title="No later day yet" aria-disabled="true" class="grid size-10 place-items-center text-cream/30"><i class="fas fa-angle-right"></i></span>
                @else
                    <a href="{{ route('attendanceMonitor', ['date' => $day->copy()->addDay()->toDateString()]) }}" title="Next day" class="{{ $dayButton }}">
                        <i class="fas fa-angle-right"></i><span class="sr-only">Next day</span>
                    </a>
                @endif
            </div>

            {{-- Filled in by the script at the bottom of the page. Hangs from
                 the right edge of the picker, which sits at the right of the
                 banner; on a phone the picker is at the left, so it hangs from
                 that side instead and stays on the screen. --}}
            <div data-day-panel hidden role="dialog" aria-label="Choose a day"
                 class="absolute top-full right-0 z-40 mt-2 w-76 rounded-2xl border border-line bg-surface p-3 text-ink shadow-xl shadow-forest-950/20 max-sm:right-auto max-sm:left-0">
                <div class="flex items-center justify-between gap-2 px-1">
                    <p class="font-display text-base font-semibold tracking-tight" data-day-title></p>
                    <div class="flex gap-0.5">
                        <button type="button" data-day-month="-1" aria-label="Previous month" class="grid size-8 cursor-pointer place-items-center rounded-lg text-ink/60 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-angle-left"></i></button>
                        <button type="button" data-day-month="1" aria-label="Next month" class="grid size-8 cursor-pointer place-items-center rounded-lg text-ink/60 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500 disabled:cursor-default disabled:opacity-30 disabled:hover:bg-transparent"><i class="fas fa-angle-right"></i></button>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-7 text-center text-xs text-ink/45">
                    @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $weekday)
                        <span class="py-1">{{ $weekday }}</span>
                    @endforeach
                </div>
                <div class="grid grid-cols-7 gap-y-0.5" data-day-grid></div>

                <div class="mt-2 flex items-center justify-between border-t border-line px-1 pt-2.5 text-xs text-ink/55">
                    <span class="inline-flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-sun-500"></span> Has punches</span>
                    <a href="{{ route('attendanceMonitor') }}" class="font-medium text-forest-700 underline-offset-2 hover:underline">Go to today</a>
                </div>
            </div>

            <noscript>
                <form method="GET" action="{{ route('attendanceMonitor') }}" class="mt-2 flex items-center gap-2">
                    <input type="date" name="date" value="{{ $date }}" class="h-10 rounded-xl border border-cream/25 bg-forest-950/40 px-3 text-cream [color-scheme:dark]">
                    <button class="h-10 rounded-xl bg-cream px-4 font-medium text-forest-900">Go</button>
                </form>
            </noscript>
        </div>
    </div>
@endsection

@section('body')
{{-- The day against the fortnight before it. The two tiles HR follows up
     filter the list below to just those punches when pressed. --}}
<div class="grid grid-cols-1 gap-3 md:grid-cols-3 sm:gap-4">
    @include('partials.stat-tile', [
        'label' => $total == 1 ? 'Punch' : 'Punches',
        'value' => $total,
        'icon' => 'fa-fingerprint',
        'href' => null, 'press' => null, 'waiting' => false,
        'note' => $total
            ? $clockIns . ' in, ' . ($total - $clockIns) . ' out, by ' . $people . ' ' . ($people == 1 ? 'employee' : 'employees')
            : 'Nobody has clocked through the portal',
        'series' => $over('total'), 'caption' => 'This day and the 13 before it', 'unit' => 'punched',
        'chart' => 'area', 'current' => true, 'trend' => 'neutral', 'versus' => 'vs the day before',
    ])

    @include('partials.stat-tile', [
        'label' => 'Out of range',
        'value' => $flagged,
        'icon' => 'fa-location-arrow',
        'href' => null, 'press' => 'data-show-place="far"', 'waiting' => $flagged > 0,
        'note' => $total ? $shareOf($flagged) . '% of the day\'s punches' : 'Further from a station than its radius allows',
        'series' => $over('far'), 'caption' => 'This day and the 13 before it', 'unit' => 'out of range',
        'chart' => 'area', 'current' => true, 'trend' => 'down-good', 'versus' => 'vs the day before',
    ])

    @include('partials.stat-tile', [
        'label' => 'Without location',
        'value' => $unlocated,
        'icon' => 'fa-location-crosshairs',
        'href' => null, 'press' => 'data-show-place="none"', 'waiting' => $unlocated > 0,
        'note' => $total ? $shareOf($unlocated) . '% of the day\'s punches' : 'The device shared no position',
        'series' => $over('unlocated'), 'caption' => 'This day and the 13 before it', 'unit' => 'without location',
        'chart' => 'area', 'current' => true, 'trend' => 'down-good', 'versus' => 'vs the day before',
    ])
</div>

{{-- When the punches came, and where from. --}}
<div class="mt-3 grid gap-3 sm:mt-4 sm:gap-4 xl:grid-cols-3">
    <section class="relative overflow-hidden rounded-2xl border border-line bg-surface p-5 xl:col-span-2">
        <h2 class="font-display text-lg font-semibold tracking-tight">Punches by hour</h2>
        <p class="mt-0.5 text-ink/60">{{ $day->format('l, F j') }}. Hover a column for its count.</p>

        {{-- Plot: three rules with their values on the left, a column per
             hour, the hour named under every third one. --}}
        <div class="mt-5 flex gap-3" aria-hidden="true">
            <div class="flex h-40 flex-col justify-between text-right text-[11px] text-ink/45 tabular-nums">
                <span class="-translate-y-1/2">{{ number_format($hourPeak) }}</span>
                <span>{{ $hourPeak > 1 ? number_format($hourPeak / 2, $hourPeak % 2 ? 1 : 0) : '' }}</span>
                <span class="translate-y-1/2">0</span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="relative h-40">
                    <div class="absolute inset-0 flex flex-col justify-between">
                        <span class="block border-t border-line"></span>
                        <span class="block border-t border-line"></span>
                        <span class="block border-t border-line"></span>
                    </div>

                    <div class="absolute inset-0 flex items-end gap-0.5">
                        @foreach($hours as $hour)
                            <div class="group/col flex h-full flex-1 items-end justify-center"
                                 data-spark="{{ number_format($hour['value']) }} punched" data-spark-label="{{ $hour['label'] }}">
                                @if($hour['value'] > 0)
                                    <div class="w-full max-w-6 rounded-t-[4px] bg-forest-600 transition-colors group-hover/col:bg-forest-800 dark:bg-forest-500"
                                         style="height: {{ max(3, round($hour['value'] / $hourPeak * 100)) }}%"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(!$total)
                        <p class="absolute inset-0 grid place-items-center text-ink/45">No punches this day</p>
                    @endif
                </div>

                <div class="mt-2 flex gap-0.5 text-[11px] text-ink/45">
                    @foreach($hours as $hour)
                        <span class="flex-1 text-center whitespace-nowrap">{{ $loop->index % 3 == 0 ? $hour['label'] : '' }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <table class="sr-only">
            <caption>Punches by hour, {{ $day->format('F j, Y') }}</caption>
            <tbody>
                @foreach($hours as $hour)
                    <tr><th scope="row">{{ $hour['label'] }}</th><td>{{ number_format($hour['value']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="flex flex-col rounded-2xl border border-line bg-surface p-5">
        <h2 class="font-display text-lg font-semibold tracking-tight">Where they came from</h2>
        <p class="mt-0.5 text-ink/60">Each punch against the station nearest to it.</p>

        {{-- One bar for the whole day, split by outcome; the list under it
             names each part and gives its count, so colour is never alone. --}}
        <div class="mt-5 flex h-3 gap-0.5 overflow-hidden rounded-full {{ $total ? '' : 'bg-line/70' }}" aria-hidden="true">
            @foreach($breakdown as [$partLabel, $partCount, $partColour])
                @if($partCount > 0)
                    <span class="{{ $partColour }}" style="width: {{ $partCount / $total * 100 }}%" title="{{ $partLabel }}: {{ $partCount }}"></span>
                @endif
            @endforeach
        </div>

        <dl class="mt-4 divide-y divide-line">
            @foreach($breakdown as [$partLabel, $partCount, $partColour])
                <div class="flex items-center gap-3 py-2.5">
                    <span class="size-2.5 shrink-0 rounded-full {{ $partColour }}"></span>
                    <dt class="flex-1">{{ $partLabel }}</dt>
                    <dd class="tabular-nums text-ink/55">
                        <strong class="font-semibold text-ink">{{ number_format($partCount) }}</strong>
                        <span class="mx-1 text-ink/25">&middot;</span>{{ $shareOf($partCount) }}%
                    </dd>
                </div>
            @endforeach
        </dl>
    </section>
</div>

@if($stations->isEmpty())
    {{-- Nothing to measure a punch against yet. Says what that costs and
         where to fix it; words and one button, no icon. --}}
    <section class="mt-5 flex flex-wrap items-center justify-between gap-x-8 gap-y-3 rounded-2xl border border-sun-500/40 bg-sun-50 px-5 py-4">
        <div class="min-w-0 flex-1 basis-80">
            <h2 class="font-display text-base font-semibold tracking-tight">No attendance stations are set up</h2>
            <p class="mt-0.5 text-ink/70">
                Punches are still recorded, but with no station to measure from they cannot be judged near or far.
                Stations are added in Settings, under Attendance Stations.
            </p>
        </div>
        <a href="{{ route('settings') }}#stations"
           class="inline-flex h-10 shrink-0 items-center rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">
            Open Settings
        </a>
    </section>
@endif

{{-- Newest first, as the controller hands them over; the shell's list script
     (data-list) searches, filters and pages them. --}}
<section class="mt-5 rounded-2xl border border-line bg-surface" id="punchList" data-list>
    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
            <span class="sr-only">Search punches</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search employee, position, station" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>

        <label>
            <span class="sr-only">Action</span>
            <select data-list-filter="action" class="{{ $field }} h-10 pr-8 pl-3">
                <option value="">In and out</option>
                <option value="in">Clock in</option>
                <option value="out">Clock out</option>
            </select>
        </label>

        <label>
            <span class="sr-only">Location</span>
            <select data-list-filter="place" class="{{ $field }} h-10 pr-8 pl-3">
                <option value="">Any location</option>
                <option value="far">Out of range</option>
                <option value="none">No location shared</option>
                <option value="near">At a station</option>
                <option value="unknown">Not compared</option>
            </select>
        </label>

        <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
    </div>

    {{-- relative: keeps the visually hidden labels in the cells inside this
         scroller instead of widening the page on a phone. --}}
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-4 py-3 pl-5 font-medium">Time</th>
                    <th scope="col" class="px-4 py-3 font-medium">Employee</th>
                    <th scope="col" class="px-4 py-3 font-medium">Action</th>
                    <th scope="col" class="px-4 py-3 font-medium max-md:hidden">Mode</th>
                    <th scope="col" class="px-4 py-3 font-medium">Location</th>
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium max-lg:hidden">GPS accuracy</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($logs as $log)
                    @php
                        $who = $log->employee ? trim($log->employee->fname . ' ' . $log->employee->lname) : $log->emp_ID;
                        $position = $log->employee->position ?? '';

                        // No coordinates at all; judged far; judged near; or
                        // recorded with no station to judge it against.
                        $place = $log->lat === null ? 'none'
                            : ($log->out_of_range === true ? 'far'
                            : ($log->out_of_range === false ? 'near' : 'unknown'));

                        $distance = $log->distance_m >= 1000
                            ? number_format($log->distance_m / 1000, 1) . ' km'
                            : $log->distance_m . ' m';
                    @endphp
                    <tr data-row data-action="{{ $log->action === 'out' ? 'out' : 'in' }}" data-place="{{ $place }}"
                        data-search="{{ strtolower($who . ' ' . $position . ' ' . $log->station_name . ' ' . $log->emp_ID) }}"
                        class="transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5 whitespace-nowrap tabular-nums">{{ $log->created_at->format('g:i:s A') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $who }}</p>
                            @if($position)
                                <p class="mt-0.5 text-xs text-ink/55">{{ $position }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($log->action === 'out')
                                <span class="{{ $badge }} bg-sun-100 text-sun-700"><i class="fas fa-right-from-bracket"></i> Clock out</span>
                            @else
                                <span class="{{ $badge }} bg-forest-100 text-forest-800"><i class="fas fa-right-to-bracket"></i> Clock in</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-md:hidden">
                            <span class="{{ $badge }} border border-line text-ink/75">
                                <i class="fas {{ $log->mode === 'qr' ? 'fa-qrcode' : 'fa-user' }}"></i> {{ strtoupper($log->mode) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $badge }} {{ $places[$place][0] }}">
                                <i class="fas {{ $places[$place][1] }}"></i>
                                @if($place === 'none')
                                    No location shared
                                @elseif($place === 'far')
                                    {{ $distance }} from {{ $log->station_name }}
                                @elseif($place === 'near')
                                    {{ $log->station_name }}
                                @else
                                    Recorded (no stations to compare)
                                @endif
                            </span>
                            @if($place === 'far')
                                <a href="https://www.google.com/maps?q={{ $log->lat }},{{ $log->lng }}" target="_blank" rel="noopener" title="View on map"
                                   class="ml-1 inline-grid size-7 place-items-center rounded-md align-middle text-ink/55 transition-colors hover:bg-forest-100 hover:text-forest-700 focus-visible:outline-2 focus-visible:outline-sun-500">
                                    <i class="fas fa-map-location-dot"></i><span class="sr-only">View on map</span>
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 pr-5 text-right tabular-nums text-ink/60 max-lg:hidden">
                            {{ $log->accuracy_m !== null ? '±' . $log->accuracy_m . ' m' : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($logs->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-forest-100 text-xl text-forest-700"><i class="fas fa-fingerprint"></i></span>
            <p class="mt-4 font-medium">No portal punches on {{ $day->format('F j, Y') }}.</p>
            <p class="mt-1 text-ink/55">Pick another day above; the days marked with a dot have punches.</p>
        </div>
    @else
        <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No punch matches that.</p>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
        <label class="flex items-center gap-2 text-ink/55">
            Rows
            <select data-list-size class="{{ $field }} h-9 pr-7 pl-3">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="0">All</option>
            </select>
        </label>
        <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Day picker calendar. One month at a time, built here; each day is a link
    // to that day's page. Days after today are not offered.
    (function () {
        var picker = document.getElementById('dayPicker');
        var toggle = picker.querySelector('[data-day-toggle]');
        var panel = picker.querySelector('[data-day-panel]');
        var title = picker.querySelector('[data-day-title]');
        var grid = picker.querySelector('[data-day-grid]');
        var later = picker.querySelector('[data-day-month="1"]');

        var selected = picker.dataset.selected;
        var today = picker.dataset.today;
        var busy = JSON.parse(picker.dataset.busy || '{}');

        function pad(number) { return String(number).padStart(2, '0'); }
        function iso(year, month, day) { return year + '-' + pad(month + 1) + '-' + pad(day); }

        // The month on show, as [year, month]; it opens on the selected day's.
        var shown = [parseInt(selected.slice(0, 4), 10), parseInt(selected.slice(5, 7), 10) - 1];

        function draw() {
            var year = shown[0], month = shown[1];
            title.textContent = new Date(year, month, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            later.disabled = iso(year, month, 1).slice(0, 7) >= today.slice(0, 7);
            grid.textContent = '';

            for (var blank = new Date(year, month, 1).getDay(); blank > 0; blank--) {
                grid.appendChild(document.createElement('span'));
            }

            for (var day = 1, last = new Date(year, month + 1, 0).getDate(); day <= last; day++) {
                var date = iso(year, month, day);
                var future = date > today;
                var cell = document.createElement(future ? 'span' : 'a');
                var look = 'relative grid h-9 place-items-center rounded-lg tabular-nums';

                if (future) {
                    look += ' text-ink/25';
                } else {
                    cell.href = picker.dataset.url + '?date=' + date;
                    look += ' transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
                    if (date === selected) {
                        look += ' bg-forest-900 font-semibold text-cream dark:bg-forest-600';
                        cell.setAttribute('aria-current', 'date');
                    } else if (date === today) {
                        look += ' font-semibold text-sun-700 ring-1 ring-sun-500/60 ring-inset hover:bg-forest-100';
                    } else {
                        look += ' hover:bg-forest-100';
                    }
                }

                cell.className = look;
                cell.textContent = day;

                if (busy[date]) {
                    var dot = document.createElement('span');
                    dot.className = 'absolute bottom-1 size-1 rounded-full ' + (date === selected ? 'bg-cream' : 'bg-sun-500');
                    cell.appendChild(dot);
                    cell.title = busy[date] + (busy[date] == 1 ? ' punch' : ' punches');
                }

                grid.appendChild(cell);
            }
        }

        function setOpen(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) { draw(); }
        }

        toggle.addEventListener('click', function () { setOpen(panel.hidden); });

        picker.querySelectorAll('[data-day-month]').forEach(function (step) {
            step.addEventListener('click', function () {
                var moved = new Date(shown[0], shown[1] + parseInt(step.dataset.dayMonth, 10), 1);
                shown = [moved.getFullYear(), moved.getMonth()];
                draw();
            });
        });

        document.addEventListener('click', function (event) {
            if (!picker.contains(event.target)) { setOpen(false); }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) { setOpen(false); toggle.focus(); }
        });
    })();

    // The "Out of range" and "Without location" tiles set the location filter
    // to themselves; pressed again, they clear it.
    (function () {
        var filter = document.querySelector('#punchList [data-list-filter="place"]');

        document.querySelectorAll('[data-show-place]').forEach(function (tile) {
            tile.addEventListener('click', function () {
                filter.value = filter.value === tile.dataset.showPlace ? '' : tile.dataset.showPlace;
                filter.dispatchEvent(new Event('input', { bubbles: true }));
                document.getElementById('punchList').scrollIntoView({ block: 'nearest' });
            });
        });
    })();
</script>
@endpush
