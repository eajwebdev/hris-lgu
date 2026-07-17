<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sign in | HRIS - LGU Mabinay</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- Auth theme (cache-busted so browsers never serve a stale copy) -->
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">
</head>
<body class="auth-body">

    <!-- Icon sprite: inline SVG, so icons never depend on a webfont download -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
        <symbol id="i-shield" viewBox="0 0 512 512"><path d="M256 0c4.6 0 9.2 1 13.4 2.9L457.7 82.8c22 9.3 38.4 31 38.3 57.2-.5 99.2-41.3 280.7-213.6 363.2-16.7 8-36.1 8-52.8 0C57.3 420.7 16.5 239.2 16 140c-.1-26.2 16.3-47.9 38.3-57.2L242.7 2.9C246.8 1 251.4 0 256 0z"/></symbol>
        <symbol id="i-check" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></symbol>
        <symbol id="i-alert" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1-64 0z"/></symbol>
        <symbol id="i-ok" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></symbol>
        <symbol id="i-user" viewBox="0 0 448 512"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3 0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3H178.3z"/></symbol>
        <symbol id="i-lock" viewBox="0 0 448 512"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></symbol>
        <symbol id="i-eye" viewBox="0 0 576 512"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4 142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1-288 0zm144-64c0 35.3-28.7 64-64 64-7.1 0-13.9-1.2-20.3-3.3-5.5-1.8-11.9 1.6-11.7 7.4.3 6.9 1.3 13.8 3.2 20.7 13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1-5.8-.2-9.2 6.1-7.4 11.7 2.1 6.4 3.3 13.2 3.3 20.3z"/></symbol>
        <symbol id="i-eye-off" viewBox="0 0 640 512"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144 0 24.9-6.3 48.3-17.4 68.7L408 294.5c8.4-19.3 10.6-41.4 4.8-63.3-11.1-41.5-47.8-69.4-88.6-71.1-5.8-.2-9.2 6.1-7.4 11.7 2.1 6.4 3.3 13.2 3.3 20.3 0 .3 0 .5 0 .8l-97-76.4zM373 389.9c-16.4 6.5-34.3 10.1-53 10.1-79.5 0-144-64.5-144-144 0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5L373 389.9z"/></symbol>
        <symbol id="i-signin" viewBox="0 0 512 512"><path d="M352 96l64 0c17.7 0 32 14.3 32 32l0 256c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l64 0c53 0 96-43 96-96l0-256c0-53-43-96-96-96l-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32zm-9.4 182.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L242.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l210.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z"/></symbol>
    </svg>

    <!-- Animated background -->
    <div class="auth-bg" aria-hidden="true">
        <div class="auth-grid"></div>
        <div class="auth-blob auth-blob--1"></div>
        <div class="auth-blob auth-blob--2"></div>
        <div class="auth-blob auth-blob--3"></div>
        <div id="sparks"></div>
    </div>

    <main class="auth-shell">

        <!-- Brand pane -->
        <section class="auth-hero">
            <img src="{{ asset('Uploads/logo.png') }}" alt="Municipality of Mabinay Official Seal" class="auth-hero__seal">

            <span class="auth-hero__eyebrow">
                <svg class="ico"><use href="#i-shield"></use></svg> Local Government Unit
            </span>

            <h1 class="auth-hero__title">
                Human Resource<br><span>Information System</span>
            </h1>

            <p class="auth-hero__sub">
                The official personnel platform of the Municipality of Mabinay &mdash; records,
                daily time, leave, and performance in one secure workspace.
            </p>

            <ul class="auth-hero__points">
                <li><svg class="ico"><use href="#i-check"></use></svg>Personal Data Sheet</li>
                <li><svg class="ico"><use href="#i-check"></use></svg>Daily Time Record</li>
                <li><svg class="ico"><use href="#i-check"></use></svg>Leave Management</li>
            </ul>
        </section>

        <!-- Auth card -->
        <section class="auth-card">
            <img src="{{ asset('Uploads/logo.png') }}" alt="Mabinay Seal" class="auth-card__logo">

            <h2 class="auth-card__title">Welcome back</h2>
            <p class="auth-card__desc">
                Sign in with your HRIS credentials or your official Google account.
            </p>

            @if(session('error'))
                <div class="auth-alert auth-alert--error">
                    <svg class="ico"><use href="#i-alert"></use></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="auth-alert auth-alert--success">
                    <svg class="ico"><use href="#i-ok"></use></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('postLogin') }}" method="post" id="signInAuth">
                @csrf

                <div class="auth-field">
                    <label for="login">Username or Email</label>
                    <div class="auth-input-wrap">
                        <svg class="ico"><use href="#i-user"></use></svg>
                        <input type="text" class="auth-input" id="login" name="login"
                               value="{{ old('login') }}"
                               placeholder="username or name@mabinay.gov.ph"
                               autocomplete="username" autofocus required>
                    </div>
                    @error('login')
                        <small class="auth-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input-wrap">
                        <svg class="ico"><use href="#i-lock"></use></svg>
                        <input type="password" class="auth-input" id="password" name="password"
                               placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="auth-toggle" id="togglePassword" aria-label="Show password">
                            <svg class="ico"><use href="#i-eye"></use></svg>
                        </button>
                    </div>
                    @error('password')
                        <small class="auth-error">{{ $message }}</small>
                    @enderror
                </div>

                <button type="submit" class="btn-auth">
                    <svg class="ico"><use href="#i-signin"></use></svg> Sign in
                </button>
            </form>

            <div class="auth-divider">or</div>

            <a href="{{ route('google.login') }}" class="btn-google" id="googleBtn">
                <svg viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                <span id="googleBtnText">Continue with Google</span>
            </a>

            <p class="auth-card__desc" style="margin: 1.4rem 0 0; font-size: .78rem;">
                Accounts are issued by the HR Office. If your credentials are not
                recognized, please contact the Human Resource Management Office.
            </p>

            <div class="auth-card__foot">
                &copy; {{ date('Y') }} Municipality of Mabinay, Negros Oriental<br>
            </div>
        </section>

    </main>

    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Rising sparks for the animated background
        (function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            var host = document.getElementById('sparks');
            for (var i = 0; i < 18; i++) {
                var s = document.createElement('span');
                s.className = 'auth-spark';
                var size = 2 + Math.random() * 5;
                s.style.left = (Math.random() * 100) + '%';
                s.style.width = size + 'px';
                s.style.height = size + 'px';
                s.style.opacity = 0.2 + Math.random() * 0.6;
                s.style.animationDuration = (10 + Math.random() * 16) + 's';
                s.style.animationDelay = (-Math.random() * 20) + 's';
                host.appendChild(s);
            }
        })();

        // Password visibility
        (function () {
            var toggle = document.getElementById('togglePassword');
            var password = document.getElementById('password');
            toggle.addEventListener('click', function () {
                var show = password.getAttribute('type') === 'password';
                password.setAttribute('type', show ? 'text' : 'password');
                toggle.querySelector('use').setAttribute('href', show ? '#i-eye-off' : '#i-eye');
                password.focus();
            });
        })();

        // Loading state on the Google button
        document.getElementById('googleBtn').addEventListener('click', function () {
            var icon = this.querySelector('svg');
            var text = document.getElementById('googleBtnText');
            if (icon) {
                var spinner = document.createElement('span');
                spinner.className = 'spinner';
                icon.replaceWith(spinner);
            }
            text.textContent = 'Redirecting to Google…';
            this.style.pointerEvents = 'none';
        });
    </script>

    @if(session('error'))
        <script>
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-bottom-center',
                timeOut: 4000
            };
            toastr.error(@json(session('error')));
        </script>
    @endif
</body>
</html>
