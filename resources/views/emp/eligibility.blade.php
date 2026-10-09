@extends('layouts.app')

@php
    // Eligibility, a page of an employee's Personal Data Sheet: civil service
    // and board eligibilities, licences, each with its certificate as a PDF.
    // An entry is submitted, then reviewed by HR, who approve it or cancel it
    // with remarks. The page is drawn by emp/partials/pds-records; this file
    // only says what an eligibility is.

    $isStaff = $guard == 'web';
    $editing = $eligibilityedit ?? null;
    $day = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('M j, Y') : null;

    $records = [
        'noun' => 'eligibility',
        'article' => 'an',
        'editing' => $editing,
        'create' => route('eligibilityCreate'),
        'update' => $editing ? route('eligibilityUpdate', $editing->id) : null,
        'cancelUrl' => route('eliCancel'),
        'listUrl' => $isStaff ? route('eligibility', $employee->id) : route('eligibility'),

        'fields' => [
            ['name' => 'careereligible', 'label' => 'Eligibility', 'span' => 2, 'required' => true,
                'placeholder' => 'Career service, RA 1080 (board / bar), CES, CSEE, barangay, driver\'s licence',
                'hint' => 'Career service, RA 1080 (board / bar) under special laws, CES, CSEE, barangay eligibility or driver\'s licence.'],
            ['name' => 'rating', 'label' => 'Rating (if applicable)', 'type' => 'number', 'attrs' => ['step' => '0.01', 'min' => '0']],
            ['name' => 'date_exam', 'label' => 'Date of examination / conferment', 'type' => 'date', 'required' => true],
            ['name' => 'place_exam', 'label' => 'Place of examination / conferment', 'span' => 2, 'required' => true],
            ['name' => 'number', 'label' => 'Licence number', 'type' => 'number'],
            ['name' => 'date_valid', 'label' => 'Valid until', 'type' => 'date'],
            // The certificate is asked for once; an edit keeps it unless
            // another is chosen.
            ['name' => 'attachment', 'label' => 'Attachment (PDF)', 'type' => 'file', 'span' => 2, 'required' => !$editing,
                'attrs' => ['accept' => 'application/pdf']],
        ],

        // HR may do anything to an entry. The employee may change or remove
        // their own whatever its status: the old page meant to stop that
        // once it was reviewed, but compared the status, which is stored as
        // text, with a number, so it never did.
        'entries' => $eligibility->map(fn ($eli) => [
            'id' => $eli->id,
            'title' => $eli->careereligible ?: 'Eligibility',
            'subtitle' => null,
            'facts' => [
                'Rating' => $eli->rating,
                'Date of examination / conferment' => $day($eli->date_exam),
                'Place of examination / conferment' => $eli->place_exam,
                'Licence number' => $eli->number,
                'Valid until' => $day($eli->date_valid),
            ],
            'status' => $eli->status,
            'remarks' => $eli->remarks,
            'attachment' => $eli->attachment ? asset('storage/' . $eli->attachment) : null,
            'edit' => route('eligibilityEdit', ['id' => $empid, 'eid' => $eli->id]),
            'approve' => $isStaff && $eli->status != 1 ? route('eliApprove', $eli->id) : null,
            'cancel' => $isStaff && $eli->status == 0,
            'delete' => route('eliDelete', $eli->id),
        ])->all(),
    ];
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Eligibility')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Eligibility'])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')
    @include('emp.partials.pds-records')
</div>
@endsection
