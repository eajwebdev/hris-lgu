@extends('layouts.app')

@php
    // Leave applications that are over: approved, disapproved, or approved
    // and then cancelled by HR. HR opens a named employee's and can print
    // the report of everyone's between two dates; an employee sees their own
    // and, as a supervisor, the ones they signed.

    $isStaff = $guard == 'web';

    $otherBalances = leave_other_balances();
    $leaveTypes = leave_type_names();

    $own = collect($leaveApplication);
    // A supervisor's own applications are already in the list above.
    $signed = collect($leaveApplication1)->reject(fn ($leave) => $own->contains('id', $leave->id));

    $person = ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname));

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Leave</h1>
            <p class="mt-1 text-cream/70">
                @if($isStaff)
                    {{ $person }}'s leave applications that have been decided.
                @else
                    Your leave applications that have been decided.
                @endif
            </p>
        </div>
        @include('leaves.partials.tabs')
    </div>
@endsection

@section('body')
<div class="grid gap-5 lg:grid-cols-[21rem_minmax(0,1fr)]">
    @include('leaves.partials.balances', ['switchRoute' => 'historyRead'])

    <div class="min-w-0 space-y-8">
        @if($isStaff)
            {{-- The report of leave applications filed between two dates, for
                 every employee, as a PDF in a new tab. The controller reads
                 one field, "from to to" (or a single day); the script below
                 writes it from the two dates. --}}
            <form action="{{ route('leaveReport') }}" method="POST" target="_blank" id="leaveReport"
                  class="flex flex-wrap items-end gap-x-3 gap-y-3 rounded-2xl border border-line bg-surface p-4">
                @csrf
                <input type="hidden" name="date">
                <div class="mr-auto min-w-0 basis-56">
                    <h2 class="font-medium">Leave report</h2>
                    <p class="text-ink/60">Everyone's applications filed between two dates.</p>
                </div>
                <div>
                    <label for="reportFrom" class="{{ $label }}">From</label>
                    <input type="date" id="reportFrom" required class="{{ $field }} mt-1 block h-10 px-3">
                </div>
                <div>
                    <label for="reportTo" class="{{ $label }}">To</label>
                    <input type="date" id="reportTo" class="{{ $field }} mt-1 block h-10 px-3">
                </div>
                <button type="submit" title="Open the report as a PDF" class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                    <i class="fas fa-file-pdf text-xs text-ink/50"></i> PDF
                </button>
            </form>
        @endif

        <section aria-labelledby="ownHistory">
            <h2 id="ownHistory" class="{{ $signed->isEmpty() ? 'sr-only' : 'mb-3 font-display text-xl font-semibold tracking-tight' }}">
                {{ $isStaff ? 'Decided applications' : 'Your leave' }}
            </h2>
            @include('leaves.partials.history-table', ['table' => [
                'id' => 'leaveHistory',
                'rows' => $own,
                'empty' => ($isStaff ? $person . ' has' : 'You have') . ' no leave application that has been decided. Ones still in progress are under Status.',
            ]])
        </section>

        @if($signed->isNotEmpty())
            <section aria-labelledby="signedHistory">
                <h2 id="signedHistory" class="font-display text-xl font-semibold tracking-tight">Signed as supervisor</h2>
                <p class="mt-0.5 mb-3 text-ink/60">Decided applications of the people you sign for.</p>
                @include('leaves.partials.history-table', ['table' => [
                    'id' => 'leaveSigned',
                    'rows' => $signed,
                    'withFiler' => true,
                    'empty' => '',
                ]])
            </section>
        @endif
    </div>
</div>

@include('leaves.partials.actions')

@if($isStaff)
    @include('leaves.partials.credit-dialogs')
@endif
@endsection

@if($isStaff)
@push('scripts')
<script>
(function () {
    // The report takes its dates as one field: a day, or "from to to".
    var report = document.getElementById('leaveReport');
    report.addEventListener('submit', function () {
        var from = document.getElementById('reportFrom').value;
        var to = document.getElementById('reportTo').value;
        report.elements.date.value = to && to !== from ? from + ' to ' + to : from;
    });
})();
</script>
@endpush
@endif
