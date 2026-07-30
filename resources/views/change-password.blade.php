<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $forced ? 'Set a new password' : 'Change password' }} | HRIS - LGU Mabinay</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">
</head>
<body class="auth-body">

    {{-- Same inline sprite the sign-in page uses, so the icons never depend on
         a webfont download. Only the symbols this screen actually shows. --}}
    <svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
        <symbol id="i-alert" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1-64 0z"/></symbol>
        <symbol id="i-ok" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></symbol>
        <symbol id="i-lock" viewBox="0 0 448 512"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></symbol>
        <symbol id="i-eye" viewBox="0 0 576 512"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4 142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1-288 0zm144-64c0 35.3-28.7 64-64 64-7.1 0-13.9-1.2-20.3-3.3-5.5-1.8-11.9 1.6-11.7 7.4.3 6.9 1.3 13.8 3.2 20.7 13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1-5.8-.2-9.2 6.1-7.4 11.7 2.1 6.4 3.3 13.2 3.3 20.3z"/></symbol>
        <symbol id="i-check" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></symbol>
    </svg>

    <div class="auth-bg" aria-hidden="true">
        <div class="auth-grid"></div>
        <div class="auth-blob auth-blob--1"></div>
        <div class="auth-blob auth-blob--2"></div>
        <div class="auth-blob auth-blob--3"></div>
    </div>

    {{-- --solo centres the card. The base .auth-shell is the sign-in layout's
         two-column grid (hero + card); without a hero this page was leaving the
         first column empty and sitting off to the right. --}}
    <main class="auth-shell auth-shell--solo">
        <section class="auth-card">
            <img src="{{ asset('Uploads/logo.png') }}" alt="Mabinay Seal" class="auth-card__logo">

            <h2 class="auth-card__title">{{ $forced ? 'Set a new password' : 'Change password' }}</h2>

            <p class="auth-card__desc">
                @if($forced)
                    Your account is still using the password HR issued. Everyone receives
                    the same one, so please replace it before you continue.
                @else
                    Choose a new password for your account.
                @endif
            </p>

            @if($forced)
                <div class="auth-alert auth-alert--error">
                    <svg class="ico"><use href="#i-alert"></use></svg>
                    <span>You cannot use the rest of the system until this is done.</span>
                </div>
            @endif

            @if(session('error'))
                <div class="auth-alert auth-alert--error">
                    <svg class="ico"><use href="#i-alert"></use></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('password.change.update') }}" method="post" id="changePassword">
                @csrf

                {{-- No "current password" field. The account reaching this
                     screen is almost always still on the password HR issued —
                     which every new account receives, so asking them to retype
                     a shared secret proved nothing and was one more thing to
                     mistype on a phone. The server no longer asks for it
                     either; see PasswordController::update. --}}
                <div class="auth-field">
                    <label for="password">New password</label>
                    <div class="auth-input-wrap">
                        <svg class="ico"><use href="#i-lock"></use></svg>
                        <input type="password" class="auth-input" id="password" name="password"
                               placeholder="At least 8 characters" autocomplete="new-password" autofocus required>
                        <button type="button" class="auth-toggle" data-toggle-for="password" aria-label="Show password">
                            <svg class="ico"><use href="#i-eye"></use></svg>
                        </button>
                    </div>
                    @error('password')
                        <small class="auth-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password_confirmation">Confirm new password</label>
                    <div class="auth-input-wrap">
                        <svg class="ico"><use href="#i-lock"></use></svg>
                        <input type="password" class="auth-input" id="password_confirmation" name="password_confirmation"
                               placeholder="Type it again" autocomplete="new-password" required>
                        <button type="button" class="auth-toggle" data-toggle-for="password_confirmation" aria-label="Show password">
                            <svg class="ico"><use href="#i-eye"></use></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <svg class="ico"><use href="#i-check"></use></svg> Save new password
                </button>
            </form>

            <form action="{{ route('logout') }}" method="post" class="auth-signout">
                @csrf
                <button type="submit" class="auth-link-btn">Sign out instead</button>
            </form>
        </section>
    </main>

    <style>
        /* Only what auth.css does not already provide. */
        .auth-signout { margin-top: 18px; text-align: center; }
        .auth-link-btn {
            background: none;
            border: 0;
            padding: 0;
            font: inherit;
            font-size: 13px;
            color: #64748B;
            cursor: pointer;
            text-decoration: underline;
        }
        .auth-link-btn:hover { color: #0F172A; }
    </style>

    <script>
        // Each eye button reveals the field named on its data attribute.
        document.querySelectorAll('.auth-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var field = document.getElementById(btn.getAttribute('data-toggle-for'));

                if (!field) return;

                field.type = field.type === 'password' ? 'text' : 'password';
                btn.setAttribute('aria-label', field.type === 'password' ? 'Show password' : 'Hide password');
            });
        });
    </script>

</body>
</html>
