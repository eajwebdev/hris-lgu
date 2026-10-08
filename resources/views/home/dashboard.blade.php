@extends('layouts.app')

@php
    $viewer = auth()->guard($guard)->user();
    $viewerFirstName = ucwords(strtolower((string) $viewer->fname));

    $card = 'rounded-2xl border border-line bg-surface';
    $cardTitle = 'font-display text-lg font-semibold tracking-tight';
@endphp

{{-- The greeting sits on the header banner, under the top bar. --}}
@section('hero')
    @if($guard == 'employee')
        @php
            $heroPhoto = $employee->profile && file_exists(public_path('Profile/Employee/' . $employee->profile))
                ? asset('Profile/Employee/' . $employee->profile)
                : asset('Profile/Employee/default.png');
        @endphp
        <div class="flex items-center gap-5">
            <img src="{{ $heroPhoto }}" alt="" class="size-16 shrink-0 rounded-full object-cover ring-4 ring-cream/15 sm:size-20">
            <div class="min-w-0">
                <p class="text-cream/70 lg:hidden">{{ now('Asia/Manila')->format('F j, Y') }}</p>
                <h1 class="font-display text-3xl/none font-semibold tracking-tight sm:text-4xl/none">
                    Maayong adlaw, {{ $viewerFirstName }}<span class="text-sun-500">.</span>
                </h1>
                <p class="mt-2 text-cream/75">
                    {{ $employee->position ?: 'Employee' }}
                    @if($employee->emp_ID)
                        <span class="mx-2 text-cream/30">|</span>{{ $employee->emp_ID }}
                    @endif
                </p>
            </div>
        </div>
    @else
        <p class="text-cream/70 lg:hidden">{{ now('Asia/Manila')->format('l, F j, Y') }}</p>
        <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">
            Maayong adlaw, {{ $viewerFirstName }}<span class="text-sun-500">.</span>
        </h1>
    @endif
@endsection

@section('body')
@include('partials.event-calendar')
@if($guard == 'employee')
    @php
        // Shortcuts for the date filter. "This week" is the range the
        // controller falls back to when no dates are given.
        $manilaNow = now('Asia/Manila');
        $ranges = [
            'This week'   => [$manilaNow->copy()->startOfWeek(), $manilaNow->copy()->endOfWeek()],
            'Today'       => [$manilaNow->copy(), $manilaNow->copy()],
            'Last 7 days' => [$manilaNow->copy()->subDays(6), $manilaNow->copy()],
            'This month'  => [$manilaNow->copy()->startOfMonth(), $manilaNow->copy()->endOfMonth()],
            'Last month'  => [$manilaNow->copy()->subMonthNoOverflow()->startOfMonth(), $manilaNow->copy()->subMonthNoOverflow()->endOfMonth()],
        ];

        $metrics = array_values(array_filter([
            $canFileLeave ? ['Leave Records', number_format($leaveCount), 'Total applications filed', 'fa-calendar-check'] : null,
            ['Total Late', $totalLate, 'For selected range', 'fa-business-time'],
            ['Total Undertime', $totalUndertime, 'For selected range', 'fa-hourglass-half'],
            ['Service',
             is_null($serviceYears) ? '--' : $serviceYears . ' yr' . ($serviceYears == 1 ? '' : 's'),
             $employee->date_hired ? 'Since ' . \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : 'Date hired not set',
             'fa-id-badge'],
        ]));
    @endphp

    {{-- Date range. Two plain date fields: the old shell used a jQuery
         range picker here, and this layout carries no jQuery. --}}
    <form method="GET" action="{{ route('dashboard') }}" class="{{ $card }} flex flex-wrap items-end gap-3 p-4">
        <div>
            <label for="date_from" class="block text-xs font-medium text-ink/60">From</label>
            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}"
                   class="mt-1 block h-10 rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15">
        </div>
        <div>
            <label for="date_to" class="block text-xs font-medium text-ink/60">To</label>
            <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}"
                   class="mt-1 block h-10 rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15">
        </div>
        <button type="submit" class="h-10 cursor-pointer rounded-xl bg-forest-900 px-4 font-medium text-cream transition-colors hover:bg-forest-950 dark:bg-forest-600 dark:hover:bg-forest-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            Apply
        </button>

        <div class="flex flex-wrap gap-1 sm:ml-auto">
            @foreach($ranges as $label => [$from, $to])
                @php
                    $current = $from->toDateString() === $dateFrom && $to->toDateString() === $dateTo;
                @endphp
                <a href="{{ route('dashboard', ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]) }}"
                   @if($current) aria-current="true" @endif
                   class="rounded-lg px-3 py-2 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500 {{ $current ? 'bg-forest-100 font-medium text-forest-800' : 'text-ink/65 hover:bg-paper hover:text-ink' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </form>

    <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @foreach($metrics as [$label, $value, $note, $icon])
            <div class="{{ $card }} p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-ink/60">{{ $label }}</p>
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-forest-100 text-forest-700"><i class="fas {{ $icon }}"></i></span>
                </div>
                <p class="mt-1 font-display text-2xl font-semibold tracking-tight tabular-nums sm:text-3xl">{{ $value }}</p>
                <p class="mt-1 text-xs text-ink/50">{{ $note }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <section class="{{ $card }} p-4 sm:p-5 xl:col-span-2">
            <h2 class="{{ $cardTitle }} mb-4">Events</h2>
            <div id="calendar"></div>
        </section>

        <div class="grid gap-5 self-start md:grid-cols-2 xl:grid-cols-1">
            <section class="{{ $card }}">
                <h2 class="{{ $cardTitle }} px-5 pt-5 pb-3">Recent DTR</h2>

                <ul class="divide-y divide-line border-t border-line">
                    @forelse($recentDtrs as $dtr)
                        <li class="flex items-center gap-4 px-5 py-3.5">
                            <div class="w-24 shrink-0">
                                <p class="font-semibold">{{ \Carbon\Carbon::parse($dtr->date)->format('M d') }}</p>
                                <p class="mt-0.5 text-xs/snug text-ink/50">
                                    AM {{ $dtr->official_schedule['am'] }}<br>
                                    PM {{ $dtr->official_schedule['pm'] }}
                                </p>
                            </div>
                            <dl class="grid min-w-0 flex-1 grid-cols-4 gap-1.5">
                                @foreach(['am_in' => 'AM In', 'am_out' => 'AM Out', 'pm_in' => 'PM In', 'pm_out' => 'PM Out'] as $key => $slot)
                                    <div class="rounded-lg bg-paper px-1 py-1.5 text-center">
                                        <dt class="text-[10px] text-ink/50">{{ $slot }}</dt>
                                        <dd class="mt-0.5 text-xs font-semibold tabular-nums">{{ $dtr->daily_punches[$key] ?: '--' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-ink/55">No DTR records yet.</li>
                    @endforelse
                </ul>
            </section>

            <section class="{{ $card }} p-5">
                <h2 class="{{ $cardTitle }} mb-3">Quick Actions</h2>

                @php
                    $actions = array_values(array_filter([
                        ['Open PDS', route('empPDS'), 'fa-clipboard'],
                        // Face enrolment lives here rather than in the PDS submenu:
                        // the PDS is the HR-facing record, and this is the page an
                        // employee actually opens. The tick or cross is the whole
                        // point — "am I set up for the attendance kiosk" should be
                        // answerable at a glance.
                        ['Face Registration', route('faceRecognition'), 'fa-user-shield', $faceRegistered],
                        $canFileLeave ? ['File or Check Leave', route('leavesReadEmp'), 'fa-calendar-plus'] : null,
                        ['View DTR', route('dtr-read'), 'fa-clock'],
                    ]));
                @endphp

                <ul class="space-y-2">
                    @foreach($actions as $action)
                        <li>
                            <a href="{{ $action[1] }}" class="group flex items-center gap-3 rounded-xl border border-line p-3 transition-colors hover:border-forest-600/40 hover:bg-paper focus-visible:outline-2 focus-visible:outline-sun-500">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-sun-100 text-sun-700"><i class="fas {{ $action[2] }}"></i></span>
                                <span class="flex-1 font-medium">{{ $action[0] }}</span>
                                @if(array_key_exists(3, $action))
                                    @if($action[3])
                                        <i class="fas fa-check-circle text-base text-forest-600" title="Registered"></i>
                                    @else
                                        <i class="fas fa-times-circle text-base text-red-600" title="Not registered"></i>
                                    @endif
                                @else
                                    <i class="fas fa-arrow-right text-xs text-ink/30 transition-transform group-hover:translate-x-0.5"></i>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
@else
    @php
        // Each tile (home/stat-tile): label, number, icon, one line of context,
        // then its chart (series, caption, hover unit) from
        // MasterController::dashboardTrends.
        // The second row is HR's review queue: each tile opens the pending
        // list it counts, and lights up while there is something in it.
        $share = fn ($count) => $totalEmployees > 0 ? round($count / $totalEmployees * 100) . '% of employees' : 'No employees yet';
        $waitingFor = fn ($queue) => $queue['oldest']
            ? 'Oldest waiting ' . $queue['oldest']->diffForHumans(now(), \Carbon\CarbonInterface::DIFF_ABSOLUTE)
            : 'Nothing waiting';
        $offices = $offCount->count();

        $stats = [
            ['label' => 'Employee', 'value' => $totalEmployees, 'icon' => 'fa-user-tie', 'href' => null, 'waiting' => false,
             'note' => 'Across ' . $offices . ' ' . ($offices == 1 ? 'office' : 'offices'),
             'series' => $trends['hires'], 'caption' => 'Hires, last 12 months', 'unit' => 'hired'],
            ['label' => 'Present', 'value' => $dtrCount, 'icon' => 'fa-users-viewfinder', 'href' => null, 'waiting' => false,
             'note' => $share($dtrCount),
             'series' => $trends['present'], 'caption' => '10 working days to today', 'unit' => 'present'],
            ['label' => 'Absent', 'value' => $totalEmployees - $dtrCount, 'icon' => 'fa-user-clock', 'href' => null, 'waiting' => false,
             'note' => 'No time record today',
             'series' => $trends['absent'], 'caption' => '10 working days to today', 'unit' => 'absent'],
        ];

        $queues = [
            ['Leave Application', $leaveappCount, 'fa-file-alt', 1, 'leave', 'Filed, last 14 days', 'filed'],
            ['Eligibility', $eliCount, 'fa-award', 2, 'eligibility', 'Submitted, last 14 days', 'submitted'],
            ['Working experience', $workexpCount, 'fa-tools', 3, 'experience', 'Submitted, last 14 days', 'submitted'],
            ['Learning & Development', $learDevCount, 'fa-book', 5, 'learning', 'Submitted, last 14 days', 'submitted'],
            ['Voluntary works', $volWorkCount, 'fa-hands-helping', 4, 'voluntary', 'Submitted, last 14 days', 'submitted'],
        ];

        foreach ($queues as [$label, $count, $icon, $pending, $key, $caption, $unit]) {
            $stats[] = [
                'label' => $label, 'value' => $count, 'icon' => $icon,
                'href' => route('readPending', $pending), 'waiting' => $count > 0,
                'note' => $waitingFor($trends[$key]),
                'series' => $trends[$key]['series'], 'caption' => $caption, 'unit' => $unit,
            ];
        }

        $statuses = [
            1 => 'Regular',
            2 => 'Full-time / Part-time',
            3 => 'Part-time / Part-time',
            4 => 'Job Order',
        ];
    @endphp

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach($stats as $stat)
                    @include('home.stat-tile', $stat)
                @endforeach
            </div>

            <section class="{{ $card }} p-4 sm:p-5">
                <div id="calendar"></div>
            </section>
        </div>

        <div class="grid gap-5 self-start md:grid-cols-2 xl:grid-cols-1">
            <section class="{{ $card }}">
                <h2 class="{{ $cardTitle }} px-5 pt-5 pb-3">Employee Status</h2>

                <ul class="divide-y divide-line border-t border-line">
                    @foreach($statuses as $status => $label)
                        @php
                            $share = $empStatusPercentages->get($status);
                        @endphp
                        <li class="px-5 py-3.5">
                            <div class="flex items-baseline justify-between gap-4">
                                <span>{{ $label }}</span>
                                <span class="tabular-nums text-ink/55">
                                    <strong class="font-semibold text-ink">{{ $share['count'] }}</strong>
                                    <span class="mx-1 text-ink/25">&middot;</span>{{ number_format($share['percentage'], 2) }}%
                                </span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line/70">
                                <div class="h-full rounded-full bg-forest-600 dark:bg-forest-500" style="width: {{ number_format($share['percentage'], 2) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="{{ $card }}">
                <h2 class="{{ $cardTitle }} px-5 pt-5 pb-3">Birthday</h2>

                <ul class="divide-y divide-line border-t border-line">
                    @forelse($upcomingBirthdays as $celebrant)
                        @php
                            $photo = $celebrant->profile && file_exists(public_path('Profile/Employee/' . $celebrant->profile))
                                ? asset('Profile/Employee/' . $celebrant->profile)
                                : asset('Profile/Employee/default.png');
                            $isToday = $celebrant->bdate->format('F j') == now('Asia/Manila')->format('F j');
                        @endphp
                        <li class="flex items-center gap-3 px-5 py-3">
                            <img src="{{ $photo }}" alt="" class="size-10 shrink-0 rounded-xl object-cover">
                            <div class="min-w-0 flex-1 leading-tight">
                                <p class="truncate font-medium">{{ ucfirst(strtolower($celebrant->lname)) . ' ' . ucfirst(strtolower($celebrant->fname)) }}</p>
                                <p class="mt-0.5 truncate text-xs text-ink/55">{{ $celebrant->office_abbr }}</p>
                            </div>
                            <p class="shrink-0 text-right text-xs text-ink/55">
                                @if($isToday)
                                    <i class="fas fa-birthday-cake mr-1 text-sun-600" title="Today"></i>
                                @endif
                                {{ $celebrant->bdate->format('F j, Y') }}
                            </p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-ink/55">No birthdays to show.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endif

{{-- Shown to any employee with no face on file, every time the dashboard
     loads, until they enrol. See MasterController::dashboard for why this is no
     longer once-per-sign-in: an employee with no biometric cannot use the
     attendance kiosk at all, so a prompt they can lose track of is worse than
     one that keeps asking.

     Still escapable ("I'll do this later") rather than a hard gate: enrolment
     needs a camera and reasonable light, and somebody checking their payslip
     from a phone on the road should be reminded, not locked out of the
     dashboard. It ignores Escape and clicks on the backdrop, so it has to be
     answered deliberately instead of dismissed by a stray tap. --}}
@if($guard == 'employee' && ($promptFaceRegistration ?? false))
    <dialog id="facePromptDialog" data-dialog-static aria-labelledby="facePromptLabel"
            class="m-auto w-[min(26rem,calc(100vw-2rem))] rounded-2xl border border-line bg-surface p-7 text-center text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60">
        <span class="mx-auto grid size-16 place-items-center rounded-full bg-forest-100 text-2xl text-forest-700">
            <i class="fas fa-user-shield"></i>
        </span>
        <h2 class="mt-4 font-display text-xl font-semibold tracking-tight" id="facePromptLabel">Register your face</h2>
        <p class="mt-2 leading-relaxed text-ink/60">
            You have no face registered yet. Registering lets you clock in and
            out at the attendance kiosk without typing anything. It takes about
            a minute and needs a camera with decent light.
        </p>
        <a href="{{ route('faceRecognition') }}"
           class="mt-6 flex h-11 items-center justify-center rounded-xl bg-forest-900 font-medium text-cream transition-colors hover:bg-forest-950 dark:bg-forest-600 dark:hover:bg-forest-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            Register now
        </a>
        <button type="button" data-dialog-close
                class="mt-2 h-10 w-full cursor-pointer rounded-xl text-ink/55 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
            I'll do this later
        </button>
    </dialog>
@endif
@endsection

@push('scripts')
<script>
    // Events calendar, read-only: partials/event-calendar with no options.
    // Events are added and changed on the Events page.
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl && window.FullCalendar) { hrisEventCalendar(calendarEl); }
    });

    // The face prompt waits its turn behind the privacy consent: that one
    // cannot be skipped, and this one comes back on the next dashboard load.
    (function () {
        var prompt = document.getElementById('facePromptDialog');
        if (prompt && !document.getElementById('dpnDialog')) { prompt.showModal(); }
    })();

    // Back cannot return to the page before the dashboard (the sign-in form).
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
@endpush
