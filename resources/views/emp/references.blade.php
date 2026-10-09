@extends('layouts.app')

@php
    // References, a page of an employee's Personal Data Sheet: three people,
    // each a name, an address and a telephone number. The three columns
    // (refname, refadd, reftelno) each hold the three answers separated by
    // semicolons, and each answer is saved into its own position as it
    // changes (emp/partials/pds-autosave, to PdsReferencesController::update).

    $isStaff = $guard == 'web';

    $stored = collect(['refname', 'refadd', 'reftelno'])
        ->mapWithKeys(fn ($column) => [$column => explode(';', (string) $references->{$column})]);

    // No semicolons in an answer: they are what separates one from the next.
    $slot = fn (string $column, int $at, string $label) => [
        'name' => $column . '_' . $at, 'label' => $label, 'slot' => $at,
        'value' => trim($stored[$column][$at] ?? ''), 'attrs' => ['data-strip' => ';'],
    ];
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'References')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'References', 'autosaves' => true])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <form id="pdsForm" data-slot-url="{{ route('update.references') }}" data-employee="{{ $empid }}" novalidate autocomplete="off" class="@container">
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
            <h2 class="font-display text-lg font-semibold tracking-tight">References</h2>
            <p class="mt-0.5 text-ink/60">Three people not related to you by consanguinity or affinity.</p>

            <div class="mt-2 divide-y divide-line">
                @foreach(range(0, 2) as $at)
                    <fieldset class="py-4 last:pb-0">
                        <legend class="float-left w-full font-medium">Reference {{ $at + 1 }}</legend>

                        <div class="mt-3 grid clear-both gap-4 @md:grid-cols-2 @4xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,.8fr)]">
                            @include('emp.partials.field', ['spec' => $slot('refname', $at, 'Name')])
                            @include('emp.partials.field', ['spec' => $slot('refadd', $at, 'Address')])
                            @include('emp.partials.field', ['spec' => $slot('reftelno', $at, 'Telephone no.')])
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </section>
    </form>
</div>

@include('emp.partials.pds-autosave')
@endsection
