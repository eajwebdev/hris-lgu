@extends('layouts.app')

@php
    $field = 'mt-1 block h-10 rounded-xl border border-line bg-paper text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';

    // Set once a report has been asked for. $employeeId is 0 for everyone.
    $generated = request()->isMethod('post') && isset($employeeId, $month);
@endphp

@section('hero')
    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Tardiness &amp; Undertime</h1>
    <p class="mt-1 text-cream/70">Late arrivals and early departures for a month, for one employee or everyone.</p>
@endsection

@section('body')
<form action="{{ route('tirednessSearch') }}" method="POST" data-generates-pdf
      class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4">
    @csrf

    <div class="w-full sm:w-64">
        <label for="employee" class="{{ $label }}">Employee</label>
        <select name="employee" id="employee" required class="{{ $field }} w-full pr-8 pl-3">
            <option value="0">All employees</option>
            @if(auth()->guard($guard)->user()->role !== 'employee')
                {{-- Surname first and in order, so typing one in the open list jumps to it. --}}
                @foreach($employeeall->sortBy(fn ($emp) => strtolower($emp->lname . ' ' . $emp->fname)) as $emp)
                    <option value="{{ $emp->emp_ID }}" @if($employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                        {{ $emp->lname }}, {{ trim($emp->prefix . ' ' . $emp->fname) }} {{ isset($emp->mname) ? substr($emp->mname, 0, 1) . '.' : '' }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    <div>
        <label for="month" class="{{ $label }}">Month</label>
        <input type="month" name="month" id="month" value="{{ $month ?? date('Y-m') }}" required class="{{ $field }} px-3">
    </div>

    @include('partials.generate-button')
</form>

@include('partials.pdf-preview', [
    'pdfUrl' => $generated ? route('pdfTirednes', ['employeeId' => $employeeId, 'month' => $month]) : null,
    'working' => 'Generating the tardiness report',
    'prompt' => 'Choose an employee or everyone, and a month, then select Generate.',
])
@endsection
