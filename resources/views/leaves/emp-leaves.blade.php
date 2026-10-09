@extends('layouts.app')

@php
    // HR sees an employee's credit ledger here; an employee sees their own
    // application form. Status and History are leaves/status and
    // leaves/history; the three share the tabs, the balances panel and HR's
    // credit dialogs in leaves/partials.

    $otherBalances = leave_other_balances();

    $field = 'mt-1 block w-full rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $rowAction = 'grid size-9 place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Leave</h1>
            <p class="mt-1 text-cream/70">
                @if($guard == 'web')
                    Credits earned, deducted and remaining for {{ ucwords(strtolower($employee->fname)) }} {{ ucwords(strtolower($employee->lname)) }}.
                @else
                    Apply for leave, then follow it under Status.
                @endif
            </p>
        </div>
        @include('leaves.partials.tabs')
    </div>
@endsection

@section('body')
<div class="grid gap-5 lg:grid-cols-[21rem_minmax(0,1fr)]">
    @include('leaves.partials.balances')

    <div class="min-w-0">
        @if($guard == 'web')
            @if(count($leaves) == 0)
                {{-- Nothing recorded yet: the first entry is the balance the
                     employee starts with. --}}
                <section class="rounded-2xl border border-line bg-surface p-6 sm:p-8">
                    <div class="mx-auto max-w-md">
                        <h2 class="text-center font-display text-2xl font-semibold tracking-tight">Input leave credit balance to start</h2>
                        <p class="mt-1 text-center text-ink/60">Please enter employee leave credit balance below to proceed.</p>

                        <form action="{{ route('leavesCreate') }}" method="POST" class="mt-6">
                            @csrf
                            <input type="hidden" name="empid" value="{{ $employee->id }}">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="startSl" class="{{ $label }}">Sick Leave</label>
                                    <input type="number" id="startSl" name="sl" step="0.001" min="0" placeholder="0.00" required class="{{ $field }} h-10">
                                </div>
                                <div>
                                    <label for="startVl" class="{{ $label }}">Vacation Leave</label>
                                    <input type="number" id="startVl" name="vl" step="0.001" min="0" placeholder="0.00" required class="{{ $field }} h-10">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="startRemarks" class="{{ $label }}">Remarks</label>
                                <textarea id="startRemarks" name="remarks" rows="3" class="{{ $field }} py-2"></textarea>
                            </div>

                            <div class="mt-5 flex justify-end">
                                <button type="submit" name="btn-submit" class="h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">
                                    <i class="fas fa-save mr-1"></i> Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @else
                <section class="rounded-2xl border border-line bg-surface" id="creditLedger" data-list>
                    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
                        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
                            <span class="sr-only">Search entries</span>
                            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
                            <input type="search" data-list-search placeholder="Search month, remarks" autocomplete="off" class="{{ $field }} mt-0 h-10 pl-9">
                        </label>
                        <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
                    </div>

                    {{-- relative: keeps the visually hidden labels in the cells
                         inside this scroller instead of widening the page. --}}
                    <div class="relative overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b border-line text-xs text-ink/55">
                                <tr>
                                    <th scope="col" class="px-4 py-3 pl-5 text-right font-medium">SL</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">VL</th>
                                    <th scope="col" class="px-4 py-3 font-medium">For the month of</th>
                                    <th scope="col" class="px-4 py-3 font-medium max-md:hidden">Remarks</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Entry</th>
                                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach($leaves as $leave)
                                    @php
                                        $month = \Carbon\Carbon::parse($leave->date)->format('F Y');
                                        $entered = $leave->created_at ? \Carbon\Carbon::parse($leave->created_at)->format('F d, Y') : '';
                                        // stat 0 is the opening entry; an entry with no days is a deduction.
                                        $kind = $leave->stat == 0 ? 'starting' : ($leave->stat == 1 && $leave->days == 0 ? 'deduction' : 'added');
                                        $kinds = [
                                            'starting'  => ['Starting balance', 'bg-sun-100 text-sun-700'],
                                            'deduction' => ['Deducted', 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300'],
                                            'added'     => ['Added', 'bg-forest-100 text-forest-800'],
                                        ];
                                    @endphp
                                    <tr id="tr-{{ $leave->id }}" data-row
                                        data-search="{{ strtolower($month . ' ' . $leave->remarks . ' ' . $entered . ' ' . $kinds[$kind][0]) }}"
                                        data-credit-id="{{ $leave->id }}" data-credit-kind="{{ $kind }}"
                                        data-credit-summary="{{ $kinds[$kind][0] }}, {{ $month }}: SL {{ $leave->earn_sl }}, VL {{ $leave->earn_vl }}"
                                        class="transition-colors hover:bg-paper/70">
                                        <td class="px-4 py-3 pl-5 text-right tabular-nums">{{ $leave->earn_sl }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums">{{ $leave->earn_vl }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <p>{{ $month }}</p>
                                            @if($entered)
                                                <p class="mt-0.5 text-xs text-ink/55">Entered {{ $entered }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-ink/75 max-md:hidden">{{ $leave->remarks }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-block rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap {{ $kinds[$kind][1] }}">{{ $kinds[$kind][0] }}</span>
                                        </td>
                                        <td class="px-4 py-3 pr-5">
                                            <div class="flex justify-end gap-1">
                                                <button type="button" title="Edit" data-credit-edit class="{{ $rowAction }} cursor-pointer hover:bg-forest-100 hover:text-forest-700">
                                                    <i class="fas fa-pen"></i><span class="sr-only">Edit</span>
                                                </button>
                                                {{-- The starting balance is corrected, never removed. --}}
                                                @if($kind === 'starting')
                                                    <span title="The starting balance cannot be deleted" aria-disabled="true" class="{{ $rowAction }} cursor-not-allowed text-ink/25"><i class="fas fa-trash"></i></span>
                                                @else
                                                    <button type="button" title="Delete" data-credit-delete class="{{ $rowAction }} cursor-pointer hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                                        <i class="fas fa-trash"></i><span class="sr-only">Delete</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No entry matches that.</p>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
                        <label class="flex items-center gap-2 text-ink/55">
                            Rows
                            <select data-list-size class="h-9 rounded-xl border border-line bg-paper pr-7 pl-3 text-ink outline-none focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="0">All</option>
                            </select>
                        </label>
                        <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
                    </div>
                </section>
            @endif
        @else
            @include('leaves.partials.application-form')
        @endif
    </div>
</div>

@if($guard == 'web')
    @include('leaves.partials.credit-dialogs')
@endif
@endsection
