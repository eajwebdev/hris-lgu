@extends('layouts.app')

@php
    $field = 'mt-1 block h-10 rounded-xl border border-line bg-paper text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';

    // $data is set once a report has been asked for (DtrController::dtrLogs).
    $data = $data ?? null;
@endphp

@section('breadcrumb', 'Logs')

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Daily Time Record</h1>
            <p class="mt-1 text-cream/70">Every punch between two dates, with the device it came from.</p>
        </div>
        @include('dtr.submenu')
    </div>
@endsection

@section('body')
<form action="{{ route('dtrLogspost') }}" method="POST" data-generates-pdf
      class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4">
    @csrf

    @include('dtr.partials.employee-field', ['selected' => $data['employeeId'] ?? null])

    <div>
        <label for="date_from" class="{{ $label }}">From</label>
        <input type="date" name="date_from" id="date_from" value="{{ $data['dateFrom'] ?? '' }}" required class="{{ $field }} px-3">
    </div>

    <div>
        <label for="date_to" class="{{ $label }}">To</label>
        <input type="date" name="date_to" id="date_to" value="{{ $data['dateTo'] ?? '' }}" required class="{{ $field }} px-3">
    </div>

    <label class="flex h-10 cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3.5 has-checked:border-forest-600/50 has-checked:bg-forest-100">
        <input type="checkbox" value="1" name="overtime" class="size-4 accent-forest-600" {{ ($data['overtime'] ?? 0) == 1 ? 'checked' : '' }}>
        Overtime
    </label>

    @include('partials.generate-button')
</form>

@include('partials.pdf-preview', [
    'pdfUrl' => $data
        ? route('logDtrView', ['employeeId' => $data['employeeId'] ?? 0, 'dateFrom' => $data['dateFrom'] ?? null, 'dateTo' => $data['dateTo'] ?? null, 'overtime' => $data['overtime'] ?? null])
        : null,
    'working' => 'Generating the log report',
    'prompt' => $acctstat == 1
        ? 'Choose an employee and a date range, then select Generate.'
        : 'Choose a date range, then select Generate.',
])
@endsection

@push('scripts')
<script>
    // "To" cannot be earlier than "From": it is held back, and emptied if it
    // has just been overtaken.
    (function () {
        var from = document.getElementById('date_from');
        var to = document.getElementById('date_to');

        function hold() {
            if (from.value) { to.min = from.value; } else { to.removeAttribute('min'); }
            if (from.value && to.value && to.value < from.value) { to.value = ''; }
        }

        from.addEventListener('change', hold);
        hold();
    })();
</script>
@endpush
