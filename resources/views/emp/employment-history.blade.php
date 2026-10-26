@extends('layouts.app')

@php
    // An employee's employment history (EmploymentHistoryController): every
    // period they have worked for the LGU, latest first, with the gaps
    // between them, and HR's two actions: record a separation while they are
    // employed, rehire them once they are not.

    $fullName = trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname)));

    // "3 years 2 months", leaving out a part that is nought.
    $span = function (?\DateInterval $length) {
        if (!$length) return null;
        $parts = array_filter([
            $length->y ? $length->y . ' ' . ($length->y == 1 ? 'year' : 'years') : null,
            $length->m ? $length->m . ' ' . ($length->m == 1 ? 'month' : 'months') : null,
        ]);
        return $parts ? implode(' ', $parts) : ($length->d . ' ' . ($length->d == 1 ? 'day' : 'days'));
    };

    // Total service is the periods added together, not first hire to today.
    $months = $periods->sum(fn ($period) => ($length = $period->length()) ? $length->y * 12 + $length->m : 0);
    $total = $months ? $span(new \DateInterval('P' . intdiv($months, 12) . 'Y' . ($months % 12) . 'M')) : null;

    $lastPeriod = $periods->first();

    $card = 'rounded-2xl border border-line bg-surface p-5 shadow-sm shadow-forest-950/5 sm:p-6';
    $heading = 'font-display text-lg font-bold tracking-tight';
    $dialog = 'm-auto w-[min(34rem,calc(100vw-2rem))] rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $label = 'block text-[13px] font-semibold text-ink';
    $textarea = 'mt-1.5 block w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15';
    $primary = 'inline-flex h-11 cursor-pointer items-center gap-2 rounded-xl px-6 font-semibold text-white shadow-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $secondary = 'inline-flex h-11 cursor-pointer items-center rounded-xl border border-line bg-surface px-6 font-semibold transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $bannerButton = 'inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl bg-white px-4 font-semibold text-forest-900 shadow-md shadow-forest-950/25 transition-colors [text-shadow:none] hover:bg-cream focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
@endphp

@section('breadcrumb', $fullName)

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Employment History</h1>
            <p class="mt-1 text-white/85">
                {{ $fullName }}
                <span class="mx-1.5 text-white/40">|</span>
                Employee ID {{ $employee->emp_ID }}
            </p>
        </div>

        @if($current)
            <button type="button" data-dialog-open="separateDialog" class="{{ $bannerButton }}">
                <i class="fas fa-user-minus"></i> Record separation
            </button>
        @else
            <button type="button" data-dialog-open="rehireDialog" class="{{ $bannerButton }}">
                <i class="fas fa-user-plus"></i> Rehire
            </button>
        @endif
    </div>
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <div class="space-y-5">
        <section class="grid gap-3 sm:grid-cols-3" aria-label="Summary">
            <div class="{{ $card }}">
                <p class="text-ink/60">Now</p>
                @if($current)
                    <p class="mt-1 font-display text-2xl font-semibold tracking-tight text-forest-700">Employed</p>
                    <p class="mt-1 text-xs text-ink/55">{{ $current->date_start ? 'Since ' . $current->date_start->format('M d, Y') : 'Start date not recorded' }}</p>
                @else
                    <p class="mt-1 font-display text-2xl font-semibold tracking-tight text-red-700 dark:text-red-300">Separated</p>
                    <p class="mt-1 text-xs text-ink/55">{{ $lastPeriod?->date_end ? 'Since ' . $lastPeriod->date_end->format('M d, Y') : '' }}{{ $lastPeriod?->separation_reason ? ' · ' . $lastPeriod->separation_reason : '' }}</p>
                @endif
            </div>
            <div class="{{ $card }}">
                <p class="text-ink/60">Periods worked</p>
                <p class="mt-1 font-display text-2xl font-semibold tracking-tight tabular-nums">{{ $periods->count() }}</p>
                <p class="mt-1 text-xs text-ink/55">{{ $periods->count() > 1 ? 'Rehired ' . ($periods->count() - 1) . ' ' . ($periods->count() == 2 ? 'time' : 'times') : 'No break in service' }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="text-ink/60">Total service</p>
                <p class="mt-1 font-display text-2xl font-semibold tracking-tight">{{ $total ?? '--' }}</p>
                <p class="mt-1 text-xs text-ink/55">All periods added together</p>
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $heading }}">Periods of service</h2>
            <p class="text-xs text-ink/55">Latest first. A rehire keeps the same record and Employee ID; each return starts a new period.</p>

            @if($periods->isEmpty())
                <p class="mt-6 rounded-xl bg-paper px-4 py-6 text-center text-ink/55">No employment recorded yet.</p>
            @else
                <ol class="mt-6">
                    @foreach($periods as $period)
                        @php
                            $isCurrent = $period->date_end === null;
                            $older = $periods->get($loop->index + 1);
                            // The time out of service between this period and
                            // the one before it.
                            $gap = $older?->date_end && $period->date_start && $period->date_start->gt($older->date_end)
                                ? $older->date_end->diff($period->date_start) : null;
                        @endphp
                        <li class="relative pb-6 pl-9 last:pb-0">
                            {{-- The rail, down to the next period. --}}
                            @unless($loop->last)
                                <span class="absolute top-6 bottom-0 left-[11px] w-0.5 bg-line" aria-hidden="true"></span>
                            @endunless
                            <span class="absolute top-1 left-0 grid size-6 place-items-center rounded-full {{ $isCurrent ? 'bg-forest-600 text-white ring-4 ring-forest-100' : 'border-2 border-line bg-surface text-ink/40' }}" aria-hidden="true">
                                <i class="fas {{ $isCurrent ? 'fa-briefcase' : 'fa-clock-rotate-left' }} text-[10px]"></i>
                            </span>

                            <div class="rounded-xl border {{ $isCurrent ? 'border-forest-600/30 bg-forest-100/40' : 'border-line' }} p-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                    <p class="font-semibold tabular-nums">
                                        {{ $period->date_start?->format('M d, Y') ?? 'Start not recorded' }}
                                        <span class="mx-1 text-ink/40">&ndash;</span>
                                        {{ $isCurrent ? 'Present' : $period->date_end->format('M d, Y') }}
                                    </p>
                                    @if($length = $span($period->length()))
                                        <span class="rounded-full bg-paper px-2.5 py-0.5 text-xs font-medium text-ink/65">{{ $length }}</span>
                                    @endif
                                </div>

                                <p class="mt-2 font-display text-lg font-semibold tracking-tight">{{ $period->position ?: 'No position recorded' }}</p>
                                <dl class="mt-1 flex flex-wrap gap-x-5 gap-y-1 text-ink/65">
                                    <div class="flex items-center gap-1.5"><dt><i class="fas fa-building text-xs text-ink/40"></i><span class="sr-only">Office</span></dt><dd>{{ $period->office?->office_name ?? 'No office recorded' }}</dd></div>
                                    <div class="flex items-center gap-1.5"><dt><i class="fas fa-user-check text-xs text-ink/40"></i><span class="sr-only">Status</span></dt><dd>{{ $period->status?->status_name ?? 'No status recorded' }}</dd></div>
                                    @if($period->item_no)
                                        <div class="flex items-center gap-1.5"><dt><i class="fas fa-file-lines text-xs text-ink/40"></i><span class="sr-only">Item no.</span></dt><dd>Item {{ $period->item_no }}</dd></div>
                                    @endif
                                </dl>

                                @if(!$isCurrent)
                                    <p class="mt-3 inline-flex items-center gap-2 rounded-lg bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                        <i class="fas fa-user-minus"></i> Separated{{ $period->separation_reason ? ': ' . $period->separation_reason : '' }}
                                    </p>
                                @endif
                                @if($period->remarks)
                                    <p class="mt-2 text-ink/70">{{ $period->remarks }}</p>
                                @endif
                            </div>

                            @if($gap)
                                <p class="mt-3 flex items-center gap-2 text-xs text-ink/50">
                                    <i class="fas fa-pause"></i> Out of service for {{ $span($gap) }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</div>

@if($current)
    <dialog id="separateDialog" aria-labelledby="separateTitle" class="{{ $dialog }}" @if(old('form') === 'separate') data-reopen @endif>
        <form method="POST" action="{{ route('employeeSeparate', $employee->id) }}" class="p-6">
            @csrf
            <input type="hidden" name="form" value="separate">

            <h2 id="separateTitle" class="font-display text-xl font-semibold tracking-tight">Record separation</h2>
            <p class="mt-1 text-ink/65">
                Ends {{ $fullName }}'s current period as {{ $current->position ?: 'an employee' }} and turns their sign-in off.
                Their record stays, so they can be rehired later.
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @include('emp.partials.add-field', ['spec' => [
                    'name' => 'date_end', 'label' => 'Last day of service', 'type' => 'date', 'icon' => 'fa-calendar-xmark', 'required' => true, 'span' => 1,
                    'attrs' => array_filter(['min' => $current->date_start?->toDateString()]),
                ]])
                @include('emp.partials.add-field', ['spec' => [
                    'name' => 'separation_reason', 'label' => 'Reason', 'type' => 'select', 'prompt' => 'Select reason', 'icon' => 'fa-circle-info', 'required' => true, 'span' => 1,
                    'options' => array_combine(\App\Models\EmploymentHistory::SEPARATION_REASONS, \App\Models\EmploymentHistory::SEPARATION_REASONS),
                ]])
                <div class="sm:col-span-2">
                    <label for="separateRemarks" class="{{ $label }}">Remarks</label>
                    <textarea id="separateRemarks" name="remarks" rows="3" placeholder="e.g. Resignation letter dated Nov 15, 2026" class="{{ $textarea }}">{{ old('form') === 'separate' ? old('remarks') : '' }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }} bg-red-700 hover:bg-red-800"><i class="fas fa-user-minus"></i> Separate</button>
            </div>
        </form>
    </dialog>
@else
    <dialog id="rehireDialog" aria-labelledby="rehireTitle" class="{{ $dialog }}" @if(old('form') === 'rehire') data-reopen @endif>
        <form method="POST" action="{{ route('employeeRehire', $employee->id) }}" class="p-6">
            @csrf
            <input type="hidden" name="form" value="rehire">

            <h2 id="rehireTitle" class="font-display text-xl font-semibold tracking-tight">Rehire {{ $fullName }}</h2>
            <p class="mt-1 text-ink/65">
                Starts a new period on the same record and Employee ID. Their position, office and status become the ones below,
                and their sign-in is turned on again.
                @if($lastPeriod)
                    Last served as {{ $lastPeriod->position ?: 'an employee' }}{{ $lastPeriod->office ? ', ' . $lastPeriod->office->office_name : '' }}.
                @endif
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @include('emp.partials.add-field', ['spec' => [
                    'name' => 'date_hired', 'label' => 'Date rehired', 'type' => 'date', 'icon' => 'fa-calendar-days', 'required' => true, 'span' => 1,
                    'attrs' => array_filter(['min' => $lastPeriod?->date_end?->copy()->addDay()->toDateString()]),
                ]])
                @include('emp.partials.add-field', ['spec' => [
                    'name' => 'position', 'label' => 'Position', 'icon' => 'fa-user-tie', 'required' => true, 'span' => 1,
                    'placeholder' => 'Enter new position',
                ]])
                @include('emp.partials.add-field', ['spec' => [
                    'name' => 'emp_dept', 'label' => 'Department/Office', 'type' => 'select', 'prompt' => 'Select department/office', 'icon' => 'fa-building', 'required' => true, 'span' => 1,
                    'options' => $offices->pluck('office_name', 'id')->all(),
                ]])
                @include('emp.partials.add-field', ['spec' => [
                    'name' => 'emp_status', 'label' => 'Employment Status', 'type' => 'select', 'prompt' => 'Select status', 'icon' => 'fa-user-check', 'required' => true, 'span' => 1,
                    'options' => $stat->pluck('status_name', 'id')->all(),
                ]])
                @include('emp.partials.add-field', ['spec' => [
                    'name' => 'item_no', 'label' => 'Item / Plantilla No.', 'icon' => 'fa-file-lines', 'span' => 1, 'placeholder' => 'Enter item no.',
                ]])
                <div class="sm:col-span-2">
                    <label for="rehireRemarks" class="{{ $label }}">Remarks</label>
                    <textarea id="rehireRemarks" name="remarks" rows="3" placeholder="e.g. Appointment dated Feb 1, 2027" class="{{ $textarea }}">{{ old('form') === 'rehire' ? old('remarks') : '' }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
                <button type="submit" class="{{ $primary }} bg-forest-700 hover:bg-forest-800 dark:bg-forest-600 dark:hover:bg-forest-500"><i class="fas fa-user-plus"></i> Rehire</button>
            </div>
        </form>
    </dialog>
@endif
@endsection

@push('scripts')
<script>
    // A form sent back with errors opens again, still filled in.
    document.querySelectorAll('dialog[data-reopen]').forEach(function (dialog) { dialog.showModal(); });
</script>
@endpush
