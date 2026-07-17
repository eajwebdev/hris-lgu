@extends('layouts.master')

@section('body')
<style>
    :root {
        --scan-green: #187744;
        --scan-green-600: #136038;
        --scan-green-050: #f0fdf4;
        --scan-line: #e5e7eb;
        --scan-muted: #6b7280;
        --scan-amber: #b45309;
        --scan-red: #b91c1c;
    }
    .scan-wrap { max-width: 1120px; margin: 0 auto; }
    .scan-head { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .scan-head h1 { font-size: 1.4rem; font-weight: 800; margin: 0; letter-spacing: -.01em; }
    .scan-head .sub { color: var(--scan-muted); font-size: .85rem; }
    .scan-head .pick { margin-left: auto; display: flex; align-items: center; gap: 8px; }
    .scan-head select {
        border: 1px solid var(--scan-line); border-radius: 10px; padding: 9px 12px;
        font: 600 .88rem 'Inter', sans-serif; min-width: 260px; background: #fff; outline: none;
    }
    .scan-head select:focus { border-color: var(--scan-green); box-shadow: 0 0 0 3px rgba(24,119,68,.14); }

    .scan-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 18px; align-items: start; }
    @media (max-width: 900px) { .scan-grid { grid-template-columns: 1fr; } }

    /* camera stage */
    .stage {
        position: relative; background: #0b1220; border-radius: 16px; overflow: hidden;
        aspect-ratio: 4 / 3; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 12px 34px rgba(4,20,12,.18);
    }
    .stage video { width: 100%; height: 100%; object-fit: cover; }
    .stage__reticle {
        position: absolute; width: 58%; max-width: 320px; aspect-ratio: 1;
        border-radius: 22px; box-shadow: 0 0 0 3px rgba(255,255,255,.85), 0 0 0 2000px rgba(6,14,26,.42);
        transition: box-shadow .12s;
    }
    .stage.is-hit .stage__reticle { box-shadow: 0 0 0 4px #22c55e, 0 0 0 2000px rgba(6,14,26,.42); }
    .stage__scanline {
        position: absolute; width: 58%; max-width: 320px; height: 2px;
        background: linear-gradient(90deg, transparent, #22c55e, transparent);
        animation: sweep 2.4s ease-in-out infinite; opacity: .9;
    }
    @keyframes sweep { 0%,100% { transform: translateY(-150px); } 50% { transform: translateY(150px); } }
    .stage__veil {
        position: absolute; inset: 0; display: flex; flex-direction: column; gap: 10px;
        align-items: center; justify-content: center; text-align: center;
        color: #e2e8f0; background: #0b1220; padding: 24px; font-size: .9rem;
    }
    .stage__veil i { font-size: 2rem; opacity: .8; }
    .stage__hint {
        position: absolute; left: 50%; bottom: 14px; transform: translateX(-50%);
        background: rgba(8,15,26,.72); color: #fff; border-radius: 999px;
        padding: 7px 16px; font-size: .82rem; font-weight: 600; white-space: nowrap;
        display: flex; align-items: center; gap: 8px; backdrop-filter: blur(4px);
    }
    .stage__hint.is-ok  { background: rgba(21,96,56,.9); }
    .stage__hint.is-bad { background: rgba(153,27,27,.9); }

    /* result flash */
    .flash {
        position: absolute; inset: 0; display: none; flex-direction: column;
        align-items: center; justify-content: center; text-align: center;
        color: #fff; padding: 24px; z-index: 3;
    }
    .flash.show { display: flex; animation: pop .18s ease; }
    @keyframes pop { from { transform: scale(.94); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .flash--in  { background: rgba(21,96,56,.96); }
    .flash--out { background: rgba(30,58,138,.96); }
    .flash--dup { background: rgba(120,53,15,.96); }
    .flash--bad { background: rgba(120,20,20,.96); }
    .flash__avatar {
        width: 78px; height: 78px; border-radius: 50%; background: rgba(255,255,255,.16);
        display: flex; align-items: center; justify-content: center; font-size: 1.7rem; font-weight: 800;
        margin-bottom: 12px; border: 2px solid rgba(255,255,255,.4);
    }
    .flash__action { font-size: 1.5rem; font-weight: 800; letter-spacing: .02em; }
    .flash__name { font-size: 1.05rem; font-weight: 600; margin-top: 4px; }
    .flash__pos { font-size: .82rem; opacity: .85; }
    .flash__time { margin-top: 10px; font-size: .9rem; font-weight: 700; background: rgba(255,255,255,.18); border-radius: 999px; padding: 5px 16px; }

    /* side panel */
    .panel { background: #fff; border: 1px solid var(--scan-line); border-radius: 16px; overflow: hidden; }
    .panel__head { padding: 14px 18px; border-bottom: 1px solid var(--scan-line); display: flex; align-items: center; gap: 10px; }
    .panel__head h3 { font-size: .95rem; font-weight: 700; margin: 0; }
    .panel__head .live { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; font-size: .72rem; color: var(--scan-muted); font-weight: 600; }
    .panel__head .live .dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 0 rgba(34,197,94,.6); animation: pulse 1.6s infinite; }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(34,197,94,.5); } 70% { box-shadow: 0 0 0 8px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); } }

    .tallies { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--scan-line); }
    .tally { background: #fff; padding: 14px 18px; text-align: center; }
    .tally b { display: block; font-size: 1.6rem; font-weight: 800; line-height: 1; }
    .tally.in b  { color: var(--scan-green); }
    .tally.out b { color: #1e3a8a; }
    .tally span { font-size: .72rem; color: var(--scan-muted); text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }

    .feed { max-height: 420px; overflow-y: auto; }
    .feed__empty { padding: 40px 18px; text-align: center; color: var(--scan-muted); font-size: .84rem; }
    .feed__empty i { font-size: 1.6rem; display: block; margin-bottom: 8px; color: #cbd5e1; }
    .feed__item { display: flex; align-items: center; gap: 11px; padding: 11px 18px; border-bottom: 1px solid #f1f5f4; }
    .feed__item:last-child { border-bottom: none; }
    .feed__badge { width: 38px; height: 38px; border-radius: 50%; flex: none; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .78rem; color: #fff; }
    .feed__badge.in  { background: var(--scan-green); }
    .feed__badge.out { background: #1e3a8a; }
    .feed__main { min-width: 0; flex: 1; }
    .feed__name { font-size: .86rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .feed__meta { font-size: .74rem; color: var(--scan-muted); }
    .feed__tag { font-size: .68rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; flex: none; }
    .feed__tag.in  { background: var(--scan-green-050); color: var(--scan-green); }
    .feed__tag.out { background: #eef2ff; color: #1e3a8a; }

    .scan-note { margin-top: 14px; font-size: .78rem; color: var(--scan-muted); display: flex; align-items: center; gap: 8px; }
    .scan-note i { color: var(--scan-green); }

    .noevent {
        border: 1px dashed #cbd5d1; border-radius: 16px; background: #fff;
        padding: 54px 24px; text-align: center; color: var(--scan-muted);
    }
    .noevent i { font-size: 2rem; color: #9ca3af; display: block; margin-bottom: 10px; }

    /* ---------------------------------------------------------- phone view */
    @media (max-width: 640px) {
        .scan-wrap { max-width: 100%; }
        .container-fluid { padding-left: 10px; padding-right: 10px; }

        .scan-head { flex-direction: column; align-items: stretch; gap: 10px; margin-bottom: 12px; }
        .scan-head h1 { font-size: 1.15rem; }
        .scan-head .pick { margin-left: 0; }
        .scan-head select { flex: 1; min-width: 0; width: 100%; }

        .scan-grid { gap: 12px; }

        /* The camera was 4/3 at full width — on a phone that's a tall block that
           shoves the tally + feed off-screen. Cap it to part of the viewport so
           the whole page still fits, and let the reticle track that height. */
        .stage { aspect-ratio: auto; height: 46vh; max-height: 380px; min-height: 240px; }
        .stage__reticle,
        .stage__scanline { width: 66%; max-width: 240px; }

        .scan-note { margin-top: 10px; font-size: .74rem; }

        .feed { max-height: 240px; }
        .tally b { font-size: 1.35rem; }
    }
</style>

<div class="container-fluid">
    <div class="scan-wrap">

        <div class="scan-head">
            <div>
                <h1><i class="fas fa-qrcode text-success1"></i> Event QR Attendance</h1>
            </div>
            <div class="pick">
                <label for="eventPick" class="mb-0 mr-1" style="font-weight:600; font-size:.85rem;">Event:</label>
                <select id="eventPick">
                    <option value="" disabled {{ $events->isEmpty() ? 'selected' : '' }}>— select an event —</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}" @if($loop->first) selected @endif
                            data-venue="{{ $ev->venue }}"
                            data-when="{{ \Carbon\Carbon::parse($ev->start)->format('M d, Y') }}">
                            {{ $ev->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($events->isEmpty())
            <div class="noevent">
                <i class="fas fa-calendar-xmark"></i>
                <p><strong>No active events.</strong></p>
                <p>Create an event first, then come back here to scan attendance.</p>
                <a href="{{ route('eventIndex') }}" class="btn btn-success1 btn-sm mt-2"><i class="fas fa-calendar-plus"></i> Go to Events</a>
            </div>
        @else
        <div class="scan-grid">
            {{-- camera --}}
            <div>
                <div class="stage" id="stage">
                    <video id="video" playsinline muted></video>
                    <div class="stage__reticle"></div>
                    <div class="stage__scanline"></div>
                    <div class="stage__hint" id="hint"><i class="fas fa-camera"></i> Starting camera…</div>

                    <div class="flash" id="flash">
                        <div class="flash__avatar" id="flash-avatar">--</div>
                        <div class="flash__action" id="flash-action">CLOCK IN</div>
                        <div class="flash__name" id="flash-name">—</div>
                        <div class="flash__pos" id="flash-pos">—</div>
                        <div class="flash__time" id="flash-time">—</div>
                    </div>

                    <div class="stage__veil" id="veil">
                        <i class="fas fa-video"></i>
                        <div id="veil-text">Allow camera access to begin scanning.</div>
                    </div>
                </div>
                <div class="scan-note">
                    <i class="fas fa-shield-halved"></i>
                    Hold the badge steady inside the frame. Each employee must be on the event's attendee list.
                </div>
            </div>

            {{-- panel --}}
            <div class="panel">
                <div class="panel__head">
                    <h3>This session</h3>
                    <span class="live"><span class="dot"></span> Live</span>
                </div>
                <div class="tallies">
                    <div class="tally in"><b id="count-in">0</b><span>Clocked in</span></div>
                    <div class="tally out"><b id="count-out">0</b><span>Clocked out</span></div>
                </div>
                <div class="feed" id="feed">
                    <div class="feed__empty" id="feed-empty">
                        <i class="fas fa-clock-rotate-left"></i>
                        Scans will appear here as you record them.
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@if(!$events->isEmpty())
<script src="{{ asset('js/jsqr/jsQR.min.js') }}"></script>
<script>
(function () {
    'use strict';

    var CSRF     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var PUNCH_URL = "{{ route('eventScanPunch') }}";

    var el = {
        stage:  document.getElementById('stage'),
        video:  document.getElementById('video'),
        hint:   document.getElementById('hint'),
        veil:   document.getElementById('veil'),
        veilText: document.getElementById('veil-text'),
        flash:  document.getElementById('flash'),
        fAvatar: document.getElementById('flash-avatar'),
        fAction: document.getElementById('flash-action'),
        fName:  document.getElementById('flash-name'),
        fPos:   document.getElementById('flash-pos'),
        fTime:  document.getElementById('flash-time'),
        pick:   document.getElementById('eventPick'),
        feed:   document.getElementById('feed'),
        feedEmpty: document.getElementById('feed-empty'),
        countIn:  document.getElementById('count-in'),
        countOut: document.getElementById('count-out'),
    };

    var state = {
        stream:  null,
        busy:    false,          // a punch is in flight
        lastToken: null,         // last scanned value
        lastAt:  0,              // when we last acted on it
        inCount: 0,
        outCount: 0,
    };

    var scratch = document.createElement('canvas');
    var sctx    = scratch.getContext('2d', { willReadFrequently: true });

    var barcodeDetector = null;
    if ('BarcodeDetector' in window) {
        try { barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { /* jsQR fallback */ }
    }

    function setHint(text, tone) {
        el.hint.innerHTML = (tone === 'ok' ? '<i class="fas fa-check-circle"></i> '
                          : tone === 'bad' ? '<i class="fas fa-exclamation-circle"></i> '
                          : '<i class="fas fa-qrcode"></i> ') + text;
        el.hint.className = 'stage__hint' + (tone ? ' is-' + tone : '');
    }

    // ---------------------------------------------------------------- camera
    async function startCamera() {
        stopCamera();
        try {
            state.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
        } catch (e) {
            try {
                state.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            } catch (inner) { return cameraError(inner); }
        }

        el.video.srcObject = state.stream;
        try { await el.video.play(); } catch (e) { /* autoplay race; loop copes */ }
        el.veil.style.display = 'none';
        setHint('Point the camera at an employee QR badge', null);
    }

    function stopCamera() {
        if (state.stream) { state.stream.getTracks().forEach(function (t) { t.stop(); }); state.stream = null; }
        el.video.srcObject = null;
    }

    function cameraError(e) {
        el.veil.style.display = 'flex';
        if (e && (e.name === 'NotAllowedError' || e.name === 'SecurityError')) {
            el.veilText.textContent = 'Camera permission was denied. Allow camera access, then reload this page.';
        } else if (e && e.name === 'NotFoundError') {
            el.veilText.textContent = 'No camera was found on this device.';
        } else if (e && e.name === 'NotReadableError') {
            el.veilText.textContent = 'The camera is in use by another app. Close it and reload.';
        } else {
            el.veilText.textContent = 'Could not start the camera: ' + (e && (e.message || e.name) || 'unknown error');
        }
    }

    // ---------------------------------------------------------------- QR read
    async function readQr() {
        var v = el.video;
        if (!v.videoWidth) return null;

        if (barcodeDetector) {
            try {
                var found = await barcodeDetector.detect(v);
                return found.length ? found[0].rawValue : null;
            } catch (e) { barcodeDetector = null; /* fall through to jsQR */ }
        }

        var w = 480;
        var h = Math.round(v.videoHeight * (w / v.videoWidth));
        scratch.width = w; scratch.height = h;
        sctx.drawImage(v, 0, 0, w, h);
        var img = sctx.getImageData(0, 0, w, h);
        var code = jsQR(img.data, w, h, { inversionAttempts: 'dontInvert' });
        return code ? code.data : null;
    }

    async function loop() {
        if (el.video.readyState === 4 && !state.busy) {
            try {
                var raw = await readQr();
                if (raw) await onScan(raw);
            } catch (e) { console.error('scan frame failed', e); }
        }
        requestAnimationFrame(function () { setTimeout(loop, 120); });
    }

    // ---------------------------------------------------------------- punch
    async function onScan(raw) {
        var now = Date.now();

        // Debounce: the same badge held in frame fires many times a second.
        // Ignore a repeat of the same value within 3.5s.
        if (raw === state.lastToken && (now - state.lastAt) < 3500) return;

        state.lastToken = raw;
        state.lastAt = now;

        var eventId = el.pick.value;
        if (!eventId) { setHint('Select an event first', 'bad'); return; }

        state.busy = true;
        setHint('Reading badge…', null);

        try {
            var response = await fetch(PUNCH_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ event_id: eventId, qr: raw }),
            });

            var body = await response.json().catch(function () { return {}; });

            if (!response.ok) {
                showBad(body.message || 'Could not record this scan.');
            } else {
                showResult(body);
            }
        } catch (e) {
            showBad('Could not reach the server. Check the connection.');
        } finally {
            // Brief hold so the operator reads the flash, and so the same badge
            // still in view is not instantly re-read.
            setTimeout(function () { state.busy = false; hideFlash(); }, 1400);
        }
    }

    function showResult(body) {
        var emp = body.employee || {};
        var action = body.action || 'CLOCK IN';
        var isOut = action === 'CLOCK OUT';
        var dup = body.recorded === false;

        el.flash.className = 'flash show ' + (dup ? 'flash--dup' : isOut ? 'flash--out' : 'flash--in');
        el.fAvatar.textContent = emp.initials || '--';
        el.fAction.textContent = dup ? 'ALREADY SCANNED' : action;
        el.fName.textContent = emp.name || '—';
        el.fPos.textContent = emp.position || '';
        el.fTime.textContent = body.time || '';

        setHint((dup ? 'Already recorded — ' : action + ' — ') + (emp.name || ''), 'ok');
        flashStage();

        if (!dup) {
            if (isOut) { state.outCount++; el.countOut.textContent = state.outCount; }
            else       { state.inCount++;  el.countIn.textContent  = state.inCount;  }
            addFeed(emp, action, body.time);
        }
    }

    function showBad(message) {
        el.flash.className = 'flash show flash--bad';
        el.fAvatar.textContent = '!';
        el.fAction.textContent = 'NOT RECORDED';
        el.fName.textContent = message;
        el.fPos.textContent = '';
        el.fTime.textContent = '';
        setHint(message, 'bad');
    }

    function hideFlash() {
        el.flash.className = 'flash';
        var ev = el.pick.options[el.pick.selectedIndex];
        setHint('Point the camera at an employee QR badge', null);
    }

    function flashStage() {
        el.stage.classList.add('is-hit');
        setTimeout(function () { el.stage.classList.remove('is-hit'); }, 300);
    }

    function addFeed(emp, action, time) {
        if (el.feedEmpty) el.feedEmpty.style.display = 'none';
        var isOut = action === 'CLOCK OUT';
        var row = document.createElement('div');
        row.className = 'feed__item';
        row.innerHTML =
            '<div class="feed__badge ' + (isOut ? 'out' : 'in') + '"></div>' +
            '<div class="feed__main">' +
                '<div class="feed__name"></div>' +
                '<div class="feed__meta">' + (time || '') + (emp.id ? ' · ' + emp.id : '') + '</div>' +
            '</div>' +
            '<span class="feed__tag ' + (isOut ? 'out' : 'in') + '">' + (isOut ? 'OUT' : 'IN') + '</span>';
        // Set name/initials as text (never HTML) — employee data is trusted-ish
        // but this keeps the feed injection-proof.
        row.querySelector('.feed__badge').textContent = emp.initials || '';
        row.querySelector('.feed__name').textContent = emp.name || '';
        el.feed.insertBefore(row, el.feed.firstChild);

        // Cap the visible list.
        while (el.feed.querySelectorAll('.feed__item').length > 40) {
            el.feed.removeChild(el.feed.lastChild);
        }
    }

    // ---------------------------------------------------------------- boot
    el.pick.addEventListener('change', function () {
        state.lastToken = null; // let the same person be scanned for a new event
        setHint('Scanning for: ' + el.pick.options[el.pick.selectedIndex].text, null);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stopCamera();
        else if (!state.stream) startCamera();
    });

    (async function boot() {
        if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            el.veil.style.display = 'flex';
            el.veilText.textContent = 'The camera needs a secure connection. Open this page over https:// and try again.';
            return;
        }
        await startCamera();
        loop();
    })();
})();
</script>
@endif
@endsection
