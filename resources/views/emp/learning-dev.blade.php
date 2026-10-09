@extends('layouts.app')

@php
    // Learning and Development, a page of an employee's Personal Data Sheet:
    // the trainings and programs attended. An entry is submitted, then
    // reviewed by HR, who approve it or cancel it with remarks. The page is
    // drawn by emp/partials/pds-records; this file only says what an entry is.

    $isStaff = $guard == 'web';
    $editing = $learningdevedit ?? null;
    $day = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('M j, Y') : null;

    $records = [
        'noun' => 'training',
        'article' => 'a',
        'editing' => $editing,
        'create' => route('learningdevCreate'),
        'update' => $editing ? route('learningdevUpdate', $editing->id) : null,
        'cancelUrl' => route('learningdevCancel'),
        'listUrl' => $isStaff ? route('learning-dev', $employee->id) : route('learning-dev'),

        'fields' => [
            ['name' => 'learning_dev', 'label' => 'Title of the learning and development intervention / training program', 'span' => 'full', 'required' => true, 'placeholder' => 'Write in full'],
            ['name' => 'inc_date1', 'label' => 'From', 'type' => 'date', 'required' => true],
            ['name' => 'inc_date2', 'label' => 'To', 'type' => 'date', 'required' => true],
            ['name' => 'num_hours', 'label' => 'Number of hours', 'type' => 'number', 'required' => true],
            ['name' => 'types', 'label' => 'Type of LD', 'required' => true, 'placeholder' => 'Managerial, supervisory, technical'],
            ['name' => 'conducted', 'label' => 'Conducted / sponsored by', 'span' => 2, 'required' => true, 'placeholder' => 'Write in full'],
            ['name' => 'attachment', 'label' => 'Attachment (PDF)', 'type' => 'file', 'span' => 2, 'attrs' => ['accept' => 'application/pdf']],
        ],

        // The employee may change or remove their own entry while HR has yet
        // to review it, and again after HR has canceled it.
        'entries' => $learningdev->map(fn ($learning) => [
            'id' => $learning->id,
            'title' => $learning->learning_dev,
            'subtitle' => $learning->conducted,
            'facts' => [
                'Inclusive dates' => $day($learning->inc_date1) . ' to ' . $day($learning->inc_date2),
                'Number of hours' => filled($learning->num_hours) ? number_format((float) $learning->num_hours) . ' hours' : null,
                'Type of LD' => $learning->types,
            ],
            'status' => $learning->status,
            'remarks' => $learning->remarks,
            'attachment' => $learning->attachment ? asset('storage/' . $learning->attachment) : null,
            'edit' => $isStaff || in_array($learning->status, [0, 2]) ? route('learningdevEdit', ['id' => $empid, 'eid' => $learning->id]) : null,
            'approve' => $isStaff && $learning->status != 1 ? route('learningdevApprove', $learning->id) : null,
            'cancel' => $isStaff && $learning->status == 0,
            'delete' => $isStaff || in_array($learning->status, [0, 2]) ? route('learningdevDelete', $learning->id) : null,
        ])->all(),
    ];
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Learning and Development')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Learning and development'])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')
    @include('emp.partials.pds-records')
</div>
@endsection
