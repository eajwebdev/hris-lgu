@extends('layouts.app')

@php
    // Family Background, the second page of an employee's Personal Data
    // Sheet: spouse, children, father and mother. Like Personal Information
    // there is no Save button. Each field goes to
    // FamilybgController::familyBgUpdate on its own when it changes
    // (emp/partials/pds-autosave); the children are the exception, kept as
    // one list and sent whole to FamilybgController::updateChild, which
    // replaces both of their columns with what it is sent.

    $isStaff = $guard == 'web';

    $spouseFields = [
        ['name' => 'spouse_sname', 'label' => 'Surname'],
        ['name' => 'spouse_fname', 'label' => 'First name'],
        ['name' => 'spouse_mname', 'label' => 'Middle name'],
        ['name' => 'spouse_ext', 'label' => 'Name extension', 'placeholder' => 'Jr., Sr., N/A'],
    ];

    $spouseWorkFields = [
        ['name' => 'occupation', 'label' => 'Occupation'],
        ['name' => 'bus_name', 'label' => 'Employer / business name'],
        ['name' => 'bus_address', 'label' => 'Business address'],
        ['name' => 'telephone', 'label' => 'Telephone'],
    ];

    $fatherFields = [
        ['name' => 'father_sname', 'label' => 'Surname'],
        ['name' => 'father_fname', 'label' => 'First name'],
        ['name' => 'father_mname', 'label' => 'Middle name'],
        ['name' => 'father_ext', 'label' => 'Name extension', 'placeholder' => 'Jr., Sr., N/A'],
    ];

    $motherFields = [
        ['name' => 'mother_sname', 'label' => 'Surname'],
        ['name' => 'mother_fname', 'label' => 'First name'],
        ['name' => 'mother_mname', 'label' => 'Middle name'],
    ];

    // The children are two comma-separated columns, names and dates of
    // birth, matched by position. A name with no date beside it is still a
    // child; the page always shows at least one row to type into.
    $names = explode(',', (string) $familyBg->name_child);
    $dates = explode(',', (string) $familyBg->date_birth);
    $children = collect($names)->map(fn ($name, $index) => [trim($name), trim($dates[$index] ?? '')])
        ->reject(fn ($child) => $child[0] === '' && $child[1] === '')
        ->values();
    if ($children->isEmpty()) {
        $children->push(['', '']);
    }

    $card = 'rounded-2xl border border-line bg-surface p-5 sm:p-6';
    $heading = 'font-display text-lg font-semibold tracking-tight';
    $subheading = 'mt-6 border-t border-line pt-5 font-medium';
    $grid = 'mt-4 grid gap-4 @md:grid-cols-2 @2xl:grid-cols-3 @4xl:grid-cols-4';
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Family Background')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Family background', 'autosaves' => true])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <form id="pdsForm" data-save-url="{{ route('familyBgUpdate') }}" data-employee="{{ $empid }}" novalidate autocomplete="off" class="@container space-y-5">
        <section class="{{ $card }}">
            <h2 class="{{ $heading }}">Spouse</h2>

            <div class="{{ $grid }}">
                @foreach($spouseFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec, 'record' => $familyBg])
                @endforeach
            </div>

            <h3 class="{{ $subheading }}">Spouse's work</h3>
            <div class="{{ $grid }}">
                @foreach($spouseWorkFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec, 'record' => $familyBg])
                @endforeach
            </div>
        </section>

        <section class="{{ $card }}" id="children" data-rows data-rows-url="{{ route('update-child') }}" data-rows-what="The children" data-rows-item="child">
            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                <div>
                    <h2 class="{{ $heading }}">Children</h2>
                    <p class="mt-0.5 text-ink/60">The full name of each child, without commas. List all of them.</p>
                </div>
                <button type="button" data-rows-add
                        class="h-10 cursor-pointer rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add a child
                </button>
            </div>

            <div class="mt-4 hidden grid-cols-[minmax(0,1fr)_11rem_auto] gap-4 text-xs font-medium text-ink/60 @md:grid" aria-hidden="true">
                <span>Full name</span>
                <span>Date of birth</span>
                <span class="w-10"></span>
            </div>

            <ol class="mt-4 space-y-4 @md:mt-1 @md:space-y-2" data-rows-list>
                @foreach($children as $child)
                    @include('emp.partials.child-row', ['child' => $child])
                @endforeach
            </ol>

            <template>
                @include('emp.partials.child-row', ['child' => ['', '']])
            </template>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $heading }}">Parents</h2>

            <h3 class="mt-4 font-medium">Father</h3>
            <div class="{{ $grid }}">
                @foreach($fatherFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec, 'record' => $familyBg])
                @endforeach
            </div>

            <h3 class="{{ $subheading }}">Mother's maiden name</h3>
            <div class="{{ $grid }}">
                @foreach($motherFields as $spec)
                    @include('emp.partials.field', ['spec' => $spec, 'record' => $familyBg])
                @endforeach
            </div>
        </section>
    </form>
</div>

@include('emp.partials.pds-autosave')
@endsection

