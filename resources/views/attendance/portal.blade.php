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

    {{-- Stub for the Android WebView's native location bridge. The wrapper app
         pushes fixes with evaluateJavascript("window.setPortalLocation(...)") and
         may fire before the main script at the end of <body> has parsed — this
         buffers such a fix so it is consumed, not lost, the moment the real
         implementation replaces this stub. --}}
    <script>
        window.setPortalLocation = function (lat, lng, accuracy) {
            window.__pendingGeo = [lat, lng, accuracy];
            return true;
        };
    </script>

    <title>Attendance — LGU Mabinay</title>

    <link rel="shortcut icon" href="{{ asset('Uploads/time_entry.png') }}">
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
            /* Installed as a web app, so it is a FIXED ONE-SCREEN surface, not a
               document. Nothing here scrolls: the readout floats over the video
               rather than taking its own row, which is what lets the column
               always fit. overscroll-behavior below additionally kills the
               rubber-band bounce that makes a standalone PWA feel like a
               web page. */
            height: 100%;
            overflow: hidden;
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
            height: 100vh;
            height: 100dvh; /* newer engines; the vh line above is the fallback */
            max-width: 560px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;   /* one screen, always — nothing scrolls */
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
            /* Back to 0. The readout floats over the video again rather than
               taking a row of its own, so the stage is free to absorb whatever
               a short screen leaves it — which is what guarantees the column
               fits in one viewport and never scrolls. */
            min-height: 0;
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

        /* Framing reticle. Purely an aiming aid — nothing is judged from it.
           A modern animated frame instead of a hard box: a faint guide outline,
           four glowing corner brackets, and a sweeping scan line that settles
           into a soft lock the moment the face (or QR) is ready. */
        .guide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .reticle { position: relative; display: block; }
        .reticle--face { width: 62%; aspect-ratio: 3 / 4; }
        .reticle--qr   { width: 66%; aspect-ratio: 1; }

        /* faint inner guide outline */
        .reticle::after {
            content: '';
            position: absolute;
            inset: 7%;
            border: 1.5px solid rgba(255, 255, 255, .16);
            border-radius: 22px;
            transition: border-color .3s ease, box-shadow .3s ease;
        }
        .reticle--face::after { border-radius: 50%; }

        .reticle__corner {
            position: absolute;
            width: 34px;
            height: 34px;
            border: 3px solid #DBF4FF;
            filter: drop-shadow(0 0 6px rgba(56, 224, 255, .7));
            transition: border-color .25s ease, filter .25s ease;
        }
        .reticle__corner--tl { top: -2px; left: -2px;  border-right: 0; border-bottom: 0; border-top-left-radius: 16px; }
        .reticle__corner--tr { top: -2px; right: -2px; border-left: 0;  border-bottom: 0; border-top-right-radius: 16px; }
        .reticle__corner--bl { bottom: -2px; left: -2px;  border-right: 0; border-top: 0; border-bottom-left-radius: 16px; }
        .reticle__corner--br { bottom: -2px; right: -2px; border-left: 0;  border-top: 0; border-bottom-right-radius: 16px; }

        .reticle__scan {
            position: absolute;
            left: 6%;
            right: 6%;
            top: 6%;
            height: 2px;
            border-radius: 2px;
            background: linear-gradient(90deg, transparent, rgba(56, 224, 255, .95), transparent);
            box-shadow: 0 0 14px rgba(56, 224, 255, .85);
            animation: reticle-scan 2.6s cubic-bezier(.45, 0, .55, 1) infinite;
        }
        @keyframes reticle-scan {
            0%   { top: 6%;  opacity: 0; }
            12%  { opacity: 1; }
            88%  { opacity: 1; }
            100% { top: 92%; opacity: 0; }
        }

        /* Ready: corners turn green and the outline gives one soft pulse; the
           scan line steps aside. A calm lock, not a hard green box. */
        .guide--ok .reticle__corner {
            border-color: var(--ok);
            filter: drop-shadow(0 0 9px rgba(34, 197, 94, .9));
        }
        .guide--ok .reticle__scan { opacity: 0; }
        .guide--ok .reticle::after {
            border-color: rgba(34, 197, 94, .55);
            box-shadow: 0 0 26px rgba(34, 197, 94, .35);
            animation: reticle-lock .45s ease;
        }
        @keyframes reticle-lock {
            0%   { transform: scale(1); }
            45%  { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* Sits over the video, above the guide, below the veil. */
        .cue {
            position: absolute;
            top: 14px;
            left: 14px;
            /* Stops short of the top-right corner so it never runs underneath
               the round icon button parked there — whichever one that is: the
               camera switch normally, or the map button when the switch is
               hidden because the badge is mandatory. */
            right: 68px;
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
            white-space: pre-line; /* honour \n in status/error messages */
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

        /* The action buttons. Each tap captures the face and writes the punch
           directly — there is no separate "confirm" step. In is green, out amber
           and overtime purple, so the choice reads at a glance across a room.
           IN and OUT share the full-size top row; OVERTIME spans both columns
           beneath them, which keeps the two daily actions large and lets the
           occasional one stay legible instead of squeezing all three into
           thirds of a phone-width screen. */
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .action {
            appearance: none;
            border: 0;
            border-radius: 16px;
            padding: 18px 12px;
            font: inherit;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: .04em;
            color: #fff;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: opacity .15s ease, transform .06s ease;
        }
        .action i { font-size: 20px; }
        .action--in  { background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%); }
        .action--out { background: linear-gradient(135deg, #F59E0B 0%, #B45309 100%); color: #1a1200; }
        /* Overtime spans both columns on its own row — the two daily actions
           keep the full-size top row, and OT reads as the secondary choice
           without being hidden. Purple matches the 'OT' punch pill on the
           employee dashboard, so the same action is the same colour in both
           places. */
        .action--ot  {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #7C5CD6 0%, #4C3193 100%);
            flex-direction: row;
            gap: 10px;
            padding: 14px 12px;
            font-size: 14px;
        }
        .action--ot i { font-size: 17px; }
        .action:active:not(:disabled) { transform: scale(.97); }
        .action:disabled { opacity: .40; cursor: not-allowed; }

        /* Camera / QR switch — an icon button pinned to the top-right corner of
           the live camera, out of the framing guide's way. */
        .camswap {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 6;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--line);
            background: rgba(11, 18, 32, .78);
            color: var(--text);
            font-size: 17px;
            display: grid;
            place-items: center;
            cursor: pointer;
            backdrop-filter: blur(8px);
        }
        .camswap:active:not(:disabled) { transform: scale(.94); }
        .camswap:disabled { opacity: .35; cursor: not-allowed; }

        /* Nearest-station map — a second round icon button tucked directly under
           the camera switch. Opens the animated station map so the employee can
           see which site is closest and which way to walk to be in range. */
        /* Sits under the camera switch — unless that switch is hidden (badge
           mandatory, so there is no face-only mode to offer), in which case it
           takes the vacated top slot instead of floating below an empty gap.
           A sibling rule rather than a JS class: .camswap is rendered with
           d-none server-side and toggled by setMode(), and this follows either
           way without the two having to be kept in step. */
        .mapbtn {
            position: absolute;
            top: 68px;   /* camswap top (14) + its height (44) + a 10px gap */
            right: 14px;
            z-index: 6;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--line);
            background: rgba(11, 18, 32, .78);
            color: var(--text);
            font-size: 16px;
            display: grid;
            place-items: center;
            cursor: pointer;
            backdrop-filter: blur(8px);
        }
        .mapbtn::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            border: 2px solid rgba(56, 224, 255, .6);
            animation: mapbtn-pulse 2.4s ease-out infinite;
        }
        @keyframes mapbtn-pulse {
            0%   { transform: scale(1);   opacity: .7; }
            70%  { transform: scale(1.35); opacity: 0; }
            100% { transform: scale(1.35); opacity: 0; }
        }
        .mapbtn:active:not(:disabled) { transform: scale(.94); }
        .camswap.d-none ~ .mapbtn { top: 14px; }

        /* Today's punches, third in the corner column beneath the map button.
           Same 44px round shape, but no pulse ring — one thing on this screen
           asking for attention is enough, and the map's ring is the one that
           matters (it is telling you whether you are in range). */
        .histbtn {
            position: absolute;
            top: 122px;  /* mapbtn top (68) + its height (44) + a 10px gap */
            right: 14px;
            z-index: 6;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--line);
            background: rgba(11, 18, 32, .78);
            color: var(--text);
            font-size: 16px;
            display: grid;
            place-items: center;
            cursor: pointer;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .histbtn:active:not(:disabled) { transform: scale(.94); }
        /* Follows the map button up when the camera switch is hidden. */
        .camswap.d-none ~ .histbtn { top: 68px; }
        /* Only meaningful once a badge names somebody — there is no "today" to
           show before that. JS un-hides it when the QR resolves. */
        .histbtn.d-none { display: none; }

        /* When the capture cue banner is up it owns the top strip; the switches
           step aside rather than sitting on the text. */
        .cue:not(.d-none) ~ .camswap,
        .cue:not(.d-none) ~ .histbtn,
        .cue:not(.d-none) ~ .mapbtn { display: none; }

        /* ------------------------------------------------------------ map sheet */

        .mapsheet {
            position: absolute;
            inset: 0;
            z-index: 30;
            display: flex;
            flex-direction: column;
            background: radial-gradient(120% 90% at 50% 0%, #10213B 0%, #0B1220 55%, #070C16 100%);
            animation: sheet-in .25s ease;
        }
        @keyframes sheet-in {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: none; }
        }
        .mapsheet__top {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: calc(env(safe-area-inset-top) + 14px) 16px 12px;
        }
        .mapsheet__title {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 15px;
            font-weight: 700;
        }
        .mapsheet__title i { color: var(--amber); }
        .mapsheet__close {
            margin-left: auto;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, .06);
            color: var(--text);
            font-size: 16px;
            display: grid;
            place-items: center;
            cursor: pointer;
        }
        .mapsheet__close:active { transform: scale(.94); }
        .mapsheet__stage {
            position: relative;
            flex: 1 1 auto;
            margin: 0 14px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: #070D18;
            min-height: 0;
        }
        .mapsheet__stage canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            display: block;
        }
        /* View switch. Two pills; the active one carries the accent. */
        .mapviews {
            display: flex;
            gap: 8px;
            padding: 0 14px 10px;
        }
        .mapview {
            flex: 1 1 0;
            appearance: none;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255, 255, 255, .05);
            color: var(--muted);
            font: inherit;
            font-size: 12.5px;
            font-weight: 700;
            padding: 9px 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background .15s ease, color .15s ease, border-color .15s ease;
        }
        .mapview i { font-size: 12px; }
        .mapview.is-on {
            background: rgba(56, 224, 255, .14);
            border-color: rgba(56, 224, 255, .45);
            color: #DBF4FF;
        }
        .mapview:active { transform: scale(.98); }

        /* The station list. Capped and scrollable so a municipality with a
           dozen sites cannot push the footer off a phone screen. */
        .stationlist {
            margin: 10px 14px 0;
            max-height: 34vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }
        .stationrow {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            text-align: left;
            appearance: none;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255, 255, 255, .04);
            color: var(--text);
            font: inherit;
            padding: 10px 12px;
            cursor: pointer;
        }
        .stationrow.is-focused {
            border-color: rgba(56, 224, 255, .55);
            background: rgba(56, 224, 255, .10);
        }
        .stationrow__dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex: 0 0 auto;
            background: var(--muted);
        }
        .stationrow.is-in  .stationrow__dot { background: var(--ok); box-shadow: 0 0 8px rgba(34,197,94,.8); }
        .stationrow.is-out .stationrow__dot { background: #FCD34D; }
        .stationrow__name { font-size: 13px; font-weight: 700; line-height: 1.25; }
        .stationrow__meta { font-size: 11px; color: var(--muted); margin-top: 1px; }
        .stationrow__dist {
            margin-left: auto;
            font-size: 12px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            flex: 0 0 auto;
        }
        .stationrow.is-in  .stationrow__dist { color: #86EFAC; }
        .stationrow.is-out .stationrow__dist { color: #FCD34D; }

        .mapsheet__foot {
            padding: 13px 18px calc(env(safe-area-inset-bottom) + 16px);
        }
        .mapsheet__dist { font-size: 16px; font-weight: 800; }
        .mapsheet__sub  { font-size: 12px; color: var(--muted); margin-top: 2px; line-height: 1.4; }
        .mapsheet.is-ok  .mapsheet__dist { color: #86EFAC; }
        .mapsheet.is-far .mapsheet__dist { color: #FCD34D; }

        /* --------------------------------------------------------- history sheet */

        /* Same full-cover sheet as the map, so the two panels feel like one
           idea. Reuses .mapsheet__top / __title / __close for the header rather
           than inventing a parallel set. */
        .histsheet {
            position: absolute;
            inset: 0;
            z-index: 30;
            display: flex;
            flex-direction: column;
            background: radial-gradient(120% 90% at 50% 0%, #10213B 0%, #0B1220 55%, #070C16 100%);
            animation: sheet-in .25s ease;
        }
        .histsheet__who {
            padding: 0 16px 10px;
            font-size: 12px;
            color: var(--muted);
        }
        .histsheet__who strong { color: var(--text); font-size: 13px; }
        /* The one scrollable region in the app. A long day genuinely can run
           past the screen, and this is a panel the employee opened rather than
           the fixed kiosk surface underneath it. */
        .histlist {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0 14px calc(env(safe-area-inset-bottom) + 14px);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .histrow {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border-radius: 14px;
            background: var(--ink-soft);
            border: 1px solid var(--line);
        }
        .histrow__ico {
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 14px;
        }
        /* Colour-matched to the buttons that create them, so a row reads as the
           same action the employee tapped: green in, amber out, purple OT. */
        .histrow--login  .histrow__ico { background: rgba(34, 197, 94, .16);  color: #86EFAC; }
        .histrow--logout .histrow__ico { background: rgba(239, 144, 23, .16); color: var(--amber); }
        .histrow--ot-in  .histrow__ico,
        .histrow--ot-out .histrow__ico { background: rgba(124, 92, 214, .18); color: #A78BFA; }

        .histrow__body { min-width: 0; flex: 1 1 auto; }
        .histrow__what {
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1.3;
        }
        .histrow__where {
            font-size: 11px;
            color: var(--muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .histrow__time {
            flex: 0 0 auto;
            font-size: 14px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .histempty {
            margin: 26px 14px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }
        .histempty i { display: block; font-size: 26px; margin-bottom: 10px; opacity: .5; }

        /* ------------------------------------------------------------ geo HUD */

        /* Live location readout over the bottom of the camera: how far from the
           nearest station, and the raw fix. Courtesy display only — the server
           re-derives all of it at punch time from the same station table. */
        .geohud {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 6px 10px;
            border-radius: 12px;
            background: rgba(11, 18, 32, .82);
            border: 1px solid var(--line);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            font-size: 11px;
        }
        .geohud__row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            min-width: 0;
        }
        .geohud__row i { flex: 0 0 auto; font-size: 10px; }
        /* Takes the space, and gives it up by truncating rather than by
           wrapping — a wrapped station name would grow this panel upward into
           the face, which is the whole thing this layout is avoiding. */
        .geohud__dist {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .geohud__note {
            font-size: 10px;
            color: #FDE68A;
            display: none;
        }
        /* Diagnostic, not something the employee acts on — it exists so HR has
           the raw fix when a punch is disputed. Trails the distance on the SAME
           row (it used to own a line of its own, a third of this panel) and is
           the first thing dropped when there is no room for it. */
        .geohud__coords {
            margin-left: auto;
            flex: 0 1 auto;
            font-size: 9px;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
            letter-spacing: .02em;
            opacity: .7;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 360px) {
            .geohud__coords { display: none; }
        }
        .geohud--ok  .geohud__row { color: #86EFAC; }
        .geohud--far .geohud__row { color: #FCD34D; }
        .geohud--far .geohud__note { display: block; }

        /* ---------------------------------------------------------------- name card */

        /* Floating readout pinned to the bottom edge of the video.
           It overlays the frame — which keeps the app exactly one screen tall
           with nothing to scroll — so the whole job of this block is to stay
           SMALL. A portrait frame puts the face in the middle; anything tall
           here climbs into it. Roughly half the height it used to be. */
        .readout {
            position: absolute;
            left: 10px;
            right: 10px;
            bottom: 10px;
            z-index: 4;
            display: flex;
            flex-direction: column;
            gap: 6px;
            pointer-events: none;   /* the map button behind stays tappable */
        }
        .readout > * { pointer-events: auto; }

        .named {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 10px;
            border-radius: 12px;
            background: rgba(11, 18, 32, .82);
            border: 1px solid var(--line);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        /* min-width:0 lets a long name ellipsis instead of stretching the card
           and pushing the whole panel taller by wrapping onto a third line. */
        .named__text { min-width: 0; }
        .avatar {
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 12px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
        }
        /* Both truncate. A long name wrapping is what would silently make this
           panel taller and start covering the face again. */
        .named__name,
        .named__pos {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .named__name { font-weight: 700; font-size: 13px; line-height: 1.25; }
        .named__pos  { font-size: 10.5px; color: var(--muted); line-height: 1.25; }

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
        .result--ot  .result__mark { background: rgba(124, 92, 214, .16); color: #A78BFA; }
        @keyframes pop { from { transform: scale(.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        /* The confirmation, sized to be read at a glance from arm's length —
           this is the thing an employee looks for before walking away. */
        .result__headline {
            font-size: 18px;
            font-weight: 800;
            color: var(--ok);
            margin-bottom: -2px;
        }
        .result--out .result__headline { color: var(--amber); }
        .result--ot  .result__headline { color: #A78BFA; }

        .result__name   { font-size: 21px; font-weight: 800; }
        .result__pos    { font-size: 12.5px; color: var(--muted); margin-top: -8px; }
        .result__action { font-size: 12px; font-weight: 700; letter-spacing: .12em; color: var(--ok); }
        .result--out .result__action { color: var(--amber); }
        .result--ot  .result__action { color: #A78BFA; }
        .result__time   { font-size: 34px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .result__date   { font-size: 12px; color: var(--muted); margin-top: -10px; }
        .result__note   { font-size: 12px; color: var(--muted); margin-top: 6px; }

        /* ---------------------------------------------------------------- flash */

        /* The liveness flash. z-index 40 puts it above everything else on the
           page (.mapsheet, the previous ceiling, is 30) because this is a light
           source rather than a UI layer — it has to cover the header and the
           action bar too, or the screen is not evenly lighting the face.
           pointer-events: none: it is timed, not dismissible. It does not
           interrupt capture — the video element's pixel buffer keeps updating
           underneath whatever is painted over it. */
        .flash {
            position: absolute;
            inset: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        /* The illumination palette. Saturated on purpose: the colour channel
           the server looks for has to actually dominate what the face sends
           back, and a pastel would not move the ratio enough to measure.

           'dark' is not pure #000 — SCRFD still has to find the face on that
           segment, and in an already-dim room a true black screen takes the
           last of the fill light with it. That tone is a field-tuning knob: if
           a kiosk starts timing out on dark segments, lift it before touching
           any liveness threshold. */
        .flash--white  { background: #FFFFFF; }
        .flash--dark   { background: #0A0A0A; }
        .flash--red    { background: #FF2D2D; }
        .flash--green  { background: #23FF6A; }
        .flash--blue   { background: #2D6BFF; }

        /* The hint has to stay readable on all five. */
        .flash--red .flash__hint,
        .flash--blue .flash__hint { color: rgba(255, 255, 255, .9); }
        .flash--green .flash__hint { color: rgba(0, 0, 0, .6); }
        .flash__hint {
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            padding: 0 24px;
            color: rgba(0, 0, 0, .55);
        }
        .flash--dark .flash__hint { color: rgba(255, 255, 255, .65); }

        .d-none { display: none !important; }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

@php
    // Badge-first kiosk. Read once here so the markup below can render the
    // face-only switch already hidden rather than letting setMode() blink it
    // away on first paint. The punch endpoint enforces the same rule itself —
    // this only decides what the kiosk shows.
    $requireQr = (bool) config('face.require_qr', true);
@endphp

<div class="portal">

    <header class="top">
        <img class="top__seal" src="{{ asset('Uploads/time_entry.png') }}" alt="">
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
            <div class="reticle reticle--face" id="guide-oval">
                <span class="reticle__corner reticle__corner--tl"></span>
                <span class="reticle__corner reticle__corner--tr"></span>
                <span class="reticle__corner reticle__corner--bl"></span>
                <span class="reticle__corner reticle__corner--br"></span>
                <span class="reticle__scan"></span>
            </div>
            <div class="reticle reticle--qr d-none" id="guide-box">
                <span class="reticle__corner reticle__corner--tl"></span>
                <span class="reticle__corner reticle__corner--tr"></span>
                <span class="reticle__corner reticle__corner--bl"></span>
                <span class="reticle__corner reticle__corner--br"></span>
                <span class="reticle__scan"></span>
            </div>
        </div>

        {{-- The capture prompt. Guidance only — the server decides what actually
             happened, by measuring the submitted frames itself. --}}
        <div class="cue d-none" id="cue">
            <i class="fas fa-user" id="cue-icon"></i>
            <span id="cue-text">Look straight at the camera</span>
        </div>

        {{-- Face/QR switch (also flips to the rear camera for QR). Pinned over
             the live view's top-right corner rather than in the control bar.
             HIDDEN when face.require_qr is on — there is no face-only mode to
             switch to. Rendered hidden server-side rather than only by setMode()
             so it never flashes into view on the first paint, and .mapbtn slides
             up into the vacated slot (see the .camswap.d-none rule in the CSS). --}}
        <button type="button" class="camswap{{ $requireQr ? ' d-none' : '' }}" id="mode-toggle" title="Scan QR instead" aria-label="Switch camera mode">
            <i class="fas fa-qrcode" id="mode-toggle-icon"></i>
        </button>

        {{-- Nearest-station map. Sits under the camera switch, or in its place
             when the switch is hidden; opens the animated map so the employee
             can see the closest site and how far they are. --}}
        <button type="button" class="mapbtn" id="map-toggle" title="Nearest station map" aria-label="Show nearest station map">
            <i class="fas fa-map-location-dot"></i>
        </button>

        {{-- Today's punches for whoever's badge was just scanned. Hidden until
             the QR resolves, because until then the kiosk does not know whose
             history it would be showing. --}}
        <button type="button" class="histbtn d-none" id="hist-toggle" title="Today's log" aria-label="Show today's attendance log">
            <i class="fas fa-clock-rotate-left"></i>
        </button>

        {{-- Everything that lives along the bottom of the live view, in ONE
             stack rather than several things each absolutely positioned to the
             same corner. The name card and the location HUD were both pinned to
             the bottom and overlapped whenever a badge had been scanned — which,
             now that the badge is mandatory, is every single punch. A flex
             column keeps them clear of each other without anyone having to know
             how tall the other one is, and collapses cleanly when the name card
             is hidden. --}}
        {{-- Floating readout, hugging the BOTTOM EDGE of the video.
             Deliberately slim: it sits over the frame so the app stays exactly
             one screen tall with nothing to scroll, but the face occupies the
             middle of a portrait frame and this must stay clear of it. Roughly
             half its old height — a 30px avatar instead of 42, one line of
             location instead of three, and no gap between them to spare. --}}
        <div class="readout">
            {{-- Shown after a QR scan resolves, so the person sees their name
                 before the face step rather than after it. --}}
            <div class="named d-none" id="named">
                <div class="avatar" id="named-initials">--</div>
                <div class="named__text">
                    <div class="named__name" id="named-name">—</div>
                    <div class="named__pos" id="named-pos">—</div>
                </div>
            </div>

            {{-- ONE line. The distance leads; the raw fix trails it in the same
                 row, small and muted — it is diagnostic (what HR is given when
                 a punch is disputed), not something the employee acts on, and
                 as its own line it was costing a third of this panel's height.
                 The note only appears when out of range, written by
                 updateGeoHud() which knows whether the perimeter is enforced. --}}
            <div class="geohud" id="geohud">
                <div class="geohud__row">
                    <i class="fas fa-location-dot"></i>
                    <span class="geohud__dist" id="geo-distance">Waiting for location…</span>
                    <span class="geohud__coords" id="geo-coords">Lat —, Lng —</span>
                </div>
                <div class="geohud__note" id="geo-note"></div>
            </div>
        </div>

        <div class="veil" id="veil">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <div id="veil-text">Starting camera…</div>
        </div>
    </main>


    {{-- Nearest-station map. A self-contained animated canvas (no tiles, no CDN —
         it must work on the LGU LAN with no internet): stations are blinking
         "wave" rings sized to their geofence radius, the employee is a live dot,
         and an animated route shows which way to walk to be in range. --}}
    <div class="mapsheet d-none" id="mapsheet" aria-hidden="true">
        <header class="mapsheet__top">
            <div class="mapsheet__title">
                <i class="fas fa-location-crosshairs"></i>
                <span>Nearest station</span>
            </div>
            <button type="button" class="mapsheet__close" id="map-close" aria-label="Close map">
                <i class="fas fa-xmark"></i>
            </button>
        </header>
        {{-- Nearest is the default view and stays exactly as it was; "All
             stations" is an additional lens on the same canvas. --}}
        <div class="mapviews" role="tablist" aria-label="Map view">
            <button type="button" class="mapview is-on" id="view-near" role="tab" aria-selected="true">
                <i class="fas fa-location-crosshairs"></i> Nearest
            </button>
            <button type="button" class="mapview" id="view-all" role="tab" aria-selected="false">
                <i class="fas fa-layer-group"></i> All stations
            </button>
        </div>

        <div class="mapsheet__stage">
            <canvas id="mapcanvas"></canvas>
        </div>

        {{-- Every station, nearest first, with its distance. Only rendered in
             the "All stations" view; tapping one focuses it on the map. --}}
        <div class="stationlist d-none" id="stationlist"></div>

        <footer class="mapsheet__foot">
            <div class="mapsheet__dist" id="map-dist">Locating…</div>
            <div class="mapsheet__sub"  id="map-sub">Finding the station closest to you.</div>
        </footer>
    </div>

    {{-- Today's punches for the scanned badge. Rows are built by renderHistory()
         from what the SERVER computed — the kiosk does not decide which overtime
         entry is a start and which is an end, because that pairing has to agree
         with the DTR the employee will be paid from. --}}
    <div class="histsheet d-none" id="histsheet" aria-hidden="true">
        <header class="mapsheet__top">
            <div class="mapsheet__title">
                <i class="fas fa-clock-rotate-left"></i>
                <span>Today's log</span>
            </div>
            <button type="button" class="mapsheet__close" id="hist-close" aria-label="Close log">
                <i class="fas fa-xmark"></i>
            </button>
        </header>

        <div class="histsheet__who">
            <strong id="hist-name">—</strong>
            <span id="hist-date"></span>
        </div>

        <div class="histlist" id="histlist"></div>
    </div>

    <div class="hint" id="hint">
        <i class="fas fa-circle-notch fa-spin" id="hint-icon"></i>
        <span id="hint-text">Getting ready…</span>
    </div>

    <div class="controls">
        {{-- Each button captures the face and records the punch directly — no
             separate confirm tap. --}}
        <div class="actions" role="group" aria-label="Attendance action">
            <button type="button" class="action action--in" data-action="in">
                <i class="fas fa-camera"></i>
                <span>CLOCK IN</span>
            </button>
            <button type="button" class="action action--out" data-action="out">
                <i class="fas fa-right-from-bracket"></i>
                <span>CLOCK OUT</span>
            </button>
            {{-- Overtime is one column in the DTR: both the start and the end of
                 an OT stretch append to time_over and are told apart by order,
                 so there is one button here rather than an OT IN / OT OUT pair.
                 Full width on its own row because it is the occasional action —
                 three equal columns would shrink the two daily ones and cramp
                 their labels on a phone-sized kiosk. --}}
            <button type="button" class="action action--ot" data-action="ot">
                <i class="fas fa-moon"></i>
                <span>OVERTIME</span>
            </button>
        </div>
    </div>

    {{-- The liveness flash. Covers the whole app column so the screen itself
         becomes the light source: a real face reflects it and its brightness
         tracks the sequence, while a phone or monitor replaying a recording is
         self-lit and stays flat no matter what this does. --}}
    <div class="flash d-none" id="flash" aria-hidden="true">
        <div class="flash__hint">Hold still</div>
    </div>

    {{-- Result takes over the whole screen, then hands it back. --}}
    <div class="result d-none" id="result">
        <div class="result__mark" id="result-mark"><i class="fas fa-check"></i></div>
        {{-- The plain-language confirmation. The line under it is the action in
             the system's own words (CLOCK IN / OVERTIME); this one is what the
             employee actually reads from arm's length as they walk away. --}}
        <div class="result__headline" id="result-headline">Clocked in successfully</div>
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
        'ortPath'    => $ortPath,
        'urls'       => [
            'punch'     => route('attendancePunch'),
            'qrCheck'   => route('attendanceQrCheck'),
            'history'   => route('attendanceHistory'),
            'challenge' => route('attendanceChallenge'),
        ],
        'resetAfter' => (int) config('attendance.portal.reset_after', 5),
        'thresholds' => config('face.client'),
        // Badge-first. Drives the kiosk's starting mode and hides the "use face
        // only" switch; the punch endpoint enforces the same rule again, which
        // is the half that actually counts.
        'requireQr'  => (bool) config('face.require_qr', true),
        // For the live distance HUD only — the authoritative distance/range
        // judgement is re-derived server-side at punch time.
        'stations'   => $stations,
        // Whether the perimeter is a hard gate. Drives the pre-flight check
        // that saves a wasted camera pass; the server re-derives the same
        // judgement from the same config and station table at punch time.
        'geofence'   => [
            'enforce' => (bool) config('attendance.geofence.enforce', true),
            // Whether an empty station list closes the kiosk. Mirrored here so
            // the refusal happens before the camera runs; the server enforces
            // the same rule regardless.
            'requireStation' => (bool) config('attendance.geofence.require_station', true),
        ],
        // How many frontal frames to gather, and how long to let the screen
        // settle on a flash colour before trusting the frame. Every threshold
        // that decides whether the face is alive — including min_flash_delta —
        // stays on the server, where it cannot be edited.
        'liveness'   => [
            'frames'        => (int) config('face.liveness.min_neutral_frames', 5),
            'flashSettleMs' => (int) config('face.liveness.flash_settle_ms', 220),
        ],
        // The browser gates locally on this; the server enforces it again.
        'antispoof'  => [
            'enabled'      => (bool) config('face.antispoof.enabled', true),
            'minReal'      => (float) config('face.antispoof.min_real', 0.7),
            'minRealFrame' => (float) config('face.antispoof.min_real_frame', 0.35),
        ],
    ];
@endphp
<script id="portal-config" type="application/json">@json($portalConfig)</script>

{{-- ONNX Runtime Web + the FaceEngine wrapper (SCRFD detection, ArcFace
     embeddings). Vendored, no CDN: the portal must work on the LGU LAN with no
     internet. The .wasm binaries live next to ort.wasm.min.js under js/onnx. --}}
<script src="{{ asset('js/onnx/ort.wasm.min.js') }}"></script>
<script src="{{ asset('js/face-engine/face-engine.js') }}?v={{ filemtime(public_path('js/face-engine/face-engine.js')) }}"></script>
<script src="{{ asset('js/jsqr/jsQR.min.js') }}"></script>
@include('attendance.portal-script')

</body>
</html>
