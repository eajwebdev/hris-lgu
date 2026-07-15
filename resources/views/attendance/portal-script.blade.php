<script>
(function () {
    'use strict';

    var CONFIG = JSON.parse(document.getElementById('portal-config').textContent);
    var T      = CONFIG.thresholds;
    var L      = CONFIG.liveness;
    var CSRF   = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // See config/face.php — the registration module reads the same flag, and the
    // two must agree on which way "left" is.
    var YAW_INVERT = !!T.yaw_invert;

    var el = {
        stage:     document.getElementById('stage'),
        video:     document.getElementById('video'),
        overlay:   document.getElementById('overlay'),
        guide:     document.getElementById('guide'),
        guideOval: document.getElementById('guide-oval'),
        guideBox:  document.getElementById('guide-box'),
        cue:       document.getElementById('cue'),
        cueIcon:   document.getElementById('cue-icon'),
        cueText:   document.getElementById('cue-text'),
        steps:     document.getElementById('steps'),
        veil:      document.getElementById('veil'),
        veilText:  document.getElementById('veil-text'),
        hint:      document.getElementById('hint'),
        hintIcon:  document.getElementById('hint-icon'),
        hintText:  document.getElementById('hint-text'),
        modeBtn:   document.getElementById('mode-toggle'),
        modeIcon:  document.getElementById('mode-toggle-icon'),
        mapBtn:    document.getElementById('map-toggle'),
        mapSheet:  document.getElementById('mapsheet'),
        mapClose:  document.getElementById('map-close'),
        mapCanvas: document.getElementById('mapcanvas'),
        mapDist:   document.getElementById('map-dist'),
        mapSub:    document.getElementById('map-sub'),
        geohud:    document.getElementById('geohud'),
        geoDist:   document.getElementById('geo-distance'),
        geoCoords: document.getElementById('geo-coords'),
        named:     document.getElementById('named'),
        namedName: document.getElementById('named-name'),
        namedPos:  document.getElementById('named-pos'),
        namedInit: document.getElementById('named-initials'),
        result:    document.getElementById('result'),
        clock:     document.getElementById('clock'),
        today:     document.getElementById('today'),
        actions:   document.querySelectorAll('.action'),
    };

    function setActionsDisabled(disabled) {
        el.actions.forEach(function (btn) { btn.disabled = disabled; });
    }

    var state = {
        mode:    'face',   // face | qr | qrface | result
        action:  'in',
        stream:  null,
        looping: false,
        busy:    false,    // a capture sequence or network call owns the camera
        qrToken: null,
        modelsReady: false,
        geo:     null,     // last GPS fix {lat, lng, accuracy, at}
    };

    var scratch    = document.createElement('canvas');
    var scratchCtx = scratch.getContext('2d', { willReadFrequently: true });

    var luma = document.createElement('canvas');
    luma.width = luma.height = 32;
    var lumaCtx = luma.getContext('2d', { willReadFrequently: true });

    var sleep = function (ms) { return new Promise(function (r) { setTimeout(r, ms); }); };

    // ---------------------------------------------------------------- clock

    function tick() {
        var now = new Date();
        el.clock.textContent = now.toLocaleTimeString('en-PH', { hour12: true });
        el.today.textContent = now.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' });
    }
    tick();
    setInterval(tick, 1000);

    // ---------------------------------------------------------------- geometry

    function dist(a, b) { return Math.hypot(a.x - b.x, a.y - b.y); }

    /**
     * Head turn as a signed, scale-free ratio. Negative means the subject has
     * turned toward their own left. The measure itself (nose offset from the
     * eye midpoint over interocular distance) lives in the engine; only the
     * camera-orientation flip is applied here.
     */
    function yawOf(landmarks) {
        var yaw = FaceEngine.yawOf(landmarks);

        return YAW_INVERT ? -yaw : yaw;
    }

    function brightnessOf(box) {
        var v = el.video;
        var sx = Math.max(0, box.x), sy = Math.max(0, box.y);
        var sw = Math.min(box.width,  v.videoWidth  - sx);
        var sh = Math.min(box.height, v.videoHeight - sy);

        if (sw <= 0 || sh <= 0) return 0;

        lumaCtx.drawImage(v, sx, sy, sw, sh, 0, 0, 32, 32);

        var d = lumaCtx.getImageData(0, 0, 32, 32).data, sum = 0;

        for (var i = 0; i < d.length; i += 4) {
            sum += 0.2126 * d[i] + 0.7152 * d[i + 1] + 0.0722 * d[i + 2];
        }

        return sum / (d.length / 4);
    }

    var sharp = document.createElement('canvas');
    sharp.width = sharp.height = 64;
    var sharpCtx = sharp.getContext('2d', { willReadFrequently: true });

    /**
     * Variance of a Laplacian over the face crop. Motion blur flattens edges and
     * drags this toward zero; a blurred frame yields a mushy descriptor that
     * matches everyone a little and nobody well, so refusing the frame is cheaper
     * than spending an attempt on it.
     */
    function sharpnessOf(box) {
        var v = el.video;
        var sx = Math.max(0, box.x), sy = Math.max(0, box.y);
        var sw = Math.min(box.width,  v.videoWidth  - sx);
        var sh = Math.min(box.height, v.videoHeight - sy);

        if (sw <= 0 || sh <= 0) return 0;

        sharpCtx.drawImage(v, sx, sy, sw, sh, 0, 0, 64, 64);

        var d = sharpCtx.getImageData(0, 0, 64, 64).data;
        var g = new Float32Array(64 * 64);

        for (var i = 0, j = 0; i < d.length; i += 4, j++) {
            g[j] = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
        }

        var sum = 0, sum2 = 0, n = 0;

        for (var y = 1; y < 63; y++) {
            for (var x = 1; x < 63; x++) {
                var k = y * 64 + x;
                var lap = 4 * g[k] - g[k - 1] - g[k + 1] - g[k - 64] - g[k + 64];
                sum  += lap;
                sum2 += lap * lap;
                n++;
            }
        }

        var mean = sum / n;

        return sum2 / n - mean * mean;
    }

    // ---------------------------------------------------------------- face gate

    /**
     * The framing checks. Note what is NOT here: any judgement about whether the
     * face is alive. That question is settled on the server, against descriptors
     * it can compare to what HR enrolled — because anything decided in this file
     * can be edited by whoever is holding the phone.
     */
    function gateOf(detections) {
        if (!detections.length)    return { ok: false, message: 'No face detected' };
        if (detections.length > 1) return { ok: false, message: 'Only one person should be visible' };

        var d = detections[0];
        var box = d.box;

        if (box.width / el.video.videoWidth < T.min_face_ratio) {
            return { ok: false, message: 'Please move closer to the camera', detection: d };
        }
        if (d.score < T.min_detection_score) {
            return { ok: false, message: 'Please position your face properly', detection: d };
        }
        if (brightnessOf(box) < T.min_brightness) {
            return { ok: false, message: 'Lighting is insufficient', detection: d };
        }
        if (sharpnessOf(box) < T.min_sharpness) {
            return { ok: false, message: 'Hold still — the image is blurry', detection: d };
        }

        return { ok: true, detection: d, message: 'Ready' };
    }

    function poseHolds(landmarks, pose) {
        var yaw = yawOf(landmarks);

        if (pose === 'front') return Math.abs(yaw) <= T.front_yaw_max;
        if (pose === 'left')  return yaw <= -T.turn_yaw_min;

        return yaw >= T.turn_yaw_min;
    }

    /**
     * Nothing is drawn on the face any more — no box, no brackets following it
     * around. The fixed aiming reticle (in the CSS) is the only framing guide;
     * this just keeps the overlay canvas clear. Kept as a function so the call
     * sites in the idle loop and capture pass don't have to change.
     */
    function drawBox(detection, ok) {
        var c = el.overlay, ctx = c.getContext('2d');

        if (c.width !== el.video.videoWidth) {
            c.width  = el.video.videoWidth;
            c.height = el.video.videoHeight;
        }

        ctx.clearRect(0, 0, c.width, c.height);
    }

    // ---------------------------------------------------------------- detectors

    /**
     * 320 for the preview loop: the framing gate already demands a face filling
     * a fifth of the frame, and SCRFD finds a face that size trivially at 320 —
     * cheap enough to keep the preview smooth on a low-end phone. The permissive
     * score lets a rejected-but-seen face through so the gate can say WHY it is
     * being refused instead of just "no face".
     */
    function detectCheap() {
        return FaceEngine.detect(el.video, { size: 320, scoreThreshold: 0.35 });
    }

    /** The capture pass: tighter boxes and cleaner landmarks at 640. */
    function detectFull() {
        return FaceEngine.detect(el.video, { size: 640, scoreThreshold: 0.45 });
    }

    // ---------------------------------------------------------------- QR

    var barcodeDetector = null;

    if ('BarcodeDetector' in window) {
        try { barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { /* fall through */ }
    }

    async function readQr() {
        var v = el.video;

        if (!v.videoWidth) return null;

        if (barcodeDetector) {
            try {
                var found = await barcodeDetector.detect(v);
                return found.length ? found[0].rawValue : null;
            } catch (e) {
                barcodeDetector = null; // unusable here; fall back to jsQR
            }
        }

        var w = 480;
        var h = Math.round(v.videoHeight * (w / v.videoWidth));

        scratch.width = w;
        scratch.height = h;
        scratchCtx.drawImage(v, 0, 0, w, h);

        var image = scratchCtx.getImageData(0, 0, w, h);
        var code  = jsQR(image.data, w, h, { inversionAttempts: 'dontInvert' });

        return code ? code.data : null;
    }

    // ---------------------------------------------------------------- idle loop

    async function loop() {
        if (!state.looping) return;

        try {
            if (el.video.readyState === 4 && !state.busy) {
                if (state.mode === 'qr') {
                    await idleQr();
                } else {
                    await idleFace();
                }
            }
        } catch (e) {
            console.error('portal frame failed', e);
        }

        setTimeout(loop, state.mode === 'qr' ? 180 : 130);
    }

    async function idleFace() {
        // Camera comes up before the models finish loading; QR mode never needs
        // them at all, so only face detection waits here.
        if (!state.modelsReady) {
            setHint('Loading face recognition…', null);
            return;
        }

        var gate = gateOf(await detectCheap());

        drawBox(gate.detection, gate.ok);
        el.guide.classList.toggle('guide--ok', gate.ok);

        setHint(gate.ok ? 'Ready — tap CLOCK IN or CLOCK OUT' : gate.message, gate.ok ? 'ok' : 'bad');
    }

    async function idleQr() {
        var raw = await readQr();

        if (!raw) return;

        state.busy = true;
        setHint('Reading QR…', null);

        try {
            var response = await fetch(CONFIG.urls.qrCheck, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ qr: raw }),
            });

            var body = await response.json();

            if (!response.ok) {
                setHint(body.message || 'This QR code is not valid.', 'bad');
                // Give the operator a moment to read it before the scanner grabs
                // the same bad code again.
                await sleep(1600);
                state.busy = false;
                return;
            }

            state.qrToken = raw;

            showName(body.employee);
            await enterQrFace();
        } catch (e) {
            setHint('Could not reach the server.', 'bad');
            await sleep(1600);
            state.busy = false;
        }
    }

    // ---------------------------------------------------------------- liveness run

    /**
     * The guided capture — frontal only. The employee faces the camera and holds
     * still for a beat while a handful of frames are taken a moment apart.
     *
     * There are no head turns. Liveness is decided server-side from the natural
     * frame-to-frame drift of a real face: a living person is never perfectly
     * still, so consecutive descriptors differ, whereas a flat static photo held
     * to the lens produces very nearly the same vector every time. The cue on
     * screen is only guidance; the verdict is the server's.
     *
     * Honest limit: this defeats a printed or on-screen still photo. It does not
     * defeat a video/live replay of the employee, which drifts like a real face.
     * The QR path stays the stronger option where that matters.
     */
    async function runSequence() {
        var t0     = performance.now();
        var frames = [];
        var reals  = [];   // per-frame anti-spoof "live" probabilities

        // Still single-use: the nonce is redeemed server-side so a captured
        // payload cannot be replayed, even though we no longer ask for poses.
        var challenge = await getChallenge();

        showCue(null, 'Look at the camera and hold still');

        for (var i = 0; i < L.frames; i++) {
            var frame = await captureAt('front', 'Look straight at the camera');

            frames.push({
                stage: 'neutral',
                pose: null,
                t: Math.round(performance.now() - t0),
                descriptor: Array.from(frame.descriptor),
            });

            if (typeof frame.real === 'number') reals.push(frame.real);

            // Spaced out so consecutive frames are genuinely different moments —
            // that spread is exactly what the liveness check reads.
            await sleep(180);
        }

        hideCue();

        // Average the live-probability across the frames. Averaging rather than
        // taking the worst frame keeps one unlucky blurry frame from failing a
        // real person, while a photo stays low across all of them.
        var liveness = reals.length
            ? reals.reduce(function (a, b) { return a + b; }, 0) / reals.length
            : null;

        return { nonce: challenge.nonce, frames: frames, liveness: liveness };
    }

    async function getChallenge() {
        var response = await fetch(CONFIG.urls.challenge, { method: 'POST', headers: jsonHeaders() });

        if (!response.ok) throw new Error('Could not start the face check.');

        return (await response.json()).challenge;
    }

    /**
     * Poll cheaply until the framing and the pose both hold, then spend one
     * descriptor pass — and re-check the pose on that same result, because the
     * head may have drifted in the milliseconds between.
     */
    async function captureAt(pose, instruction) {
        // A genuine head turn lands in a second or two. A shorter deadline than
        // the old 20s does not rush an honest employee — it just stops a stalled
        // capture from holding the whole sequence hostage before the retry, which
        // is what made a failing punch feel like minutes.
        var deadline = performance.now() + 10000;

        while (performance.now() < deadline) {
            var gate = gateOf(await detectCheap());

            drawBox(gate.detection, gate.ok);

            if (!gate.ok) {
                setHint(gate.message, 'bad');
                await sleep(90);
                continue;
            }

            if (!poseHolds(gate.detection.landmarks, pose)) {
                setHint(instruction, 'bad');
                await sleep(90);
                continue;
            }

            setHint('Hold still…', 'ok');

            // The quality pass, then the embedding — and the pose is re-checked
            // on the fresh detection, because the head may have drifted in the
            // milliseconds between.
            var full = (await detectFull())[0];

            if (full && poseHolds(full.landmarks, pose)) {
                full.descriptor = await FaceEngine.embed(el.video, full);
                // Same frame, judged by the anti-spoof model. null when the model
                // is not loaded, in which case the check simply does not apply.
                full.real = await FaceEngine.antispoof(el.video, full);
                return full;
            }

            await sleep(90);
        }

        throw new Error('Face check timed out. Please try again.');
    }

    // ---------------------------------------------------------------- punch

    async function punch() {
        if (state.busy) return;

        if (!state.modelsReady) {
            setHint('Loading face recognition… one moment', null);
            return;
        }

        state.busy = true;
        setActionsDisabled(true);
        el.modeBtn.disabled = true;

        try {
            var run = await runSequence();

            // Anti-spoof gate, client side. Blocked here means the punch is never
            // even sent — a photo or a phone screen stops at the kiosk. Skipped
            // only when the model produced no score at all (not loaded).
            if (CONFIG.antispoof && CONFIG.antispoof.enabled &&
                typeof run.liveness === 'number' && run.liveness < CONFIG.antispoof.minReal) {
                fail('Please use your real face — a photo or phone screen was detected.');
                return;
            }

            setHint('Matching…', null);

            var payload = {
                mode:   state.mode === 'qrface' ? 'qr' : 'face',
                action: state.action,
                nonce:  run.nonce,
                frames: run.frames,
                liveness_score: run.liveness,
                geo:    freshGeo(),
            };

            if (payload.mode === 'qr') {
                payload.qr = state.qrToken;
            }

            var response = await fetch(CONFIG.urls.punch, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(payload),
            });

            var body = await response.json();

            if (!response.ok) {
                fail(body.message || 'Could not record attendance.');
                return;
            }

            showResult(body);
        } catch (e) {
            fail(e.message || 'Could not reach the server.');
        }
    }

    function fail(message) {
        hideCue();
        setHint(message, 'bad');

        // Hold the reason on screen for a moment. Without this, the idle loop
        // repaints "Ready…" within a frame or two and the employee never sees why
        // the punch failed — so they tap again into the same failure, which made
        // it look like an endless loop. Keeping busy set freezes the hint and the
        // action buttons during the pause.
        el.modeBtn.disabled = false;

        setTimeout(function () {
            // The challenge is spent either way, so a retry starts a fresh one.
            state.busy = false;
            setActionsDisabled(false);
        }, 1800);
    }

    // ---------------------------------------------------------------- screens

    function showCue(pose, text) {
        el.cueText.textContent = text;

        el.cueIcon.className = pose === 'left'  ? 'fas fa-arrow-left'
                             : pose === 'right' ? 'fas fa-arrow-right'
                             : 'fas fa-user';

        el.cue.classList.remove('d-none');
        el.cue.classList.toggle('cue--turn', !!pose);
    }

    function hideCue() {
        el.cue.classList.add('d-none');
    }

    function showName(employee) {
        el.namedName.textContent = employee.name;
        el.namedPos.textContent  = employee.position || 'Employee';
        el.namedInit.textContent = employee.initials;
        el.named.classList.remove('d-none');
    }

    /**
     * The location line under the result. Shown to the employee on purpose:
     * seeing "recorded 2.3 km from Municipal Hall" at punch time is what makes
     * the HR flag unarguable later.
     */
    function locationNote(location) {
        if (!location) return '';

        if (!location.has_location) return 'Location not shared';

        if (location.out_of_range === true) {
            var d = location.distance_m >= 1000
                ? (location.distance_m / 1000).toFixed(1) + ' km'
                : location.distance_m + ' m';

            return 'Recorded ' + d + ' from ' + location.station_name
                 + ' — flagged for HR clarification.';
        }

        if (location.out_of_range === false) {
            return '\u{1F4CD} ' + location.station_name;
        }

        return '';
    }

    function showResult(body) {
        state.looping = false;
        stopCamera();
        hideCue();

        var out = body.action === 'CLOCK OUT';

        el.result.classList.toggle('result--out', out);

        document.getElementById('result-mark').innerHTML = out
            ? '<i class="fas fa-right-from-bracket"></i>'
            : '<i class="fas fa-check"></i>';

        document.getElementById('result-action').textContent = body.action;
        document.getElementById('result-name').textContent   = body.employee.name;
        document.getElementById('result-pos').textContent    = body.employee.position || 'Employee';
        document.getElementById('result-time').textContent   = body.time;
        document.getElementById('result-date').textContent   = body.date;
        document.getElementById('result-note').textContent   = body.recorded ? locationNote(body.location) : 'Already recorded earlier.';

        el.result.classList.remove('d-none');

        setTimeout(reset, CONFIG.resetAfter * 1000);
    }

    function reset() {
        el.result.classList.add('d-none');
        el.named.classList.add('d-none');
        el.modeBtn.disabled = false;
        setActionsDisabled(false);

        state.qrToken = null;
        state.busy    = false;

        setMode('face');
    }

    // ---------------------------------------------------------------- modes

    async function setMode(mode) {
        state.mode = mode;
        state.busy = false;

        setActionsDisabled(false);
        el.guide.classList.remove('guide--ok');
        hideCue();

        var qr = mode === 'qr';

        // Rear camera for a badge, front for a face. Un-mirror the rear view — a
        // mirrored world is disorienting to aim in.
        el.stage.classList.toggle('stage--mirror', !qr);
        el.guideOval.classList.toggle('d-none', qr);
        el.guideBox.classList.toggle('d-none', !qr);

        // Icon-only switch pinned over the camera: show what tapping it goes TO.
        var toQr = !(qr || mode === 'qrface');
        el.modeIcon.className = toQr ? 'fas fa-qrcode' : 'fas fa-user';
        el.modeBtn.title = toQr ? 'Scan QR instead' : 'Use face only instead';
        el.modeBtn.setAttribute('aria-label', el.modeBtn.title);

        if (mode !== 'qrface') {
            el.named.classList.add('d-none');
            state.qrToken = null;
        }

        await startCamera(qr ? 'environment' : 'user');

        setHint(
            qr ? 'Point the camera at the employee QR code'
               : mode === 'qrface' ? 'Now look at the camera'
               : 'Look at the camera',
            null
        );

        if (!state.looping) {
            state.looping = true;
            loop();
        }
    }

    async function enterQrFace() {
        await setMode('qrface');
        el.named.classList.remove('d-none');
    }

    // ---------------------------------------------------------------- camera

    async function startCamera(facing) {
        stopCamera();

        try {
            state.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: facing }, width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
        } catch (e) {
            // A laptop has no rear camera; using the one it has beats refusing to
            // scan at all.
            if (facing === 'environment') {
                try {
                    state.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                } catch (inner) {
                    return cameraError(inner);
                }
            } else {
                return cameraError(e);
            }
        }

        el.video.srcObject = state.stream;

        try { await el.video.play(); } catch (e) { /* autoplay races the swap; the loop copes */ }

        hideVeil();
    }

    function stopCamera() {
        if (state.stream) {
            state.stream.getTracks().forEach(function (t) { t.stop(); });
            state.stream = null;
        }

        el.video.srcObject = null;
    }

    function cameraError(e) {
        state.looping = false;

        if (e.name === 'NotAllowedError' || e.name === 'SecurityError') {
            veil('Camera permission was denied. Allow camera access, then reload this page.');
        } else if (e.name === 'NotFoundError') {
            veil('No camera was found on this device.');
        } else if (e.name === 'NotReadableError') {
            veil('The camera is in use by another app. Close it and reload.');
        } else {
            veil('Could not start the camera: ' + (e.message || e.name));
        }
    }

    // ---------------------------------------------------------------- chrome

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
        };
    }

    function setHint(message, tone) {
        el.hintText.textContent = message;
        el.hint.className = 'hint' + (tone ? ' hint--' + tone : '');
        el.hintIcon.className = tone === 'ok'  ? 'fas fa-check-circle'
                             : tone === 'bad'  ? 'fas fa-exclamation-circle'
                             : 'fas fa-circle-notch fa-spin';
    }

    function veil(message) {
        el.veilText.textContent = message;
        el.veil.style.display = 'flex';
    }

    function hideVeil() {
        el.veil.style.display = 'none';
    }

    // ---------------------------------------------------------------- boot

    // Each action button both picks in/out and fires the punch — one tap records
    // the time, no separate confirm.
    el.actions.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (state.busy) return; // not mid-sequence

            state.action = btn.dataset.action;
            punch();
        });
    });

    el.modeBtn.addEventListener('click', function () {
        if (state.busy) return;

        setMode(state.mode === 'face' ? 'qr' : 'face');
    });

    // Release the camera when backgrounded. In a WebView the light otherwise stays
    // on behind whatever the user switched to.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            state.looping = false;
            stopCamera();
        } else if (el.result.classList.contains('d-none')) {
            setMode(state.mode === 'qrface' ? 'face' : state.mode);
        }
    });

    // ---------------------------------------------------------------- geo HUD

    /**
     * Metres between two coordinates — the same haversine GeoService runs on
     * the server. The HUD must agree with what HR will later see on the punch,
     * or the employee gets told one distance and flagged at another.
     */
    function haversine(lat1, lng1, lat2, lng2) {
        var rad = Math.PI / 180, earth = 6371000;
        var dLat = (lat2 - lat1) * rad, dLng = (lng2 - lng1) * rad;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
              + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * earth * Math.asin(Math.min(1, Math.sqrt(a)));
    }

    function nearestStation(lat, lng) {
        var best = null, shortest = Infinity;

        (CONFIG.stations || []).forEach(function (s) {
            var d = haversine(lat, lng, s.lat, s.lng);
            if (d < shortest) { shortest = d; best = s; }
        });

        return best ? { station: best, distance: Math.round(shortest) } : null;
    }

    function fmtMeters(m) {
        return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : m + ' m';
    }

    /**
     * The live location readout over the camera: nearest station, distance, and
     * the raw fix. Amber when outside the station radius — with the reassurance
     * that the punch still counts, just flagged for HR. Courtesy only: the
     * server re-derives all of this at punch time.
     */
    function updateGeoHud() {
        el.geohud.classList.remove('geohud--ok', 'geohud--far');

        if (!state.geo) {
            el.geoDist.textContent = 'Location off — punch is recorded without location';
            el.geoCoords.textContent = 'Lat —, Lng —';
            return;
        }

        el.geoCoords.textContent =
            'Lat ' + state.geo.lat.toFixed(5) + ', Lng ' + state.geo.lng.toFixed(5) +
            (state.geo.accuracy ? '  (±' + Math.round(state.geo.accuracy) + ' m)' : '');

        var near = nearestStation(state.geo.lat, state.geo.lng);

        if (!near) {
            el.geoDist.textContent = 'No attendance station configured';
            return;
        }

        if (near.distance <= near.station.radius_m) {
            el.geohud.classList.add('geohud--ok');
            el.geoDist.textContent = near.station.name + ' · ' + fmtMeters(near.distance) + ' away — within range';
        } else {
            el.geohud.classList.add('geohud--far');
            el.geoDist.textContent = fmtMeters(near.distance) + ' from ' + near.station.name + ' — outside station range';
        }
    }

    updateGeoHud();
    // A fix that ages out of the 2-minute punch window should stop reading as
    // live; a slow refresh keeps the HUD honest without burning the battery.
    setInterval(updateGeoHud, 15000);

    /**
     * Keep a rolling GPS fix so the punch doesn't have to sit and wait for one.
     * A cold getCurrentPosition on a phone takes several seconds; a watch that
     * started at boot has usually settled long before anyone taps the button.
     *
     * Denied or unavailable is fine — the punch goes through and is recorded as
     * "no location shared", which is exactly what HR's monitor displays.
     */
    function startGeoWatch() {
        if (!navigator.geolocation) return;

        navigator.geolocation.watchPosition(function (pos) {
            state.geo = {
                lat:      pos.coords.latitude,
                lng:      pos.coords.longitude,
                accuracy: pos.coords.accuracy,
                at:       Date.now(),
            };

            updateGeoHud();
        }, function () {
            /* leave state.geo as-is; a stale fix beats none */
            updateGeoHud();
        }, { enableHighAccuracy: true, maximumAge: 20000, timeout: 15000 });
    }

    /** The fix to attach to a punch — nothing older than two minutes. */
    function freshGeo() {
        if (!state.geo || Date.now() - state.geo.at > 120000) return null;

        return {
            lat:      state.geo.lat,
            lng:      state.geo.lng,
            accuracy: Math.round(state.geo.accuracy || 0),
        };
    }

    /**
     * Native bridge for the Android WebView wrapper.
     *
     * The app can push fixes from FusedLocationProvider — typically faster and
     * more accurate than what the in-WebView geolocation API returns — by
     * calling, from Kotlin/Java:
     *
     *   webView.evaluateJavascript(
     *       "window.setPortalLocation(" + lat + "," + lng + "," + accuracy + ")",
     *       null
     *   );
     *
     * Call it whenever a fresh fix arrives; the newest fix (native or browser)
     * is the one a punch carries. Returns true when the fix was accepted.
     */
    window.setPortalLocation = function (lat, lng, accuracy) {
        lat = parseFloat(lat);
        lng = parseFloat(lng);

        if (!isFinite(lat) || !isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
            return false;
        }

        state.geo = {
            lat:      lat,
            lng:      lng,
            accuracy: isFinite(parseFloat(accuracy)) ? parseFloat(accuracy) : 0,
            at:       Date.now(),
        };

        updateGeoHud();

        return true;
    };

    // A fix the app pushed while the page was still parsing landed in the head
    // stub's buffer — consume it now that the real implementation exists.
    if (window.__pendingGeo) {
        window.setPortalLocation.apply(null, window.__pendingGeo);
        delete window.__pendingGeo;
    }

    // ---------------------------------------------------------------- station map

    /**
     * The nearest-station map. Deliberately tile-free: no map service, no CDN,
     * so it works on the LGU LAN with no internet. Everything is drawn on a
     * canvas from the same station table the HUD uses.
     *
     *   • each station is a set of blinking "wave" rings whose size is its real
     *     geofence radius, drawn to scale;
     *   • the employee is a live dot that moves with every GPS fix;
     *   • an animated dashed route runs from the dot to the nearest station,
     *     with a marker walking along it — so it reads at a glance which way to
     *     go, and how far, to be inside a station's range.
     *
     * The animation loop runs only while the sheet is open, to spare the battery.
     */
    var map = (function () {
        var canvas = el.mapCanvas;
        if (!canvas) return { open: function () {}, close: function () {} };

        var ctx    = canvas.getContext('2d');
        var raf    = null;
        var open   = false;
        var dpr    = Math.max(1, Math.min(3, window.devicePixelRatio || 1));

        // meters -> pixels, eased toward its target so the "camera" glides as the
        // employee moves rather than snapping.
        var view   = null;   // { scale, cx, cy }
        var target = null;
        var origin = null;   // projection origin { lat, lng }

        function ease(a, b, t) { return a + (b - a) * t; }

        // Local equirectangular projection to meters around the origin. Fine at
        // town scale, and it matches the haversine the HUD/server use closely
        // enough for a courtesy map.
        function project(lat, lng) {
            var mPerLat = 111320;
            var mPerLng = 111320 * Math.cos(origin.lat * Math.PI / 180);
            return { x: (lng - origin.lng) * mPerLng, y: -(lat - origin.lat) * mPerLat };
        }

        function toPx(m) {
            return {
                x: canvas.clientWidth  / 2 + (m.x - view.cx) * view.scale,
                y: canvas.clientHeight / 2 + (m.y - view.cy) * view.scale,
            };
        }

        function stations() { return CONFIG.stations || []; }

        function roundRect(x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y,     x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x,     y + h, r);
            ctx.arcTo(x,     y + h, x,     y,     r);
            ctx.arcTo(x,     y,     x + w, y,     r);
            ctx.closePath();
        }

        function resize() {
            var W = canvas.clientWidth, H = canvas.clientHeight;
            canvas.width  = Math.round(W * dpr);
            canvas.height = Math.round(H * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        // Fit every station (with its radius) plus the employee into the canvas.
        function computeTarget() {
            var W = canvas.clientWidth, H = canvas.clientHeight;
            var pts = [];

            stations().forEach(function (s) {
                var m = project(s.lat, s.lng);
                var r = s.radius_m || 50;
                pts.push({ x: m.x - r, y: m.y - r });
                pts.push({ x: m.x + r, y: m.y + r });
            });

            if (state.geo) pts.push(project(state.geo.lat, state.geo.lng));

            if (!pts.length) { target = { scale: 0.4, cx: 0, cy: 0 }; return; }

            var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            pts.forEach(function (p) {
                if (p.x < minX) minX = p.x;
                if (p.x > maxX) maxX = p.x;
                if (p.y < minY) minY = p.y;
                if (p.y > maxY) maxY = p.y;
            });

            var spanX = Math.max(1, maxX - minX), spanY = Math.max(1, maxY - minY);
            var fill  = 0.78; // fraction of the canvas the content should occupy
            var scale = Math.min(W * fill / spanX, H * fill / spanY);
            scale = Math.max(0.02, Math.min(scale, 2.2)); // no runaway zoom on one point

            target = { scale: scale, cx: (minX + maxX) / 2, cy: (minY + maxY) / 2 };
        }

        function drawGrid(W, H, t) {
            ctx.save();
            ctx.strokeStyle = 'rgba(255,255,255,.045)';
            ctx.lineWidth = 1;
            var step = 42, off = (t * 6) % step;
            for (var x = -off; x < W; x += step) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke(); }
            for (var y = -off; y < H; y += step) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke(); }
            ctx.restore();
        }

        function drawStation(p, rPx, s, isNear, t) {
            rPx = Math.max(12, rPx);
            var rgb    = isNear ? '34,197,94' : '148,163,184';
            var accent = isNear ? '#22C55E'   : '#94A3B8';

            ctx.save();

            // geofence disc + boundary, sized to the real radius
            var grd = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, rPx);
            grd.addColorStop(0, 'rgba(' + rgb + ',.18)');
            grd.addColorStop(1, 'rgba(' + rgb + ',0)');
            ctx.fillStyle = grd;
            ctx.beginPath(); ctx.arc(p.x, p.y, rPx, 0, Math.PI * 2); ctx.fill();

            ctx.strokeStyle = 'rgba(' + rgb + ',.5)';
            ctx.lineWidth = 1.5;
            ctx.setLineDash([5, 5]);
            ctx.beginPath(); ctx.arc(p.x, p.y, rPx, 0, Math.PI * 2); ctx.stroke();
            ctx.setLineDash([]);

            // blinking wave: rings expanding out to the radius, two offset in phase
            for (var i = 0; i < 2; i++) {
                var phase = ((t / 2.6) + i / 2) % 1;
                ctx.globalAlpha = (1 - phase) * (isNear ? 0.9 : 0.5);
                ctx.strokeStyle = accent;
                ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(p.x, p.y, phase * rPx, 0, Math.PI * 2); ctx.stroke();
            }
            ctx.globalAlpha = 1;

            // core marker
            ctx.fillStyle = accent;
            ctx.shadowColor = accent;
            ctx.shadowBlur = isNear ? 14 : 6;
            ctx.beginPath(); ctx.arc(p.x, p.y, isNear ? 7 : 5, 0, Math.PI * 2); ctx.fill();
            ctx.shadowBlur = 0;
            ctx.fillStyle = '#0B1220';
            ctx.beginPath(); ctx.arc(p.x, p.y, isNear ? 3 : 2, 0, Math.PI * 2); ctx.fill();

            // label
            ctx.fillStyle = isNear ? '#DCFCE7' : 'rgba(226,232,240,.8)';
            ctx.font = '600 12px Inter, system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'alphabetic';
            ctx.shadowColor = 'rgba(0,0,0,.8)';
            ctx.shadowBlur = 4;
            ctx.fillText(s.name, p.x, p.y - 13);
            ctx.restore();
        }

        function drawRoute(a, b, best, t) {
            var within = best.distance <= (best.station.radius_m || 50);

            ctx.save();
            ctx.strokeStyle = within ? 'rgba(34,197,94,.7)' : 'rgba(56,224,255,.7)';
            ctx.lineWidth = 2.5;
            ctx.setLineDash([8, 8]);
            ctx.lineDashOffset = -(t * 24) % 16;
            ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
            ctx.setLineDash([]);

            // a marker walking the route, looping from the employee toward the site
            if (!within) {
                var tt = (t / 2.2) % 1;
                var wx = a.x + (b.x - a.x) * tt;
                var wy = a.y + (b.y - a.y) * tt;
                ctx.fillStyle = '#38E0FF';
                ctx.shadowColor = '#38E0FF';
                ctx.shadowBlur = 12;
                ctx.beginPath(); ctx.arc(wx, wy, 5, 0, Math.PI * 2); ctx.fill();
                ctx.shadowBlur = 0;
            }
            ctx.restore();

            // distance pill at the midpoint
            var mx = (a.x + b.x) / 2, my = (a.y + b.y) / 2;
            var label = fmtMeters(best.distance);
            ctx.save();
            ctx.font = '700 11px Inter, system-ui, sans-serif';
            var pw = ctx.measureText(label).width + 16;
            ctx.fillStyle = 'rgba(7,13,24,.88)';
            ctx.strokeStyle = 'rgba(255,255,255,.12)';
            ctx.lineWidth = 1;
            roundRect(mx - pw / 2, my - 11, pw, 22, 11);
            ctx.fill(); ctx.stroke();
            ctx.fillStyle = within ? '#86EFAC' : '#E2F5FF';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, mx, my + 1);
            ctx.restore();
        }

        function drawUser(p, t) {
            var pulse = 0.5 + 0.5 * Math.sin(t * 3);

            ctx.save();
            ctx.fillStyle = 'rgba(56,224,255,' + (0.12 + 0.10 * pulse) + ')';
            ctx.beginPath(); ctx.arc(p.x, p.y, 16 + 6 * pulse, 0, Math.PI * 2); ctx.fill();

            ctx.strokeStyle = '#38E0FF';
            ctx.lineWidth = 2;
            ctx.beginPath(); ctx.arc(p.x, p.y, 10, 0, Math.PI * 2); ctx.stroke();

            ctx.fillStyle = '#38E0FF';
            ctx.shadowColor = '#38E0FF';
            ctx.shadowBlur = 14;
            ctx.beginPath(); ctx.arc(p.x, p.y, 6, 0, Math.PI * 2); ctx.fill();
            ctx.shadowBlur = 0;
            ctx.fillStyle = '#fff';
            ctx.beginPath(); ctx.arc(p.x, p.y, 2.5, 0, Math.PI * 2); ctx.fill();

            ctx.fillStyle = '#E2F5FF';
            ctx.font = '700 11px Inter, system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.shadowColor = 'rgba(0,0,0,.8)';
            ctx.shadowBlur = 4;
            ctx.fillText('You', p.x, p.y + 26);
            ctx.restore();
        }

        function updateFoot(best) {
            el.mapSheet.classList.remove('is-ok', 'is-far');

            if (!state.geo) {
                el.mapDist.textContent = 'Locating…';
                el.mapSub.textContent  = 'Waiting for your GPS position. Make sure location is turned on.';
                return;
            }
            if (!best) {
                el.mapDist.textContent = 'No station configured';
                el.mapSub.textContent  = 'There are no attendance stations to show yet.';
                return;
            }

            var within = best.distance <= (best.station.radius_m || 50);

            if (within) {
                el.mapSheet.classList.add('is-ok');
                el.mapDist.textContent = 'Within range · ' + best.station.name;
                el.mapSub.textContent  = fmtMeters(best.distance) + ' away — you can clock in here.';
            } else {
                el.mapSheet.classList.add('is-far');
                el.mapDist.textContent = fmtMeters(best.distance) + ' to ' + best.station.name;
                el.mapSub.textContent  = 'Walk toward the highlighted station to get inside its range.';
            }
        }

        function draw(t) {
            var W = canvas.clientWidth, H = canvas.clientHeight;
            ctx.clearRect(0, 0, W, H);
            drawGrid(W, H, t);

            var best   = state.geo ? nearestStation(state.geo.lat, state.geo.lng) : null;
            var nearPx = null;

            stations().forEach(function (s) {
                var p      = toPx(project(s.lat, s.lng));
                var rPx    = (s.radius_m || 50) * view.scale;
                var isNear = best && best.station === s;
                drawStation(p, rPx, s, isNear, t);
                if (isNear) nearPx = p;
            });

            if (state.geo) {
                var userPx = toPx(project(state.geo.lat, state.geo.lng));
                if (nearPx && best) drawRoute(userPx, nearPx, best, t);
                drawUser(userPx, t);
            }

            updateFoot(best);
        }

        function frame(ts) {
            if (!open) return;

            // origin: the fixed centroid of the stations (so they don't drift as
            // the employee moves); fall back to the employee, then to zero.
            if (!origin) {
                var ss = stations();
                if (ss.length) {
                    var la = 0, lo = 0;
                    ss.forEach(function (s) { la += s.lat; lo += s.lng; });
                    origin = { lat: la / ss.length, lng: lo / ss.length };
                } else if (state.geo) {
                    origin = { lat: state.geo.lat, lng: state.geo.lng };
                } else {
                    origin = { lat: 0, lng: 0 };
                }
            }

            computeTarget();
            if (!view) {
                view = { scale: target.scale, cx: target.cx, cy: target.cy };
            } else {
                view.scale = ease(view.scale, target.scale, 0.08);
                view.cx    = ease(view.cx,    target.cx,    0.08);
                view.cy    = ease(view.cy,    target.cy,    0.08);
            }

            draw(ts / 1000);
            raf = requestAnimationFrame(frame);
        }

        function openMap() {
            el.mapSheet.classList.remove('d-none');
            el.mapSheet.setAttribute('aria-hidden', 'false');
            open = true;
            origin = null; view = null; target = null;
            resize();
            if (raf) cancelAnimationFrame(raf);
            raf = requestAnimationFrame(frame);
        }

        function closeMap() {
            open = false;
            el.mapSheet.classList.add('d-none');
            el.mapSheet.setAttribute('aria-hidden', 'true');
            if (raf) { cancelAnimationFrame(raf); raf = null; }
        }

        window.addEventListener('resize', function () { if (open) resize(); });
        document.addEventListener('keydown', function (e) { if (open && e.key === 'Escape') closeMap(); });

        return { open: openMap, close: closeMap };
    })();

    el.mapBtn.addEventListener('click', function () { map.open(); });
    el.mapClose.addEventListener('click', function () { map.close(); });

    // ---------------------------------------------------------------- boot

    (async function boot() {
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            return veil('The camera needs a secure connection. Open this page over https:// and try again.');
        }

        startGeoWatch();

        // Camera and models in parallel: the preview appears as soon as the
        // camera grants, and the loop simply says "loading" until the engine is
        // warm instead of holding the whole screen hostage. init() loads both
        // ONNX sessions (WebGPU where available, WASM otherwise) and runs one
        // warmup inference each, so the first real frame pays no compile stall.
        FaceEngine.init({
            modelsUrl: CONFIG.modelsUrl,
            ortPath:   CONFIG.ortPath,
        }).then(function () {
            state.modelsReady = true;
            console.info('FaceEngine ready on ' + FaceEngine.provider);
        }).catch(function (e) {
            console.error('FaceEngine init failed', e);
            // Show the actual reason — on a phone/WebView this is usually the
            // only way to tell a missing file from a blocked one.
            veil('Could not load face recognition.\n' + (e && e.message ? e.message : e) +
                 '\n\nTap to retry.');
            el.veil.style.cursor = 'pointer';
            el.veil.onclick = function () { location.reload(); };
        });

        try {
            await setMode('face');
        } catch (e) {
            console.error(e);
        }
    })();
})();
</script>
