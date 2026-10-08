@extends('layouts.app')

@php
    $field = 'block h-10 w-full rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $roles = ['Chairperson', 'Vice-Chairperson', 'Member'];

    // The rows to draw: the board as saved, or one blank row to start from.
    $rows = $members->isNotEmpty() ? $members : collect([null]);
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Selection Board</h1>
            <p class="mt-1 max-w-2xl text-cream/70">
                The Personnel Selection Board: the signatory block printed beneath every Comparative Assessment Form.
                Names are stored as typed, so a past assessment still prints correctly after a member leaves.
            </p>
        </div>

        <a href="{{ route('positionDescriptionList') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-cream/25 px-4 font-medium transition-colors hover:border-cream hover:bg-cream hover:text-forest-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <i class="fas fa-arrow-left"></i> Positions &amp; Vacancies
        </a>
    </div>
@endsection

@section('body')
<form method="POST" action="{{ route('psbMembersSave') }}" id="boardForm" class="rounded-2xl border border-line bg-surface">
    @csrf

    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-line px-5 py-4">
        <h2 class="font-display text-lg font-semibold tracking-tight">Board membership</h2>
        <p class="text-ink/55">Rows print in this order; the chairperson prints first.</p>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full min-w-[52rem] text-left" id="memberRows">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-2 py-3 pl-5 font-medium">Printed name</th>
                    <th scope="col" class="w-36 px-2 py-3 font-medium">Credentials</th>
                    <th scope="col" class="w-48 px-2 py-3 font-medium">Role</th>
                    <th scope="col" class="w-64 px-2 py-3 font-medium">Employee record</th>
                    <th scope="col" class="w-20 px-2 py-3 text-center font-medium">Active</th>
                    <th scope="col" class="w-14 px-2 py-3 pr-5"><span class="sr-only">Remove</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $m)
                    @include('psb.partials.member-row', ['i' => $i, 'm' => $m])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-4">
        <button type="button" id="addMember" class="h-10 cursor-pointer rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <i class="fas fa-plus mr-1"></i> Add member
        </button>
        <button type="submit" class="h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">
            <i class="fas fa-save mr-1"></i> Save board
        </button>
    </div>
</form>

{{-- A blank row for the Add member button to copy, its place written __i__. --}}
<template id="memberRowPattern">@include('psb.partials.member-row', ['i' => '__i__', 'm' => null])</template>
@endsection

@push('scripts')
<script>
(function () {
    var body = document.querySelector('#memberRows tbody');
    var pattern = document.getElementById('memberRowPattern').innerHTML;
    // Places are never reused, so two rows cannot post under the same one.
    var next = body.rows.length;

    function addRow() {
        var holder = document.createElement('tbody');
        holder.innerHTML = pattern.replace(/__i__/g, next++);
        var row = holder.rows[0];
        body.appendChild(row);
        row.querySelectorAll('select').forEach(window.hrisSelect);
        return row;
    }

    document.getElementById('addMember').addEventListener('click', function () {
        addRow().querySelector('input[type="text"]').focus();
    });

    // Removing a row and saving deletes that member. The table always keeps
    // one row, so the last one is emptied instead of taken away.
    body.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-remove-member]');
        if (!remove) return;

        remove.closest('tr').remove();
        if (!body.rows.length) { addRow(); }
    });
})();
</script>
@endpush
