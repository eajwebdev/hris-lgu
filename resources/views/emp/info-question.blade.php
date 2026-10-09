@extends('layouts.app')

@php
    // Other Information Questions, a page of an employee's Personal Data
    // Sheet: the twelve yes/no questions near the end of the form, with the
    // details a yes asks for.
    //
    // The answers are two comma-separated columns, question and qdetails,
    // matched by position. Each answer is saved into its own position as it
    // changes (emp/partials/pds-autosave, to InfoQuestionController::update).
    // Answering no empties that question's details, here and on the server.

    $isStaff = $guard == 'web';

    $answers = explode(',', (string) $infoquestion->question);
    $details = explode(',', (string) $infoquestion->qdetails);

    // Each question is [position, wording, details]. Details are
    // [position in qdetails, label, input type]; most sit at the question's
    // own position, but the date a case was filed was added later, at 12.
    $say = fn (int $at, string $label = 'If yes, give details', string $type = 'text') => [$at, $label, $type];

    $groups = [
        [
            'heading' => 'Relationship to the appointing authority',
            'lead' => 'Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office, or to the person who has immediate supervision over you in the office:',
            'questions' => [
                [0, 'Within the third degree?', []],
                [1, 'Within the fourth degree (for Local Government Unit career employees)?', [$say(1)]],
            ],
        ],
        [
            'heading' => 'Record',
            'lead' => null,
            'questions' => [
                [2, 'Have you ever been found guilty of any administrative offense?', [$say(2)]],
                [3, 'Have you been criminally charged before any court?', [$say(12, 'Date filed', 'date'), $say(3, 'Status of case/s')]],
                [4, 'Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?', [$say(4)]],
                [5, 'Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?', [$say(5)]],
                [6, 'Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?', [$say(6)]],
                [7, 'Have you resigned from the government service during the three (3)-month period before the last election to promote or actively campaign for a national or local candidate?', [$say(7)]],
                [8, 'Have you acquired the status of an immigrant or permanent resident of another country?', [$say(8, 'If yes, give details (country)')]],
            ],
        ],
        [
            'heading' => 'Indigenous people, persons with disability, solo parents',
            'lead' => 'Pursuant to the Indigenous People\'s Act (RA 8371), the Magna Carta for Disabled Persons (RA 7277) and the Solo Parents Welfare Act of 2000 (RA 8972):',
            'questions' => [
                [9, 'Are you a member of any indigenous group?', [$say(9, 'If yes, please specify')]],
                [10, 'Are you a person with disability?', [$say(10, 'If yes, please specify')]],
                [11, 'Are you a solo parent?', [$say(11, 'If yes, please specify')]],
            ],
        ],
    ];

    $choice = 'cursor-pointer rounded-lg px-4 py-1.5 font-medium text-ink/60 transition-colors has-checked:bg-forest-900 has-checked:text-cream has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-sun-500 dark:has-checked:bg-forest-600';
@endphp

@section('breadcrumb', $isStaff ? trim(ucwords(strtolower($employee->fname)) . ' ' . ucwords(strtolower($employee->lname))) : 'Other Information Questions')

@section('hero')
    @include('emp.partials.pds-hero', ['about' => 'Other information questions', 'autosaves' => true])
@endsection

@section('body')
<div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
    @include('emp.partials.pds-side')

    <form id="pdsForm" data-slot-url="{{ route('update.info.question') }}" data-employee="{{ $empid }}" novalidate autocomplete="off" class="@container space-y-5">
        @foreach($groups as $group)
            <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                <h2 class="font-display text-lg font-semibold tracking-tight">{{ $group['heading'] }}</h2>
                @if($group['lead'])
                    <p class="mt-1 max-w-3xl leading-relaxed text-ink/70">{{ $group['lead'] }}</p>
                @endif

                <div class="mt-3 divide-y divide-line">
                    @foreach($group['questions'] as [$at, $wording, $asks])
                        @php
                            $answer = trim($answers[$at] ?? '');
                        @endphp
                        <div class="py-4 last:pb-0" role="group" aria-labelledby="question-{{ $at }}" data-question="{{ $at }}">
                            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3 @2xl:flex-nowrap">
                                <p class="max-w-3xl leading-relaxed" id="question-{{ $at }}">{{ $wording }}</p>

                                <div class="flex shrink-0 gap-1 rounded-xl border border-line bg-paper p-1">
                                    @foreach(['0' => 'No', '1' => 'Yes'] as $value => $said)
                                        <label class="{{ $choice }}">
                                            <input type="radio" name="question_{{ $at }}" value="{{ $value }}" data-save-slot="{{ $at }}" data-save-name="That answer" class="sr-only" @checked($answer === (string) $value)>
                                            {{ $said }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            @if($asks)
                                <div class="mt-3 grid gap-4 @md:grid-cols-[minmax(0,12rem)_minmax(0,1fr)]" data-question-details @if($answer !== '1') hidden @endif>
                                    @foreach($asks as [$slot, $label, $type])
                                        <label class="{{ count($asks) == 1 ? 'col-span-full' : '' }}">
                                            <span class="block text-xs font-medium text-ink/60">{{ $label }}</span>
                                            <input type="{{ $type }}" name="qdetails_{{ $slot }}" value="{{ trim($details[$slot] ?? '') }}" data-save-slot="{{ $slot }}" data-save-name="{{ $label }}" data-strip=","
                                                   class="mt-1 block h-10 w-full rounded-xl border border-line bg-paper px-3 text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15">
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </form>
</div>

@include('emp.partials.pds-autosave')
@endsection

@push('scripts')
<script>
    // A yes opens the question's details and a no shuts and empties them.
    // The server empties the detail stored at the question's own position
    // when it saves a no; one kept anywhere else (the date a case was filed)
    // is emptied from here.
    document.getElementById('pdsForm').addEventListener('change', function (event) {
        var radio = event.target;
        if (radio.type !== 'radio') return;

        var question = radio.closest('[data-question]');
        var details = question.querySelector('[data-question-details]');
        if (!details) return;

        details.hidden = radio.value !== '1';
        if (radio.value === '1') {
            details.querySelector('input').focus();
            return;
        }

        details.querySelectorAll('input').forEach(function (input) {
            if (input.value && input.dataset.saveSlot !== question.dataset.question) {
                pdsSave(this.dataset.slotUrl, { empid: this.dataset.employee, column: input.name, index: input.dataset.saveSlot, value: '' }, 'The details');
            }
            input.value = '';
        }, this);
    });
</script>
@endpush
