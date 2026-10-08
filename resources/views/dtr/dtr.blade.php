@extends('layouts.app')

@php
    $field = 'mt-1 block h-10 rounded-xl border border-line bg-paper text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';

    // Set once a DTR has been asked for (DtrController::dtrSearch).
    $generated = isset($employee, $period, $date) && $employee;
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Daily Time Record</h1>
            <p class="mt-1 text-cream/70">The printable DTR for half a month or a whole one.</p>
        </div>
        @include('dtr.submenu')
    </div>
@endsection

@section('body')
<form action="{{ route('dtrSearch') }}" method="POST" data-generates-pdf
      class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4">
    @csrf

    @include('dtr.partials.employee-field', ['selected' => $generated ? $employee->emp_ID : null])

    <div>
        <label for="period" class="{{ $label }}">Period</label>
        <select name="period" id="period" required class="{{ $field }} pr-8 pl-3">
            <option value="1" @if($generated && $period == 1) selected @endif>1st half</option>
            <option value="2" @if($generated && $period == 2) selected @endif>2nd half</option>
            <option value="3" @if($generated && $period == 3) selected @endif>Whole Month</option>
        </select>
    </div>

    <div>
        <label for="date" class="{{ $label }}">Month</label>
        <input type="month" name="date" id="date" value="{{ $generated ? $date : '' }}" required class="{{ $field }} px-3">
    </div>

    <label class="flex h-10 cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3.5 has-checked:border-forest-600/50 has-checked:bg-forest-100">
        <input type="checkbox" value="1" name="overtime" class="size-4 accent-forest-600" {{ $generated && $overtime == 1 ? 'checked' : '' }}>
        Overtime
    </label>

    @include('partials.generate-button')
</form>

@include('partials.pdf-preview', [
    'pdfUrl' => $generated
        ? route('dtr-pdf', ['employee' => $employee->emp_ID, 'period' => $period, 'date' => $date, 'overtime' => $overtime])
        : null,
    'working' => 'Generating the DTR',
    'prompt' => $acctstat == 1
        ? 'Choose an employee and a period, then select Generate.'
        : 'Choose a period, then select Generate.',
])
@endsection
