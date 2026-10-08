@extends('layouts.app')

@php
    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $rowAction = 'inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg px-3 font-medium text-ink/70 transition-colors hover:bg-forest-100 hover:text-forest-800 focus-visible:outline-2 focus-visible:outline-sun-500';

    // A plain employee sees only their own IPCR; heads and HR see an office's.
    $ownOnly = !$isHead && $guard === 'employee';
    $officeName = $activeOffice->office_name ?? 'Office';
    $half = $semester == 1 ? '1st half (Jan to Jun)' : '2nd half (Jul to Dec)';

    $years = collect(range(date('Y') - 2, date('Y') + 1))->push((int) $year)->unique()->sort()->values();
@endphp

@section('breadcrumb', 'IPCR')

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">SPMS</h1>
            <p class="mt-1 text-cream/70">
                {{ $ownOnly ? 'Your IPCR' : 'IPCR documents of the ' . $officeName }} for {{ $year }}, {{ $half }}.
            </p>
        </div>
        @include('spms.partials.tabs')
    </div>
@endsection

@section('body')
<section class="rounded-2xl border border-line bg-surface" id="ipcrList" data-list>
    {{-- Which office and which period. Changing any of them reloads the list. --}}
    <form method="GET" action="{{ route('spms.ipcr') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        @if(!$ownOnly && $managedOffices->count() > 1)
            <div class="w-full sm:w-72">
                <label for="ipcrOffice" class="{{ $label }}">Office</label>
                <select id="ipcrOffice" name="office_id" onchange="this.form.submit()" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    @foreach($managedOffices as $off)
                        <option value="{{ $off->id }}" @selected($activeOffice && $activeOffice->id == $off->id)>{{ $off->office_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label for="ipcrYear" class="{{ $label }}">Year</label>
            <select id="ipcrYear" name="year" onchange="this.form.submit()" class="{{ $field }} mt-1 block h-10 pr-8 pl-3">
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="ipcrHalf" class="{{ $label }}">Half</label>
            <select id="ipcrHalf" name="semester" onchange="this.form.submit()" class="{{ $field }} mt-1 block h-10 pr-8 pl-3">
                <option value="1" @selected($semester == 1)>1st half (Jan to Jun)</option>
                <option value="2" @selected($semester == 2)>2nd half (Jul to Dec)</option>
            </select>
        </div>

        <noscript><button class="h-10 rounded-xl border border-line px-5 font-medium">Show</button></noscript>

        @unless($ownOnly)
            <label class="relative w-full sm:ml-auto sm:w-72">
                <span class="sr-only">Search employees</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
                <input type="search" data-list-search placeholder="Search name, position" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
            </label>
        @endunless
    </form>

    {{-- relative: keeps the visually hidden labels in the cells inside this
         scroller instead of widening the page on a phone. --}}
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-4 py-3 pl-5 font-medium">Employee</th>
                    <th scope="col" class="px-4 py-3 font-medium max-md:hidden">Period</th>
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($officeEmployees as $emp)
                    @php
                        $name = trim($emp->fname . ' ' . $emp->lname);
                        $matrix = route('spms.ipcr.matrix', ['id' => $emp->id, 'semester' => $semester, 'year' => $year]);
                    @endphp
                    <tr data-row data-search="{{ strtolower($name . ' ' . $emp->position . ' ' . $emp->emp_ID) }}" class="transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5">
                            <a href="{{ $matrix }}" class="font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-sun-500">{{ $name }}</a>
                            <p class="mt-0.5 text-xs text-ink/55">{{ $emp->position ?: 'Personnel' }}</p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap max-md:hidden">{{ $year }}, {{ $half }}</td>
                        <td class="px-4 py-3 pr-5">
                            <div class="flex justify-end gap-1">
                                <a href="{{ $matrix }}" class="{{ $rowAction }}">Open</a>
                                <button type="button" class="{{ $rowAction }}"
                                        data-print-url="{{ route('spms.ipcr.print.cos', ['id' => $emp->id, 'semester' => $semester, 'year' => $year]) }}"
                                        data-print-title="Performance rating form, {{ $name }}, {{ $year }}">
                                    <i class="fas fa-file-pdf"></i> Rating form
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($officeEmployees->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-sun-100 text-xl text-sun-700"><i class="fas fa-folder-open"></i></span>
            <p class="mt-4 font-medium">No IPCR documents for the {{ $officeName }}.</p>
            <p class="mt-1 text-ink/55">An IPCR is kept for every active employee assigned to an office, and nobody is assigned to this one.</p>
        </div>
    @else
        <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No employee matches that.</p>

        {{-- Paging is for an office's list; one's own IPCR is a single row. --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3" @if($ownOnly) hidden @endif>
            <p class="text-ink/55" data-list-count aria-live="polite"></p>
            <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
            <select data-list-size aria-label="Rows per page" class="{{ $field }} h-9 pr-7 pl-3">
                <option value="25">25 rows</option>
                <option value="50">50 rows</option>
                <option value="0">All rows</option>
            </select>
        </div>
    @endif

    <p class="border-t border-line px-5 py-3 text-xs text-ink/55">Every IPCR is weighted the same way: core functions 60%, strategic functions 20%, support functions 20%.</p>
</section>

@include('spms.partials.print-dialog')
@endsection
