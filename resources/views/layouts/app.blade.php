{{--
    Signed-in shell, on Tailwind.

    The replacement for layouts/master (AdminLTE / Bootstrap 4). Pages move over
    one module at a time: a converted view swaps its @extends for this layout and
    keeps the same `body` section. Nothing here loads jQuery, Bootstrap or
    AdminLTE, so a page must not arrive until its own markup and scripts no
    longer need them.

    layouts/master draws this same shell — sidebar, top bar, banner, footer,
    dialogs — around the pages still on AdminLTE, from the same partials and
    the same shell_account() data, so every page shares one frame.
--}}
@php
    [
        'account' => $account,
        'accountPhoto' => $accountPhoto,
        'accountName' => $accountName,
        'accountRole' => $accountRole,
        'menu' => $menu,
        'needsPrivacyConsent' => $needsPrivacyConsent,
    ] = shell_account($guard ??= guard());
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
        // Put the sidebar back the way it was left, before first paint, so it
        // does not flash the wrong width on every page load. The dark theme is
        // not restored: its toggle is hidden, so anyone who once chose it would
        // be stuck there with no way back to the light design.
        try {
            if (localStorage.getItem('hris.sidebar') === 'collapsed') {
                document.documentElement.dataset.sidebar = 'collapsed';
            }
        } catch (e) {}
    </script>
</head>
<body class="min-h-screen bg-paper font-sans text-sm text-ink antialiased">

    @include('layouts.app-frame')

    @include('layouts.app-scripts')
    @include('layouts.app-select')
    @stack('scripts')
    @yield('scripts')
@include('partials.notification_realtime')
</body>
</html>
