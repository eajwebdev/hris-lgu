@extends('layouts.app')

@php
    // Interview assessments, one per vacancy being interviewed for. Managing
    // one (casting candidates, the panel's rating forms, the ranking screen)
    // is still on the old shell: interview/show, rate, consolidated-screen.

    $field = 'rounded-xl border border-line bg-paper text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-xs font-medium text-ink/60';
    $rowAction = 'grid size-9 cursor-pointer place-items-center rounded-lg text-ink/55 transition-colors focus-visible:outline-2 focus-visible:outline-sun-500';
    $primary = 'h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500';
    $secondary = 'h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';

    // Ranking and the summary PDF are for the two administrator roles.
    $seesResults = auth()->guard('web')->check()
        && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true);
@endphp

@section('hero')
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Interview Assessment</h1>
            <p class="mt-1 max-w-2xl text-cream/70">Panel interviews for a vacancy: who sits on the panel, which candidate is up, and how many ratings are in.</p>
        </div>

        <button type="button" data-dialog-open="interviewDialog"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-cream bg-cream px-4 font-medium text-forest-900 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <i class="fas fa-plus"></i> Add interview
        </button>
    </div>
@endsection

@section('body')
<section class="rounded-2xl border border-line bg-surface" id="interviewList" data-list>
    <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
        <label class="relative w-full sm:w-auto sm:max-w-xs sm:flex-1">
            <span class="sr-only">Search interviews</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-ink/40"><i class="fas fa-magnifying-glass text-xs"></i></span>
            <input type="search" data-list-search placeholder="Search position, panel, candidate" autocomplete="off" class="{{ $field }} h-10 w-full pr-3 pl-9">
        </label>
        <p class="ml-auto text-ink/55" data-list-count aria-live="polite"></p>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-line text-xs text-ink/55">
                <tr>
                    <th scope="col" class="px-4 py-3 pl-5 font-medium">Position</th>
                    <th scope="col" class="px-4 py-3 font-medium max-md:hidden">Interview date</th>
                    <th scope="col" class="px-4 py-3 font-medium max-lg:hidden">Panel</th>
                    <th scope="col" class="px-4 py-3 font-medium max-sm:hidden">Active candidate</th>
                    <th scope="col" class="px-4 py-3 font-medium max-lg:hidden">Ratings</th>
                    <th scope="col" class="px-4 py-3 pr-5 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach($interviews as $interview)
                    @php
                        $job = $interview->job;
                        $title = $job->title ?? 'N/A';
                        $panel = $interview->panels->map(fn ($p) => trim(($p->employee->lname ?? '') . ', ' . ($p->employee->fname ?? ''), ', '))->filter()->values();
                        $candidate = $interview->activeApplication;
                        $candidateName = $candidate ? trim($candidate->first_name . ' ' . $candidate->last_name) : '';
                        $submitted = $interview->ratings->whereNotNull('submitted_at')->count();
                    @endphp
                    <tr data-row data-search="{{ strtolower($title . ' ' . ($job->plantilla_item_no ?? '') . ' ' . $panel->implode(' ') . ' ' . $candidateName) }}" class="transition-colors hover:bg-paper/70">
                        <td class="px-4 py-3 pl-5">
                            <a href="{{ route('interviewEvaluationShow', $interview->id) }}" class="font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-sun-500">{{ $title }}</a>
                            @if($job && $job->plantilla_item_no)
                                <p class="mt-0.5 text-xs text-ink/55">{{ $job->plantilla_item_no }}</p>
                            @endif
                            {{-- The description the vacancy was advertised from. --}}
                            @if($job && $job->positionDescription)
                                <a href="{{ route('positionDescriptionPrint', $job->position_description_id) }}" target="_blank" title="DBM-CSC Form No. 1"
                                   class="mt-1 inline-block text-xs text-forest-700 underline-offset-2 hover:underline">
                                    Position description{{ $job->positionDescription->bureau_office ? ', ' . $job->positionDescription->bureau_office : '' }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap max-md:hidden">
                            @if($interview->interview_date)
                                <p>{{ $interview->interview_date->format('M d, Y') }}</p>
                                <p class="mt-0.5 text-xs text-ink/55">{{ $interview->interview_date->format('h:i A') }}</p>
                            @else
                                <span class="text-ink/45">Not set</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-lg:hidden">
                            @if($panel->isNotEmpty())
                                <p class="max-w-xs leading-snug">{{ $panel->implode('; ') }}</p>
                            @else
                                <span class="text-ink/45">No panel</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-sm:hidden">
                            @if($candidate)
                                <p class="font-medium">{{ $candidateName }}</p>
                                <p class="mt-0.5 text-xs text-ink/55">{{ $candidate->app_number }}</p>
                            @else
                                <span class="text-ink/45">No cast candidate</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap tabular-nums max-lg:hidden">{{ $submitted }} submitted</td>
                        <td class="px-4 py-3 pr-5">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('interviewEvaluationShow', $interview->id) }}" title="Manage" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                    <i class="fas fa-eye"></i><span class="sr-only">Manage</span>
                                </a>
                                @if($seesResults)
                                    <a href="{{ route('interviewConsolidatedScreen', $interview->id) }}" target="_blank" title="Ranking" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                        <i class="fas fa-ranking-star"></i><span class="sr-only">Ranking</span>
                                    </a>
                                    <a href="{{ route('interviewSummaryRatingPdf', $interview->id) }}" target="_blank" title="Summary Rating of Applicants" class="{{ $rowAction }} hover:bg-forest-100 hover:text-forest-700">
                                        <i class="fas fa-file-pdf"></i><span class="sr-only">Summary rating</span>
                                    </a>
                                @endif
                                <form action="{{ route('interviewEvaluationDelete', $interview->id) }}" method="POST" data-confirm-danger
                                      data-confirm="Delete this interview assessment?"
                                      data-confirm-detail="{{ $title }}, with all of its panel ratings. This cannot be undone."
                                      data-confirm-button="Yes, delete it">
                                    @csrf
                                    <button type="submit" title="Delete" class="{{ $rowAction }} hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-300">
                                        <i class="fas fa-trash"></i><span class="sr-only">Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($interviews->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-sun-100 text-xl text-sun-700"><i class="fas fa-comments"></i></span>
            <p class="mt-4 font-medium">No interview assessments yet.</p>
            <p class="mt-1 text-ink/55">One is set up for a vacancy once it has applicants marked Qualified / Ready for Interview.</p>
        </div>
    @else
        <p class="px-5 py-10 text-center text-ink/55" data-list-empty hidden>No interview matches that.</p>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
            <label class="flex items-center gap-2 text-ink/55">
                Rows
                <select data-list-size class="{{ $field }} h-9 pr-7 pl-3">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="0">All</option>
                </select>
            </label>
            <nav aria-label="Pages" class="flex items-center gap-1" data-list-pager></nav>
        </div>
    @endif
</section>

{{-- New interview assessment. --}}
<dialog id="interviewDialog" aria-labelledby="interviewDialogTitle"
        class="m-auto max-h-[calc(100dvh-2rem)] w-[min(38rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60">
    <form action="{{ route('interviewEvaluationStore') }}" method="POST" class="p-6" id="interviewForm">
        @csrf

        <div class="flex items-start justify-between gap-4">
            <h2 class="font-display text-xl font-semibold tracking-tight" id="interviewDialogTitle">New interview assessment</h2>
            <button type="button" data-dialog-close aria-label="Close" class="-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_14rem]">
            <div>
                <label for="interviewJob" class="{{ $label }}">Position</label>
                <select id="interviewJob" name="jid" required class="{{ $field }} mt-1 block h-10 w-full pr-8 pl-3">
                    <option value="" disabled selected>{{ $jobs->isEmpty() ? 'No vacancy is ready' : 'Select the vacancy' }}</option>
                    @foreach($jobs as $job)
                        <option value="{{ $job->id }}">{{ $job->title }}{{ $job->plantilla_item_no ? ' — ' . $job->plantilla_item_no : '' }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink/55">Only positions with applicants marked Qualified / Ready for Interview appear here.</p>
            </div>
            <div>
                <label for="interviewDate" class="{{ $label }}">Interview date</label>
                <input type="datetime-local" id="interviewDate" name="interview_date" value="{{ now()->format('Y-m-d\TH:i') }}" class="{{ $field }} mt-1 block h-10 w-full px-3">
            </div>
        </div>

        <div class="mt-5">
            @include('partials.checklist', [
                'label' => 'Interview panel',
                'name' => 'panels[]',
                'options' => $employees->map(fn ($employee) => [$employee->id, trim($employee->lname . ', ' . $employee->fname . ' ' . $employee->mname)])->all(),
                'hint' => 'Each one gets a rating form when a candidate is cast.',
                'required' => 'Choose at least one panel member.',
            ])
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-dialog-close class="{{ $secondary }}">Cancel</button>
            <button type="submit" class="{{ $primary }}"><i class="fas fa-save mr-1"></i> Save interview</button>
        </div>
    </form>
</dialog>
@endsection
