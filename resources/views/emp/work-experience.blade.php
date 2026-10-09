@extends('layouts.app')

@php
    // Work Experience, a page of an employee's Personal Data Sheet. An entry
    // is submitted, then reviewed by HR, who approve it or cancel it with
    // remarks. The page is drawn by emp/partials/pds-records; this file only
    // says what a work experience is.
    //
    // The last two fields are not on the sheet itself but on its attachment,
    // the Work Experience Sheet (emp/gen-pds-attachment).

    $isStaff = $guard == 'web';
    $editing = $workexperienceedit ?? null;
    $day = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('M j, Y') : null;

    $records = [
        'noun' => 'work experience',
        'article' => '',
        'editing' => $editing,
        'create' => route('workexperienceCreate'),
        'update' => $editing ? route('workexperienceUpdate', $editing->id) : null,
        'cancelUrl' => route('workexperienceCancel'),
        'listUrl' => $isStaff ? route('work-experience', $employee->id) : route('work-experience'),

        'fields' => [
            ['name' => 'position', 'label' => 'Position title', 'span' => 2, 'required' => true, 'placeholder' => 'Write in full, do not abbreviate'],
            ['name' => 'department', 'label' => 'Department / agency / office / company', 'span' => 2, 'required' => true, 'placeholder' => 'Write in full, do not abbreviate'],
            ['name' => 'inc_date1', 'label' => 'From', 'type' => 'date', 'required' => true],
            ['name' => 'inc_date2', 'label' => 'To', 'type' => 'date', 'hint' => 'Leave empty if this is the present job.'],
            ['name' => 'stat_app', 'label' => 'Status of appointment'],
            ['name' => 'service', 'label' => 'Government service', 'type' => 'select', 'prompt' => 'Select', 'required' => true,
                'options' => ['Y' => 'Yes', 'N' => 'No']],
            ['name' => 'sg_grade', 'label' => 'Salary / job / pay grade and step', 'placeholder' => '00-0', 'hint' => 'If applicable, as grade and step: 11-2.'],
            ['name' => 'salary', 'label' => 'Monthly salary', 'required' => true, 'attrs' => ['data-thousands' => '', 'inputmode' => 'numeric']],
            ['name' => 'supervisor', 'label' => 'Immediate supervisor', 'span' => 2],
            ['name' => 'list_accom', 'label' => 'Accomplishments and contributions (if any)', 'type' => 'lines', 'count' => 8, 'separator' => ';', 'span' => 2],
            // Kept with <br> between lines by an earlier version of the form.
            ['name' => 'actual_summary', 'label' => 'Summary of actual duties', 'type' => 'textarea', 'rows' => 14, 'span' => 2, 'placeholder' => '',
                'value' => old('actual_summary', str_replace(['<br>', '<br/>', '<br />'], "\n", (string) optional($editing)->actual_summary))],
            ['name' => 'attachment', 'label' => 'Attachment (PDF)', 'type' => 'file', 'span' => 2, 'attrs' => ['accept' => 'application/pdf']],
        ],

        // The employee may always change their own entry, but remove it only
        // while HR has yet to review it.
        'entries' => $workexperience->map(fn ($work) => [
            'id' => $work->id,
            'title' => $work->position,
            'subtitle' => $work->department,
            'facts' => [
                'Inclusive dates' => $day($work->inc_date1) . ' to ' . ($day($work->inc_date2) ?? 'present'),
                'Salary / job / pay grade and step' => $work->sg_grade,
                'Monthly salary' => $work->salary,
                'Status of appointment' => $work->stat_app,
                'Government service' => $work->service == 'Y' ? 'Yes' : 'No',
                'Immediate supervisor' => $work->supervisor,
            ],
            'status' => $work->status,
            'remarks' => $work->remarks,
            'attachment' => $work->attachment ? asset('storage/' . $work->attachment) : null,
            'edit' => route('workexperienceEdit', ['id' => $empid, 'eid' => $work->id]),
            'approve' => $isStaff && $work->status != 1 ? route('expApprove', $work->id) : null,
            'cancel' => $isStaff && $work->status == 0,
            'delete' => $isStaff || $work->status == 0 ? route('workDelete', $work->id) : null,
        ])->all(),
    ];
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Work Experience')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Work experience'])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')
    @include('emp.partials.pds-records')
</div>
@endsection
