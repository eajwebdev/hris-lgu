@extends('layouts.app')

@php
    // Leave applications still on their way: each one drawn as a card by
    // leaves/partials/application, with what the person looking may do to it.
    // HR opens a named employee's; an employee sees their own and, as an
    // office head, OIC or approving official, other people's that come to
    // them. Once an application is approved, disapproved or cancelled it
    // moves to History.

    $isStaff = $guard == 'web';
    $me = auth()->guard($guard)->user();

    $otherBalances = leave_other_balances();
    $leaveTypes = leave_type_names();
    $leavePurposes = [
        1 => 'Within the Philippines',
        2 => 'Abroad',
        3 => 'In Hospital',
        4 => 'Out Patient',
        5 => "Completion of Master's Degree",
        6 => 'BAR/Board Examination Review',
        7 => 'Monetization of Leave Credits',
        8 => 'Terminal Leave',
    ];

    // Other people's applications: for the OIC or head of an office, the
    // ones from that office; for the Mayor and the Vice Mayor, everything
    // waiting for final approval. The employee's own are already above.
    $isApprover = !$isStaff && $setting->isApprovingOfficial($me->id);
    $seesOthers = ($oic->oic_id ?? 0) == $me->id || $isOfficeHead || $isApprover;
    $mine = $leavesapp->pluck('id')->all();
    $others = $seesOthers
        ? collect($leavesapphead)->filter(fn ($leave) => !in_array($leave->id, $mine)
            && ($leave->supervisor_emp_dept == $me->emp_dept || $isApprover))
        : collect();

    $person = ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname));
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Leave</h1>
            <p class="mt-1 text-cream/70">
                @if($isStaff)
                    {{ $person }}'s applications in progress, and whose signature each is waiting on.
                @else
                    Where each application stands, and whose signature it is waiting on.
                @endif
            </p>
        </div>
        @include('leaves.partials.tabs')
    </div>
@endsection

@section('body')
<div class="grid gap-5 lg:grid-cols-[21rem_minmax(0,1fr)]">
    @include('leaves.partials.balances', ['switchRoute' => 'leaveStatus'])

    <div class="min-w-0 space-y-8">
        <section aria-labelledby="ownHeading">
            <h2 id="ownHeading" class="{{ $others->isEmpty() ? 'sr-only' : 'mb-3 font-display text-xl font-semibold tracking-tight' }}">
                {{ $isStaff ? 'Applications in progress' : 'Your applications' }}
            </h2>

            @if($leavesapp->isEmpty())
                <div class="rounded-2xl border border-line bg-surface px-5 py-14 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-forest-100 text-xl text-forest-700"><i class="fas fa-stamp"></i></span>
                    <p class="mt-4 font-medium">Nothing in progress.</p>
                    <p class="mx-auto mt-1 max-w-sm text-ink/55">
                        @if($isStaff)
                            {{ $person }} has no leave application waiting on a signature. Finished ones are under History.
                        @else
                            You have no leave application waiting on a signature. Finished ones are under History.
                        @endif
                    </p>
                    @unless($isStaff)
                        <a href="{{ route('leavesReadEmp') }}" class="mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:ring-1 dark:ring-cream/15">
                            <i class="fas fa-plus text-xs"></i> Apply for leave
                        </a>
                    @endunless
                </div>
            @else
                <div class="space-y-4">
                    @foreach($leavesapp as $leave)
                        @include('leaves.partials.application', ['entry' => ['leave' => $leave, 'own' => true]])
                    @endforeach
                </div>
            @endif
        </section>

        @if($others->isNotEmpty())
            <section aria-labelledby="othersHeading">
                <h2 id="othersHeading" class="font-display text-xl font-semibold tracking-tight">
                    {{ $isApprover ? 'For final approval' : 'From your office' }}
                </h2>
                <p class="mt-0.5 mb-3 text-ink/60">
                    {{ $isApprover ? 'Signed by the HRMO and the supervisor, and waiting on the Mayor or the Vice Mayor.' : 'Applications filed by the people you sign for.' }}
                </p>
                <div class="space-y-4">
                    @foreach($others as $leave)
                        @include('leaves.partials.application', ['entry' => ['leave' => $leave, 'own' => false]])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

@include('leaves.partials.actions', ['withDays' => $isStaff])

@if($isStaff)
    @include('leaves.partials.credit-dialogs')
@endif
@endsection
