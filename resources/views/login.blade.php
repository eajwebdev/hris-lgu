<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sign in | HRIS - LGU Mabinay</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..700&family=Instrument+Sans:wght@400..600&display=swap">
    {{-- Tailwind. This is the first screen on it; the rest of the app is still
         AdminLTE and does not load this stylesheet. --}}
    @vite('resources/css/app.css')
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">

    <div class="grid lg:min-h-screen lg:grid-cols-[minmax(0,1.12fr)_minmax(460px,1fr)]">

        {{-- Brand panel --}}
        <aside class="relative isolate m-2.5 flex flex-col overflow-hidden rounded-3xl bg-forest-900 px-6 pt-5 pb-32 text-cream sm:m-3 sm:px-10 sm:pt-8 lg:pb-0">
            <div class="flex items-center justify-between gap-4">
                @include('partials.auth-brand')
                @include('partials.auth-clock')
            </div>

            <div class="mt-7 max-w-xl motion-safe:animate-settle lg:mt-[clamp(3rem,12vh,7.5rem)]">
                <p class="text-sm font-medium text-cream/70">Human Resource Information System</p>

                <h1 class="mt-3 font-display text-[2.6rem]/[.95] font-semibold tracking-tight sm:text-6xl/[.95] xl:text-7xl/[.92]">
                    Maayong adlaw<span class="text-sun-500">.</span>
                </h1>

                <p class="mt-5 hidden max-w-md text-lg/relaxed text-cream/80 sm:block">
                    Your personal data sheet, daily time record, leave and performance
                    ratings, kept by the Human Resource Management Office.
                </p>

                <ul class="mt-6 flex flex-wrap gap-2 sm:mt-7 sm:gap-2.5">
                    <li>
                        <a href="{{ route('attendancePortal') }}"
                           class="group inline-flex items-center gap-2 rounded-xl border border-cream/25 px-3.5 py-2.5 text-sm sm:px-4 font-medium transition-colors hover:border-cream hover:bg-cream hover:text-forest-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                            Attendance portal
                            <svg class="size-3.5 fill-current transition-transform group-hover:translate-x-0.5" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.5 2.5 14 8l-5.5 5.5-1.1-1.1 3.6-3.6H2V7.2h9L7.4 3.6z"/></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('careersPortal') }}"
                           class="group inline-flex items-center gap-2 rounded-xl border border-cream/25 px-3.5 py-2.5 text-sm sm:px-4 font-medium transition-colors hover:border-cream hover:bg-cream hover:text-forest-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                            Job vacancies
                            <svg class="size-3.5 fill-current transition-transform group-hover:translate-x-0.5" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.5 2.5 14 8l-5.5 5.5-1.1-1.1 3.6-3.6H2V7.2h9L7.4 3.6z"/></svg>
                        </a>
                    </li>
                </ul>
            </div>

            @include('partials.auth-scene')
        </aside>

        {{-- Sign-in --}}
        <main class="relative isolate flex flex-col overflow-hidden px-6 pt-8 pb-6 sm:px-10">
            {{-- Dot grid in the top-right corner, fading out toward the form. --}}
            <div aria-hidden="true"
                 class="pointer-events-none absolute top-0 right-0 -z-10 hidden h-64 w-80 bg-[radial-gradient(var(--color-forest-900)_1.5px,transparent_1.5px)] bg-size-[16px_16px] opacity-45 [mask-image:radial-gradient(ellipse_at_top_right,black_25%,transparent_72%)] lg:block"></div>

            <div class="m-auto w-full max-w-sm">
                <h2 class="font-display text-3xl font-semibold tracking-tight">Sign in</h2>
                <p class="mt-2 text-[15px]/relaxed text-ink/60">
                    Use the username or office email issued to you by the HR Office.
                </p>

                @if(session('error'))
                    <div class="mt-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200 ring-inset" role="alert">{{ session('error') }}</div>
                @endif

                @if(session('success'))
                    <div class="mt-6 rounded-xl bg-forest-100 px-4 py-3 text-sm text-forest-800 ring-1 ring-forest-600/25 ring-inset" role="status">{{ session('success') }}</div>
                @endif

                <form action="{{ route('postLogin') }}" method="post" id="signInAuth" class="mt-7 space-y-5">
                    @csrf

                    <div>
                        <label for="login" class="block text-sm font-medium">Username or email</label>
                        <input type="text" id="login" name="login"
                               class="mt-1.5 block h-12 w-full rounded-xl border border-line bg-white px-4 text-base text-ink outline-none transition-shadow placeholder:text-ink/35 focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15"
                               value="{{ old('login') }}"
                               placeholder="name@mabinay.gov.ph"
                               autocomplete="username" autofocus required>
                        @error('login')
                            <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium">Password</label>
                        <div class="relative mt-1.5">
                            <input type="password" id="password" name="password"
                                   class="block h-12 w-full rounded-xl border border-line bg-white pr-16 pl-4 text-base text-ink outline-none transition-shadow focus:border-forest-600 focus:ring-4 focus:ring-forest-600/15"
                                   autocomplete="current-password" required>
                            <button type="button" id="togglePassword" aria-controls="password"
                                    class="absolute inset-y-0 right-0 cursor-pointer rounded-r-xl px-4 text-sm font-medium text-forest-700 hover:text-forest-950 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-sun-500">Show</button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="group flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-forest-900 font-medium text-cream transition-colors hover:bg-forest-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                        Sign in
                        <svg class="size-4 fill-current transition-transform group-hover:translate-x-0.5" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.5 2.5 14 8l-5.5 5.5-1.1-1.1 3.6-3.6H2V7.2h9L7.4 3.6z"/></svg>
                    </button>
                </form>

                @include('partials.demo-login')

                <div class="my-6 flex items-center gap-3 text-sm text-ink/45 before:h-px before:flex-1 before:bg-line after:h-px after:flex-1 after:bg-line">or</div>

                <a href="{{ route('google.login') }}" id="googleBtn"
                   class="flex h-12 w-full items-center justify-center gap-3 rounded-xl border border-line bg-white font-medium transition-colors hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
                    <svg class="size-[18px] shrink-0" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    <span id="googleBtnText">Sign in with Google</span>
                </a>

                <p class="mt-6 text-sm/relaxed text-ink/55">
                    Accounts are issued by the HR Office. If your credentials are not
                    recognized, please contact the Human Resource Management Office.
                </p>
            </div>

            <p class="mt-8 text-center text-xs text-ink/45">
                &copy; {{ date('Y') }} Municipality of Mabinay, Negros Oriental
            </p>
        </main>

    </div>

    <script>
        // Password visibility
        (function () {
            var toggle = document.getElementById('togglePassword');
            var password = document.getElementById('password');
            toggle.addEventListener('click', function () {
                var show = password.getAttribute('type') === 'password';
                password.setAttribute('type', show ? 'text' : 'password');
                toggle.textContent = show ? 'Hide' : 'Show';
                password.focus();
            });
        })();

        // Loading state on the Google button
        document.getElementById('googleBtn').addEventListener('click', function () {
            var icon = this.querySelector('svg');
            var text = document.getElementById('googleBtnText');
            if (icon) {
                var spinner = document.createElement('span');
                spinner.className = 'size-[18px] shrink-0 animate-spin rounded-full border-2 border-line border-t-forest-600';
                icon.replaceWith(spinner);
            }
            text.textContent = 'Redirecting to Google…';
            this.style.pointerEvents = 'none';
        });
    </script>

    {{-- Sign-in errors are shown by the alert above the username field.
         A toastr copy repeated the same sentence at the bottom of the screen. --}}
</body>
</html>
