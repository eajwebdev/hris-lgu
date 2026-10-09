@extends('layouts.app')

@php
    // One employee's IPCR for one half-year: the objectives set for them (some
    // cascaded from the office's OPCR, some added by hand or loaded from a
    // template), what they accomplished against each, the rating given, and
    // the three people who sign the form.
    //
    // SpmsController::ipcrMatrix starts the IPCR for the period if there is
    // none, so merely opening this page for a new period creates it.
    //
    // Only the current half-year can be changed; any other is shown as it
    // was left. Within it:
    //   - the employee writes their own accomplishments
    //   - anyone who can open the page may rate, add objectives, load a
    //     template and edit the signatories (the controller checks whose
    //     IPCR it is)
    //   - an objective cascaded from the OPCR can be removed by the office
    //     head or HR, not by the employee it was given to

    $isEditablePeriod = $year == (int) date('Y') && $semester == ((int) date('n') <= 6 ? 1 : 2);
    $isOwn = $guard === 'employee' && $employee->id == $user->id;
    $fullName = trim($employee->fname . ' ' . $employee->lname);
    $half = $semester == 1 ? '1st half (Jan to Jun)' : '2nd half (Jul to Dec)';
    $isJoOrCos = !empty($isJoOrCos);

    // The stored category => how it is headed, and its weight.
    $categories = [
        'Core Functions' => ['Core functions', '60%'],
        'Strategic Functions' => ['Strategic functions', '20%'],
        'Support Functions' => ['Support functions', '20%'],
    ];

    // The system began in 2026; the year being viewed is always offered.
    $years = collect(range(2026, max(2026, (int) date('Y'))))->push((int) $year)->unique()->sort()->values();

    // Who signs, until somebody types otherwise (Edit signatories).
    $resolvedHead = $officeHead ?: ($ipcr->office?->head ?: $office?->head);
    $signatories = [
        ['Discussed with (ratee)', 'ratee_name', 'ratee_position',
            $ipcr->ratee_name ?? $fullName, $ipcr->ratee_position ?? ($employee->position ?? 'Personnel')],
        ['Assessed by (supervisor)', 'assessed_by_name', 'assessed_by_position',
            $ipcr->assessed_by_name ?? ($resolvedHead ? $resolvedHead->fname . ' ' . $resolvedHead->lname : 'OFFICE HEAD NAME'),
            $ipcr->assessed_by_position ?? ($resolvedHead?->position ?: 'Head, ' . ($office->office_name ?? $ipcr->office?->office_name ?? 'Department'))],
        ['Final rating by', 'approved_by_name', 'approved_by_position',
            $ipcr->approved_by_name ?? 'LUCRECIA C. NICOLAS, MAEd', $ipcr->approved_by_position ?? 'MGDH-I (GSO)/HRMO-Designate'],
    ];

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $primary = 'inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl bg-forest-900 px-4 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $small = 'inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-lg border border-line px-2.5 text-xs font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-sun-500';
    $rowAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $chip = 'inline-flex h-7 items-center gap-1.5 rounded-full px-3 text-xs font-medium whitespace-nowrap';
    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $close = '-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500';
    $dialogTitle = 'font-display text-xl font-semibold tracking-tight';

    // Six columns once the page is wide enough to read them side by side;
    // below that an objective is a stack, numbered down its left edge.
    $columns = '@5xl:grid-cols-[3.25rem_minmax(0,1.15fr)_minmax(0,1.15fr)_minmax(0,1fr)_6.5rem_5rem]';
    $caption = 'text-xs text-ink/55 @5xl:hidden';
@endphp

@section('breadcrumb', $isOwn ? 'My IPCR' : 'IPCR of ' . $fullName)

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $fullName }}</h1>
            <p class="mt-1 text-cream/70">
                IPCR for {{ $year }}, {{ $half }}
                <span class="mx-1.5 text-cream/30">|</span>
                {{ $employee->position ?? 'Personnel' }}, {{ $office->office_name ?? 'LGU' }}
            </p>
        </div>
        @include('spms.partials.tabs')
    </div>
@endsection

@section('body')
<div class="@container space-y-5" id="ipcrMatrix">
    {{-- Which period, how it stands, and what can be done to the whole form --}}
    <section class="flex flex-wrap items-end gap-x-3 gap-y-4 rounded-2xl border border-line bg-surface p-4">
        <form method="GET" action="{{ route('spms.ipcr.matrix', $employee->id) }}" class="flex items-end gap-3">
            <div>
                <label for="matrixYear" class="{{ $label }}">Year</label>
                <select id="matrixYear" name="year" onchange="this.form.submit()" class="{{ $field }} mt-1 block h-10 pr-8 pl-3">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="matrixHalf" class="{{ $label }}">Half</label>
                <select id="matrixHalf" name="semester" onchange="this.form.submit()" class="{{ $field }} mt-1 block h-10 pr-8 pl-3">
                    <option value="1" @selected($semester == 1)>1st half (Jan to Jun)</option>
                    <option value="2" @selected($semester == 2)>2nd half (Jul to Dec)</option>
                </select>
            </div>
            <noscript><button class="{{ $secondary }}">Show</button></noscript>
        </form>

        <div class="flex h-10 flex-wrap items-center gap-2">
            <span class="{{ $chip }} bg-forest-100 text-forest-800">{{ $ipcr->status }}</span>
            @if($ipcr->final_numerical_rating)
                <span class="{{ $chip }} bg-sun-100 text-sun-700">
                    <span class="tabular-nums">{{ number_format($ipcr->final_numerical_rating, 2) }}</span> {{ $ipcr->final_adjectival_rating }}
                </span>
            @endif
            @unless($isEditablePeriod)
                <span class="{{ $chip }} border border-line text-ink/60" title="Only the current half-year can be changed"><i class="fas fa-lock text-[10px]"></i> Read-only period</span>
            @endunless
        </div>

        <div class="flex flex-wrap gap-2 sm:ml-auto">
            <button type="button" class="{{ $secondary }}"
                    data-print-url="{{ route('spms.ipcr.print.cos', ['id' => $employee->id, 'semester' => $semester, 'year' => $year]) }}"
                    data-print-title="Performance rating form, {{ $fullName }}, {{ $year }}">
                <i class="fas fa-file-pdf text-xs text-ink/50"></i> Rating form
            </button>
            @if($isEditablePeriod)
                <button type="button" data-dialog-open="templateDialog" class="{{ $secondary }}">
                    <i class="fas fa-file-import text-xs text-ink/50"></i> Load a template
                </button>
                <button type="button" data-dialog-open="objectiveDialog" class="{{ $primary }}">
                    <i class="fas fa-plus text-xs"></i> Add an objective
                </button>
            @endif
        </div>
    </section>

    {{-- The matrix --}}
    <section class="overflow-hidden rounded-2xl border border-line bg-surface">
        <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 px-5 pt-5 pb-4">
            <h2 class="font-display text-lg font-semibold tracking-tight">Objectives and accomplishments</h2>
            @if($isEditablePeriod)
                <p class="text-ink/55">Drag an objective by its handle to reorder it within its function.</p>
            @endif
        </div>

        <div class="hidden gap-x-5 border-t border-line px-5 py-2.5 text-xs font-medium text-ink/55 @5xl:grid {{ $columns }}" aria-hidden="true">
            <span>No.</span>
            <span>Major final output (MFO / PAPs)</span>
            <span>Success indicators (targets and measures)</span>
            <span>Actual accomplishment and evidence</span>
            <span>Rating</span>
            <span class="text-right">Actions</span>
        </div>

        @foreach($categories as $category => [$heading, $weight])
            @php
                $items = $ipcr->items->where('category', $category);
            @endphp
            <div class="flex items-baseline justify-between gap-4 border-y border-line bg-paper px-5 py-2.5">
                <h3 class="font-medium">{{ $heading }} <span class="ml-1 font-normal text-ink/55">{{ $weight }}</span></h3>
                <p class="text-xs text-ink/55">{{ $items->count() }} {{ $items->count() == 1 ? 'objective' : 'objectives' }}</p>
            </div>

            <ol class="divide-y divide-line" @if($isEditablePeriod) data-sortable @endif>
                @foreach($items as $item)
                    @php
                        $isMine = $guard === 'employee' && $item->employee_id == $user->id;
                        $mayRemove = $isMine || $isHead || $guard === 'web';
                        // Cascaded targets are the office head's to take back.
                        $isHeld = $item->opcr_item_id && $guard === 'employee' && !$isHead;
                    @endphp
                    <li class="grid grid-cols-[2rem_minmax(0,1fr)] gap-x-3 gap-y-3 px-5 py-4 @5xl:gap-x-5 {{ $columns }}"
                        data-item="{{ $item->id }}" data-objective="{{ \Illuminate\Support\Str::limit($item->mfo_pap, 120) }}"
                        data-accomplishment="{{ $item->actual_accomplishment }}" data-evidence="{{ $item->evidence_file }}"
                        data-q="{{ $item->rating_q }}" data-e="{{ $item->rating_e }}" data-t="{{ $item->rating_t }}" data-remarks="{{ $item->remarks }}">

                        <div class="row-[1/span_6] flex flex-col items-center gap-1 self-start @5xl:row-auto @5xl:flex-row @5xl:gap-2">
                            @if($isEditablePeriod)
                                <span data-drag title="Drag to reorder" class="grid size-7 cursor-grab place-items-center rounded-md text-ink/35 hover:bg-paper hover:text-ink/70 active:cursor-grabbing">
                                    <i class="fas fa-grip-vertical text-xs"></i>
                                </span>
                            @endif
                            <span class="font-medium tabular-nums" data-item-number>{{ $loop->iteration }}</span>
                        </div>

                        <div class="col-start-2 min-w-0 @5xl:col-auto">
                            @if($item->subcategory)
                                <p class="mb-1 text-xs text-ink/55">{{ ucfirst(strtolower($item->subcategory)) }}</p>
                            @endif
                            <p class="font-medium whitespace-pre-line">{{ $item->mfo_pap }}</p>
                            @if($item->opcr_item_id)
                                <p class="mt-1.5 text-xs text-forest-700"><i class="fas fa-sitemap mr-1"></i> Cascaded from the OPCR, row {{ $item->opcr_item_id }}</p>
                            @endif
                        </div>

                        <div class="col-start-2 min-w-0 @5xl:col-auto">
                            <p class="{{ $caption }}">Success indicators</p>
                            <p class="whitespace-pre-line text-ink/75">{{ $item->success_indicators }}</p>
                        </div>

                        <div class="col-start-2 min-w-0 @5xl:col-auto">
                            <p class="{{ $caption }}">Actual accomplishment</p>
                            @if($item->actual_accomplishment)
                                <p class="whitespace-pre-line">{{ $item->actual_accomplishment }}</p>
                            @else
                                <p class="text-ink/45">None entered yet.</p>
                            @endif

                            <div class="mt-2 flex flex-wrap gap-2 empty:hidden">
                                @if($item->evidence_file && $item->is_evidence_url)
                                    <button type="button" data-item-evidence class="{{ $small }}"><i class="fas fa-link text-ink/50"></i> View evidence</button>
                                @elseif($item->evidence_file)
                                    <a href="{{ asset('storage/' . $item->evidence_file) }}" target="_blank" class="{{ $small }}"><i class="fas fa-paperclip text-ink/50"></i> View attachment</a>
                                @endif
                                @if($isEditablePeriod && $isMine)
                                    <button type="button" data-item-accomplish class="{{ $small }}">
                                        <i class="fas fa-pen text-ink/50"></i> {{ $item->actual_accomplishment ? 'Edit' : 'Add accomplishment' }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="col-start-2 @5xl:col-auto">
                            <p class="{{ $caption }}">Rating</p>
                            @if($item->rating_ave)
                                <p class="font-display text-xl leading-tight font-semibold tabular-nums">{{ number_format($item->rating_ave, 2) }}</p>
                                <p class="text-xs text-ink/55 tabular-nums">
                                    @foreach(['Q' => $item->rating_q, 'E' => $item->rating_e, 'T' => $item->rating_t] as $measure => $given)
                                        {{ $measure }} {{ $given === null ? '-' : (float) $given }}@if(!$loop->last) / @endif
                                    @endforeach
                                </p>
                            @else
                                <p class="text-ink/45">Unrated</p>
                            @endif
                        </div>

                        <div class="col-start-2 flex gap-1 @5xl:col-auto @5xl:justify-end">
                            @if($isEditablePeriod)
                                <button type="button" data-item-rate title="Rate this accomplishment" class="{{ $rowAction }} hover:bg-sun-100 hover:text-sun-700">
                                    <i class="fas fa-star"></i><span class="sr-only">Rate this accomplishment</span>
                                </button>

                                @if($mayRemove && $isHeld)
                                    <span title="Assigned by the office head through the OPCR, so only they can remove it" class="grid size-9 place-items-center text-ink/25">
                                        <i class="fas fa-lock"></i><span class="sr-only">Assigned by the office head; cannot be removed here</span>
                                    </span>
                                @elseif($mayRemove)
                                    <form method="POST" action="{{ route('spms.ipcr.item.delete', $item->id) }}" data-confirm-danger
                                          data-confirm="Delete this objective?" data-confirm-detail="{{ \Illuminate\Support\Str::limit($item->mfo_pap, 120) }}" data-confirm-button="Yes, delete">
                                        @csrf
                                        <button type="submit" title="Delete this objective" class="{{ $rowAction }} hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                            <i class="fas fa-trash"></i><span class="sr-only">Delete this objective</span>
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span title="Only the current half-year can be changed" class="grid size-9 place-items-center text-ink/25"><i class="fas fa-lock"></i></span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if($items->isEmpty())
                <p class="px-5 py-5 text-ink/45">Nothing under {{ strtolower($heading) }} yet.</p>
            @endif
        @endforeach

        @if($isEditablePeriod && $ipcr->items->count())
            <form method="POST" action="{{ route('spms.ipcr.clear', $ipcr->id) }}" class="border-t border-line px-5 py-3 text-right" data-confirm-danger
                  data-confirm="Clear all the rows?" data-confirm-button="Yes, clear them"
                  data-confirm-detail="{{ $guard === 'employee' && !$isHead ? 'This removes the objectives you added yourself. Targets cascaded from the OPCR stay. It cannot be undone.' : 'This removes every objective on this IPCR, with its accomplishment and rating. It cannot be undone.' }}">
                @csrf
                <button type="submit" class="cursor-pointer font-medium text-red-700 underline-offset-2 hover:underline dark:text-red-300">Clear all rows</button>
            </form>
        @endif
    </section>

    {{-- Who signs the printed form --}}
    <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-lg font-semibold tracking-tight">Signatories</h2>
            @if($isEditablePeriod)
                <button type="button" data-dialog-open="signatoriesDialog" class="{{ $small }} h-9 px-3 text-sm"><i class="fas fa-pen text-xs text-ink/50"></i> Edit signatories</button>
            @endif
        </div>

        <dl class="mt-4 grid gap-5 @2xl:grid-cols-3">
            @foreach($signatories as [$role, , , $signer, $signerPosition])
                <div class="border-l-2 border-sun-500 pl-4">
                    <dt class="text-xs text-ink/55">{{ $role }}</dt>
                    <dd class="mt-1 font-semibold uppercase">{{ $signer }}</dd>
                    <dd class="text-ink/65">{{ $signerPosition }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
</div>

@include('spms.partials.print-dialog')

{{-- The dialogs below are one each for the whole page, filled from the
     objective whose button opened them (the script at the end). --}}

@if($isEditablePeriod)
    {{-- Written by the employee the IPCR belongs to --}}
    <dialog id="accomplishmentDialog" aria-labelledby="accomplishmentTitle" class="{{ $dialog }} w-[min(34rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('spms.ipcr.accomplishment.submit') }}" class="p-6">
            @csrf
            <input type="hidden" name="ipcr_item_id">

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="{{ $dialogTitle }}" id="accomplishmentTitle">Accomplishment and evidence</h2>
                    <p class="mt-1 line-clamp-2 text-ink/60" data-objective></p>
                </div>
                <button type="button" data-dialog-close aria-label="Close" class="{{ $close }}"><i class="fas fa-xmark"></i></button>
            </div>

            <label for="accomplishmentText" class="{{ $label }} mt-5">Actual accomplishment</label>
            <textarea id="accomplishmentText" name="actual_accomplishment" rows="5" required placeholder="What was actually done against this target"
                      class="{{ $field }} mt-1 block w-full px-3 py-2 leading-relaxed"></textarea>

            <label for="accomplishmentEvidence" class="{{ $label }} mt-4">Link to the evidence</label>
            <input type="url" id="accomplishmentEvidence" name="evidence_file" placeholder="https://drive.google.com/file/d/.../view" class="{{ $field }} mt-1 block h-10 w-full px-3">
            <p class="mt-1 text-xs text-ink/55">A Google Drive or other web link the rater can open. Optional.</p>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }}"><i class="fas fa-save text-xs"></i> Save</button>
            </div>
        </form>
    </dialog>

    <dialog id="rateDialog" aria-labelledby="rateTitle" class="{{ $dialog }} w-[min(34rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('spms.ipcr.item.rate') }}" class="p-6">
            @csrf
            <input type="hidden" name="ipcr_item_id">

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="{{ $dialogTitle }}" id="rateTitle">Rate the accomplishment</h2>
                    <p class="mt-1 line-clamp-2 text-ink/60" data-objective></p>
                </div>
                <button type="button" data-dialog-close aria-label="Close" class="{{ $close }}"><i class="fas fa-xmark"></i></button>
            </div>

            <p class="{{ $label }} mt-5">Accomplishment</p>
            <p class="mt-1 max-h-40 overflow-y-auto rounded-xl bg-paper px-3 py-2 leading-relaxed whitespace-pre-line text-ink/80" data-accomplishment></p>

            <fieldset class="mt-4">
                <legend class="{{ $label }}">Rating, from 1 to 5</legend>
                <div class="mt-1 grid grid-cols-3 gap-3">
                    @foreach(['rating_q' => 'Quality', 'rating_e' => 'Efficiency', 'rating_t' => 'Timeliness'] as $name => $measure)
                        <label>
                            <span class="text-xs text-ink/60">{{ $measure }}</span>
                            <input type="number" name="{{ $name }}" min="1" max="5" step="0.1" inputmode="decimal" class="{{ $field }} mt-1 block h-10 w-full px-3 tabular-nums">
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-ink/55">Leave out a measure that does not apply; the average is taken over the ones given.</p>
            </fieldset>

            <label for="rateRemarks" class="{{ $label }} mt-4">Remarks</label>
            <textarea id="rateRemarks" name="remarks" rows="2" class="{{ $field }} mt-1 block w-full px-3 py-2 leading-relaxed"></textarea>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }}"><i class="fas fa-star text-xs"></i> Save rating</button>
            </div>
        </form>
    </dialog>

    <dialog id="objectiveDialog" aria-labelledby="objectiveTitle" class="{{ $dialog }} w-[min(40rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('spms.ipcr.item.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="ipcr_id" value="{{ $ipcr->id }}">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="{{ $dialogTitle }}" id="objectiveTitle">Add an objective</h2>
                    <p class="mt-1 text-ink/60">A deliverable or routine duty that is not among the targets cascaded from the OPCR.</p>
                </div>
                <button type="button" data-dialog-close aria-label="Close" class="{{ $close }}"><i class="fas fa-xmark"></i></button>
            </div>

            <label for="objectiveCategory" class="{{ $label }} mt-5">Function</label>
            <select id="objectiveCategory" name="category" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                <option value="Core Functions">Core functions (60%)</option>
                <option value="Strategic Functions">Strategic functions (20%)</option>
                <option value="Support Functions" selected>Support functions (20%): routine and administrative</option>
            </select>

            <label for="objectiveMfo" class="{{ $label }} mt-4">Major final output (MFO / PAPs)</label>
            <textarea id="objectiveMfo" name="mfo_pap" rows="3" required placeholder="The deliverable, or the routine duty" class="{{ $field }} mt-1 block w-full px-3 py-2 leading-relaxed"></textarea>

            <label for="objectiveIndicators" class="{{ $label }} mt-4">Success indicators (targets and measures)</label>
            <textarea id="objectiveIndicators" name="success_indicators" rows="3" required placeholder="How much, how well and by when" class="{{ $field }} mt-1 block w-full px-3 py-2 leading-relaxed"></textarea>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }}"><i class="fas fa-save text-xs"></i> Save objective</button>
            </div>
        </form>
    </dialog>

    {{-- A template adds its rows to whatever is already there. Job order
         and contract of service staff are rated on a different form from
         regular employees, so each is offered its own. --}}
    <dialog id="templateDialog" aria-labelledby="templateTitle" class="{{ $dialog }} w-[min(40rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('spms.ipcr.template.cos') }}" class="p-6">
            @csrf
            <input type="hidden" name="ipcr_id" value="{{ $ipcr->id }}">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="{{ $dialogTitle }}" id="templateTitle">Load a template</h2>
                    <p class="mt-1 text-ink/60">
                        {{ $isJoOrCos
                            ? 'The standard rows of the performance rating form for job order and contract of service personnel.'
                            : 'The standard rows of the official LGU Mabinay IPCR form.' }}
                        They are added to the rows already here.
                    </p>
                </div>
                <button type="button" data-dialog-close aria-label="Close" class="{{ $close }}"><i class="fas fa-xmark"></i></button>
            </div>

            @php
                $templates = $isJoOrCos
                    ? [
                        'general_services' => ['General services, maintenance and utility personnel', 'Hallway cleanliness, garbage gathering and segregation, daily routine tasks, flag ceremony, LCE activities, and the work ethics evaluation.'],
                        'admin_support' => ['Administrative and clerical support personnel', 'Document encoding and filing, records routing, client assistance, departmental support, and the work ethics evaluation.'],
                    ]
                    : [
                        'official_regular' => ['Official LGU Mabinay IPCR form, for regular and permanent employees', 'Core functions: policy and program implementation, operational management, service delivery and public engagement, personnel management, strategic planning, financial resource management. Support functions: compliance and regulation, human resource management, department meetings, trainings and flag ceremonies.'],
                    ];
            @endphp
            <fieldset class="mt-5 space-y-2">
                <legend class="sr-only">Template</legend>
                @foreach($templates as $value => [$templateName, $holds])
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 transition-colors has-checked:border-forest-600 has-checked:bg-forest-100/60">
                        <input type="radio" name="template_type" value="{{ $value }}" class="mt-0.5 size-4 shrink-0 accent-forest-600" @checked($loop->first)>
                        <span>
                            <span class="block font-medium">{{ $templateName }}</span>
                            <span class="mt-0.5 block leading-relaxed text-ink/65">{{ $holds }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }}"><i class="fas fa-file-import text-xs"></i> Load the rows</button>
            </div>
        </form>
    </dialog>

    <dialog id="signatoriesDialog" aria-labelledby="signatoriesTitle" class="{{ $dialog }} w-[min(44rem,calc(100vw-2rem))]">
        <form method="POST" action="{{ route('spms.ipcr.signatories', $ipcr->id) }}" class="p-6">
            @csrf

            <div class="flex items-start justify-between gap-4">
                <h2 class="{{ $dialogTitle }}" id="signatoriesTitle">Signatories</h2>
                <button type="button" data-dialog-close aria-label="Close" class="{{ $close }}"><i class="fas fa-xmark"></i></button>
            </div>

            <div class="mt-4 space-y-4">
                @foreach($signatories as [$role, $nameField, $positionField, $signer, $signerPosition])
                    <fieldset class="grid gap-3 sm:grid-cols-2">
                        <legend class="mb-1 font-medium">{{ $role }}</legend>
                        <label>
                            <span class="{{ $label }}">Name</span>
                            <input type="text" name="{{ $nameField }}" value="{{ $signer }}" required maxlength="255" class="{{ $field }} mt-1 block h-10 w-full px-3">
                        </label>
                        <label>
                            <span class="{{ $label }}">Position</span>
                            <input type="text" name="{{ $positionField }}" value="{{ $signerPosition }}" required maxlength="255" class="{{ $field }} mt-1 block h-10 w-full px-3">
                        </label>
                    </fieldset>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }}"><i class="fas fa-save text-xs"></i> Save signatories</button>
            </div>
        </form>
    </dialog>
@endif

{{-- Evidence given as a link, read without leaving the page. The frame gets
     its address on opening and is emptied on closing. --}}
<dialog id="evidenceDialog" aria-labelledby="evidenceTitle"
        class="m-auto h-[calc(100dvh-2rem)] w-[min(72rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60 open:flex">
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-line px-5 py-3">
        <h2 class="min-w-0 truncate font-display text-lg font-semibold tracking-tight" id="evidenceTitle">Evidence</h2>
        <div class="flex items-center gap-2">
            <a data-evidence-tab href="#" target="_blank" rel="noopener noreferrer" class="{{ $secondary }} h-9">Open in new tab</a>
            <button type="button" data-dialog-close aria-label="Close" class="{{ $close }} mt-0"><i class="fas fa-xmark"></i></button>
        </div>
    </div>
    <iframe title="Evidence document" allow="autoplay; encrypted-media" class="min-h-0 w-full flex-1 border-0 bg-white"></iframe>
</dialog>
@endsection

@push('scripts')
<script src="{{ asset('template/plugins/sortablejs/Sortable.min.js') }}"></script>
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var page = document.getElementById('ipcrMatrix');

    /* ------------------------------------------- the per-objective dialogs */
    var accomplishment = document.getElementById('accomplishmentDialog');
    var rate = document.getElementById('rateDialog');
    var evidence = document.getElementById('evidenceDialog');
    var evidenceFrame = evidence.querySelector('iframe');

    evidence.addEventListener('close', function () { evidenceFrame.removeAttribute('src'); });

    page.addEventListener('click', function (event) {
        var button = event.target.closest('[data-item-accomplish], [data-item-rate], [data-item-evidence]');
        if (!button) return;

        var item = button.closest('[data-item]').dataset;

        if (button.hasAttribute('data-item-accomplish')) {
            accomplishment.querySelector('[name="ipcr_item_id"]').value = item.item;
            accomplishment.querySelector('[data-objective]').textContent = item.objective;
            accomplishment.querySelector('[name="actual_accomplishment"]').value = item.accomplishment;
            accomplishment.querySelector('[name="evidence_file"]').value = item.evidence;
            accomplishment.showModal();
        }

        if (button.hasAttribute('data-item-rate')) {
            rate.querySelector('[name="ipcr_item_id"]').value = item.item;
            rate.querySelector('[data-objective]').textContent = item.objective;
            rate.querySelector('[data-accomplishment]').textContent = item.accomplishment || 'No accomplishment has been entered for this objective.';
            rate.querySelector('[name="rating_q"]').value = item.q;
            rate.querySelector('[name="rating_e"]').value = item.e;
            rate.querySelector('[name="rating_t"]').value = item.t;
            rate.querySelector('[name="remarks"]').value = item.remarks;
            rate.showModal();
        }

        if (button.hasAttribute('data-item-evidence')) {
            // A Google Drive file link has a page of its own made for framing.
            var drive = item.evidence.match(/drive\.google\.com\/file\/d\/([^\/]+)/i);
            evidence.querySelector('[data-evidence-tab]').href = item.evidence;
            evidenceFrame.src = drive ? 'https://drive.google.com/file/d/' + drive[1] + '/preview' : item.evidence;
            evidence.showModal();
        }
    });

    /* ------------------------------------------------------------ reorder */
    // Within one function only. The new order of that function's rows is
    // sent whole; the numbers down the side follow straight away.
    if (typeof Sortable === 'undefined') return;

    Array.prototype.forEach.call(page.querySelectorAll('[data-sortable]'), function (list) {
        Sortable.create(list, {
            animation: 150,
            handle: '[data-drag]',
            draggable: '[data-item]',
            ghostClass: 'opacity-40',
            chosenClass: 'bg-forest-100',
            onEnd: function (moved) {
                if (moved.oldIndex === moved.newIndex) return;

                var rows = Array.prototype.slice.call(list.querySelectorAll('[data-item]'));
                rows.forEach(function (row, index) { row.querySelector('[data-item-number]').textContent = index + 1; });

                fetch("{{ route('spms.ipcr.item.reorder') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ order: rows.map(function (row) { return row.dataset.item; }) })
                })
                    .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
                    .catch(function () { hrisToast('error', 'The new order could not be saved. Reload the page to see the order that is stored.'); });
            }
        });
    });
})();
</script>
@endpush
