{{--
    Public careers portal: the positions open at the Municipal Government,
    the application form, and the application tracker. No account is needed
    for any of it.

    A page of its own (no app shell: nobody is signed in), drawn to match the
    sign-in page it is reached from: the green banner, the landscape from the
    municipal seal, the same two typefaces.

    Everything it sends goes to the public API, unchanged:
      POST /api/application/store            the application, with its PDFs
      GET  /api/application/status/{number}  where an application stands
    Submitting emails the applicant their application number.
--}}
@php
    $openings = $jobs->count();
    $types = $jobs->pluck('type')->filter()->unique()->values();

    // What an applicant must have ready, as the form will ask for it.
    $documents = [
        ['pds', 'Personal Data Sheet (PDS)', true],
        ['wes', 'Work Experience Sheet', true],
        ['intent', 'Letter of intent', true],
        ['resume', 'Resume or CV', true],
        ['tor', 'Transcript of records', true],
        ['coe', 'Certificate of employment', false],
    ];

    $input = 'block h-11 w-full min-w-0 rounded-xl border border-line bg-white px-3.5 text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15';
    $label = 'block text-sm font-medium';
    $primary = 'inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 disabled:cursor-wait disabled:opacity-60';
    $secondary = 'inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-line bg-white px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $onGreen = 'inline-flex h-10 items-center gap-2 rounded-xl border border-cream/25 px-4 text-sm font-medium transition-colors hover:border-cream hover:bg-cream hover:text-forest-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500';
    $card = 'rounded-2xl border border-line bg-white p-5 sm:p-6';
    $dialog = 'm-auto flex-col overflow-hidden rounded-3xl border border-line bg-paper p-0 text-ink shadow-2xl shadow-forest-950/30 backdrop:bg-forest-950/60 open:flex';
    $close = '-mt-1 -mr-2 grid size-10 shrink-0 cursor-pointer place-items-center rounded-xl text-ink/50 transition-colors hover:bg-white hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500';
    $section = 'font-display text-lg font-semibold tracking-tight';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Careers | Municipality of Mabinay</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..700&family=Instrument+Sans:wght@400..600&display=swap">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    @vite('resources/css/app.css')
    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">
</head>
<body class="min-h-screen scroll-smooth bg-paper font-sans text-[15px] text-ink antialiased">

    {{-- Banner. The landscape runs down its right side on a wide screen and
         along its foot on a narrow one. --}}
    <header class="relative isolate m-2.5 overflow-hidden rounded-3xl bg-forest-900 px-6 pt-5 pb-36 text-cream sm:m-3 sm:px-10 sm:pt-8 lg:pb-16">
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                @include('partials.auth-brand')

                <nav aria-label="Careers" class="flex gap-2">
                    <a href="#track" data-focus="trackInput" class="{{ $onGreen }}">Track an application</a>
                    <a href="{{ route('getLogin') }}" class="{{ $onGreen }} max-sm:hidden">Employee sign in</a>
                </nav>
            </div>

            <div class="mt-10 max-w-xl motion-safe:animate-settle lg:mt-16">
                <p class="text-sm font-medium text-cream/70">Careers at the Municipal Government</p>

                <h1 class="mt-3 font-display text-[2.6rem]/[.95] font-semibold tracking-tight sm:text-6xl/[.95]">
                    Serve the people of Mabinay<span class="text-sun-500">.</span>
                </h1>

                <p class="mt-5 max-w-md text-lg/relaxed text-cream/80">
                    See which positions are open, send your application online and follow its progress.
                    No account needed.
                </p>

                @if($openings)
                    <label class="relative mt-7 block max-w-md">
                        <span class="sr-only">Search the open positions</span>
                        <span class="pointer-events-none absolute inset-y-0 left-4 grid place-items-center text-ink/45"><i class="fas fa-magnifying-glass text-sm"></i></span>
                        <input type="search" id="jobSearch" placeholder="Search positions: nurse, engineer, clerk" autocomplete="off"
                               class="h-13 w-full rounded-2xl border border-transparent bg-cream pr-4 pl-11 text-base text-ink outline-none placeholder:text-ink/45 focus:ring-4 focus:ring-sun-500/50">
                    </label>
                @endif
            </div>
        </div>

        @include('partials.auth-scene', ['sceneClass' => 'right-0 bottom-0 h-32 w-full lg:h-full lg:w-[46%] lg:[mask-image:linear-gradient(to_right,transparent,black_38%)]'])
    </header>

    {{-- Lined up with the banner's own text: the banner is inset by its
         margin and then its padding, so what follows takes the two together. --}}
    <div class="px-[2.125rem] sm:px-[3.25rem]">
    <main class="mx-auto grid max-w-6xl items-start gap-x-10 gap-y-12 py-10 lg:grid-cols-[minmax(0,1fr)_21rem] lg:py-14">

        {{-- The openings, soonest to close first --}}
        <section aria-labelledby="openingsTitle">
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-3">
                <h2 class="font-display text-3xl font-semibold tracking-tight" id="openingsTitle">Open positions</h2>
                <p class="text-ink/60" id="jobCount" aria-live="polite">{{ $openings }} {{ Str::plural('opening', $openings) }}</p>
            </div>

            {{-- Only worth offering when there is more than one kind. --}}
            @if($types->count() > 1)
                <div class="mt-4 flex flex-wrap gap-1.5" role="group" aria-label="Nature of appointment">
                    @foreach($types->prepend('') as $type)
                        <button type="button" data-type-filter="{{ $type }}" aria-pressed="{{ $type === '' ? 'true' : 'false' }}"
                                class="h-9 cursor-pointer rounded-full border border-line bg-white px-4 text-sm font-medium text-ink/70 transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 aria-pressed:border-forest-900 aria-pressed:bg-forest-900 aria-pressed:text-cream">
                            {{ $type === '' ? 'All' : $type }}
                        </button>
                    @endforeach
                </div>
            @endif

            @if($jobs->isEmpty())
                <div class="mt-6 rounded-2xl border border-dashed border-ink/20 px-6 py-16 text-center">
                    <img src="{{ asset('Uploads/logo.png') }}" alt="" class="mx-auto size-16 opacity-40 grayscale">
                    <p class="mt-5 font-display text-xl font-semibold tracking-tight">No openings right now</p>
                    <p class="mx-auto mt-1 max-w-sm text-ink/60">New positions are posted here as soon as they open. If you have already applied, you can still track your application.</p>
                </div>
            @else
                <ol class="mt-6 space-y-3" id="jobList">
                    @foreach($jobs as $job)
                        @php
                            $closes = \Carbon\Carbon::parse($job->expiration_at);
                            $daysLeft = (int) now()->startOfDay()->diffInDays($closes->copy()->startOfDay(), false);
                            $soon = $daysLeft <= 7;
                            $salary = number_format((float) $job->salary, 2);

                            // What the details dialog shows; read by the script.
                            $payload = [
                                'id' => $job->id, 'type' => $job->type, 'title' => $job->title, 'item' => $job->plantilla_item_no,
                                'salary' => $salary, 'assignment' => $job->assignment, 'education' => $job->education,
                                'eligibility' => $job->eligibility, 'training' => $job->training, 'experience' => $job->experience,
                                'competency' => $job->competency, 'deadline' => $closes->format('F j, Y'),
                            ];
                        @endphp
                        <li data-job="{{ json_encode($payload) }}" data-type="{{ $job->type }}"
                            data-haystack="{{ Str::lower($job->title . ' ' . $job->type . ' ' . $job->assignment . ' ' . $job->plantilla_item_no) }}"
                            class="grid gap-x-5 gap-y-4 rounded-2xl border border-line bg-white p-5 transition-shadow hover:shadow-lg hover:shadow-forest-950/5 sm:grid-cols-[4.25rem_minmax(0,1fr)_auto] sm:items-center sm:p-6">

                            {{-- The closing date, as the leaf of a desk calendar.
                                 It turns orange in the last week. --}}
                            <div class="w-[4.25rem] overflow-hidden rounded-xl border text-center max-sm:hidden {{ $soon ? 'border-sun-500/50' : 'border-line' }}" aria-hidden="true">
                                <p class="py-1 text-xs font-medium {{ $soon ? 'bg-sun-500 text-forest-950' : 'bg-forest-900 text-cream' }}">{{ $closes->format('M') }}</p>
                                <p class="py-1.5 font-display text-2xl leading-none font-semibold tabular-nums">{{ $closes->format('j') }}</p>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-medium text-forest-700">{{ $job->type }}</p>
                                <h3 class="mt-0.5 font-display text-xl leading-snug font-semibold tracking-tight">{{ $job->title }}</h3>

                                <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-ink/60">
                                    @if($job->assignment)
                                        <span><i class="fas fa-location-dot mr-1 text-xs text-ink/40"></i>{{ $job->assignment }}</span>
                                    @endif
                                    @if($job->plantilla_item_no)
                                        <span>Item no. {{ $job->plantilla_item_no }}</span>
                                    @endif
                                </p>

                                <p class="mt-3 flex flex-wrap items-baseline gap-x-4 gap-y-1">
                                    <span><strong class="font-semibold tabular-nums">&#8369;{{ $salary }}</strong> <span class="text-sm text-ink/55">a month</span></span>
                                    <span class="text-sm {{ $soon ? 'font-medium text-sun-700' : 'text-ink/60' }}">
                                        Apply by {{ $closes->format('F j, Y') }}@if($soon), {{ $daysLeft <= 0 ? 'last day today' : $daysLeft . ' ' . Str::plural('day', $daysLeft) . ' left' }}@endif
                                    </span>
                                </p>
                            </div>

                            <button type="button" data-view-job class="{{ $primary }} group">
                                View and apply
                                <svg class="size-3.5 fill-current transition-transform group-hover:translate-x-0.5" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.5 2.5 14 8l-5.5 5.5-1.1-1.1 3.6-3.6H2V7.2h9L7.4 3.6z"/></svg>
                            </button>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-6 rounded-2xl border border-dashed border-ink/20 px-6 py-12 text-center text-ink/60" id="noResults" hidden>No open position matches that.</p>
            @endif
        </section>

        <aside class="space-y-5 lg:sticky lg:top-6">
            {{-- Tracking, in the page: it is the other thing people come for. --}}
            <section class="{{ $card }} scroll-mt-6" id="track" aria-labelledby="trackTitle">
                <h2 class="{{ $section }}" id="trackTitle">Track an application</h2>
                <p class="mt-1 text-sm text-ink/60">Enter the application number from your confirmation email.</p>

                <form id="trackForm" class="mt-4 flex gap-2">
                    <label for="trackInput" class="sr-only">Application number</label>
                    <input id="trackInput" placeholder="APP-{{ date('Y') }}-0000A" autocomplete="off" required class="{{ $input }} font-medium uppercase tabular-nums placeholder:normal-case">
                    <button type="submit" id="trackButton" class="{{ $primary }} px-4">Track</button>
                </form>

                <p class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800" id="trackError" role="alert" hidden></p>

                <div class="mt-4 border-t border-line pt-4" id="trackResult" hidden>
                    <p class="font-display text-lg leading-snug font-semibold tracking-tight" data-track="name"></p>
                    <p class="mt-0.5 text-sm text-ink/60" data-track="position"></p>
                    <p class="text-sm text-ink/60" data-track="date"></p>
                    <p class="mt-3 inline-block rounded-full px-3.5 py-1.5 text-sm font-medium data-[tone=bad]:bg-red-50 data-[tone=bad]:text-red-800 data-[tone=ok]:bg-forest-100 data-[tone=ok]:text-forest-800 data-[tone=warn]:bg-sun-100 data-[tone=warn]:text-sun-700" data-track="status"></p>
                    <p class="mt-3 rounded-xl bg-paper px-4 py-3 text-sm leading-relaxed" data-track="note" hidden></p>
                </div>
            </section>

            <section class="{{ $card }}" aria-labelledby="prepareTitle">
                <h2 class="{{ $section }}" id="prepareTitle">What to prepare</h2>
                <p class="mt-1 text-sm text-ink/60">Each as a PDF of 20 MB or less.</p>

                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach($documents as [, $document, $needed])
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full text-[10px] {{ $needed ? 'bg-forest-100 text-forest-700' : 'border border-line text-ink/35' }}"><i class="fas fa-check"></i></span>
                            <span>{{ $document }}@unless($needed) <span class="text-ink/50">(if you have one)</span>@endunless</span>
                        </li>
                    @endforeach
                    <li class="flex gap-3">
                        <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border border-line text-[10px] text-ink/35"><i class="fas fa-check"></i></span>
                        <span>Training certificates <span class="text-ink/50">(if you have any)</span></span>
                    </li>
                </ul>

                <p class="mt-4 border-t border-line pt-4 text-sm leading-relaxed text-ink/60">
                    Already working at the LGU? Have your employee ID number ready; your present position is read from your 201 file.
                </p>
            </section>
        </aside>
    </main>

    <footer class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-line py-6 text-sm text-ink/55">
        <p>&copy; {{ date('Y') }} Municipality of Mabinay, Negros Oriental. Human Resource Management Office.</p>
        <a href="{{ route('getLogin') }}" class="font-medium text-forest-700 underline-offset-2 hover:underline">Employee sign in</a>
    </footer>
    </div>

    {{-- ---------------------------------------------------- one position --}}
    <dialog id="jobDialog" aria-labelledby="jobTitle" class="{{ $dialog }} max-h-[calc(100dvh-1.5rem)] w-[min(42rem,calc(100vw-1.5rem))]">
        <div class="flex items-start justify-between gap-4 bg-forest-900 px-6 py-5 text-cream sm:px-8">
            <div class="min-w-0">
                <p class="text-sm text-cream/70" data-job-field="type"></p>
                <h2 class="mt-0.5 font-display text-2xl leading-tight font-semibold tracking-tight" id="jobTitle"></h2>
                <p class="mt-1 text-sm text-cream/70" data-job-field="sub"></p>
            </div>
            <button type="button" data-close aria-label="Close" class="-mt-1 -mr-2 grid size-10 shrink-0 cursor-pointer place-items-center rounded-xl text-cream/70 transition-colors hover:bg-cream/12 hover:text-cream focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-xmark"></i></button>
        </div>

        {{-- Filled by the script, as text, from the position's data. --}}
        <dl class="min-h-0 flex-1 divide-y divide-line overflow-y-auto px-6 sm:px-8" id="jobSpec"></dl>

        <div class="flex flex-wrap justify-end gap-2 border-t border-line bg-white px-6 py-4 sm:px-8">
            <button type="button" data-close class="{{ $secondary }}">Close</button>
            <button type="button" id="jobApply" class="{{ $primary }}">Apply for this position</button>
        </div>
    </dialog>

    {{-- ------------------------------------------------------ application --}}
    <dialog id="applyDialog" aria-labelledby="applyTitle" class="{{ $dialog }} h-[calc(100dvh-1.5rem)] w-[min(50rem,calc(100vw-1.5rem))]">
        <div class="flex items-start justify-between gap-4 border-b border-line bg-white px-6 py-5 sm:px-8">
            <div class="min-w-0">
                <p class="text-sm text-ink/60">Application for</p>
                <h2 class="font-display text-2xl leading-tight font-semibold tracking-tight" id="applyTitle"></h2>
            </div>
            <button type="button" data-apply-leave aria-label="Close" class="{{ $close }}"><i class="fas fa-xmark"></i></button>
        </div>

        <form id="applyForm" novalidate class="min-h-0 flex-1 space-y-8 overflow-y-auto px-6 py-6 sm:px-8">
            <input type="hidden" name="jid">

            <p class="rounded-xl bg-red-50 px-4 py-3 text-red-800" id="applyError" role="alert" hidden></p>

            {{-- Employment status with the LGU.

                 The Comparative Assessment the selection board signs asks for each
                 candidate's present position, salary grade and status, and gives a
                 performance rating 35 of its 100 points. Those apply only to someone
                 already in the service. Rather than ask an applicant to type facts
                 about their own appointment, an internal applicant gives their
                 Employee ID and HR reads the rest from the 201 file. --}}
            <fieldset>
                <legend class="{{ $section }}">Are you currently employed at LGU Mabinay?</legend>

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach(['0' => 'No, I am applying from outside the LGU', '1' => 'Yes, I am a current employee'] as $value => $answer)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-white p-3.5 transition-colors has-checked:border-forest-600 has-checked:bg-forest-100/60">
                            <input type="radio" name="is_internal" value="{{ $value }}" class="size-4 shrink-0 accent-forest-600" @checked((string) $value === '0')>
                            <span class="font-medium">{{ $answer }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-3 max-w-sm" id="internalFields" hidden>
                    <label for="apEmpId" class="{{ $label }}">Employee ID number</label>
                    <input id="apEmpId" name="emp_ID" placeholder="1051-02" autocomplete="off" class="{{ $input }} mt-1.5">
                    <p class="mt-1.5 text-sm text-ink/60">As printed on your employee card. Your present position, salary grade and appointment status are taken from your 201 file.</p>
                </div>
            </fieldset>

            <fieldset>
                <legend class="{{ $section }}">About you</legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div><label for="apFirst" class="{{ $label }}">First name</label><input id="apFirst" name="first_name" required autocomplete="given-name" class="{{ $input }} mt-1.5"></div>
                    <div><label for="apLast" class="{{ $label }}">Last name</label><input id="apLast" name="last_name" required autocomplete="family-name" class="{{ $input }} mt-1.5"></div>
                    <div><label for="apMiddle" class="{{ $label }}">Middle name <span class="font-normal text-ink/50">(optional)</span></label><input id="apMiddle" name="middle_name" autocomplete="additional-name" class="{{ $input }} mt-1.5"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label for="apAge" class="{{ $label }}">Age</label><input id="apAge" name="age" type="number" min="18" max="65" required inputmode="numeric" class="{{ $input }} mt-1.5"></div>
                        <div><label for="apSex" class="{{ $label }}">Sex</label>
                            <select id="apSex" name="sex" required class="{{ $input }} mt-1.5 pr-8">
                                <option value="" disabled selected>Select</option>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                    </div>
                    <div><label for="apMobile" class="{{ $label }}">Mobile number</label><input id="apMobile" name="mobile" type="tel" placeholder="09XX XXX XXXX" required autocomplete="tel" class="{{ $input }} mt-1.5"></div>
                    <div><label for="apEmail" class="{{ $label }}">Email address</label><input id="apEmail" name="email" type="email" required autocomplete="email" class="{{ $input }} mt-1.5">
                        <p class="mt-1.5 text-sm text-ink/60">Your application number is sent here.</p></div>
                    <div class="sm:col-span-2"><label for="apAddress" class="{{ $label }}">Complete address</label><input id="apAddress" name="address" required autocomplete="street-address" class="{{ $input }} mt-1.5"></div>
                </div>
            </fieldset>

            <div role="group" aria-labelledby="educationTitle">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="{{ $section }}" id="educationTitle">Education</h3>
                    <button type="button" id="addEducation" class="{{ $secondary }} h-9 px-3.5 text-sm"><i class="fas fa-plus text-xs"></i> Add another</button>
                </div>
                <div class="mt-3 space-y-3" id="educationRows"></div>
            </div>

            <div role="group" aria-labelledby="eligibilityTitle">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="{{ $section }}" id="eligibilityTitle">Eligibility <span class="font-sans text-sm font-normal text-ink/50">(if any)</span></h3>
                    <button type="button" id="addEligibility" class="{{ $secondary }} h-9 px-3.5 text-sm"><i class="fas fa-plus text-xs"></i> Add</button>
                </div>
                <div class="mt-3 space-y-3 empty:hidden" id="eligibilityRows"></div>
            </div>

            <fieldset>
                <legend class="{{ $section }}">Documents</legend>
                <p class="mt-1 text-sm text-ink/60">PDF files, 20 MB each at most.</p>

                {{-- The real file input covers its tile, so the whole tile is
                     the thing to press; the script writes the chosen name in. --}}
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach(array_merge($documents, [['cert_training[]', 'Training certificates', false]]) as [$name, $document, $needed])
                        <div class="{{ $name === 'cert_training[]' ? 'sm:col-span-2' : '' }}">
                            <div data-file-tile class="relative flex items-center gap-3 rounded-xl border border-dashed border-ink/25 bg-white p-3.5 transition-colors focus-within:border-forest-600 focus-within:ring-4 focus-within:ring-forest-600/15 hover:border-ink/45 data-[chosen]:border-solid data-[chosen]:border-forest-600 data-[chosen]:bg-forest-100/60 data-[refused]:border-red-400 data-[refused]:bg-red-50">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-paper text-ink/45"><i class="fas fa-file-pdf"></i></span>
                                <span class="min-w-0">
                                    <span class="block font-medium">{{ $document }}@unless($needed) <span class="font-normal text-ink/50">(optional)</span>@endunless</span>
                                    <span class="block truncate text-sm text-ink/55" data-file-name data-idle="{{ $name === 'cert_training[]' ? 'Choose one or more PDFs' : 'Choose a PDF' }}"></span>
                                </span>
                                <input type="file" name="{{ $name }}" accept="application/pdf" aria-label="{{ $document }}" class="absolute inset-0 cursor-pointer opacity-0"
                                       @if($needed) required @endif @if($name === 'cert_training[]') multiple @endif>
                            </div>
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-line bg-white px-6 py-4 sm:px-8" id="applyFooter">
            <p class="text-sm text-ink/60 max-sm:hidden">Everything except what is marked optional is required.</p>
            <div class="flex gap-2 max-sm:w-full max-sm:justify-end">
                <button type="button" data-apply-leave class="{{ $secondary }}">Cancel</button>
                <button type="submit" form="applyForm" id="applySubmit" class="{{ $primary }}">Submit application</button>
            </div>
        </div>

        {{-- Shown in place of the form once the application is in. --}}
        <div class="m-auto max-w-md px-6 py-10 text-center" id="applyDone" hidden>
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-forest-100 text-2xl text-forest-700"><i class="fas fa-check"></i></span>
            <h3 class="mt-5 font-display text-2xl font-semibold tracking-tight">Application submitted</h3>
            <p class="mt-2 text-ink/65">Keep your application number: it is how you track your status. A confirmation was also sent to your email.</p>

            <p class="mt-6 inline-flex items-center gap-3 rounded-2xl border border-forest-600/30 bg-forest-100 py-3 pr-3 pl-6">
                <span class="font-display text-2xl font-semibold tracking-wide text-forest-800 tabular-nums" id="applyNumber"></span>
                <button type="button" id="applyCopy" class="{{ $secondary }} h-9 px-3 text-sm">Copy</button>
            </p>

            <div class="mt-8"><button type="button" data-close class="{{ $primary }}">Done</button></div>
        </div>
    </dialog>

    {{-- Asked before a half-filled application is thrown away. --}}
    <dialog id="discardDialog" aria-labelledby="discardTitle" class="m-auto w-[min(26rem,calc(100vw-2rem))] rounded-2xl border border-line bg-white p-6 text-ink shadow-2xl shadow-forest-950/30 backdrop:bg-forest-950/60">
        <h2 class="font-display text-xl font-semibold tracking-tight" id="discardTitle">Discard this application?</h2>
        <p class="mt-2 text-ink/65">What you have typed and the files you chose will be lost.</p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-close class="{{ $secondary }}">Keep editing</button>
            <button type="button" id="discardYes" class="inline-flex h-11 cursor-pointer items-center rounded-xl bg-red-700 px-5 font-medium text-white transition-colors hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Discard</button>
        </div>
    </dialog>

    {{-- One school on the application; the first cannot be removed. --}}
    <template id="educationRow">
        <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2 sm:grid-cols-[minmax(0,1fr)_11rem_6rem_auto]" data-row>
            <input name="education[]" placeholder="School and degree: BS Civil Engineering, XYZ University" aria-label="School and degree" required class="{{ $input }} max-sm:col-span-2">
            <select name="elevel[]" aria-label="Level" required class="{{ $input }} pr-8 max-sm:col-span-2">
                <option value="" disabled selected>Level</option>
                <option>Elementary</option><option>Secondary</option><option>Vocational</option>
                <option>College</option><option>Graduate Studies</option>
            </select>
            <input name="eyear[]" placeholder="Year" aria-label="Year" maxlength="9" required class="{{ $input }}">
            <button type="button" data-row-remove title="Remove" class="grid size-11 cursor-pointer place-items-center rounded-xl text-ink/45 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-xmark"></i><span class="sr-only">Remove this school</span></button>
        </div>
    </template>

    <template id="eligibilityRow">
        <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2" data-row>
            <input name="eligibility[]" placeholder="CSC Professional (2nd level), or RA 1080 licensure" aria-label="Eligibility" class="{{ $input }}">
            <button type="button" data-row-remove title="Remove" class="grid size-11 cursor-pointer place-items-center rounded-xl text-ink/45 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-sun-500"><i class="fas fa-xmark"></i><span class="sr-only">Remove this eligibility</span></button>
        </div>
    </template>

<script>
(function () {
    'use strict';

    var API = {
        store: "{{ url('/api/application/store') }}",
        status: "{{ url('/api/application/status') }}"
    };

    function byId(id) { return document.getElementById(id); }

    /* ------------------------------------------------------------ dialogs */
    // data-close shuts the dialog it sits in; so does a press on the dimmed
    // page behind one, except behind the application, which is too much work
    // to lose to a stray click.
    document.addEventListener('click', function (event) {
        var closer = event.target.closest('[data-close]');
        if (closer) { closer.closest('dialog').close(); return; }

        if (event.target.tagName === 'DIALOG' && event.target.id !== 'applyDialog') { event.target.close(); }
    });

    /* ----------------------------------------------------- finding a job */
    var list = byId('jobList');
    var search = byId('jobSearch');
    var count = byId('jobCount');
    var none = byId('noResults');
    var chips = Array.prototype.slice.call(document.querySelectorAll('[data-type-filter]'));
    var type = '';

    function filter() {
        var query = search.value.trim().toLowerCase();
        var shown = 0;

        Array.prototype.forEach.call(list.children, function (job) {
            job.hidden = (!!query && job.dataset.haystack.indexOf(query) === -1) || (!!type && job.dataset.type !== type);
            if (!job.hidden) { shown++; }
        });

        count.textContent = shown + (shown === 1 ? ' opening' : ' openings');
        none.hidden = shown > 0;
    }

    if (list) {
        search.addEventListener('input', filter);

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                type = chip.dataset.typeFilter;
                chips.forEach(function (other) { other.setAttribute('aria-pressed', other === chip ? 'true' : 'false'); });
                filter();
            });
        });
    }

    // "Track an application" in the banner lands in the box, ready to type.
    // Done by hand: following the link to #track would move the focus off
    // the box again.
    Array.prototype.forEach.call(document.querySelectorAll('[data-focus]'), function (link) {
        link.addEventListener('click', function (event) {
            var target = byId(link.dataset.focus);
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.focus({ preventScroll: true });
        });
    });

    /* ------------------------------------------------------ one position */
    var jobDialog = byId('jobDialog');
    var current = null;

    // Everything is written as text, never as HTML: the fields are typed by
    // HR, but this page is public and takes no chances.
    function line(term, answer) {
        var row = document.createElement('div');
        row.className = 'grid gap-x-6 gap-y-1 py-4 sm:grid-cols-[10rem_minmax(0,1fr)]';
        var dt = document.createElement('dt');
        dt.className = 'text-sm text-ink/60';
        dt.textContent = term;
        var dd = document.createElement('dd');
        dd.className = 'whitespace-pre-line';
        dd.textContent = answer;
        row.append(dt, dd);
        return row;
    }

    if (list) {
        list.addEventListener('click', function (event) {
            var button = event.target.closest('[data-view-job]');
            if (!button) return;

            current = JSON.parse(button.closest('[data-job]').dataset.job);

            byId('jobTitle').textContent = current.title;
            jobDialog.querySelector('[data-job-field="type"]').textContent = current.type;
            jobDialog.querySelector('[data-job-field="sub"]').textContent =
                (current.item ? 'Item no. ' + current.item + ', ' : '') + 'closes ' + current.deadline;

            var spec = byId('jobSpec');
            spec.textContent = '';
            [
                ['Monthly salary', '₱' + current.salary],
                ['Place of assignment', current.assignment],
                ['Education', current.education],
                ['Eligibility', current.eligibility],
                ['Training', current.training],
                ['Experience', current.experience],
                ['Competency', current.competency]
            ].forEach(function (pair) {
                if (pair[1]) { spec.appendChild(line(pair[0], pair[1])); }
            });

            jobDialog.showModal();
            spec.scrollTop = 0;
        });
    }

    byId('jobApply').addEventListener('click', function () {
        if (!current) return;
        jobDialog.close();
        startApplication(current);
    });

    /* ------------------------------------------------------- application */
    var applyDialog = byId('applyDialog');
    var form = byId('applyForm');
    var footer = byId('applyFooter');
    var done = byId('applyDone');
    var error = byId('applyError');
    var submit = byId('applySubmit');
    var education = byId('educationRows');
    var eligibility = byId('eligibilityRows');
    var discard = byId('discardDialog');

    function addRow(rows, templateId) {
        rows.appendChild(byId(templateId).content.cloneNode(true));
        return rows.lastElementChild;
    }

    byId('addEducation').addEventListener('click', function () { addRow(education, 'educationRow').querySelector('input').focus(); });
    byId('addEligibility').addEventListener('click', function () { addRow(eligibility, 'eligibilityRow').querySelector('input').focus(); });

    form.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-row-remove]');
        if (!remove) return;

        var row = remove.closest('[data-row]');
        // An application needs at least one school.
        if (row.parentElement === education && education.children.length === 1) return;
        row.remove();
    });

    // The employee ID is asked for only of somebody already at the LGU.
    form.addEventListener('change', function (event) {
        if (event.target.name === 'is_internal') { byId('internalFields').hidden = event.target.value !== '1'; }
    });

    /* A tile shows what was chosen. A file the server would refuse anyway is
       turned away here, with the reason, before a long upload is wasted. */
    function showFile(tile, text, state) {
        var name = tile.querySelector('[data-file-name]');
        name.textContent = text || name.dataset.idle;
        delete tile.dataset.chosen;
        delete tile.dataset.refused;
        if (state) { tile.dataset[state] = ''; }
    }

    Array.prototype.forEach.call(form.querySelectorAll('[data-file-tile]'), function (tile) {
        var input = tile.querySelector('input');
        showFile(tile);

        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(input.files);
            var wrong = files.filter(function (file) { return file.type !== 'application/pdf'; })[0];
            var large = files.filter(function (file) { return file.size > 20 * 1024 * 1024; })[0];

            if (wrong || large) {
                input.value = '';
                showFile(tile, wrong ? wrong.name + ' is not a PDF' : large.name + ' is over 20 MB', 'refused');
            } else if (files.length) {
                showFile(tile, files.length === 1 ? files[0].name : files.length + ' files chosen', 'chosen');
            } else {
                showFile(tile);
            }
        });
    });

    function startApplication(job) {
        form.reset();
        form.hidden = false;
        footer.hidden = false;
        done.hidden = true;
        error.hidden = true;
        byId('internalFields').hidden = true;

        Array.prototype.forEach.call(form.querySelectorAll('[data-file-tile]'), function (tile) { showFile(tile); });

        education.textContent = '';
        addRow(education, 'educationRow');
        eligibility.textContent = '';

        form.elements.jid.value = job.id;
        byId('applyTitle').textContent = job.title;

        applyDialog.showModal();
        form.scrollTop = 0;
    }

    // Whether anything has been typed or chosen yet.
    function started() {
        return Array.prototype.some.call(form.elements, function (control) {
            // form.elements also lists the fieldsets and the buttons.
            if (!control.name || control.type === 'hidden' || control.type === 'radio') return false;
            return control.type === 'file' ? control.files.length > 0 : control.value !== '';
        });
    }

    function leave() {
        if (!form.hidden && started()) { discard.showModal(); } else { applyDialog.close(); }
    }

    Array.prototype.forEach.call(applyDialog.querySelectorAll('[data-apply-leave]'), function (button) {
        button.addEventListener('click', leave);
    });

    // Escape asks the same question the Cancel button does.
    applyDialog.addEventListener('cancel', function (event) {
        event.preventDefault();
        leave();
    });

    byId('discardYes').addEventListener('click', function () {
        discard.close();
        applyDialog.close();
    });

    function fail(message) {
        error.textContent = message;
        error.hidden = false;
        form.scrollTop = 0;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        error.hidden = true;

        if (!form.reportValidity()) return;

        submit.disabled = true;
        submit.textContent = 'Submitting…';

        fetch(API.store, { method: 'POST', headers: { 'Accept': 'application/json' }, body: new FormData(form) })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    if (response.status === 201) {
                        byId('applyNumber').textContent = body.data && body.data.app_number ? body.data.app_number : '—';
                        form.hidden = true;
                        footer.hidden = true;
                        done.hidden = false;
                        return;
                    }

                    // A refusal lists its reasons by field.
                    fail(response.status === 422 && body.errors
                        ? Object.keys(body.errors).map(function (field) { return body.errors[field][0]; }).join(' ')
                        : (body.message || 'Something went wrong. Please try again.'));
                });
            })
            .catch(function () { fail('Could not reach the server. Check your connection and try again.'); })
            .finally(function () {
                submit.disabled = false;
                submit.textContent = 'Submit application';
            });
    });

    byId('applyCopy').addEventListener('click', function () {
        var button = this;
        navigator.clipboard.writeText(byId('applyNumber').textContent).then(function () {
            button.textContent = 'Copied';
            setTimeout(function () { button.textContent = 'Copy'; }, 2000);
        });
    });

    /* ----------------------------------------------------------- tracking */
    var STATUS = {
        0: ['Application submitted', 'ok'],
        1: ['Under review', 'ok'],
        2: ['Qualified, for interview', 'ok'],
        3: ['Disqualified', 'bad'],
        4: ['Qualified, not selected', 'warn'],
        5: ['Top 5: psychological and pre-employment test', 'ok'],
        6: ['Not hired', 'bad'],
        7: ['Hired. Congratulations!', 'ok']
    };

    var trackError = byId('trackError');
    var trackResult = byId('trackResult');
    var trackButton = byId('trackButton');

    function tracked(part) { return trackResult.querySelector('[data-track="' + part + '"]'); }

    byId('trackForm').addEventListener('submit', function (event) {
        event.preventDefault();

        var number = byId('trackInput').value.trim().toUpperCase();
        if (!number) return;

        trackError.hidden = true;
        trackResult.hidden = true;
        trackButton.disabled = true;

        fetch(API.status + '/' + encodeURIComponent(number), { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    if (!response.ok || !body.data) {
                        trackError.textContent = 'No application was found with that number. Check it and try again.';
                        trackError.hidden = false;
                        return;
                    }

                    var application = body.data;
                    var status = STATUS[application.status] || STATUS[0];

                    tracked('name').textContent = (application.first_name + ' ' + application.last_name).trim();
                    tracked('position').textContent = application.position || 'Position not recorded';
                    tracked('date').textContent = 'Applied ' + new Date(application.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
                    tracked('status').textContent = status[0];
                    tracked('status').dataset.tone = status[1];

                    // What the applicant needs to know next, where there is something.
                    var note = '';
                    if (Number(application.status) === 2 && application.interview_datetime) {
                        note = 'Interview: ' + new Date(application.interview_datetime).toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' })
                            + (application.venue ? ', ' + application.venue : '');
                    } else if (Number(application.status) === 3 && application.dq_reason) {
                        note = 'Reason: ' + application.dq_reason;
                    }
                    tracked('note').textContent = note;
                    tracked('note').hidden = !note;

                    trackResult.hidden = false;
                });
            })
            .catch(function () {
                trackError.textContent = 'Could not reach the server. Check your connection and try again.';
                trackError.hidden = false;
            })
            .finally(function () { trackButton.disabled = false; });
    });
})();
</script>
</body>
</html>
