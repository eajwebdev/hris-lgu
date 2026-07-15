{{-- Same gate as the panel itself. Without it every employee viewing their own
     PDS would download megabytes of face models they are not permitted to use. --}}
@if(\App\Http\Middleware\EnsureFaceRegistrar::allows())

{{-- ONNX Runtime Web + the FaceEngine wrapper (SCRFD detection, ArcFace
     embeddings), vendored. No CDN at runtime: this HRIS is reachable on the LGU
     LAN and enrolment has to keep working when the internet does not. --}}
<script defer src="{{ asset('js/onnx/ort.wasm.min.js') }}"></script>
<script defer src="{{ asset('js/face-engine/face-engine.js') }}?v={{ filemtime(public_path('js/face-engine/face-engine.js')) }}"></script>

<style>
    .face-stage {
        position: relative;
        width: 100%;
        aspect-ratio: 4 / 3;
        background: #0f172a;
        border-radius: 10px;
        overflow: hidden;
    }
    /* Mirrored so the subject sees themselves the way a mirror shows them and
       turns their head the right way. The face engine reads the underlying video frame,
       which this transform does not touch. */
    .face-stage video,
    .face-stage canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }
    .face-stage__veil {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, .88);
        color: #fff;
        font-size: 13px;
        text-align: center;
        white-space: pre-line; /* honour \n in status/error messages */
        padding: 16px;
        z-index: 3;
    }
    .face-feedback {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        background: #F1F5F9;
        color: #475569;
        border: 1px solid #E2E8F0;
    }
    .face-feedback--bad   { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
    .face-feedback--ready { background: #ECFDF5; color: #047857; border-color: #A7F3D0; }

    .face-step            { transition: background-color .15s ease; }
    .face-step--active    { background: #FFF7ED; }
    .face-step--done      { background: #ECFDF5; }
    .face-step--done .face-step__label { text-decoration: line-through; color: #047857; }
    .face-step__mark      { width: 18px; text-align: center; color: #CBD5E1; }
    .face-step--active .face-step__mark { color: #EA580C; }
    .face-step--done .face-step__mark   { color: #059669; }

    /* ------------------------------------------------------------- phones.
       The modal becomes the whole screen and every piece is sized so the full
       flow — camera, steps, capture, finish — fits without scrolling. 100dvh
       rather than 100vh, because mobile browser chrome collapses and vh doesn't
       follow it. */
    @media (max-width: 767.98px) {
        #face-modal .modal-dialog {
            margin: 0;
            max-width: 100%;
            width: 100%;
            height: 100vh;
            height: 100dvh;
        }
        #face-modal .modal-content {
            height: 100vh;
            height: 100dvh;
            border: 0;
            border-radius: 0;
            display: flex;
            flex-direction: column;
        }
        #face-modal .modal-header { flex: 0 0 auto; }
        #face-modal .modal-footer { flex: 0 0 auto; padding: 8px 12px calc(env(safe-area-inset-bottom) + 8px); }

        #face-modal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            padding: 10px 12px;
        }
        #face-modal .modal-body > .row {
            height: 100%;
            display: flex;
            flex-direction: column;
            flex-wrap: nowrap;
            min-height: 0;
        }

        /* The camera gives up its fixed 4:3 and instead absorbs whatever height
           the steps and buttons leave over. */
        #face-modal .face-col-camera {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        #face-modal .face-stage {
            flex: 1 1 auto;
            min-height: 120px;
            aspect-ratio: auto;
        }
        #face-modal .face-feedback { min-height: 34px; padding: 6px 10px; font-size: 12px; }

        /* The four steps compress from a list into a row of chips. */
        #face-modal .face-col-progress { flex: 0 0 auto; padding-top: 8px; }
        #face-modal .face-progress-title,
        #face-modal .face-privacy-note,
        #face-modal .face-step small { display: none; }

        #face-modal #face-steps {
            display: flex;
            flex-direction: row;
            gap: 6px;
        }
        #face-modal .face-step {
            flex: 1 1 0;
            padding: 6px 2px !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 10px;
            text-align: center;
        }
        #face-modal .face-step .d-flex {
            flex-direction: column;
            align-items: center !important;
            gap: 2px;
        }
        #face-modal .face-step__mark { margin: 0 !important; }
        #face-modal .face-step__label {
            font-size: 10px;
            line-height: 1.15;
            white-space: nowrap;
        }
    }

    /* Short landscape phones: same treatment triggered by height. */
    @media (max-height: 450px) and (max-width: 991.98px) {
        #face-modal .modal-dialog { margin: 0; max-width: 100%; width: 100%; height: 100vh; height: 100dvh; }
        #face-modal .modal-content { height: 100vh; height: 100dvh; border-radius: 0; }
        #face-modal .modal-body { overflow-y: auto; }
    }
</style>

<script>
(function () {
    'use strict';

    var configEl = document.getElementById('face-config');
    if (!configEl) return;

    var CONFIG = JSON.parse(configEl.textContent);
    var T      = CONFIG.thresholds;
    var ORDER  = Object.keys(CONFIG.steps);
    var CSRF   = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    /* Yaw is computed on the raw (un-mirrored) frame, so a negative yaw means the
       subject turned toward their own left. Lives in config/face.php because the
       attendance portal has to agree with it — see the note there. */
    var YAW_INVERT = !!T.yaw_invert;

    var el = {
        modal:        $('#face-modal'),
        video:        document.getElementById('face-video'),
        overlay:      document.getElementById('face-overlay'),
        veil:         document.getElementById('face-veil'),
        veilText:     document.getElementById('face-veil-text'),
        feedback:     document.getElementById('face-feedback'),
        feedbackText: document.getElementById('face-feedback-text'),
        captureBtn:   document.getElementById('face-capture-btn'),
        captureText:  document.getElementById('face-capture-btn-text'),
        finishBtn:    document.getElementById('face-finish-btn'),
        restartBtn:   document.getElementById('face-restart-btn'),
        progressBar:  document.getElementById('face-progress-bar'),
        progressNum:  document.getElementById('face-progress-count'),
        registerBtn:  document.getElementById('face-register-btn'),
        reregisterBtn:document.getElementById('face-reregister-btn'),
        removeBtn:    document.getElementById('face-remove-btn'),
    };

    var state = {
        modelsReady: false,
        stream:      null,
        looping:     false,
        stepIndex:   0,
        captures:    {},   // type -> embedding array
        lastCheck:   null, // the most recent validation result
        // Movement step: reset each time that step is entered.
        movement: { baseline: null, moved: false },
    };

    var luma = document.createElement('canvas');
    luma.width = luma.height = 32;
    var lumaCtx = luma.getContext('2d', { willReadFrequently: true });

    // ---------------------------------------------------------------- geometry

    /**
     * Head turn, as a signed ratio. Frontal is ~0; negative means the subject
     * turned toward their own left. The measure (nose offset from the eye
     * midpoint over interocular distance) lives in the engine; only the
     * camera-orientation flip is applied here. Scale-free, so it does not drift
     * as the subject moves closer to or further from the camera.
     */
    function yawOf(landmarks) {
        var yaw = FaceEngine.yawOf(landmarks);

        return YAW_INVERT ? -yaw : yaw;
    }

    /** Mean luma inside the face box — the face being lit is what matters, not the room. */
    function brightnessOf(box) {
        var v = el.video;
        var sx = Math.max(0, box.x);
        var sy = Math.max(0, box.y);
        var sw = Math.min(box.width,  v.videoWidth  - sx);
        var sh = Math.min(box.height, v.videoHeight - sy);

        if (sw <= 0 || sh <= 0) return 0;

        lumaCtx.drawImage(v, sx, sy, sw, sh, 0, 0, 32, 32);

        var data = lumaCtx.getImageData(0, 0, 32, 32).data;
        var sum  = 0;

        for (var i = 0; i < data.length; i += 4) {
            sum += 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
        }

        return sum / (data.length / 4);
    }

    var sharp = document.createElement('canvas');
    sharp.width = sharp.height = 64;
    var sharpCtx = sharp.getContext('2d', { willReadFrequently: true });

    /**
     * Variance of a Laplacian over the face crop — near zero when motion blur
     * has flattened the edges. Same check the attendance portal runs, for the
     * same reason: a soft frame makes a soft descriptor.
     */
    function sharpnessOf(box) {
        var v = el.video;
        var sx = Math.max(0, box.x), sy = Math.max(0, box.y);
        var sw = Math.min(box.width,  v.videoWidth  - sx);
        var sh = Math.min(box.height, v.videoHeight - sy);

        if (sw <= 0 || sh <= 0) return 0;

        sharpCtx.drawImage(v, sx, sy, sw, sh, 0, 0, 64, 64);

        var data = sharpCtx.getImageData(0, 0, 64, 64).data;
        var g = new Float32Array(64 * 64);

        for (var i = 0, j = 0; i < data.length; i += 4, j++) {
            g[j] = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
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

    // ---------------------------------------------------------------- checks

    /**
     * The gate every capture has to pass. Ordered so the message the operator
     * reads names the *first* thing wrong, rather than an incidental symptom of
     * it — a face that is too far away also scores badly, and being told to move
     * closer is more use than being told the confidence is low.
     */
    function evaluate(detections, step) {
        if (!detections.length) {
            return { ok: false, message: 'No face detected' };
        }

        if (detections.length > 1) {
            return { ok: false, message: 'Only one person should be visible' };
        }

        var d      = detections[0];
        var box    = d.box;
        var score  = d.score;
        var ratio  = box.width / el.video.videoWidth;

        if (ratio < T.min_face_ratio) {
            return { ok: false, message: 'Please move closer to the camera', detection: d };
        }

        if (score < T.min_detection_score) {
            return { ok: false, message: 'Please position your face properly', detection: d };
        }

        if (brightnessOf(box) < T.min_brightness) {
            return { ok: false, message: 'Lighting is insufficient', detection: d };
        }

        // A blurred enrolment haunts every future match, so blur is refused at
        // the door rather than averaged into the master embedding.
        if (sharpnessOf(box) < T.min_sharpness) {
            return { ok: false, message: 'Hold still — the image is blurry', detection: d };
        }

        var pose = checkPose(d, step, box);
        if (pose) {
            return { ok: false, message: pose, detection: d };
        }

        return { ok: true, message: CONFIG.steps[step].label + ' — hold still and capture', detection: d };
    }

    /** Per-step pose requirement. Returns an instruction when unmet, null when met. */
    function checkPose(d, step, box) {
        var landmarks = d.landmarks;
        var yaw       = yawOf(landmarks);

        if (step === 'front') {
            return Math.abs(yaw) <= T.front_yaw_max ? null : 'Look directly at the camera';
        }

        if (step === 'left') {
            return yaw <= -T.turn_yaw_min ? null : 'Turn your head slightly to the left';
        }

        if (step === 'right') {
            return yaw >= T.turn_yaw_min ? null : 'Turn your head slightly to the right';
        }

        // movement: a deliberate head shift, which is what the instruction asks
        // for and what a still photograph held up to the lens cannot produce.
        // (This used to also accept a blink; the 5-point landmarks the SCRFD
        // detector emits carry no eye contour, so a blink cannot be seen.)
        var m    = state.movement;
        var nose = landmarks.nose;

        if (!m.baseline) {
            m.baseline = { x: nose.x, y: nose.y };
        } else {
            var travelled = Math.hypot(nose.x - m.baseline.x, nose.y - m.baseline.y) / box.width;
            if (travelled > T.movement_min) {
                m.moved = true;
            }
        }

        return m.moved ? null : 'Slightly move your head';
    }

    // ---------------------------------------------------------------- drawing

    function draw(detection, ok) {
        var canvas = el.overlay;
        var ctx    = canvas.getContext('2d');

        if (canvas.width !== el.video.videoWidth) {
            canvas.width  = el.video.videoWidth;
            canvas.height = el.video.videoHeight;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (!detection) return;

        var box = detection.box;

        ctx.lineWidth   = 3;
        ctx.strokeStyle = ok ? '#22C55E' : '#F97316';
        ctx.strokeRect(box.x, box.y, box.width, box.height);
    }

    // ---------------------------------------------------------------- loop

    async function loop() {
        if (!state.looping) return;

        try {
            if (el.video.readyState === 4) {
                var detections = await FaceEngine.detect(el.video, {
                    size: 320,            // preview only — capture re-detects at 640
                    scoreThreshold: 0.35, // permissive: our own gate decides, and a
                                          // rejected-but-seen face lets us say why
                });

                var step   = ORDER[state.stepIndex];
                var result = evaluate(detections, step);

                state.lastCheck = result;

                draw(result.detection, result.ok);
                setFeedback(result.message, result.ok);

                el.captureBtn.disabled = !result.ok;
            }
        } catch (e) {
            // A transient decode error should not kill the loop.
            console.error('face detection frame failed', e);
        }

        // ~8 fps. Enough for guidance to feel live without pinning a low-end
        // office CPU that also has to run the rest of the HRIS.
        setTimeout(loop, 120);
    }

    function setFeedback(message, ok) {
        el.feedbackText.textContent = message;
        el.feedback.className = 'face-feedback mt-2 ' + (ok ? 'face-feedback--ready' : 'face-feedback--bad');
        el.feedback.querySelector('i').className = ok
            ? 'fas fa-check-circle fa-fw'
            : 'fas fa-exclamation-circle fa-fw';
    }

    // ---------------------------------------------------------------- capture

    async function capture() {
        var step = ORDER[state.stepIndex];

        if (!state.lastCheck || !state.lastCheck.ok) return;

        el.captureBtn.disabled = true;
        el.captureText.textContent = 'Capturing…';

        try {
            // 640 for the enrolment frame itself: a tighter box and cleaner
            // landmarks give the recognition net a better-aligned crop, and
            // this price is paid exactly four times per registration.
            var result = (await FaceEngine.detect(el.video, { size: 640, scoreThreshold: 0.45 }))[0];

            if (!result) {
                setFeedback('No face detected', false);
                return;
            }

            // Re-run the gate against the frame we actually took, not the one
            // that happened to be on screen when the button was pressed.
            var recheck = evaluate([result], step);

            if (!recheck.ok) {
                setFeedback(recheck.message, false);
                return;
            }

            // The ArcFace pass is the expensive one, so it runs here — four
            // times per registration — rather than on every preview frame.
            state.captures[step] = Array.from(await FaceEngine.embed(el.video, result));

            markStepDone(step);

            state.stepIndex++;
            resetMovement();

            if (state.stepIndex >= ORDER.length) {
                finishReady();
            } else {
                enterStep();
            }
        } catch (e) {
            console.error(e);
            setFeedback('Capture failed. Please try again.', false);
        } finally {
            el.captureText.textContent = state.stepIndex < ORDER.length
                ? 'Capture ' + CONFIG.steps[ORDER[state.stepIndex]].label
                : 'All captures complete';
        }
    }

    function markStepDone(step) {
        var li = document.querySelector('.face-step[data-step="' + step + '"]');
        li.classList.remove('face-step--active');
        li.classList.add('face-step--done');
        li.querySelector('.face-step__mark i').className = 'fas fa-check-circle';

        var done = Object.keys(state.captures).length;
        el.progressNum.textContent = done;
        el.progressBar.style.width = (done / ORDER.length * 100) + '%';
        el.progressBar.setAttribute('aria-valuenow', done);
    }

    function enterStep() {
        document.querySelectorAll('.face-step').forEach(function (li) {
            li.classList.remove('face-step--active');
        });

        var step = ORDER[state.stepIndex];
        document.querySelector('.face-step[data-step="' + step + '"]').classList.add('face-step--active');

        el.captureText.textContent = 'Capture ' + CONFIG.steps[step].label;
        el.captureBtn.disabled = true;
    }

    function resetMovement() {
        state.movement = { baseline: null, moved: false };
    }

    /** All four in hand — only now does Finish become clickable. */
    function finishReady() {
        el.captureBtn.disabled = true;
        el.finishBtn.disabled  = false;
        setFeedback('4/4 captured — click Finish Registration to save', true);
    }

    // ---------------------------------------------------------------- models

    /**
     * Loading starts the moment the page does, not when the modal opens — by the
     * time HR has found the employee and clicked Register, the ~16 MB of ONNX
     * models are already in. FaceEngine.init() also runs one warmup inference
     * per net, which is what makes the first real frame instant instead of a
     * compile stall.
     */
    var modelsPromise = null;

    function ensureModels() {
        if (!modelsPromise) {
            modelsPromise = FaceEngine.init({
                modelsUrl: CONFIG.modelsUrl,
                ortPath:   CONFIG.ortPath,
            }).then(function () {
                state.modelsReady = true;
                console.info('FaceEngine ready on ' + FaceEngine.provider);
            });
        }

        return modelsPromise;
    }

    // Kick it off as soon as the engine itself is ready. The library loads with
    // `defer`, so it is NOT yet defined while this inline script parses —
    // DOMContentLoaded is the earliest moment both are guaranteed present.
    function preload() {
        ensureModels().catch(function (e) { console.error('model preload failed', e); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', preload);
    } else {
        preload();
    }

    // ---------------------------------------------------------------- camera

    async function start() {
        reset();

        // getUserMedia is only exposed on a secure origin. On plain http over the
        // LAN the API is simply absent, which is worth saying plainly rather than
        // letting it surface as "undefined is not a function".
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            return veil('Camera access requires a secure connection (HTTPS). Open this page over https:// and try again.');
        }

        try {
            if (!state.modelsReady) {
                veil('Loading face recognition models…');

                try {
                    await ensureModels();
                } catch (modelErr) {
                    // A model-load failure is not a camera failure — name it as
                    // itself, with the reason, so a missing/mis-served ONNX file
                    // on a phone is diagnosable rather than a blank hang.
                    console.error('model load failed', modelErr);
                    return veil('Could not load face recognition.\n' +
                        (modelErr && modelErr.message ? modelErr.message : modelErr));
                }
            }

            veil('Waiting for camera permission…');

            state.stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                audio: false,
            });

            el.video.srcObject = state.stream;
            await el.video.play();

            hideVeil();
            enterStep();

            state.looping = true;
            loop();
        } catch (e) {
            console.error(e);

            if (e.name === 'NotAllowedError' || e.name === 'SecurityError') {
                veil('Camera permission was denied. Allow camera access for this site, then reopen.');
            } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') {
                veil('No camera was found on this computer.');
            } else if (e.name === 'NotReadableError') {
                veil('The camera is already in use by another application.');
            } else {
                veil('Could not start the camera: ' + (e.message || e.name));
            }
        }
    }

    function stop() {
        state.looping = false;

        if (state.stream) {
            state.stream.getTracks().forEach(function (track) { track.stop(); });
            state.stream = null;
        }

        el.video.srcObject = null;
    }

    function reset() {
        state.stepIndex = 0;
        state.captures  = {};
        state.lastCheck = null;
        resetMovement();

        document.querySelectorAll('.face-step').forEach(function (li) {
            li.classList.remove('face-step--active', 'face-step--done');
            li.querySelector('.face-step__mark i').className = 'far fa-circle';
        });

        el.progressNum.textContent = '0';
        el.progressBar.style.width = '0%';
        el.finishBtn.disabled  = true;
        el.captureBtn.disabled = true;
    }

    function veil(message) {
        el.veilText.textContent = message;
        el.veil.classList.remove('d-none');
        el.veil.style.display = 'flex';
    }

    function hideVeil() {
        el.veil.style.display = 'none';
    }

    // ---------------------------------------------------------------- submit

    async function submit() {
        if (Object.keys(state.captures).length !== ORDER.length) return;

        el.finishBtn.disabled = true;
        el.finishBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

        var payload = ORDER.map(function (type) {
            return { type: type, embedding: state.captures[type] };
        });

        try {
            var response = await fetch(CONFIG.urls.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ captures: payload }),
            });

            var body = await response.json();

            if (!response.ok) {
                // 422 covers both a rejected duplicate and a malformed descriptor;
                // the server's message says which, and is safe to show as-is.
                throw new Error(body.message || 'Registration failed.');
            }

            applySummary(body.face);

            el.modal.modal('hide');
            toastr.success(body.message);
        } catch (e) {
            toastr.error(e.message || 'Registration failed.');
            el.finishBtn.disabled = false;
        } finally {
            el.finishBtn.innerHTML = '<i class="fas fa-save"></i> Finish Registration';
        }
    }

    async function remove() {
        var confirmed = await Swal.fire({
            title: 'Remove face data?',
            text: "Are you sure you want to remove this employee's face recognition data?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove it',
            confirmButtonColor: '#DC2626',
            cancelButtonText: 'Cancel',
        });

        if (!confirmed.isConfirmed) return;

        try {
            var response = await fetch(CONFIG.urls.remove, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });

            var body = await response.json();

            if (!response.ok) throw new Error(body.message || 'Removal failed.');

            applySummary(body.face);
            toastr.success(body.message);
        } catch (e) {
            toastr.error(e.message || 'Removal failed.');
        }
    }

    /** Swap the profile panel between its registered and unregistered states. */
    function applySummary(face) {
        var registered   = document.getElementById('face-status-registered');
        var unregistered = document.getElementById('face-status-unregistered');

        registered.classList.toggle('d-none', !face.registered);
        unregistered.classList.toggle('d-none', face.registered);

        if (!face.registered) return;

        document.getElementById('face-capture-count').textContent = face.capture_count;
        document.getElementById('face-registered-at').textContent = face.registered_at || 'date not recorded';
        document.getElementById('face-registered-by').textContent = face.registered_by || 'not recorded';
        document.getElementById('face-legacy-note').classList.toggle('d-none', !face.legacy);
    }

    // ---------------------------------------------------------------- wiring

    function open() {
        el.modal.modal('show');
    }

    if (el.registerBtn)   el.registerBtn.addEventListener('click', open);
    if (el.reregisterBtn) el.reregisterBtn.addEventListener('click', open);
    if (el.removeBtn)     el.removeBtn.addEventListener('click', remove);

    el.captureBtn.addEventListener('click', capture);
    el.finishBtn.addEventListener('click', submit);

    el.restartBtn.addEventListener('click', function () {
        reset();
        enterStep();
    });

    el.modal.on('shown.bs.modal', start);

    // Releasing the camera when the modal closes is not housekeeping — it is what
    // turns the webcam light off. Leaving it on after enrolment looks, to the
    // employee who just sat down in front of it, exactly like being recorded.
    el.modal.on('hidden.bs.modal', stop);
})();
</script>

@endif
