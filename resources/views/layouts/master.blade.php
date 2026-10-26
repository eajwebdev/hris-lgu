{{--
    Signed-in layout for the pages still on AdminLTE / Bootstrap 4.

    The frame around the page — sidebar, top bar, banner, footer, privacy and
    confirm dialogs, toasts — is layouts/app-frame, the very same partial
    layouts/app draws, so these pages look and behave like the rest of the
    system. Only the page body is Bootstrap. resources/css/shell.css styles the
    frame without letting Tailwind reach into the body; see the notes there.

    A page puts its heading in a `hero` section, as the Tailwind pages do, and
    gets the municipal hall banner for free.
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
    <!-- The shell's fonts, the same as layouts/app -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..700&family=Instrument+Sans:wght@400..700&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/style.css') }}">
    <!-- HRIS modern theme (must load after AdminLTE + custom style) -->
    <link rel="stylesheet" href="{{ asset('css/hris-theme.css') }}?v={{ filemtime(public_path('css/hris-theme.css')) }}">
    <!-- QR -->
    <script src="{{ asset('template/dist/js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/qrcode.min.js') }}"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
    .img-circle1 {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 2px solid #ddd !important;
        display: block !important;
    }
    .btn-success1 {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
    body.modal-open,
    body.modal-open html {
        overflow: hidden !important;
        height: 100vh !important;
        touch-action: none !important;
    }
    </style>
    {{-- After every legacy stylesheet, so the frame's rules are the last word. --}}
    @vite('resources/css/shell.css')

    <script>
        // Put the sidebar back the way it was left, before first paint.
        try {
            if (localStorage.getItem('hris.sidebar') === 'collapsed') {
                document.documentElement.dataset.sidebar = 'collapsed';
            }
        } catch (e) {}
    </script>
</head>
<body class="hris-legacy text-sm">
<div class="hris-shell font-sans text-ink antialiased">
    @include('layouts.app-frame', ['pageClass' => 'content'])
</div>

@include('script.masterScript')
@if(request()->is('employees') || request()->is('employees/*'))
    @include('script.employeeScript')
@endif
@if(request()->is('pds/face-recognition') || request()->is('pds/face-recognition/*'))
    @include('script.faceRegistrationScript')
@endif
@include('layouts.app-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // The few scripts that still raise toastr directly get the shell's toasts
    // instead, so a message looks the same whichever page it comes from.
    if (window.toastr && window.hrisToast) {
        ['success', 'info', 'warning', 'error'].forEach(function (kind) {
            window.toastr[kind] = function (message) {
                var holder = document.createElement('div');
                holder.innerHTML = String(message).replace(/<br\s*\/?>/gi, ' ');
                window.hrisToast(kind === 'error' || kind === 'warning' ? 'error' : 'success', holder.textContent);
            };
        });
    }

    // Lock background scrolling while a Bootstrap modal is open.
    $(document).on('show.bs.modal shown.bs.modal', '.modal', function () {
        $('body, html').css({ 'overflow': 'hidden', 'height': '100vh' });
        $('body').addClass('modal-open');
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal.show').length === 0) {
            $('body, html').css({ 'overflow': '', 'height': '' });
            $('body').removeClass('modal-open');
        }
    });
});
</script>
@stack('scripts')
@yield('scripts')
@include('partials.notification_realtime')
</body>
</html>
