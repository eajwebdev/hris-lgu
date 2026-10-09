@extends('layouts.app')

@php
    // Voluntary Work, a page of an employee's Personal Data Sheet:
    // involvement in civic, non-government, people's and voluntary
    // organisations. An entry is submitted, then reviewed by HR, who approve
    // it or cancel it with remarks. The page is drawn by
    // emp/partials/pds-records; this file only says what an entry is.

    $isStaff = $guard == 'web';
    $editing = $voluntaryworksedit ?? null;
    $day = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('M j, Y') : null;

    $records = [
        'noun' => 'voluntary work',
        'article' => '',
        'editing' => $editing,
        'create' => route('voluntaryworksCreate'),
        'update' => $editing ? route('voluntaryworksUpdate', $editing->id) : null,
        'cancelUrl' => route('voluntaryworksCancel'),
        'listUrl' => $isStaff ? route('voluntary-work', $employee->id) : route('voluntary-work'),

        'fields' => [
            ['name' => 'org_name', 'label' => 'Name and address of organization', 'span' => 2, 'required' => true, 'placeholder' => 'Write in full'],
            ['name' => 'position', 'label' => 'Position / nature of work', 'span' => 2, 'required' => true],
            ['name' => 'inc_date1', 'label' => 'From', 'type' => 'date', 'required' => true],
            ['name' => 'inc_date2', 'label' => 'To', 'type' => 'date', 'required' => true],
            ['name' => 'num_hours', 'label' => 'Number of hours', 'type' => 'number', 'required' => true],
            ['name' => 'attachment', 'label' => 'Attachment (PDF)', 'type' => 'file', 'span' => 2, 'attrs' => ['accept' => 'application/pdf']],
        ],

        // The employee may change or remove their own entry only while HR
        // has yet to review it.
        'entries' => $voluntaryworks->map(fn ($vwork) => [
            'id' => $vwork->id,
            'title' => $vwork->org_name,
            'subtitle' => $vwork->position,
            'facts' => [
                'Inclusive dates' => $day($vwork->inc_date1) . ' to ' . $day($vwork->inc_date2),
                'Number of hours' => filled($vwork->num_hours) ? number_format((float) $vwork->num_hours) . ' hours' : null,
            ],
            'status' => $vwork->status,
            'remarks' => $vwork->remarks,
            'attachment' => $vwork->attachment ? asset('storage/' . $vwork->attachment) : null,
            'edit' => $isStaff || $vwork->status == 0 ? route('voluntaryworksEdit', ['id' => $empid, 'eid' => $vwork->id]) : null,
            'approve' => $isStaff && $vwork->status != 1 ? route('voluntaryworksApprove', $vwork->id) : null,
            'cancel' => $isStaff && $vwork->status == 0,
            'delete' => $isStaff || $vwork->status == 0 ? route('voluntaryworkDelete', $vwork->id) : null,
        ])->all(),
    ];
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Voluntary Work')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Voluntary work'])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')
    @include('emp.partials.pds-records')
</div>
@endsection
