{{--
    Demo quick-access panel.

    Rendered only when the controller passes $demoAccounts (i.e. APP_DEMO=true).
    It lists every account and signs in as the chosen one with no password, by
    posting its key to the demoLogin route. Local demos / testing only; it is
    never present in production because demoAccounts() returns null there.

    The list opens in a modal <dialog> rather than unfolding in place: with a
    row per employee it used to push the sign-in column well past the bottom of
    the screen.

    Styled twice on purpose, for as long as its two hosts differ: the demo-*
    names are what public/css/auth.css styles on the administrator sign-in, and
    the Tailwind utilities are what the landing page uses. Each page loads only
    one of the two stylesheets, so they never meet. Drop the demo-* styling
    once the administrator screen is on Tailwind too (the script below still
    needs .demo-acct).
--}}
@if(!empty($demoAccounts))
    <div class="demo-panel mt-4" id="demoPanel">
        <button type="button" id="demoToggle" aria-haspopup="dialog" aria-controls="demoBody"
                class="demo-panel__toggle flex h-12 w-full cursor-pointer items-center justify-between rounded-xl border border-sun-500/45 bg-sun-50 px-4 text-sm font-medium text-ink transition-colors hover:bg-sun-100/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun-500">
            <span><span class="demo-panel__dot mr-2.5 inline-block size-2 rounded-full bg-sun-500 align-middle ring-4 ring-sun-500/20"></span> Demo quick access</span>
            <svg class="ico demo-panel__chev size-3.5 -rotate-90 fill-current text-ink/45" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.2 5.3 8 10.1l4.8-4.8 1.1 1.1L8 12.3 2.1 6.4z"/></svg>
        </button>

        {{-- open:flex, never plain flex: a display utility on a closed dialog
             would override the browser's display:none and show it. The height
             is fixed so the box does not shrink and jump while filtering. --}}
        <dialog id="demoBody" aria-labelledby="demoTitle"
                class="demo-panel__body m-auto h-[min(40rem,calc(100dvh-3rem))] w-[min(28rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-line bg-white p-0 text-ink shadow-2xl shadow-forest-950/25 backdrop:bg-forest-950/55 open:flex">
            <div class="demo-panel__head flex items-start justify-between gap-4 px-5 pt-5">
                <div>
                    <h3 class="demo-panel__title font-display text-xl font-semibold tracking-tight" id="demoTitle">Demo quick access</h3>
                    <p class="demo-panel__hint mt-1 text-[13px]/snug text-ink/60">
                        Local demo mode. Pick any account to sign in instantly — no password.
                    </p>
                </div>
                <button type="button" id="demoClose" aria-label="Close"
                        class="demo-panel__close -mt-1 -mr-2 grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-sun-500">
                    <svg class="ico size-3.5 fill-current" viewBox="0 0 16 16" aria-hidden="true"><path d="m3.3 2.2 4.7 4.7 4.7-4.7 1.1 1.1L9.1 8l4.7 4.7-1.1 1.1L8 9.1l-4.7 4.7-1.1-1.1L6.9 8 2.2 3.3z"/></svg>
                </button>
            </div>

            <input type="text" id="demoSearch"
                   class="demo-panel__search mx-5 mt-4 block h-11 w-[calc(100%-2.5rem)] shrink-0 rounded-xl border border-line bg-paper px-3.5 text-sm text-ink outline-none transition-shadow placeholder:text-ink/40 focus:border-forest-600 focus:bg-white focus:ring-4 focus:ring-forest-600/15"
                   placeholder="Filter by name, username or role…" autocomplete="off">

            {{-- Each account is its own submit button carrying its key as the
                 posted value, so sign-in works with no JavaScript. The search
                 box above only filters what is shown. --}}
            <form action="{{ route('demoLogin') }}" method="post" id="demoForm" class="mt-2 flex min-h-0 flex-1 flex-col">
                @csrf

                <div class="demo-panel__scroll min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 pb-3 [scrollbar-width:thin]" id="demoList">
                    @foreach($demoAccounts as $group => $accounts)
                        @if(!empty($accounts))
                            <div class="demo-group" data-demo-group>
                                <div class="demo-group__label sticky top-0 bg-white px-2 pt-2.5 pb-1.5 text-xs font-medium text-ink/50">{{ $group }}</div>
                                @foreach($accounts as $account)
                                    <button type="submit" name="account" value="{{ $account['key'] }}"
                                            class="demo-acct flex w-full cursor-pointer items-center justify-between gap-4 rounded-lg px-2 py-1.5 text-left transition-colors hover:bg-forest-100/70 focus-visible:bg-forest-100/70 focus-visible:outline-none"
                                            data-search="{{ strtolower($account['name'].' '.$account['username'].' '.$account['role']) }}">
                                        <span class="demo-acct__main flex min-w-0 flex-col">
                                            <span class="demo-acct__name truncate text-sm/5 font-medium text-ink">{{ $account['name'] }}</span>
                                            <span class="demo-acct__user truncate text-xs/4 text-ink/50">{{ $account['username'] }}</span>
                                        </span>
                                        <span class="demo-acct__role max-w-[45%] shrink-0 truncate text-right text-xs text-ink/50">{{ $account['role'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    <p class="demo-panel__empty px-2 py-8 text-center text-sm text-ink/50" id="demoEmpty" hidden>No account matches that.</p>
                </div>
            </form>
        </dialog>
    </div>

    <script>
        (function () {
            var toggle = document.getElementById('demoToggle');
            var body   = document.getElementById('demoBody');
            var close  = document.getElementById('demoClose');
            var search = document.getElementById('demoSearch');
            var empty  = document.getElementById('demoEmpty');
            var buttons = Array.prototype.slice.call(document.querySelectorAll('.demo-acct'));

            toggle.addEventListener('click', function () {
                body.showModal();
                search.focus();
            });

            close.addEventListener('click', function () { body.close(); });

            // A click whose target is the dialog itself, outside its box, is a
            // click on the backdrop. Escape is handled by the browser.
            body.addEventListener('click', function (event) {
                if (event.target !== body) return;

                var box = body.getBoundingClientRect();
                var inside = event.clientX >= box.left && event.clientX <= box.right
                          && event.clientY >= box.top && event.clientY <= box.bottom;

                if (!inside) { body.close(); }
            });

            if (search) {
                search.addEventListener('input', function () {
                    var q = search.value.trim().toLowerCase();
                    var anyVisible = false;

                    buttons.forEach(function (btn) {
                        var hit = !q || btn.getAttribute('data-search').indexOf(q) !== -1;
                        btn.style.display = hit ? '' : 'none';
                        if (hit) { anyVisible = true; }
                    });

                    document.querySelectorAll('[data-demo-group]').forEach(function (group) {
                        var visible = group.querySelectorAll('.demo-acct:not([style*="display: none"])').length;
                        group.style.display = visible ? '' : 'none';
                    });

                    empty.hidden = anyVisible;
                });
            }
        })();
    </script>
@endif
