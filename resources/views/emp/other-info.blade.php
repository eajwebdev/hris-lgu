@extends('layouts.app')

@php
    // Other Information, a page of an employee's Personal Data Sheet: special
    // skills and hobbies, non-academic distinctions, and memberships. Three
    // lists side by side, as on the form. Each is one comma-separated
    // column, and the three are sent together whenever any of it changes
    // (emp/partials/pds-autosave, to OtherInfoController::updateChild).

    $isStaff = $guard == 'web';

    $columns = collect(['skills_hob', 'recognition', 'mem_org'])
        ->map(fn ($column) => array_map('trim', explode(',', (string) $otherinfo->{$column})));

    // Rows empty all the way across are left out; always one to type into.
    $rows = collect(range(0, $columns->max(fn ($column) => count($column)) - 1))
        ->map(fn ($index) => $columns->map(fn ($column) => $column[$index] ?? '')->all())
        ->filter(fn ($row) => implode('', $row) !== '')
        ->values();
    if ($rows->isEmpty()) {
        $rows->push(['', '', '']);
    }
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Other Information')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Other information', 'autosaves' => true])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <form id="pdsForm" data-employee="{{ $empid }}" novalidate autocomplete="off" class="@container">
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6"
                 data-rows data-rows-url="{{ route('update-child-oi') }}" data-rows-what="Other information" data-rows-item="row">
            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                <div class="max-w-2xl">
                    <h2 class="font-display text-lg font-semibold tracking-tight">Other information</h2>
                    <p class="mt-0.5 text-ink/60">Three separate lists. Write each answer in full, without commas; what shares a row need not belong together.</p>
                </div>
                <button type="button" data-rows-add
                        class="h-10 cursor-pointer rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add a row
                </button>
            </div>

            <div class="mt-5 hidden grid-cols-[repeat(3,minmax(0,1fr))_auto] gap-4 text-xs font-medium text-ink/60 @2xl:grid" aria-hidden="true">
                <span>Special skills and hobbies</span>
                <span>Non-academic distinctions / recognition</span>
                <span>Membership in association / organization</span>
                <span class="w-10"></span>
            </div>

            <ol class="mt-4 space-y-5 @2xl:mt-1 @2xl:space-y-2" data-rows-list>
                @foreach($rows as $entry)
                    @include('emp.partials.other-info-row', ['entry' => $entry])
                @endforeach
            </ol>

            <template>
                @include('emp.partials.other-info-row', ['entry' => ['', '', '']])
            </template>
        </section>
    </form>
</div>

@include('emp.partials.pds-autosave')
@endsection
