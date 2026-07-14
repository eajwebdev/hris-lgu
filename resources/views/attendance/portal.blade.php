<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    {{-- viewport-fit=cover + the safe-area insets below keep the action bar clear
         of the iPhone home indicator when this runs full-screen or in a WebView. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1220">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="robots" content="noindex, nofollow">

    <title>Attendance — LGU Mabinay</title>

    <link rel="shortcut icon" href="{{ asset('mabinay-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">

    <style>
        :root {
            --green:      #1E7A45;
            --green-dark: #10502C;
            --amber:      #EF9017;
            --ink:        #0B1220;
            --ink-soft:   #131C2E;
            --line:       rgba(255, 255, 255, .10);
            --text:       #F8FAFC;
            --muted:      #94A3B8;
            --danger:     #EF4444;
            --ok:         #22C55E;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: var(--ink);
            color: var(--text);
            font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
            overscroll-behavior: none;
        }

        /* 100dvh, not 100vh: mobile browser chrome collapses and vh does not
           follow it, which pushes the action bar off the bottom of the screen. */
        .portal {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            max-width: 560px;
            margin: 0 auto;
            position: relative;
        }

        /* ---------------------------------------------------------------- header */

        .top {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: calc(env(safe-area-inset-top) + 12px) 16px 12px;
            flex: 0 0 auto;
        }
        .top__seal   { width: 34px; height: 34px; object-fit: contain; }
        .top__title  { font-size: 13px; font-weight: 700; letter-spacing: .04em; line-height: 1.2; }
        .top__sub    { font-size: 10px; color: var(--muted); letter-spacing: .08em; text-transform: uppercase; }
        .top__clock  { margin-left: auto; text-align: right; }
        .top__time   { font-size: 17px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .top__date   { font-size: 10px; color: var(--muted); }

        /* ---------------------------------------------------------------- stage */

        .stage {
            position: relative;
            flex: 1 1 auto;
            margin: 0 16px;
            border-radius: 22px;
            overflow: hidden;
            background: #000;
            min-height: 0; /* lets the flex child actually shrink on short screens */
        }
        .stage video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        /* Mirrored for the face camera so people turn the way they expect. Undone
           for the rear camera, where a mirrored QR view is disorienting. */
        .stage--mirror video { transform: scaleX(-1); }

        .stage canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        .stage--mirror canvas { transform: scaleX(-1); }

        /* Framing guide. Purely an aiming aid — nothing is judged from it. */
        .guide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .guide__oval {
            width: 62%;
            aspect-ratio: 3 / 4;
            border: 3px dashed rgba(255, 255, 255, .35);
            border-radius: 50%;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .guide--ok .guide__oval {
            border-color: var(--ok);
            border-style: solid;
            box-shadow: 0 0 0 9999px rgba(34, 197, 94, .10);
        }
        .guide__box {
            width: 66%;
            aspect-ratio: 1;
            border-radius: 20px;
            border: 3px solid rgba(255, 255, 255, .55);
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, .45);
        }

        /* Sits over the video, above the guide, below the veil. */
        .cue {
            position: absolute;
            top: 14px;
            left: 14px;
            right: 14px;
            z-index: 4;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(11, 18, 32, .86);
            border: 1px solid var(--line);
            backdrop-filter: blur(8px);
            font-size: 14px;
            font-weight: 700;
            text-align: center;
        }
        .cue i { font-size: 17px; color: var(--amber); }

        /* The arrow nudges toward the side we are asking them to turn. */
        .cue--turn i { animation: nudge 1s ease-in-out infinite; }
        @keyframes nudge {
            0%, 100% { transform: translateX(0); }
            50%      { transform: translateX(5px); }
        }
        .cue--turn .fa-arrow-left { animation-name: nudge-left; }
        @keyframes nudge-left {
            0%, 100% { transform: translateX(0); }
            50%      { transform: translateX(-5px); }
        }

        .veil {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 10px;
            text-align: center;
            padding: 24px;
            background: rgba(11, 18, 32, .92);
            font-size: 13px;
            line-height: 1.5;
            z-index: 5;
        }

        /* ---------------------------------------------------------------- hint */

        .hint {
            flex: 0 0 auto;
            margin: 12px 16px 0;
            padding: 11px 14px;
            border-radius: 12px;
            background: var(--ink-soft);
            border: 1px solid var(--line);
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 9px;
            min-height: 44px;
        }
        .hint--ok  { background: rgba(34, 197, 94, .12); border-color: rgba(34, 197, 94, .35); color: #86EFAC; }
        .hint--bad { background: rgba(239, 68, 68, .10); border-color: rgba(239, 68, 68, .30); color: #FCA5A5; }

        /* ---------------------------------------------------------------- controls */

        .controls {
            flex: 0 0 auto;
            padding: 12px 16px calc(env(safe-area-inset-bottom) + 14px);
        }

        .segmented {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            padding: 4px;
            background: var(--ink-soft);
            border: 1px solid var(--line);
            border-radius: 14px;
            margin-bottom: 10px;
        }
        .segmented button {
            appearance: none;
            border: 0;
            background: transparent;
            color: var(--muted);
            font: inherit;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: .04em;
            padding: 11px 8px;
            border-radius: 10px;
            cursor: pointer;
            transition: background .15s ease, color .15s ease;
        }
        .segmented button[aria-pressed="true"] { background: var(--green); color: #fff; }
        .segmented button[data-action="out"][aria-pressed="true"] { background: var(--amber); color: #1a1200; }

        .primary {
            width: 100%;
            appearance: none;
            border: 0;
            border-radius: 16px;
            padding: 17px;
            font: inherit;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: .05em;
            color: #fff;
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            transition: opacity .15s ease, transform .06s ease;
        }
        .primary:active:not(:disabled) { transform: scale(.985); }
        .primary:disabled { opacity: .40; cursor: not-allowed; }
        .primary--out { background: linear-gradient(135deg, #F59E0B 0%, #B45309 100%); }

        .link {
            display: block;
            width: 100%;
            margin-top: 11px;
            padding: 11px;
            background: none;
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--muted);
            font: inherit;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
        }
        .link:active { background: var(--ink-soft); }

        /* ---------------------------------------------------------------- name card */

        .named {
            position: absolute;
            left: 16px;
            right: 16px;
            bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(11, 18, 32, .88);
            border: 1px solid var(--line);
            backdrop-filter: blur(8px);
            z-index: 4;
        }
        .avatar {
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 15px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
        }
        .named__name { font-weight: 700; font-size: 14.5px; line-height: 1.25; }
        .named__pos  { font-size: 11.5px; color: var(--muted); }

        /* ---------------------------------------------------------------- result */

        .result {
            position: absolute;
            inset: 0;
            z-index: 20;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 32px 24px calc(env(safe-area-inset-bottom) + 32px);
            text-align: center;
            background: var(--ink);
        }
        .result__mark {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 42px;
            background: rgba(34, 197, 94, .14);
            color: var(--ok);
            animation: pop .35s cubic-bezier(.2, 1.4, .4, 1);
        }
        .result--out .result__mark { background: rgba(239, 144, 23, .14); color: var(--amber); }
        @keyframes pop { from { transform: scale(.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .result__name   { font-size: 21px; font-weight: 800; }
        .result__pos    { font-size: 12.5px; color: var(--muted); margin-top: -8px; }
        .result__action { font-size: 12px; font-weight: 700; letter-spacing: .12em; color: var(--ok); }
        .result--out .result__action { color: var(--amber); }
        .result__time   { font-size: 34px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .result__date   { font-size: 12px; color: var(--muted); margin-top: -10px; }
        .result__note   { font-size: 12px; color: var(--muted); margin-top: 6px; }

        .d-none { display: none !important; }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<div class="portal">

    <header class="top">
        <img class="top__seal" src="{{ asset('mabinay-logo.png') }}" alt="">
        <div>
            <div class="top__title">MUNICIPALITY OF MABINAY</div>
            <div class="top__sub">Attendance</div>
        </div>
        <div class="top__clock">
            <div class="top__time" id="clock">--:--:--</div>
            <div class="top__date" id="today">&nbsp;</div>
        </div>
    </header>

    <main class="stage stage--mirror" id="stage">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="overlay"></canvas>

        <div class="guide" id="guide">
            <div class="guide__oval" id="guide-oval"></div>
            <div class="guide__box d-none" id="guide-box"></div>
        </div>

        {{-- Shown after a QR scan resolves, so the person sees their name before
             the face step rather than after it. --}}
        <div class="named d-none" id="named">
            <div class="avatar" id="named-initials">--</div>
            <div>
                <div class="named__name" id="named-name">—</div>
                <div class="named__pos" id="named-pos">—</div>
            </div>
        </div>

        {{-- The head-turn prompt. Guidance only — the server decides whether the
             turn actually happened, by comparing the frame against the employee's
             enrolled left/right captures. --}}
        <div class="cue d-none" id="cue">
            <i class="fas fa-user" id="cue-icon"></i>
            <span id="cue-text">Look straight at the camera</span>
        </div>

        <div class="veil" id="veil">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <div id="veil-text">Starting camera…</div>
        </div>
    </main>

    <div class="hint" id="hint">
        <i class="fas fa-circle-notch fa-spin" id="hint-icon"></i>
        <span id="hint-text">Getting ready…</span>
    </div>

    <div class="controls">
        <div class="segmented" role="group" aria-label="Attendance action">
            <button type="button" data-action="in"  aria-pressed="true">CLOCK IN</button>
            <button type="button" data-action="out" aria-pressed="false">CLOCK OUT</button>
        </div>

        <button type="button" class="primary" id="go" disabled>
            <i class="fas fa-camera"></i>
            <span id="go-text">CLOCK IN</span>
        </button>

        <button type="button" class="link" id="mode-toggle">
            <i class="fas fa-qrcode"></i>&nbsp; Scan QR first — faster and more accurate
        </button>
    </div>

    {{-- Result takes over the whole screen, then hands it back. --}}
    <div class="result d-none" id="result">
        <div class="result__mark" id="result-mark"><i class="fas fa-check"></i></div>
        <div class="result__action" id="result-action">CLOCK IN</div>
        <div class="result__name" id="result-name">—</div>
        <div class="result__pos"  id="result-pos">—</div>
        <div class="result__time" id="result-time">—</div>
        <div class="result__date" id="result-date">—</div>
        <div class="result__note" id="result-note"></div>
    </div>

</div>

@php
    $portalConfig = [
        'modelsUrl'  => $modelsUrl,
        'urls'       => [
            'punch'     => route('attendancePunch'),
            'qrCheck'   => route('attendanceQrCheck'),
            'challenge' => route('attendanceChallenge'),
        ],
        'resetAfter' => (int) config('attendance.portal.reset_after', 5),
        'thresholds' => config('face.client'),
        // Only how many frames to gather. Every threshold that decides whether the
        // face is alive stays on the server, where it cannot be edited.
        'liveness'   => [
            'min_neutral_frames' => (int) config('face.liveness.min_neutral_frames', 3),
        ],
    ];
@endphp
<script id="portal-config" type="application/json">@json($portalConfig)</script>

<script src="{{ asset('js/face-api/face-api.min.js') }}"></script>
<script src="{{ asset('js/jsqr/jsQR.min.js') }}"></script>
@include('attendance.portal-script')

</body>
</html>
