<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    {{-- viewport-fit=cover + the safe-area insets below keep the action bar clear
         of the iPhone home indicator when this runs full-screen or in a WebView. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0A2A1A">
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

    <title>Attendance | Municipality of Mabinay</title>

    <link rel="shortcut icon" href="{{ asset('Uploads/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    {{-- The two typefaces of the sign-in page. Fetched when there is internet;
         on the LAN without it the page falls back to the system face and
         everything still works. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..700&family=Instrument+Sans:wght@400..600&display=swap">

    {{--
        Styled here and not with the app's Tailwind build, on purpose. The
        kiosk's script owns most of these class names: it rewrites .hint's
        whole class, builds the history and station rows as strings, and
        switches states with d-none, guide--ok, result--out and the like. The
        colours are the app's own (resources/css/app.css), from the two on
        the municipal seal.

        One screen, never a document: on a phone a single column with the
        camera taking whatever height is left; from 900px wide the camera
        takes the left and everything else stands in a panel on the right.
    --}}
    <style>
        :root {
            --forest-950: #0A2A1A;
            --forest-900: #0F3D26;
            --forest-800: #164B2E;
            --forest-600: #1E7A45;
            --forest-500: #2E9E5E;
            --leaf:       #86D3A5;   /* green that reads as text on the dark panels */
            --sun-500:    #EF9017;
            --sun-300:    #F6BC69;
            --cream:      #F6F1E4;
            --muted:      rgb(246 241 228 / .62);
            --line:       rgb(246 241 228 / .14);
            --danger:     #F4B4AB;

            --display: "Bricolage Grotesque", "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            --sans:    "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;

            --gap: 12px;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        html, body {
            margin: 0;
            padding: 0;
            /* Installed as a web app, so it is a FIXED ONE-SCREEN surface, not a
               document. Nothing here scrolls. overscroll-behavior additionally
               kills the rubber-band bounce that makes a standalone PWA feel like
               a web page. */
            height: 100%;
            overflow: hidden;
            background: var(--forest-950);
            color: var(--cream);
            font-family: var(--sans);
            font-size: 15px;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior: none;
        }

        button { font: inherit; color: inherit; }
        button:focus-visible { outline: 2px solid var(--sun-500); outline-offset: 2px; }

        /* 100dvh, not 100vh: mobile browser chrome collapses and vh does not
           follow it, which pushes the action bar off the bottom of the screen.

           The whole viewport, with no column in the middle of a dark page:
           the liveness flash fills this box, and the more of the screen it
           lights the more light there is on the face. */
        .portal {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            grid-template-rows: auto minmax(0, 1fr) auto auto;
            grid-template-areas:
                "top      clock"
                "stage    stage"
                "hint     hint"
                "controls controls";
            gap: var(--gap);
            height: 100vh;
            height: 100dvh; /* newer engines; the vh line above is the fallback */
            padding:
                calc(env(safe-area-inset-top) + 12px)
                calc(env(safe-area-inset-right) + 12px)
                calc(env(safe-area-inset-bottom) + 14px)
                calc(env(safe-area-inset-left) + 12px);
            overflow: hidden;   /* one screen, always */
        }

        /* ---------------------------------------------------------------- header */

        .top {
            grid-area: top;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding-left: 4px;
        }
        .top__seal  { width: 38px; height: 38px; flex: 0 0 auto; }
        .top__title { font-family: var(--display); font-size: 15px; font-weight: 600; line-height: 1.2; }
        .top__sub   { font-size: 12px; color: var(--muted); }

        .clock {
            grid-area: clock;
            align-self: center;
            text-align: right;
            padding-right: 4px;
        }
        .clock__time { font-family: var(--display); font-size: 20px; font-weight: 600; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .clock__date { font-size: 12px; color: var(--muted); }

        /* ---------------------------------------------------------------- steps */

        /* Badge first, then the face. Drawn only in the side panel of the wide
           layout; a phone has no room for it and the line under the camera
           says the same thing. Which step is lit comes from data-step on
           .portal, set by the script as the mode changes. */
        .flow { grid-area: flow; display: none; margin: 0; padding: 0; list-style: none; }
        .flow__step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            color: var(--muted);
        }
        .flow__step + .flow__step { border-top: 1px solid var(--line); }
        .flow__no {
            flex: 0 0 auto;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            font-family: var(--display);
            font-size: 14px;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        .flow__no i { display: none; font-size: 12px; }
        .portal[data-step="badge"] .flow__step--badge,
        .portal[data-step="face"]  .flow__step--face { color: var(--cream); font-weight: 600; }
        .portal[data-step="badge"] .flow__step--badge .flow__no,
        .portal[data-step="face"]  .flow__step--face  .flow__no { background: var(--cream); border-color: var(--cream); color: var(--forest-950); }
        /* The badge step, once it is behind you. */
        .portal[data-step="face"] .flow__step--badge .flow__no { border-color: var(--leaf); color: var(--leaf); }
        .portal[data-step="face"] .flow__step--badge .flow__no span { display: none; }
        .portal[data-step="face"] .flow__step--badge .flow__no i { display: block; }

        /* ---------------------------------------------------------------- stage */

        .stage {
            grid-area: stage;
            position: relative;
            border-radius: 28px;
            overflow: hidden;
            background: #000;
            /* Free to absorb whatever a short screen leaves it, which is what
               guarantees everything fits in one viewport and never scrolls. */
            min-height: 0;
            container-type: size;
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

        /* Framing reticle. Purely an aiming aid: nothing is judged from it.
           Four corner brackets, a faint outline and a line sweeping down it,
           which settle into green the moment the face (or the QR) is ready.

           Sized from the stage, whichever way it is longer: a share of the
           width on a phone's tall camera, of the height on a wide one. The
           percentages are for engines without container units. */
        .guide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .reticle { position: relative; display: block; }
        .reticle--face { width: 62%; width: min(62cqw, 54cqh); aspect-ratio: 3 / 4; }
        .reticle--qr   { width: 66%; width: min(66cqw, 60cqh); aspect-ratio: 1; }

        .reticle::after {
            content: '';
            position: absolute;
            inset: 7%;
            border: 1.5px solid rgb(246 241 228 / .22);
            border-radius: 22px;
            transition: border-color .3s ease;
        }
        .reticle--face::after { border-radius: 50%; }

        .reticle__corner {
            position: absolute;
            width: 34px;
            height: 34px;
            border: 3px solid var(--cream);
            transition: border-color .25s ease;
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
            background: linear-gradient(90deg, transparent, var(--sun-500), transparent);
            animation: reticle-scan 2.6s cubic-bezier(.45, 0, .55, 1) infinite;
        }
        @keyframes reticle-scan {
            0%   { top: 6%;  opacity: 0; }
            12%  { opacity: 1; }
            88%  { opacity: 1; }
            100% { top: 92%; opacity: 0; }
        }

        /* Ready: the corners and the outline turn green, the line steps aside. */
        .guide--ok .reticle__corner { border-color: var(--leaf); }
        .guide--ok .reticle__scan { opacity: 0; }
        .guide--ok .reticle::after {
            border-color: rgb(134 211 165 / .7);
            animation: reticle-lock .45s ease;
        }
        @keyframes reticle-lock {
            0%   { transform: scale(1); }
            45%  { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* What floats on the camera shares one look: the page's own dark green,
           nearly opaque, so it reads over any picture. */
        .cue, .camswap, .mapbtn, .histbtn {
            background: rgb(10 42 26 / .86);
            border: 1px solid var(--line);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* The capture prompt. Over the video, above the guide, below the veil. */
        .cue {
            position: absolute;
            top: 14px;
            left: 14px;
            /* Stops short of the top-right corner so it never runs underneath
               the round icon button parked there. */
            right: 68px;
            z-index: 4;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 14px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
        }
        .cue i { font-size: 17px; color: var(--sun-500); }

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
            gap: 12px;
            text-align: center;
            padding: 24px;
            background: rgb(10 42 26 / .95);
            line-height: 1.5;
            white-space: pre-line; /* honour \n in status/error messages */
            z-index: 5;
        }
        .veil i { color: var(--sun-500); }

        /* The round buttons down the stage's top-right corner: camera switch,
           station map, today's log. */
        .camswap, .mapbtn, .histbtn {
            position: absolute;
            right: 14px;
            z-index: 6;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            font-size: 17px;
            display: grid;
            place-items: center;
            cursor: pointer;
        }
        .camswap { top: 14px; }
        .mapbtn  { top: 70px; }    /* camswap top (14) + its height (46) + a 10px gap */
        .histbtn { top: 126px; }
        .camswap:active:not(:disabled),
        .mapbtn:active:not(:disabled),
        .histbtn:active:not(:disabled) { transform: scale(.94); }
        .camswap:disabled { opacity: .35; cursor: not-allowed; }

        /* The camera switch is hidden when the badge is mandatory (there is no
           face-only mode to offer); the two below it move up into the gap. A
           sibling rule rather than a class from the script: .camswap is
           rendered with d-none server-side and toggled by setMode(), and this
           follows either way. */
        .camswap.d-none ~ .mapbtn  { top: 14px; }
        .camswap.d-none ~ .histbtn { top: 70px; }

        /* The map button is the one thing here asking for attention: it is
           where you find out whether you are in range. */
        .mapbtn::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            border: 2px solid rgb(239 144 23 / .7);
            animation: mapbtn-pulse 2.4s ease-out infinite;
        }
        @keyframes mapbtn-pulse {
            0%   { transform: scale(1);   opacity: .7; }
            70%  { transform: scale(1.35); opacity: 0; }
            100% { transform: scale(1.35); opacity: 0; }
        }

        /* When the capture cue is up it owns the top strip; the buttons step
           aside rather than sitting on the text. */
        .cue:not(.d-none) ~ .camswap,
        .cue:not(.d-none) ~ .histbtn,
        .cue:not(.d-none) ~ .mapbtn { display: none; }

        /* ---------------------------------------------------------------- readout */

        /* Who the badge belongs to, and how far the nearest station is.

           On a phone it shares the stage's cell and lies along the bottom edge
           of the video, which keeps the app one screen tall. So there it must
           stay SMALL: a portrait frame puts the face in the middle, and
           anything tall here climbs into it. In the wide layout it has a place
           of its own in the side panel and can breathe. */
        .readout {
            grid-area: stage;
            align-self: end;
            z-index: 4;
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 10px;
            min-width: 0;
            pointer-events: none;
        }
        .readout > * { pointer-events: auto; }

        .named, .geohud {
            border-radius: 14px;
            background: rgb(10 42 26 / .86);
            border: 1px solid var(--line);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .named {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 10px;
        }
        /* min-width:0 lets a long name ellipsis instead of stretching the card. */
        .named__text { min-width: 0; }
        .avatar {
            width: 32px;
            height: 32px;
            flex: 0 0 auto;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-family: var(--display);
            font-weight: 600;
            font-size: 13px;
            background: var(--cream);
            color: var(--forest-950);
        }
        /* Both truncate. A long name wrapping is what would silently make this
           panel taller and start covering the face again. */
        .named__name,
        .named__pos {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .named__name { font-family: var(--display); font-weight: 600; font-size: 15px; line-height: 1.25; }
        .named__pos  { font-size: 12px; color: var(--muted); line-height: 1.25; }

        /* Courtesy display only: the server re-derives all of it at punch time
           from the same station table. */
        .geohud {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 7px 10px;
            font-size: 12px;
        }
        .geohud__row {
            display: flex;
            align-items: center;
            gap: 7px;
            font-weight: 500;
            min-width: 0;
        }
        .geohud__row i { flex: 0 0 auto; font-size: 11px; }
        /* Takes the space, and gives it up by truncating rather than wrapping. */
        .geohud__dist {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .geohud__note {
            font-size: 11px;
            color: var(--sun-300);
            display: none;
        }
        /* Diagnostic, not something the employee acts on: it exists so HR has
           the raw fix when a punch is disputed. First thing dropped when there
           is no room for it. */
        .geohud__coords {
            margin-left: auto;
            flex: 0 1 auto;
            font-size: 10px;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 360px) {
            .geohud__coords { display: none; }
        }
        .geohud--ok  .geohud__row { color: var(--leaf); }
        .geohud--far .geohud__row { color: var(--sun-300); }
        .geohud--far .geohud__note { display: block; }

        /* ---------------------------------------------------------------- hint */

        /* The one line that says what to do next, or what went wrong. The
           script replaces this element's whole class to change its tone. */
        .hint {
            grid-area: hint;
            padding: 12px 16px;
            border-radius: 16px;
            background: var(--forest-900);
            border: 1px solid var(--line);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
        }
        .hint i { flex: 0 0 auto; color: var(--sun-500); }
        .hint--ok  { background: rgb(134 211 165 / .14); border-color: rgb(134 211 165 / .4); color: var(--leaf); }
        .hint--ok i { color: var(--leaf); }
        .hint--bad { background: rgb(214 79 60 / .16); border-color: rgb(244 180 171 / .4); color: var(--danger); }
        .hint--bad i { color: var(--danger); }

        /* ---------------------------------------------------------------- controls */

        .controls { grid-area: controls; }

        /* The action buttons. Each tap captures the face and writes the punch
           directly; there is no separate "confirm" step. Green in, orange out,
           so the choice reads at a glance across a room. They share the top
           row at full size; overtime, the occasional one, spans both columns
           beneath them as an outline. */
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .action {
            appearance: none;
            border: 0;
            border-radius: 20px;
            padding: 16px 12px;
            font-family: var(--display);
            font-weight: 600;
            font-size: 19px;
            letter-spacing: -.01em;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: opacity .15s ease, transform .06s ease;
        }
        .action i { font-size: 20px; }
        .action--in  { background: var(--forest-500); color: var(--forest-950); }
        .action--out { background: var(--sun-500);    color: var(--forest-950); }
        .action--ot  {
            grid-column: 1 / -1;
            background: transparent;
            border: 1.5px solid rgb(246 241 228 / .4);
            color: var(--cream);
            flex-direction: row;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            font-size: 16px;
        }
        .action--ot i { font-size: 15px; }
        .action:active:not(:disabled) { transform: scale(.97); }
        .action:disabled { opacity: .40; cursor: not-allowed; }
        /* Nothing to record until a badge has been read. They stay tappable,
           so a tap can say why (the script answers "scan your badge first"). */
        .portal[data-flow="badge"][data-step="badge"] .action { opacity: .45; }

        /* ------------------------------------------------------------ the sheets */

        /* The station map and today's log: two panels that take over the
           screen and hand it back. */
        .mapsheet, .histsheet {
            position: absolute;
            inset: 0;
            z-index: 30;
            display: flex;
            flex-direction: column;
            background: var(--forest-950);
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
            padding: calc(env(safe-area-inset-top) + 16px) 18px 12px;
        }
        .mapsheet__title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--display);
            font-size: 20px;
            font-weight: 600;
        }
        .mapsheet__title i { color: var(--sun-500); font-size: 17px; }
        .mapsheet__close {
            margin-left: auto;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--line);
            background: var(--forest-900);
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
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: #061C11;
            min-height: 0;
        }
        .mapsheet__stage canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            display: block;
        }
        /* View switch. Two pills; the active one is the light one. */
        .mapviews {
            display: flex;
            gap: 6px;
            margin: 0 14px 12px;
            padding: 4px;
            border-radius: 16px;
            border: 1px solid var(--line);
        }
        .mapview {
            flex: 1 1 0;
            appearance: none;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: var(--muted);
            font-weight: 600;
            padding: 9px 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .15s ease, color .15s ease;
        }
        .mapview i { font-size: 12px; }
        .mapview.is-on { background: var(--cream); color: var(--forest-950); }
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
            border-radius: 14px;
            background: var(--forest-900);
            padding: 11px 13px;
            cursor: pointer;
        }
        .stationrow.is-focused { border-color: var(--sun-500); }
        .stationrow__dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex: 0 0 auto;
            background: var(--muted);
        }
        .stationrow.is-in  .stationrow__dot { background: var(--leaf); }
        .stationrow.is-out .stationrow__dot { background: var(--sun-500); }
        .stationrow__name { font-weight: 600; line-height: 1.25; }
        .stationrow__meta { font-size: 12px; color: var(--muted); margin-top: 1px; }
        .stationrow__dist {
            margin-left: auto;
            font-family: var(--display);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            flex: 0 0 auto;
        }
        .stationrow.is-in  .stationrow__dist { color: var(--leaf); }
        .stationrow.is-out .stationrow__dist { color: var(--sun-300); }

        .mapsheet__foot {
            padding: 14px 18px calc(env(safe-area-inset-bottom) + 18px);
        }
        .mapsheet__dist { font-family: var(--display); font-size: 20px; font-weight: 600; }
        .mapsheet__sub  { font-size: 13px; color: var(--muted); margin-top: 2px; line-height: 1.4; }
        .mapsheet.is-ok  .mapsheet__dist { color: var(--leaf); }
        .mapsheet.is-far .mapsheet__dist { color: var(--sun-300); }

        .histsheet__who {
            padding: 0 18px 12px;
            color: var(--muted);
        }
        .histsheet__who strong { color: var(--cream); font-weight: 600; }
        /* The one scrollable region in the app. A long day genuinely can run
           past the screen, and this is a panel the employee opened rather than
           the fixed kiosk surface underneath it. */
        .histlist {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            max-width: 44rem;
            margin: 0 auto;
            padding: 0 14px calc(env(safe-area-inset-bottom) + 14px);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .histrow {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            background: var(--forest-900);
            border: 1px solid var(--line);
        }
        .histrow__ico {
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 14px;
        }
        /* Coloured like the buttons that create them, so a row reads as the
           same action the employee tapped: green in, orange out, and overtime
           the plain light one. */
        .histrow--login  .histrow__ico { background: var(--forest-500); color: var(--forest-950); }
        .histrow--logout .histrow__ico { background: var(--sun-500);    color: var(--forest-950); }
        .histrow--ot-in  .histrow__ico,
        .histrow--ot-out .histrow__ico { border: 1.5px solid rgb(246 241 228 / .4); color: var(--cream); }

        .histrow__body { min-width: 0; flex: 1 1 auto; }
        .histrow__what { font-weight: 600; line-height: 1.3; }
        .histrow__where {
            font-size: 12px;
            color: var(--muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .histrow__time {
            flex: 0 0 auto;
            font-family: var(--display);
            font-size: 18px;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        .histempty {
            margin: 32px 14px;
            text-align: center;
            color: var(--muted);
            line-height: 1.5;
        }
        .histempty i { display: block; font-size: 26px; margin-bottom: 12px; color: var(--sun-500); }

        /* ---------------------------------------------------------------- result */

        /* Takes the whole screen, then hands it back. Set to be read at a
           glance from arm's length: this is the thing an employee looks for
           before walking away. The colour says which of the three it was. */
        .result {
            --tone: var(--leaf);
            position: absolute;
            inset: 0;
            z-index: 20;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 32px 24px calc(env(safe-area-inset-bottom) + 32px);
            text-align: center;
            background: var(--forest-950);
        }
        .result--out { --tone: var(--sun-500); }
        .result--ot  { --tone: var(--cream); }

        .result__mark {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 40px;
            background: var(--tone);
            color: var(--forest-950);
            margin-bottom: 14px;
            animation: pop .35s cubic-bezier(.2, 1.4, .4, 1);
        }
        @keyframes pop { from { transform: scale(.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .result__headline {
            font-family: var(--display);
            font-size: clamp(24px, 4.5vw, 34px);
            font-weight: 600;
            letter-spacing: -.01em;
            line-height: 1.15;
            color: var(--tone);
        }
        .result__action { font-size: 13px; font-weight: 600; letter-spacing: .08em; color: var(--muted); }
        .result__name   { font-family: var(--display); font-size: 22px; font-weight: 600; margin-top: 14px; }
        .result__pos    { color: var(--muted); }
        .result__time   {
            font-family: var(--display);
            font-size: clamp(48px, 11vw, 84px);
            font-weight: 600;
            line-height: 1;
            letter-spacing: -.02em;
            font-variant-numeric: tabular-nums;
            margin-top: 18px;
        }
        .result__date   { color: var(--muted); margin-top: 4px; }
        .result__note   { font-size: 13px; color: var(--muted); margin-top: 14px; max-width: 34rem; }

        /* ---------------------------------------------------------------- flash */

        /* The liveness flash. z-index 40 puts it above everything else on the
           page (the sheets, the previous ceiling, are 30) because this is a
           light source rather than a UI layer: it has to cover the header and
           the action bar too, or the screen is not evenly lighting the face.
           pointer-events: none: it is timed, not dismissible. It does not
           interrupt capture; the video element's pixel buffer keeps updating
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
           back, and a pastel would not move the ratio enough to measure. These
           are measured against, so they are NOT the page's colours and must
           not be restyled to match.

           'dark' is not pure #000: SCRFD still has to find the face on that
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
            font-family: var(--display);
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            padding: 0 24px;
            color: rgba(0, 0, 0, .55);
        }
        .flash--dark .flash__hint { color: rgba(255, 255, 255, .65); }

        .d-none { display: none !important; }

        /* ------------------------------------------------------------ wide screens */

        /* A tablet on its side, a desktop, a wall-mounted kiosk: the camera
           takes the left, and the clock, the steps, who and where, the status
           line and the buttons stand in a panel on the right. */
        @media (min-width: 900px) and (min-height: 520px) {
            :root { --gap: 16px; }

            html, body { font-size: 16px; }

            .portal {
                grid-template-columns: minmax(0, 1fr) clamp(21rem, 30vw, 27rem);
                grid-template-rows: auto auto auto minmax(0, 1fr) auto auto;
                grid-template-areas:
                    "stage top"
                    "stage clock"
                    "stage flow"
                    "stage readout"
                    "stage hint"
                    "stage controls";
                column-gap: 20px;
                padding:
                    calc(env(safe-area-inset-top) + 16px)
                    calc(env(safe-area-inset-right) + 20px)
                    calc(env(safe-area-inset-bottom) + 16px)
                    calc(env(safe-area-inset-left) + 16px);
            }

            .top { padding: 6px 0 0; }
            .top__seal  { width: 44px; height: 44px; }
            .top__title { font-size: 17px; }
            .top__sub   { font-size: 13px; }

            .clock { text-align: left; padding: 8px 0 4px; }
            .clock__time { font-size: clamp(44px, 5.2vw, 68px); letter-spacing: -.02em; line-height: 1; }
            .clock__date { font-size: 16px; margin-top: 6px; }

            .flow { display: block; }
            /* Only a badge-first kiosk has two steps to show. */
            .portal:not([data-flow="badge"]) .flow { display: none; }

            .readout {
                grid-area: readout;
                align-self: start;
                margin: 0;
                gap: 10px;
            }
            .named, .geohud {
                background: var(--forest-900);
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
                border-radius: 16px;
            }
            .named { padding: 12px 14px; gap: 12px; }
            .avatar { width: 44px; height: 44px; font-size: 16px; }
            .named__name { font-size: 18px; }
            .named__pos  { font-size: 13px; }

            .geohud { padding: 12px 14px; font-size: 14px; gap: 4px; }
            .geohud__row { flex-wrap: wrap; row-gap: 2px; }
            .geohud__row i { font-size: 13px; }
            /* There is room here for the whole sentence, and for the fix on a
               line of its own under it. */
            .geohud__dist { white-space: normal; flex: 1 1 0; }
            .geohud__coords { flex: 1 0 100%; margin-left: 20px; font-size: 12px; }
            .geohud__note { font-size: 13px; margin-left: 20px; }

            .hint { min-height: 56px; }

            .action { padding: 22px 12px; font-size: 22px; }
            .action i { font-size: 22px; }
            .action--ot { padding: 14px; font-size: 17px; }

            .mapsheet__top, .mapsheet__foot, .histsheet__who { padding-left: 28px; padding-right: 28px; }
            .mapsheet__stage, .mapviews, .stationlist { margin-left: 24px; margin-right: 24px; }
            .mapviews { max-width: 26rem; }
            .histsheet__who { width: 100%; max-width: 44rem; margin: 0 auto; padding-left: 14px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

@php
    // Badge-first kiosk. Read once here so the markup below can render the
    // face-only switch already hidden rather than letting setMode() blink it
    // away on first paint. The punch endpoint enforces the same rule itself;
    // this only decides what the kiosk shows.
    $requireQr = (bool) config('face.require_qr', true);
@endphp

{{-- data-flow says whether a badge comes first; data-step, kept up to date by
     the script, says which of the two steps the kiosk is on. --}}
<div class="portal" @if($requireQr) data-flow="badge" data-step="badge" @else data-step="face" @endif>

    <header class="top">
        <img class="top__seal" src="{{ asset('Uploads/logo.png') }}" alt="Official seal of the Municipality of Mabinay">
        <div>
            <div class="top__title">Municipality of Mabinay</div>
            <div class="top__sub">Attendance</div>
        </div>
    </header>

    <div class="clock">
        <div class="clock__time" id="clock">--:--:--</div>
        <div class="clock__date" id="today">&nbsp;</div>
    </div>

    <ol class="flow" aria-label="How to record your attendance">
        <li class="flow__step flow__step--badge">
            <span class="flow__no"><span>1</span><i class="fas fa-check"></i></span>
            Show your QR badge to the camera
        </li>
        <li class="flow__step flow__step--face">
            <span class="flow__no"><span>2</span></span>
            Face the camera, then tap what you are recording
        </li>
    </ol>

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

        {{-- The capture prompt. Guidance only: the server decides what actually
             happened, by measuring the submitted frames itself. --}}
        <div class="cue d-none" id="cue">
            <i class="fas fa-user" id="cue-icon"></i>
            <span id="cue-text">Look straight at the camera</span>
        </div>

        {{-- Face/QR switch (also flips to the rear camera for QR). HIDDEN when
             face.require_qr is on: there is no face-only mode to switch to.
             Rendered hidden server-side rather than only by setMode() so it
             never flashes into view on the first paint. These three buttons
             must stay after .cue and in this order: the stylesheet moves and
             hides them with sibling rules. --}}
        <button type="button" class="camswap{{ $requireQr ? ' d-none' : '' }}" id="mode-toggle" title="Scan QR instead" aria-label="Switch camera mode">
            <i class="fas fa-qrcode" id="mode-toggle-icon"></i>
        </button>

        {{-- Nearest-station map: the closest site and how far away it is. --}}
        <button type="button" class="mapbtn" id="map-toggle" title="Nearest station map" aria-label="Show nearest station map">
            <i class="fas fa-map-location-dot"></i>
        </button>

        {{-- Today's punches for whoever's badge was just scanned. Hidden until
             the QR resolves, because until then the kiosk does not know whose
             history it would be showing. --}}
        <button type="button" class="histbtn d-none" id="hist-toggle" title="Today's log" aria-label="Show today's attendance log">
            <i class="fas fa-clock-rotate-left"></i>
        </button>

        <div class="veil" id="veil">
            <i class="fas fa-circle-notch fa-spin fa-2x"></i>
            <div id="veil-text">Starting camera…</div>
        </div>
    </main>

    {{-- Who and where. Outside the stage so the wide layout can stand it in
         the side panel; on a phone the stylesheet lays it back over the foot
         of the video (see .readout). --}}
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

        {{-- The distance leads; the raw fix trails it, small and muted. It is
             diagnostic (what HR is given when a punch is disputed), not
             something the employee acts on. The note only appears when out of
             range, written by updateGeoHud() which knows whether the
             perimeter is enforced. --}}
        <div class="geohud" id="geohud">
            <div class="geohud__row">
                <i class="fas fa-location-dot"></i>
                <span class="geohud__dist" id="geo-distance">Waiting for location…</span>
                <span class="geohud__coords" id="geo-coords">Lat —, Lng —</span>
            </div>
            <div class="geohud__note" id="geo-note"></div>
        </div>
    </div>

    <div class="hint" id="hint">
        <i class="fas fa-circle-notch fa-spin" id="hint-icon"></i>
        <span id="hint-text">Getting ready…</span>
    </div>

    <div class="controls">
        {{-- Each button captures the face and records the punch directly; no
             separate confirm tap. --}}
        <div class="actions" role="group" aria-label="Attendance action">
            <button type="button" class="action action--in" data-action="in">
                <i class="fas fa-right-to-bracket"></i>
                <span>Clock in</span>
            </button>
            <button type="button" class="action action--out" data-action="out">
                <i class="fas fa-right-from-bracket"></i>
                <span>Clock out</span>
            </button>
            {{-- Overtime is one column in the DTR: both the start and the end of
                 an OT stretch append to time_over and are told apart by order,
                 so there is one button here rather than an OT IN / OT OUT pair. --}}
            <button type="button" class="action action--ot" data-action="ot">
                <i class="fas fa-moon"></i>
                <span>Overtime</span>
            </button>
        </div>
    </div>

    {{-- Nearest-station map. A self-contained animated canvas (no tiles, no CDN:
         it must work on the LGU LAN with no internet): stations are rings sized
         to their geofence radius, the employee is a live dot, and an animated
         route shows which way to walk to be in range. --}}
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
        {{-- Nearest is the default view; "All stations" is an additional lens
             on the same canvas. --}}
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
         from what the SERVER computed: the kiosk does not decide which overtime
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

    {{-- The liveness flash. Covers the whole screen so the screen itself
         becomes the light source: a real face reflects it and its brightness
         tracks the sequence, while a phone or monitor replaying a recording is
         self-lit and stays flat no matter what this does. --}}
    <div class="flash d-none" id="flash" aria-hidden="true">
        <div class="flash__hint">Hold still</div>
    </div>

    {{-- Result takes over the whole screen, then hands it back. --}}
    <div class="result d-none" id="result">
        <div class="result__mark" id="result-mark"><i class="fas fa-check"></i></div>
        {{-- The plain-language confirmation, and under it the action in the
             system's own words (CLOCK IN / OVERTIME). --}}
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
