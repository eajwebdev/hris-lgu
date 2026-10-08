@extends('layouts.app')

@php
    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $dialog = 'm-auto max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $rowAction = 'inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg px-3 font-medium text-ink/70 transition-colors hover:bg-forest-100 hover:text-forest-800 focus-visible:outline-2 focus-visible:outline-sun-500';

    $half = fn ($semester) => $semester == 1 ? '1st half (Jan to Jun)' : '2nd half (Jul to Dec)';
    $officeName = $activeOffice->office_name ?? 'Office';
@endphp

@section('breadcrumb', 'OPCR')

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">SPMS</h1>
            <p class="mt-1 text-cream/70">OPCR documents of the {{ $officeName }}.</p>
        </div>
        @include('spms.partials.tabs')
    </div>
@endsection

@section('body')
<section class="rounded-2xl border border-line bg-surface" id="opcrList" data-list>
    <div class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        {{-- Which office's documents. Choosing one reloads the list for it. --}}
        @if($managedOffices->count() > 1)
            <form method="GET" action="{{ route('spms.opcr') }}" class="w-full sm:w-72">
                <label for="opcrOffice" class="{{ $label }}">Office</label>
                <select id="opcrOffice" name="office_id" onchange="this.form.submit()" class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    @foreach($managedOffices as $off)
                        <option value="{{ $off->id }}" @selected($activeOffice && $activeOffice->id == $off->id)>{{ $off->office_name }}</option>
                    @endforeach
                </select>
                <noscript><button class="{{ $secondary }} mt-2">Show</button></noscript>
            </form>
        @endif

        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
            <span class="sr-only">Search documents</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search year, half, head" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>

        <button type="button" data-dialog-open="opcrCreateDialog" class="{{ $primary }} ml-auto">
            <i class="fas fa-plus mr-1"></i> New OPCR
        </button>
    </div>

    {{-- relative: keeps the visually hidden labels in the cells inside this
         scroller instead of widening the page on a phone. --}}
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-4 py-3 pl-5 font-medium">Period</th>
                    <th scope="col" class="px-4 py-3 font-medium max-md:hidden">Office head</th>
                    <th scope="col" class="px-4 py-3 font-medium max-sm:hidden">Items</th>
                    <th scope="col" class="px-4 py-3 font-medium max-sm:hidden">Status</th>
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($opcrs as $opcr)
                    @php
                        $head = $opcr->head ? trim($opcr->head->fname . ' ' . $opcr->head->lname) : '';
                        $period = $opcr->year . ', ' . $half($opcr->semester);
                    @endphp
                    <tr data-row data-search="{{ strtolower($period . ' ' . $head . ' ' . $opcr->status . ' semester ' . $opcr->semester) }}" class="transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5">
                            <a href="{{ route('spms.opcr.matrix', $opcr->id) }}" class="font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-sun-500">OPCR for {{ $opcr->year }}</a>
                            <p class="mt-0.5 text-xs text-ink/55">{{ $half($opcr->semester) }}</p>
                        </td>
                        <td class="px-4 py-3 max-md:hidden">
                            @if($head) {{ $head }} @else <span class="text-ink/40">Not assigned</span> @endif
                        </td>
                        <td class="px-4 py-3 tabular-nums max-sm:hidden">{{ number_format($opcr->items->count()) }}</td>
                        <td class="px-4 py-3 max-sm:hidden">
                            <span class="inline-block rounded-md bg-line/60 px-2 py-0.5 text-xs font-medium text-ink/70">{{ $opcr->status ?: 'Draft' }}</span>
                        </td>
                        <td class="px-4 py-3 pr-5">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('spms.opcr.matrix', $opcr->id) }}" class="{{ $rowAction }}">Open</a>
                                <button type="button" class="{{ $rowAction }}"
                                        data-print-url="{{ route('spms.opcr.print', $opcr->id) }}"
                                        data-print-title="OPCR form, {{ $officeName }}, {{ $period }}">
                                    <i class="fas fa-file-pdf"></i> Print
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($opcrs->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-sun-100 text-xl text-sun-700"><i class="fas fa-folder-open"></i></span>
            <p class="mt-4 font-medium">No OPCR documents for the {{ $officeName }} yet.</p>
            <p class="mt-1 text-ink/55">Start one for a year and a half-year; its targets are added on the next screen.</p>
            <button type="button" data-dialog-open="opcrCreateDialog" class="{{ $primary }} mt-5"><i class="fas fa-plus mr-1"></i> New OPCR</button>
        </div>
    @else
        <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No document matches that.</p>
    @endif

    <p class="border-t border-line px-5 py-3 text-xs text-ink/55">Every OPCR is weighted the same way: core functions 60%, strategic functions 20%, support functions 20%.</p>
</section>

{{-- New OPCR. If one already exists for the office and period, it is opened
     instead of a second one being made (SpmsController::createOpcr). --}}
<dialog id="opcrCreateDialog" aria-labelledby="opcrCreateTitle" class="{{ $dialog }} w-[min(28rem,calc(100vw-2rem))]">
    <form method="POST" action="{{ route('spms.opcr.create') }}" class="p-6">
        @csrf

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="opcrCreateTitle">New OPCR</h2>
            <button type="button" data-dialog-close aria-label="Close" class="-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="mt-5 space-y-4">
            <div>
                <label for="newOpcrOffice" class="{{ $label }}">Office</label>
                <select id="newOpcrOffice" name="office_id" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    @foreach($managedOffices as $off)
                        <option value="{{ $off->id }}" @selected($activeOffice && $activeOffice->id == $off->id)>{{ $off->office_name }} ({{ $off->office_abbr }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="newOpcrYear" class="{{ $label }}">Year</label>
                    <select id="newOpcrYear" name="year" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                        @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="newOpcrHalf" class="{{ $label }}">Half</label>
                    <select id="newOpcrHalf" name="semester" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                        <option value="1" @selected($semester == 1)>1st half (Jan to Jun)</option>
                        <option value="2" @selected($semester == 2)>2nd half (Jul to Dec)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" class="{{ $primary }}">Create OPCR</button>
        </div>
    </form>
</dialog>

@include('spms.partials.print-dialog')
@endsection
