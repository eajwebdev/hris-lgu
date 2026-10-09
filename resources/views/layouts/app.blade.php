{{--
    Signed-in shell, on Tailwind.

    The replacement for layouts/master (AdminLTE / Bootstrap 4). Pages move over
    one module at a time: a converted view swaps its @extends for this layout and
    keeps the same `body` section. Nothing here loads jQuery, Bootstrap or
    AdminLTE, so a page must not arrive until its own markup and scripts no
    longer need them.

    What the two shells share, so they cannot disagree while both exist:
      - the menu                  sidebar_menu()               app/Helpers/helpers.php
      - who sees applications     sees_job_applications()      "
      - notification rows         partials/notification_items{,_employee}
--}}
@php
    $account = auth()->guard($guard)->user();

    $accountPhoto = $account->profile && file_exists(public_path('Profile/Employee/' . $account->profile))
        ? asset('Profile/Employee/' . $account->profile)
        : asset('Profile/Employee/default.png');

    $accountName = trim(ucwords(strtolower($account->fname)) . ' ' . ucwords(strtolower($account->lname)));

    $accountRole = $guard === 'employee'
        ? ($account->emp_status == 1 && $account->position ? $account->position : 'Employee')
        : ucfirst($account->role);

    // Built once: the sidebar draws it and the top bar's breadcrumb reads it.
    $menu = sidebar_menu($guard);

    // Employees must accept the privacy notice before using the system.
    $needsPrivacyConsent = $guard === 'employee' && $account->dpn == 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HRIS - LGU Mabinay {{ isset($title) ? ' | '.$title : '' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..700&family=Instrument+Sans:wght@400..600&display=swap">
    <!-- Font Awesome: still the icon set for the whole app -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    {{-- Page stylesheets go before app.css so its overrides of them win. --}}
    @stack('styles')
    @vite('resources/css/app.css')
    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">

    <script>
        // Put the sidebar and the theme back the way they were left, before
        // first paint, so neither flashes the wrong way on every page load.
        try {
            if (localStorage.getItem('hris.sidebar') === 'collapsed') {
                document.documentElement.dataset.sidebar = 'collapsed';
            }
            if (localStorage.getItem('hris.theme') === 'dark') {
                document.documentElement.dataset.theme = 'dark';
            }
        } catch (e) {}
    </script>
</head>
<body class="min-h-screen bg-paper font-sans text-sm text-ink antialiased">

    {{-- Dims the page behind the off-canvas sidebar on phones --}}
    <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-forest-950/50 drawer:block"></div>

    @include('layouts.app-sidebar')

    <div class="flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-69 rail:pl-24">
        {{-- Header banner, pinned: the top bar, and under it whatever the page
             puts in its `hero` section (a heading, its actions). A photo of
             the municipal hall under the same green wash as the sidebar. Only
             the page body below scrolls.

             The paper-coloured surround is what the body disappears behind:
             it fills the gap above the banner and the space outside its
             rounded corners. The banner itself is not overflow-hidden — the
             top bar's dropdowns hang out below it — so the wash takes the
             rounding instead. --}}
        <div class="sticky top-0 z-20 bg-paper px-2.5 pt-2.5 lg:pl-0">
            <div class="shell-photo relative isolate rounded-3xl bg-forest-900 text-cream"
                 style="background-image: url('{{ asset('images/headerpic.jpeg') }}')">
                <div class="shell-wash pointer-events-none absolute inset-0 -z-10 rounded-[inherit]"></div>

                @include('layouts.app-topbar')

                @hasSection('hero')
                    <div class="px-4 pt-2 pb-6 sm:px-6 sm:pb-7">
                        @yield('hero')
                    </div>
                @endif
            </div>
        </div>

        <main class="flex-1 px-4 pt-5 pb-10 sm:px-6">
            @yield('body')
        </main>

        <footer class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 border-t border-line px-4 py-4 text-xs text-ink/55 sm:px-6">
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

    @include('layouts.app-scripts')
    @include('layouts.app-select')
    @stack('scripts')
    @yield('scripts')
</body>
</html>
