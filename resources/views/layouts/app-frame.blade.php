{{--
    Everything the signed-in shell draws inside <body>: the sidebar, the top
    bar, the page banner, the footer, the privacy and confirm dialogs, and the
    toasts. The page's own `hero` and `body` sections are yielded from here;
    a page names what is open beneath its menu entry with
    @section('breadcrumb', 'Maria Santos').

    Included by both layouts/app (pages on Tailwind) and layouts/master (pages
    still on AdminLTE / Bootstrap 4), so every page in the system sits in the
    same frame. Variables come from shell_account().

    .hris-page wraps the page body. layouts/master loads resources/css/shell.css,
    whose utilities are scoped to stop there, so Bootstrap keeps the page body
    and Tailwind keeps the frame. On layouts/app it is an inert wrapper.
    $pageClass adds classes to it (layouts/master passes AdminLTE's `content`).
--}}
{{-- Dims the page behind the off-canvas sidebar on phones --}}
<div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-forest-950/50 drawer:block"></div>

@include('layouts.app-sidebar')

@php
    $crumbs = breadcrumb_trail($menu);
    $isDashboard = request()->routeIs('dashboard');
@endphp

{{-- print: the frame is chrome; a printed page is only its body. --}}
<div class="flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-68 rail:pl-20 print:block print:min-h-0 print:pl-0">
    {{-- The top bar stays pinned while the page scrolls under it. At the top
         of the page it is clear, sitting on the banner photo; once the page
         has scrolled the shell script marks it data-scrolled and it turns
         solid green. --}}
    <div id="appTopbar" class="sticky top-0 z-20 px-3 transition-[background-color,box-shadow] duration-200 lg:px-5 data-scrolled:bg-forest-950/92 data-scrolled:shadow-lg data-scrolled:shadow-forest-950/20 data-scrolled:backdrop-blur print:hidden">
        @include('layouts.app-topbar')
    </div>

    {{-- The banner: the municipal hall across the full width, slid up under
         the top bar. It carries the breadcrumb and the page's `hero` section;
         a page with none is named from the breadcrumb. The dashboard draws a
         taller one for its greeting. --}}
    <section class="shell-banner relative isolate -mt-19 overflow-hidden bg-forest-900 text-white print:hidden {{ $isDashboard ? 'shell-banner--tall' : '' }}"
             style="background-image: url('{{ asset('images/mabinay-hall-wide.png') }}')">
        <div class="shell-wash pointer-events-none absolute inset-0 -z-10"></div>

        <div class="px-4 pt-22 pb-7 sm:px-6 lg:px-8 {{ $isDashboard ? 'flex min-h-80 flex-col justify-end pb-10 sm:min-h-88' : '' }}">
            @unless($isDashboard)
                <nav aria-label="Breadcrumb" class="mb-2">
                    <ol class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 font-medium text-white/90 [text-shadow:0_1px_2px_rgb(0_0_0/.4)]">
                        <li class="shrink-0">
                            <a href="{{ route('dashboard') }}" title="Dashboard" aria-label="Dashboard"
                               class="grid size-6 place-items-center rounded-md transition-colors hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-sun-500">
                                <i class="fas fa-house text-[13px]"></i>
                            </a>
                        </li>

                        @foreach($crumbs as $crumb)
                            @php
                                $isPage = $loop->last && !View::hasSection('breadcrumb');
                            @endphp
                            <li class="flex min-w-0 items-center gap-2">
                                <i class="fas fa-angle-right text-[10px] text-white/60"></i>
                                @if($isPage)
                                    <span aria-current="page" class="truncate text-white">{{ $crumb['label'] }}</span>
                                @elseif($crumb['url'])
                                    <a href="{{ $crumb['url'] }}" class="truncate rounded underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-sun-500">{{ $crumb['label'] }}</a>
                                @else
                                    <span class="truncate">{{ $crumb['label'] }}</span>
                                @endif
                            </li>
                        @endforeach

                        @hasSection('breadcrumb')
                            <li class="flex min-w-0 items-center gap-2">
                                <i class="fas fa-angle-right text-[10px] text-white/60"></i>
                                <span aria-current="page" class="truncate text-white">@yield('breadcrumb')</span>
                            </li>
                        @endif
                    </ol>
                </nav>
            @endunless

            <div class="[text-shadow:0_1px_3px_rgb(0_0_0/.35)]">
                @hasSection('hero')
                    @yield('hero')
                @else
                    <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">{{ $title ?? (last($crumbs)['label'] ?? 'HRIS') }}</h1>
                @endif
            </div>
        </div>
    </section>

    <main class="flex-1 px-3 pt-5 pb-10 sm:px-5 print:p-0">
        <div class="hris-page {{ $pageClass ?? '' }}">
            @yield('body')
        </div>
    </main>

    <footer class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 border-t border-line px-4 py-4 text-xs text-ink/55 sm:px-6 print:hidden">
        <p>
            <strong class="font-medium text-ink/75">&copy; {{ now()->year }} Municipality of Mabinay.</strong> All rights reserved.
            <span class="mx-1.5 text-ink/25">|</span>
            <button type="button" data-dialog-open="privacyDialog"
                    class="cursor-pointer font-medium text-forest-700 underline-offset-2 hover:underline">Data Privacy Policy</button>
        </p>
        <p class="hidden sm:block">Managed by <strong class="font-medium text-ink/75">EAJ Web Development Services</strong>.</p>
    </footer>
</div>

{{-- Privacy notice (layouts/app-privacy). The consent version is shown on
     every page until answered and cannot be dismissed, only accepted or
     declined (declining signs the employee out); the other is the same
     notice to read, opened from the footer. --}}
@if($needsPrivacyConsent)
    @include('layouts.app-privacy', ['consent' => true])
@endif
@include('layouts.app-privacy', ['consent' => false])

{{-- Asked before a form marked data-confirm is sent (layouts/app-scripts):
       data-confirm          the question
       data-confirm-detail   optional line under it
       data-confirm-button   optional wording for the go-ahead button
       data-confirm-danger   present when going ahead destroys something --}}
<dialog id="confirmDialog" aria-labelledby="confirmDialogTitle"
        class="m-auto w-[min(26rem,calc(100vw-2rem))] rounded-2xl border border-line bg-surface p-6 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/60">
    <h2 class="font-display text-xl font-semibold tracking-tight" id="confirmDialogTitle"></h2>
    <p class="mt-2 leading-relaxed text-ink/70" data-confirm-detail></p>

    <div class="mt-6 flex justify-end gap-2">
        <button type="button" data-dialog-close class="h-10 cursor-pointer rounded-xl border border-line px-5 font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">Cancel</button>
        <button type="button" data-confirm-yes
                class="h-10 cursor-pointer rounded-xl bg-forest-900 px-5 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500 data-[danger]:bg-red-700 data-[danger]:text-white data-[danger]:hover:bg-red-800 dark:bg-forest-600 dark:hover:bg-forest-500"></button>
    </div>
</dialog>

{{-- Flash messages. The old shell raised these through toastr. --}}
@php
    $toasts = [];
    foreach (['error' => 'error', 'error1' => 'error', 'success' => 'success'] as $key => $kind) {
        if (session()->has($key)) {
            $toasts[] = ['kind' => $kind, 'lines' => [session($key)]];
        }
    }
    if ($errors->any()) {
        $toasts[] = ['kind' => 'error', 'lines' => $errors->all()];
    }
@endphp
{{-- Always present, even when empty: hrisToast() in the shell script adds
     to it for things that happen without a page load. --}}
<div id="toasts" class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-6 sm:bottom-6">
    @foreach($toasts as $toast)
        <div data-toast role="{{ $toast['kind'] === 'error' ? 'alert' : 'status' }}"
             class="pointer-events-auto flex w-full items-start gap-3 rounded-xl px-4 py-3 shadow-lg shadow-forest-950/15 sm:w-96 {{ $toast['kind'] === 'error' ? 'bg-red-700 text-white' : 'bg-forest-900 text-cream dark:ring-1 dark:ring-cream/15' }}">
            <i class="fas {{ $toast['kind'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' }} mt-0.5"></i>
            <p class="min-w-0 flex-1 leading-snug">
                @foreach($toast['lines'] as $line)
                    {{ $line }}@if(!$loop->last)<br>@endif
                @endforeach
            </p>
            <button type="button" data-toast-close aria-label="Dismiss" class="-mr-1 cursor-pointer opacity-70 hover:opacity-100">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    @endforeach
</div>
