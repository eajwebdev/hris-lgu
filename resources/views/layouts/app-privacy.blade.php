{{--
    The Data Privacy Notice as a dialog on layouts/app. Drawn twice:

      $consent = true    asked of an employee who has not yet answered. It
                         opens with the page and cannot be dismissed: Accept
                         records the consent, Decline signs them out.
      $consent = false   the same notice to read, from the footer link.

    The wording comes from privacy_notice() in app/Helpers/helpers.php, shared
    with the PDF and the old shell (data-privacy.blade.php).

    Layout: the six sections listed down the left (desktop), the notice
    scrolling on the right, and a thin bar under the heading that fills as it
    is read. The behaviour is in layouts/app-scripts ([data-privacy]).
--}}
@php
    $notice = privacy_notice();
    $dialogId = $consent ? 'dpnDialog' : 'privacyDialog';
    // Section ids are per dialog: both can be in the same page.
    $anchor = fn ($number) => $dialogId . '-s' . $number;
@endphp
<dialog id="{{ $dialogId }}" aria-labelledby="{{ $dialogId }}-title" data-privacy
        @if($consent) data-dialog-autoshow data-dialog-static @endif
        class="m-auto h-[min(46rem,calc(100dvh-2rem))] w-[min(60rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-3xl border border-line bg-surface p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60 open:flex">

    {{-- Heading, on the shell's green --}}
    <div class="relative shrink-0 bg-forest-900 px-6 py-5 text-cream sm:px-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm text-cream/70">{{ $consent ? 'Before you continue' : 'Municipality of Mabinay' }}</p>
                <h2 class="mt-0.5 font-display text-2xl font-semibold tracking-tight" id="{{ $dialogId }}-title">Data Privacy Notice</h2>
                <p class="mt-1 text-sm text-cream/70">{{ $notice['basis'] }}</p>
            </div>
            @unless($consent)
                <button type="button" data-dialog-close aria-label="Close"
                        class="-mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-cream/70 transition-colors hover:bg-cream/12 hover:text-cream focus-visible:outline-2 focus-visible:outline-sun-500">
                    <i class="fas fa-xmark"></i>
                </button>
            @endunless
        </div>
        <div class="absolute inset-x-0 bottom-0 h-1 bg-cream/15" aria-hidden="true">
            <div class="h-full w-0 bg-sun-500" data-privacy-progress></div>
        </div>
    </div>

    <div class="flex min-h-0 flex-1">
        {{-- Contents --}}
        <nav aria-label="Sections of the notice" class="hidden w-64 shrink-0 overflow-y-auto border-r border-line bg-paper p-4 md:block">
            <p class="px-3 pb-2 text-xs font-medium text-ink/55">In this notice</p>
            <ol class="space-y-0.5">
                @foreach($notice['sections'] as $section)
                    <li>
                        <button type="button" data-privacy-jump="{{ $anchor($loop->iteration) }}"
                                class="flex w-full cursor-pointer gap-2.5 rounded-lg px-3 py-2 text-left leading-snug text-ink/65 transition-colors hover:bg-surface hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500 aria-[current=true]:bg-forest-100 aria-[current=true]:font-medium aria-[current=true]:text-forest-800">
                            <span class="tabular-nums">{{ $loop->iteration }}</span>
                            <span>{!! $section['heading'] !!}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>

        {{-- The notice. Focusable so the keyboard can scroll it; it shows no
             ring of its own, being most of the dialog. --}}
        <div class="min-h-0 flex-1 overflow-y-auto scroll-smooth px-6 py-6 outline-none sm:px-8 [&_a]:font-medium [&_a]:text-forest-700 [&_a]:underline [&_a]:underline-offset-2 [&_strong]:font-semibold [&_strong]:text-ink"
             tabindex="0" data-privacy-scroll>
            <p class="leading-relaxed text-ink/80">{!! $notice['intro'] !!}</p>

            @foreach($notice['sections'] as $section)
                <section id="{{ $anchor($loop->iteration) }}" class="mt-7 scroll-mt-4" data-privacy-section>
                    <h3 class="flex items-baseline gap-3 font-display text-lg font-semibold tracking-tight">
                        <span class="grid size-7 shrink-0 place-items-center self-center rounded-lg bg-forest-100 text-sm text-forest-800 tabular-nums">{{ $loop->iteration }}</span>
                        <span>{!! $section['heading'] !!}</span>
                    </h3>
                    <p class="mt-2 leading-relaxed text-ink/80">{!! $section['lead'] !!}</p>
                    @if($section['items'])
                        <ul class="mt-3 space-y-2">
                            @foreach($section['items'] as $item)
                                <li class="flex gap-3 leading-relaxed text-ink/80">
                                    <span class="mt-2.5 size-1.5 shrink-0 rounded-full bg-forest-600 dark:bg-forest-500"></span>
                                    <span>{!! $item !!}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            <p class="mt-8 border-t border-line pt-4 text-xs leading-relaxed text-ink/55">
                {!! $notice['issuer'] !!}<br>
                System Developer &amp; Technical Service Provider: {{ $notice['developer'] }}<br>
                &copy; {{ now()->year }} All Rights Reserved.
            </p>
        </div>
    </div>

    <div class="flex shrink-0 flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-line bg-paper px-6 py-4 sm:px-8">
        @if($consent)
            <p class="max-w-md text-sm leading-snug text-ink/65">Accepting records your consent and opens the system. Declining signs you out.</p>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                        Decline and sign out
                    </button>
                </form>
                <form method="POST" action="{{ route('dataPrivacyNotice') }}">
                    @csrf
                    <button type="submit" class="h-10 cursor-pointer rounded-xl bg-forest-900 px-6 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 dark:bg-forest-600 dark:hover:bg-forest-500">
                        I accept
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('dataPrivacy') }}" target="_blank" class="text-sm font-medium text-forest-700 underline-offset-2 hover:underline">Open as PDF</a>
            <button type="button" data-dialog-close
                    class="h-10 cursor-pointer rounded-xl border border-line bg-surface px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Close</button>
        @endif
    </div>
</dialog>
