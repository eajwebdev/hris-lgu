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
        go:        document.getElementById('go'),
        goText:    document.getElementById('go-text'),
        modeBtn:   document.getElementById('mode-toggle'),
        named:     document.getElementById('named'),
        namedName: document.getElementById('named-name'),
        namedPos:  document.getElementById('named-pos'),
        namedInit: document.getElementById('named-initials'),
        result:    document.getElementById('result'),
        clock:     document.getElementById('clock'),
        today:     document.getElementById('today'),
        segments:  document.querySelectorAll('.segmented button'),
    };

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
     * turned toward their own left.
     */
    function yawOf(landmarks) {
        var jaw  = landmarks.getJawOutline();
        var nose = landmarks.getNose()[3];

        var left  = nose.x - jaw[0].x;
        var right = jaw[16].x - nose.x;
        var total = left + right;

        if (total <= 0) return 0;

        var yaw = (right - left) / total;

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
        var box = d.detection.box;

        if (box.width / el.video.videoWidth < T.min_face_ratio) {
            return { ok: false, message: 'Please move closer to the camera', detection: d };
        }
        if (d.detection.score < T.min_detection_score) {
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

    function drawBox(detection, ok) {
        var c = el.overlay, ctx = c.getContext('2d');

        if (c.width !== el.video.videoWidth) {
            c.width  = el.video.videoWidth;
            c.height = el.video.videoHeight;
        }

        ctx.clearRect(0, 0, c.width, c.height);

        if (!detection) return;

        var b = detection.detection.box;
        ctx.lineWidth = 4;
        ctx.strokeStyle = ok ? '#22C55E' : '#F97316';
        ctx.strokeRect(b.x, b.y, b.width, b.height);
    }

    // ---------------------------------------------------------------- detectors

    // 256 for the preview loop: the framing gate already demands a face filling a
    // fifth of the frame, and a face that size is trivially found at 256 — at
    // roughly 60% the cost of 320, which is the difference between a smooth and a
    // stuttering preview on a cheap phone.
    var cheapOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 256, scoreThreshold: 0.3 });
    var fullOptions  = new faceapi.TinyFaceDetectorOptions({ inputSize: 416 });

    /** Landmarks only — cheap enough to poll while the person moves. */
    function detectCheap() {
        return faceapi.detectAllFaces(el.video, cheapOptions).withFaceLandmarks();
    }

    /** Landmarks + the 128-float descriptor. The expensive one, run sparingly. */
    function detectFull() {
        return faceapi.detectSingleFace(el.video, fullOptions).withFaceLandmarks().withFaceDescriptor();
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

        el.go.disabled = !gate.ok;

        setHint(gate.ok ? 'Ready — tap ' + label() : gate.message, gate.ok ? 'ok' : 'bad');
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
     * The guided capture. Straight-ahead frames first, then the two head turns the
     * server just asked for — in the order it asked for them.
     *
     * The cue on screen is only guidance. What actually decides the outcome is
     * whether the turned frames look more like this employee's enrolled left/right
     * captures than each other's, and that is judged server-side. A photograph can
     * be waved at the lens all day; it cannot turn its head.
     */
    async function runSequence() {
        var t0     = performance.now();
        var frames = [];

        var challenge = await getChallenge();

        // Straight ahead.
        showCue(null, 'Look straight at the camera');

        for (var i = 0; i < L.min_neutral_frames; i++) {
            var neutral = await captureAt('front', 'Look straight at the camera');

            frames.push({
                stage: 'neutral',
                pose: null,
                t: Math.round(performance.now() - t0),
                descriptor: Array.from(neutral.descriptor),
            });

            // Spaced out, so consecutive frames are genuinely different moments.
            await sleep(260);
        }

        // The two turns, in the order the server chose.
        for (var p = 0; p < challenge.poses.length; p++) {
            var pose = challenge.poses[p];
            var say  = pose === 'left' ? 'Turn your head to your LEFT' : 'Turn your head to your RIGHT';

            showCue(pose, say);

            var turned = await captureAt(pose, say);

            frames.push({
                stage: 'pose',
                pose: pose,
                t: Math.round(performance.now() - t0),
                descriptor: Array.from(turned.descriptor),
            });

            await sleep(180);
        }

        hideCue();

        return { nonce: challenge.nonce, frames: frames };
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
        var deadline = performance.now() + 20000;

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

            var full = await detectFull();

            if (full && poseHolds(full.landmarks, pose)) {
                return full;
            }

            await sleep(90);
        }

        throw new Error('Face check timed out. Please try again.');
    }

    // ---------------------------------------------------------------- punch

    async function punch() {
        if (state.busy || el.go.disabled) return;

        state.busy = true;
        el.go.disabled = true;
        el.modeBtn.disabled = true;

        try {
            var run = await runSequence();

            setHint('Matching…', null);

            var payload = {
                mode:   state.mode === 'qrface' ? 'qr' : 'face',
                action: state.action,
                nonce:  run.nonce,
                frames: run.frames,
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

        // The challenge is spent either way, so a retry starts a fresh one.
        state.busy = false;
        el.modeBtn.disabled = false;
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

            return 'Recorded ' + d + ' from ' + location.station_name;
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

        state.qrToken = null;
        state.busy    = false;

        setMode('face');
    }

    // ---------------------------------------------------------------- modes

    function label() {
        return state.action === 'out' ? 'CLOCK OUT' : 'CLOCK IN';
    }

    function paintAction() {
        el.goText.textContent = label();
        el.go.classList.toggle('primary--out', state.action === 'out');

        el.segments.forEach(function (btn) {
            btn.setAttribute('aria-pressed', String(btn.dataset.action === state.action));
        });
    }

    async function setMode(mode) {
        state.mode = mode;
        state.busy = false;

        el.go.disabled = true;
        el.guide.classList.remove('guide--ok');
        hideCue();

        var qr = mode === 'qr';

        // Rear camera for a badge, front for a face. Un-mirror the rear view — a
        // mirrored world is disorienting to aim in.
        el.stage.classList.toggle('stage--mirror', !qr);
        el.guideOval.classList.toggle('d-none', qr);
        el.guideBox.classList.toggle('d-none', !qr);

        el.modeBtn.innerHTML = (qr || mode === 'qrface')
            ? '<i class="fas fa-user"></i>&nbsp; Use face only instead'
            : '<i class="fas fa-qrcode"></i>&nbsp; Scan QR first — faster and more accurate';

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

    el.segments.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (state.busy) return; // not mid-sequence

            state.action = btn.dataset.action;
            paintAction();
        });
    });

    el.go.addEventListener('click', punch);

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

    // ---------------------------------------------------------------- geo

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
        }, function () {
            /* leave state.geo as-is; a stale fix beats none */
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

        return true;
    };

    // ---------------------------------------------------------------- warmup

    /**
     * TF.js compiles its WebGL shaders on first inference, which turns the very
     * first real detection into a multi-second stall on a phone. Running each net
     * once against a blank canvas moves that stall into boot, behind the veil,
     * where nobody is standing in front of the camera waiting on it.
     */
    async function warmupModels() {
        try {
            var c = document.createElement('canvas');
            c.width = c.height = 256;
            c.getContext('2d').fillRect(0, 0, 256, 256);

            await faceapi.detectAllFaces(c, cheapOptions);

            var c150 = document.createElement('canvas');
            c150.width = c150.height = 150;
            c150.getContext('2d').fillRect(0, 0, 150, 150);

            await faceapi.nets.faceLandmark68Net.detectLandmarks(c150);
            await faceapi.nets.faceRecognitionNet.computeFaceDescriptor(c150);
        } catch (e) {
            // Warmup is an optimisation; a failure here must never block boot.
            console.warn('model warmup skipped', e);
        }
    }

    (async function boot() {
        paintAction();

        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            return veil('The camera needs a secure connection. Open this page over https:// and try again.');
        }

        startGeoWatch();

        // Camera and models in parallel: the preview appears as soon as the
        // camera grants, and the loop simply says "loading" until the nets are
        // warm instead of holding the whole screen hostage.
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(CONFIG.modelsUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(CONFIG.modelsUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(CONFIG.modelsUrl),
        ]).then(warmupModels).then(function () {
            state.modelsReady = true;
        }).catch(function (e) {
            console.error(e);
            veil('Could not load face recognition. Check your connection and reload.');
        });

        try {
            await setMode('face');
        } catch (e) {
            console.error(e);
        }
    })();
})();
</script>
