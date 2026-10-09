@extends('layouts.app')

@php
    // Government Issued ID, a page of an employee's Personal Data Sheet: the
    // one ID named at the foot of the form. Its three answers are kept in a
    // single comma-separated column, govid, and each is saved into its own
    // position as it changes (emp/partials/pds-autosave, to
    // GovIdController::update).

    $isStaff = $guard == 'web';
    $stored = explode(',', (string) $govids->govid);

    // No commas in an answer: they are what separates one from the next.
    $fields = [
        ['label' => 'Government issued ID', 'placeholder' => 'Passport, GSIS, SSS, PRC, driver\'s licence'],
        ['label' => 'ID / licence / passport no.', 'placeholder' => 'N/A'],
        ['label' => 'Date / place of issuance', 'placeholder' => 'N/A'],
    ];
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Government Issued ID')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Government issued ID', 'autosaves' => true])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <form id="pdsForm" data-slot-url="{{ route('update.govids') }}" data-employee="{{ $empid }}" novalidate autocomplete="off" class="@container">
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
            <h2 class="font-display text-lg font-semibold tracking-tight">Government issued ID</h2>
            <p class="mt-0.5 text-ink/60">One ID, with its number and when or where it was issued. No commas in the answers.</p>

            <div class="mt-4 grid gap-4 @md:grid-cols-2 @4xl:grid-cols-3">
                @foreach($fields as $at => $spec)
                    @include('emp.partials.field', ['spec' => $spec + [
                        'name' => 'govid_' . $at, 'slot' => $at, 'value' => trim($stored[$at] ?? ''), 'attrs' => ['data-strip' => ','],
                    ]])
                @endforeach
            </div>
        </section>
    </form>
</div>

@include('emp.partials.pds-autosave')
@endsection
