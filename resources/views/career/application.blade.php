@extends('layouts.app')

@php
    // Everyone who has applied, and where each application stands. The steps
    // an application moves through, and what HR can do at each:
    //
    //   0 submitted   -> give it a control number (which unlocks its files)
    //   1 reviewing   -> qualify (schedules the interview) or disqualify
    //   2 qualified   -> not selected, or on to the top five
    //   5 top five    -> not hired, or hired
    //
    // Setting a control number and every change of status emails the
    // applicant (ApplicationController::setCtrlNo, updateStatus).

    // status => [label, pill colours]
    $statuses = [
        0 => ['Application Submitted', 'bg-line/60 text-ink/70'],
        1 => ['Reviewing', 'bg-sun-100 text-sun-700'],
        2 => ['Qualified / Ready for Interview', 'bg-forest-100 text-forest-800'],
        3 => ['Disqualified', 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300'],
        4 => ['Qualified yet not selected', 'bg-sun-100 text-sun-700'],
        5 => ['Top 5 / Psychological or Pre-Employment Test', 'bg-forest-100 text-forest-800'],
        6 => ['Not Hired', 'bg-line/60 text-ink/70'],
        7 => ['Hired', 'bg-forest-600 text-white'],
    ];

    // column on the application => [short name, what it is]
    $files = [
        'pds' => ['PDS', 'Personal Data Sheet'],
        'wes' => ['WES', 'Work Experience Sheet'],
        'intent' => ['Intent', 'Intent Letter'],
        'resume' => ['Resume', 'Resume'],
        'tor' => ['TOR', 'Transcript of Records'],
        'coe' => ['COE', 'Certificate of Employment'],
        'cert_training' => ['COT', 'Certificate of Training'],
    ];

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $input = $field . ' mt-1 block h-10 w-full px-3';
    $label = 'block text-xs font-medium text-ink/60';
    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $closeButton = '-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500';
    $step = 'inline-flex h-8 cursor-pointer items-center rounded-lg border px-2.5 text-xs font-medium whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $stepGo = $step . ' border-forest-600/40 text-forest-800 hover:bg-forest-100';
    $stepStop = $step . ' border-line text-ink/70 hover:border-ink/30';
    $removeRow = 'grid size-10 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-red-50 hover:text-red-700 disabled:cursor-default disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-ink/50 dark:hover:bg-red-500/10';
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Applications</h1>
            <p class="mt-1 max-w-2xl text-cream/70">Everyone who has applied for a vacancy, and where each application stands.</p>
        </div>

        <button type="button" data-dialog-open="applicantDialog"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-cream bg-cream px-4 font-medium text-forest-900 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <i class="fas fa-plus"></i> Add applicant
        </button>
    </div>
@endsection

@section('body')
{{-- Which applications to list. The same choices drive the printable report. --}}
<form method="GET" action="{{ route('appList') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4">
    <div class="w-full sm:w-72">
        <label for="filterPosition" class="{{ $label }}">Position</label>
        <select name="position_id" id="filterPosition" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
            <option value="">All positions</option>
            @foreach($jobs as $job)
                <option value="{{ $job->id }}" @selected((string) request('position_id') === (string) $job->id)>
                    {{ $job->title }}{{ !empty($job->plantilla_item_no) ? ' - Plantilla No. ' . $job->plantilla_item_no : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="w-full sm:w-64">
        <label for="filterStatus" class="{{ $label }}">Status</label>
        <select name="status" id="filterStatus" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
            <option value="">All statuses</option>
            @foreach($statuses as $value => [$statusLabel])
                <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="filterFrom" class="{{ $label }}">Applied from</label>
        <input type="date" name="date_from" id="filterFrom" value="{{ request('date_from') }}" class="{{ $field }} mt-1 block h-10 px-3">
    </div>
    <div>
        <label for="filterTo" class="{{ $label }}">Applied to</label>
        <input type="date" name="date_to" id="filterTo" value="{{ request('date_to') }}" class="{{ $field }} mt-1 block h-10 px-3">
    </div>

    <button type="submit" class="{{ $primary }}">Apply</button>
    <button type="submit" formaction="{{ route('applicationReport') }}" formtarget="_blank" class="{{ $secondary }}">
        <i class="fas fa-file-pdf mr-1"></i> Report
    </button>
</form>

<section class="mt-5 rounded-2xl border border-line bg-surface" id="applicationList" data-list>
    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
            <span class="sr-only">Search applications</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search name, number, email" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>
        <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-4 py-3 pl-5 font-medium">Applicant</th>
                    <th scope="col" class="px-4 py-3 font-medium max-md:hidden">Position</th>
                    <th scope="col" class="px-4 py-3 font-medium max-xl:hidden">Files</th>
                    <th scope="col" class="px-4 py-3 font-medium max-lg:hidden">Applied</th>
                    <th scope="col" class="px-4 py-3 font-medium">Status</th>
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Next step</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($applications as $app)
                    @php
                        $name = trim(preg_replace('/\s+/', ' ', $app->first_name . ' ' . $app->middle_name . ' ' . $app->last_name));
                        [$statusLabel, $statusLook] = $statuses[$app->status] ?? ['Unknown', 'bg-line/60 text-ink/70'];
                    @endphp
                    <tr id="tr-{{ $app->id }}" data-row
                        data-search="{{ strtolower($name . ' ' . $app->app_number . ' ' . $app->ctrl_no . ' ' . $app->email . ' ' . $app->mobile . ' ' . $app->position) }}"
                        class="align-top transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5">
                            <p class="font-semibold">{{ $name }}</p>
                            <p class="mt-0.5 text-xs whitespace-nowrap text-ink/55">
                                {{ $app->app_number }}@if($app->ctrl_no) <span class="mx-1 text-ink/25">|</span>Control no. {{ $app->ctrl_no }}@endif
                            </p>
                            <p class="mt-0.5 text-xs whitespace-nowrap text-ink/55">{{ ucfirst($app->sex) }}<span class="mx-1 text-ink/25">|</span>{{ $app->mobile }}</p>
                            <p class="mt-0.5 text-xs text-ink/55">{{ $app->email }}</p>
                        </td>
                        <td class="px-4 py-3 max-md:hidden">
                            <p>{{ $app->position }}</p>
                            @if(!empty($app->plantilla_item_no))
                                <p class="mt-0.5 text-xs text-ink/55">Plantilla No. {{ $app->plantilla_item_no }}</p>
                            @endif
                        </td>
                        {{-- The uploaded files stay locked until the application
                             has been given a control number. --}}
                        <td class="px-4 py-3 max-xl:hidden">
                            @if(empty($app->ctrl_no))
                                <span class="text-ink/45">Locked until a control no. is set</span>
                            @else
                                <div class="flex max-w-56 flex-wrap gap-1">
                                    @foreach($files as $column => [$short, $long])
                                        @if(!empty($app->{$column}))
                                            <a href="{{ asset('storage/' . $app->{$column}) }}" target="_blank" title="{{ $long }}"
                                               class="inline-flex h-7 items-center rounded-md border border-line px-2 text-xs font-medium transition-colors hover:border-forest-600/40 hover:bg-forest-100 hover:text-forest-800 focus-visible:outline-2 focus-visible:outline-sun-500">{{ $short }}</a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap max-lg:hidden">
                            <p>{{ $app->created_at->format('M d, Y') }}</p>
                            <p class="mt-0.5 text-xs text-ink/55">{{ $app->created_at->format('h:i A') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block max-w-44 rounded-md px-2 py-0.5 text-xs font-medium {{ $statusLook }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-4 py-3 pr-5">
                            <div class="ml-auto flex w-60 max-w-full flex-wrap justify-end gap-1">
                                <button type="button" class="{{ $app->ctrl_no ? $stepStop : $stepGo }}" data-ctrl-for="{{ $app->id }}" data-ctrl-no="{{ $app->ctrl_no }}" data-applicant="{{ $name }}">
                                    {{ $app->ctrl_no ? 'Edit control no.' : 'Set control no.' }}
                                </button>

                                @if($app->status == 1)
                                    <button type="button" class="{{ $stepGo }}" data-qualify="{{ $app->id }}" data-applicant="{{ $name }}">Qualify</button>
                                    <button type="button" class="{{ $stepStop }}" data-disqualify="{{ $app->id }}" data-applicant="{{ $name }}">Disqualify</button>
                                @elseif($app->status == 2 || $app->status == 5)
                                    @php
                                        // [new status, button, colour, question]
                                        $moves = $app->status == 2
                                            ? [[5, 'Top 5', $stepGo, 'Select ' . $name . ' for the next stage (Top 5)?'],
                                               [4, 'Not selected', $stepStop, 'Mark ' . $name . ' as qualified yet not selected?']]
                                            : [[7, 'Hired', $stepGo, 'Mark ' . $name . ' as hired?'],
                                               [6, 'Not hired', $stepStop, 'Mark ' . $name . ' as not hired?']];
                                    @endphp
                                    @foreach($moves as [$to, $moveLabel, $moveLook, $question])
                                        <form method="POST" action="{{ route('updateStatus') }}"
                                              data-confirm="{{ $question }}" data-confirm-detail="The applicant is sent an email about it." data-confirm-button="Yes, {{ strtolower($moveLabel) }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $app->id }}">
                                            <input type="hidden" name="status" value="{{ $to }}">
                                            <button type="submit" class="{{ $moveLook }}">{{ $moveLabel }}</button>
                                        </form>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($applications->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-sun-100 text-xl text-sun-700"><i class="fas fa-briefcase"></i></span>
            <p class="mt-4 font-medium">No applications {{ request()->hasAny(['position_id', 'status', 'date_from', 'date_to']) ? 'match those choices' : 'yet' }}.</p>
            <p class="mt-1 text-ink/55">Applicants apply through the careers page; one handed in on paper can be added here.</p>
        </div>
    @else
        <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No application matches that.</p>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
            <label class="flex items-center gap-2 text-ink/55">
                Rows
                <select data-list-size class="{{ $field }} h-9 pr-7 pl-3">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="0">All</option>
                </select>
            </label>
            <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
        </div>
    @endif
</section>

{{-- Control number --}}
<dialog id="ctrlDialog" aria-labelledby="ctrlDialogTitle" class="{{ $dialog }} w-[min(26rem,calc(100vw-2rem))]">
    <form method="POST" action="{{ route('setCtrlNo') }}" class="p-6">
        @csrf
        <input type="hidden" name="id">

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="font-display text-xl font-semibold tracking-tight" id="ctrlDialogTitle">Control number</h2>
                <p class="mt-0.5 truncate text-ink/60" data-applicant-name></p>
            </div>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-5">
            <label for="ctrlNo" class="{{ $label }}">Control number</label>
            <input type="text" id="ctrlNo" name="ctrl_no" placeholder="Enter control number" autocomplete="off" required class="{{ $input }}">
            <p class="mt-1 text-xs text-ink/55">Setting it unlocks the applicant's files and starts the review.</p>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </form>
</dialog>

{{-- Qualify: the interview schedule goes out with the notice --}}
<dialog id="qualifyDialog" aria-labelledby="qualifyDialogTitle" class="{{ $dialog }} w-[min(28rem,calc(100vw-2rem))]">
    <form method="POST" action="{{ route('updateStatus') }}" class="p-6">
        @csrf
        <input type="hidden" name="id">
        <input type="hidden" name="status" value="2">

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="font-display text-xl font-semibold tracking-tight" id="qualifyDialogTitle">Set interview schedule</h2>
                <p class="mt-0.5 truncate text-ink/60" data-applicant-name></p>
            </div>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-5 space-y-4">
            <div>
                <label for="interviewAt" class="{{ $label }}">Interview schedule</label>
                <input type="datetime-local" id="interviewAt" name="interview_datetime" required class="{{ $input }}">
            </div>
            <div>
                <label for="interviewVenue" class="{{ $label }}">Venue</label>
                <textarea id="interviewVenue" name="venue" rows="2" required class="{{ $field }} mt-1 block w-full px-3 py-2">Conference Room, Admin Building/Bidding Room/Accreditation/ Mini Hotel</textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" class="{{ $primary }}">Confirm and qualify</button>
        </div>
    </form>
</dialog>

{{-- Disqualify --}}
<dialog id="disqualifyDialog" aria-labelledby="disqualifyDialogTitle" class="{{ $dialog }} w-[min(28rem,calc(100vw-2rem))]">
    <form method="POST" action="{{ route('updateStatus') }}" class="p-6">
        @csrf
        <input type="hidden" name="id">
        <input type="hidden" name="status" value="3">

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="font-display text-xl font-semibold tracking-tight" id="disqualifyDialogTitle">Disqualify applicant</h2>
                <p class="mt-0.5 truncate text-ink/60" data-applicant-name></p>
            </div>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-5">
            <label for="dqReason" class="{{ $label }}">Reason for disqualification</label>
            <textarea id="dqReason" name="reason" rows="3" placeholder="Enter reason" required class="{{ $field }} mt-1 block w-full px-3 py-2"></textarea>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" class="h-10 cursor-pointer rounded-xl bg-red-700 px-5 font-medium text-white transition-colors hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Confirm disqualification</button>
        </div>
    </form>
</dialog>

{{-- Add an applicant by hand (an application handed in on paper). --}}
<dialog id="applicantDialog" aria-labelledby="applicantDialogTitle" class="{{ $dialog }} w-[min(46rem,calc(100vw-2rem))]">
    <form action="{{ route('applicationStore') }}" method="POST" class="p-6">
        @csrf

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="applicantDialogTitle">Add applicant</h2>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $closeButton }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-6">
            <div class="sm:col-span-4">
                <label for="newJid" class="{{ $label }}">Position applied</label>
                <select id="newJid" name="jid" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    <option value="" disabled selected>Select position</option>
                    @foreach($jobs as $job)
                        <option value="{{ $job->id }}">{{ $job->title }}{{ !empty($job->plantilla_item_no) ? ' - Plantilla No. ' . $job->plantilla_item_no : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="newApplied" class="{{ $label }}">Date applied</label>
                <input type="datetime-local" id="newApplied" name="created_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="{{ $input }}">
            </div>

            <div class="sm:col-span-2">
                <label for="newFirst" class="{{ $label }}">First name</label>
                <input type="text" id="newFirst" name="first_name" autocomplete="off" required class="{{ $input }}">
            </div>
            <div class="sm:col-span-2">
                <label for="newMiddle" class="{{ $label }}">Middle name</label>
                <input type="text" id="newMiddle" name="middle_name" autocomplete="off" class="{{ $input }}">
            </div>
            <div class="sm:col-span-2">
                <label for="newLast" class="{{ $label }}">Last name</label>
                <input type="text" id="newLast" name="last_name" autocomplete="off" required class="{{ $input }}">
            </div>

            <div class="sm:col-span-1">
                <label for="newAge" class="{{ $label }}">Age</label>
                <input type="number" id="newAge" name="age" min="18" max="65" required class="{{ $input }}">
            </div>
            <div class="sm:col-span-1">
                <label for="newSex" class="{{ $label }}">Sex</label>
                <select id="newSex" name="sex" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    <option value="" disabled selected>Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="newMobile" class="{{ $label }}">Mobile no.</label>
                <input type="text" id="newMobile" name="mobile" autocomplete="off" required class="{{ $input }}">
            </div>
            <div class="sm:col-span-2">
                <label for="newEmail" class="{{ $label }}">Email address</label>
                <input type="email" id="newEmail" name="email" autocomplete="off" required class="{{ $input }}">
            </div>

            <div class="sm:col-span-6">
                <label for="newAddress" class="{{ $label }}">Address</label>
                <textarea id="newAddress" name="address" rows="2" required class="{{ $field }} mt-1 block w-full px-3 py-2"></textarea>
            </div>
        </div>

        {{-- Education and eligibility take any number of rows. The first of
             each cannot be removed; the rest come from the patterns below. --}}
        <div class="mt-6 flex items-center justify-between gap-3">
            <h3 class="font-medium">Educational background</h3>
            <button type="button" data-add-row="education" class="h-9 cursor-pointer rounded-lg border border-line px-3 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-plus mr-1"></i> Add</button>
        </div>
        <div class="mt-2 space-y-2" data-rows="education">
            <div class="flex items-end gap-2" data-row-of="education">
                <div class="min-w-0 flex-1"><label class="{{ $label }}">School / course / description<input type="text" name="education[]" required class="{{ $input }}"></label></div>
                <div class="w-36"><label class="{{ $label }}">Level<input type="text" name="elevel[]" placeholder="College, HS" required class="{{ $input }}"></label></div>
                <div class="w-24"><label class="{{ $label }}">Year<input type="text" name="eyear[]" placeholder="2020" required class="{{ $input }}"></label></div>
                <button type="button" disabled title="The first row stays" class="{{ $removeRow }}"><i class="fas fa-xmark"></i></button>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between gap-3">
            <h3 class="font-medium">Eligibility</h3>
            <button type="button" data-add-row="eligibility" class="h-9 cursor-pointer rounded-lg border border-line px-3 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-plus mr-1"></i> Add</button>
        </div>
        <div class="mt-2 space-y-2" data-rows="eligibility">
            <div class="flex items-end gap-2" data-row-of="eligibility">
                <input type="text" name="eligibility[]" placeholder="Civil Service, PRC, etc." aria-label="Eligibility" class="{{ $field }} block h-10 w-full min-w-0 flex-1 px-3">
                <button type="button" disabled title="The first row stays" class="{{ $removeRow }}"><i class="fas fa-xmark"></i></button>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save applicant</button>
        </div>
    </form>

    <template data-pattern="education">
        <div class="flex items-end gap-2" data-row-of="education">
            <input type="text" name="education[]" aria-label="School / course / description" required class="{{ $field }} block h-10 min-w-0 flex-1 px-3">
            <input type="text" name="elevel[]" aria-label="Level" required class="{{ $field }} block h-10 w-36 px-3">
            <input type="text" name="eyear[]" aria-label="Year" required class="{{ $field }} block h-10 w-24 px-3">
            <button type="button" data-remove-row title="Remove this row" class="{{ $removeRow }}"><i class="fas fa-xmark"></i></button>
        </div>
    </template>
    <template data-pattern="eligibility">
        <div class="flex items-end gap-2" data-row-of="eligibility">
            <input type="text" name="eligibility[]" placeholder="Civil Service, PRC, etc." aria-label="Eligibility" class="{{ $field }} block h-10 w-full min-w-0 flex-1 px-3">
            <button type="button" data-remove-row title="Remove this row" class="{{ $removeRow }}"><i class="fas fa-xmark"></i></button>
        </div>
    </template>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    var list = document.getElementById('applicationList');

    // Opens one of the three per-applicant dialogs for the row's application.
    function open(id, button, applicationId, fill) {
        var dialog = document.getElementById(id);
        var form = dialog.querySelector('form');
        form.reset();
        form.elements['id'].value = applicationId;
        dialog.querySelector('[data-applicant-name]').textContent = button.dataset.applicant;
        if (fill) { fill(form); }
        dialog.showModal();
    }

    list.addEventListener('click', function (event) {
        var button;

        if ((button = event.target.closest('[data-ctrl-for]'))) {
            open('ctrlDialog', button, button.dataset.ctrlFor, function (form) {
                form.elements['ctrl_no'].value = button.dataset.ctrlNo || '';
            });
        } else if ((button = event.target.closest('[data-qualify]'))) {
            open('qualifyDialog', button, button.dataset.qualify);
        } else if ((button = event.target.closest('[data-disqualify]'))) {
            open('disqualifyDialog', button, button.dataset.disqualify);
        }
    });

    // Extra education and eligibility rows on the Add applicant form.
    var applicant = document.getElementById('applicantDialog');

    applicant.addEventListener('click', function (event) {
        var add = event.target.closest('[data-add-row]');
        if (add) {
            var kind = add.dataset.addRow;
            var row = applicant.querySelector('template[data-pattern="' + kind + '"]').content.firstElementChild.cloneNode(true);
            applicant.querySelector('[data-rows="' + kind + '"]').appendChild(row);
            row.querySelector('input').focus();
            return;
        }

        var remove = event.target.closest('[data-remove-row]');
        if (remove) { remove.closest('[data-row-of]').remove(); }
    });
})();
</script>
@endpush
