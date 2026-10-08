{{-- Philippine time on the sign-in panel. Rendered by the server so it is right
     without JavaScript; the script only keeps it from going stale on a tab left
     open, and pins it to Manila whatever the device clock says. --}}
@php($authNow = now('Asia/Manila'))
<p class="hidden text-right text-xs leading-tight tabular-nums opacity-70 sm:block" id="authClock">
    <span class="block" data-clock-date>{{ $authNow->format('D, M j') }}</span>
    <strong class="block text-sm font-semibold" data-clock-time>{{ $authNow->format('g:i A') }}</strong>
</p>

<script>
    (function () {
        var el = document.getElementById('authClock');
        if (!el || !window.Intl) return;

        var date = el.querySelector('[data-clock-date]');
        var time = el.querySelector('[data-clock-time]');
        var fmtDate = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric' });
        var fmtTime = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit' });

        function tick() {
            var now = new Date();
            date.textContent = fmtDate.format(now);
            time.textContent = fmtTime.format(now);
        }

        tick();
        setInterval(tick, 15000);
    })();
</script>
