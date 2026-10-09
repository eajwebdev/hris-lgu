@extends('layouts.app')

@php
    // Educational Background, the third page of an employee's Personal Data
    // Sheet. No Save button (emp/partials/pds-autosave): elementary,
    // secondary and vocational are one set of columns each, saved a field at
    // a time by EducBgController::educBgUpdate. College and graduate studies
    // can be several, so each of their columns holds a comma-separated list;
    // those are sent whole, to educBgUpdateArray and
    // educBgUpdateGraduateArray.

    $isStaff = $guard == 'web';

    // The printed sheet splits the period on its hyphen into "from" and "to".
    $period = ['placeholder' => '2021-2024', 'data' => ['period' => '']];

    $levels = [
        'Elementary' => [
            ['name' => 'elem_school', 'label' => 'Name of school (write in full)', 'span' => 2],
            ['name' => 'elem_period', 'label' => 'Period of attendance'] + $period,
            ['name' => 'elem_level', 'label' => 'Highest level / units earned', 'placeholder' => 'If not graduated'],
            ['name' => 'elem_grad', 'label' => 'Year graduated', 'type' => 'number'],
            ['name' => 'elem_honor', 'label' => 'Scholarship / academic honors'],
        ],
        'Secondary' => [
            ['name' => 'sec_school', 'label' => 'Name of school (write in full)', 'span' => 2],
            ['name' => 'sec_period', 'label' => 'Period of attendance'] + $period,
            ['name' => 'sec_level', 'label' => 'Highest level / units earned', 'placeholder' => 'If not graduated'],
            ['name' => 'sec_grad', 'label' => 'Year graduated', 'type' => 'number'],
            ['name' => 'sec_honor', 'label' => 'Scholarship / academic honors'],
        ],
        'Vocational / trade course' => [
            ['name' => 'voc_school', 'label' => 'Name of school (write in full)', 'span' => 2],
            ['name' => 'voc_course', 'label' => 'Course (write in full)', 'span' => 2],
            ['name' => 'voc_period', 'label' => 'Period of attendance'] + $period,
            ['name' => 'voc_level', 'label' => 'Highest level / units earned', 'placeholder' => 'If not graduated'],
            ['name' => 'voc_grad', 'label' => 'Year graduated', 'type' => 'number'],
            ['name' => 'voc_honor', 'label' => 'Scholarship / academic honors'],
        ],
    ];

    // Turns the six comma-separated columns of one level into rows, leaving
    // out any that are empty all the way across; always at least one, to
    // type into.
    $entries = function (string $prefix) use ($educBg) {
        $columns = collect(['school', 'course', 'period', 'level', 'grad', 'honor'])
            ->map(fn ($column) => array_map('trim', explode(',', (string) $educBg->{$prefix . $column})));

        $rows = collect(range(0, $columns->max(fn ($column) => count($column)) - 1))
            ->map(fn ($index) => $columns->map(fn ($column) => $column[$index] ?? '')->all())
            ->filter(fn ($row) => implode('', $row) !== '')
            ->values();

        return $rows->isEmpty() ? collect([array_fill(0, 6, '')]) : $rows;
    };

    // What each list is called, where it goes, and what its six answers are
    // posted as (the two endpoints name them differently).
    $lists = [
        'College' => [
            'rows' => $entries('coll_'), 'url' => route('educBgUpdateArray'), 'add' => 'Add a degree',
            'keys' => ['schools', 'degrees', 'periods', 'levels', 'years', 'honors'],
        ],
        'Graduate studies' => [
            'rows' => $entries('grad_'), 'url' => route('educBgUpdateGraduateArray'), 'add' => 'Add a course',
            'keys' => ['grad_schools', 'grad_courses', 'grad_periods', 'grad_levels', 'grad_years', 'grad_honors'],
        ],
    ];

    $card = 'rounded-2xl border border-line bg-surface p-5 sm:p-6';
    $heading = 'font-display text-lg font-semibold tracking-tight';
    $grid = 'mt-4 grid gap-4 @md:grid-cols-2 @4xl:grid-cols-4';
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Educational Background')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Educational background', 'autosaves' => true])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <form id="pdsForm" data-save-url="{{ route('educBgUpdate') }}" data-employee="{{ $empid }}" novalidate autocomplete="off" class="@container space-y-5">
        @foreach($levels as $level => $fields)
            <section class="{{ $card }}">
                <h2 class="{{ $heading }}">{{ $level }}</h2>

                <div class="{{ $grid }}">
                    @foreach($fields as $spec)
                        @include('emp.partials.field', ['spec' => $spec, 'record' => $educBg])
                    @endforeach
                </div>
            </section>
        @endforeach

        @foreach($lists as $level => $list)
            <section class="{{ $card }}" data-rows data-rows-url="{{ $list['url'] }}" data-rows-what="{{ $level }}" data-rows-item="entry">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
                    <div>
                        <h2 class="{{ $heading }}">{{ $level }}</h2>
                        <p class="mt-0.5 text-ink/60">One entry for each. Leave commas out of the answers.</p>
                    </div>
                    <button type="button" data-rows-add
                            class="h-10 cursor-pointer rounded-xl border border-line px-4 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                        <i class="fas fa-plus mr-1 text-xs"></i> {{ $list['add'] }}
                    </button>
                </div>

                <ol class="mt-4 space-y-3" data-rows-list>
                    @foreach($list['rows'] as $entry)
                        @include('emp.partials.educ-row', ['entry' => $entry, 'keys' => $list['keys'], 'item' => $level])
                    @endforeach
                </ol>

                <template>
                    @include('emp.partials.educ-row', ['entry' => array_fill(0, 6, ''), 'keys' => $list['keys'], 'item' => $level])
                </template>
            </section>
        @endforeach
    </form>
</div>

@include('emp.partials.pds-autosave')
@endsection

@push('scripts')
<script>
    // A period of attendance is two years and the hyphen between them.
    document.getElementById('pdsForm').addEventListener('input', function (event) {
        if (event.target.matches('[data-period]')) { event.target.value = event.target.value.replace(/[^0-9-]/g, ''); }
    });
</script>
@endpush
