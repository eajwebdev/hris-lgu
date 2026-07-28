<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Embedding dimension
    |--------------------------------------------------------------------------
    |
    | The ArcFace recognition model (w600k_mbf.onnx, run in the browser on ONNX
    | Runtime Web) emits a 512-float embedding. Every vector we accept, average
    | or compare is checked against this length.
    |
    | MIGRATION NOTE: the previous engine (face-api.js) produced 128-float
    | descriptors. The two are mathematically incompatible — a 128-d enrolment
    | cannot be compared to, or converted into, a 512-d ArcFace embedding. Any
    | employee enrolled under the old engine shows as "not registered" (the
    | length check filters their stored vectors out everywhere) and must be
    | re-registered through the Face Recognition page.
    |
    */

    'dimension' => 512,

    /*
    |--------------------------------------------------------------------------
    | Required captures
    |--------------------------------------------------------------------------
    |
    | Registration is only accepted once all of these poses have been captured,
    | exactly once each. The order here is the order the UI walks through.
    |
    */

    'captures' => ['front', 'left', 'right', 'movement'],

    /*
    |--------------------------------------------------------------------------
    | Duplicate rejection threshold
    |--------------------------------------------------------------------------
    |
    | Squared-euclidean distance between two L2-normalised master embeddings.
    | Anything closer than this is treated as the same human being and the
    | registration is refused.
    |
    | Lower  = stricter (fewer false "already registered", more real duplicates
    |          slip through).
    | Higher = looser (catches more duplicates, risks blocking lookalikes such
    |          as identical twins, which this system cannot separate anyway).
    |
    | ArcFace scale. Distances live on the unit sphere (d² = 2 − 2·cosine), and
    | ArcFace spreads people much further apart than face-api did: same person
    | across sessions typically lands at cosine 0.55+ (d ≤ ~0.95), different
    | people below cosine 0.3 (d ≥ ~1.18). 1.0 (cosine 0.5) sits between with
    | margin on both sides.
    |
    */

    'duplicate_distance' => 1.0,

    /*
    |--------------------------------------------------------------------------
    | Matching (attendance portal)
    |--------------------------------------------------------------------------
    |
    | 'distance' is the furthest a probe may sit from an enrolled face and still
    | be called a match, in euclidean distance on the unit sphere.
    |
    | 'ratio' is the more important one. A probe is only accepted if the best
    | candidate is meaningfully closer than the runner-up (best/second <= ratio).
    | An unenrolled stranger tends to sit at a mediocre distance from *everybody*,
    | so the absolute threshold alone will happily hand their face to whichever
    | employee they least resemble. The ratio test is what refuses that.
    |
    | 'shortlist' caps how many candidates get the expensive per-capture refine
    | pass after the cheap master-embedding scan.
    |
    | ArcFace scale (see the duplicate_distance note): 1.10 euclidean is cosine
    | ≈ 0.40, a standard acceptance point for this model family. The ratio test
    | is unchanged — it is scale-free, and ArcFace's wider person-to-person
    | separation only makes it bite harder.
    |
    */

    'match' => [
        'distance'  => 1.10,
        'ratio'     => 0.78,
        'shortlist' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Liveness (attendance portal)
    |--------------------------------------------------------------------------
    |
    | A photograph passes every ordinary face check: it is a real face, correctly
    | lit, the right size, looking at the lens. What a still photograph cannot do
    | is obey an instruction it has never seen.
    |
    | This build uses RANDOM POSE CHALLENGES. The server mints a single-use
    | challenge naming a random sequence of head gestures (drawn from 'pose_pool',
    | shuffled), and the client must capture the employee performing them in that
    | exact order, after a run of straight-ahead frames. A printed photo or a
    | phone screen cannot turn left when told to — and because the sequence is
    | random per attempt, an attacker cannot pre-record the right moves.
    |
    | The browser is untrusted — it only gathers frames. Everything below is
    | re-checked on the server against the descriptors: the challenged poses must
    | be present in the issued order, every frame must verify as the enrolled
    | employee, the straight-ahead frames must drift the way a living face does
    | ('min_variation'), and the pose frames must sit measurably away from the
    | straight-ahead master ('min_pose_shift') — a rigid image swivelled on the
    | spot produces embeddings that barely move.
    |
    | Blink detection is NOT possible on this engine: SCRFD provides five
    | landmarks (eyes, nose tip, mouth corners) with no eye contour to read a
    | blink from. Head gestures are the challenge this hardware can verify.
    |
    | Honest limit: a coached live video replay of the employee performing many
    | gestures could in principle follow along; the random order, the timing
    | window, and the anti-spoof pixel model below are what make that
    | impractical. The 'flash_count'/'min_flash_delta' screen-flash sequence
    | further down this array closes exactly that gap for an *unmodified*
    | replay device — a phone or video-call app has no reason to react to a
    | light it was never recorded reacting to — but neither check is a
    | guarantee, and an attacker who has additionally patched the client JS to
    | fabricate liveness/luma values is not caught by either alone (the same
    | class of risk the anti-spoof score already carries). Where identity truly
    | matters, the QR path (encrypted token + 1:1 face verify) remains the
    | stronger option.
    |
    */

    'liveness' => [
        // Straight-ahead frames the client gathers before the gestures. More
        // frames give the variation test below more to read and average the
        // identity match over a steadier signal.
        'min_neutral_frames' => 5,

        // Gestures the challenge can demand, and how many are drawn per attempt
        // (distinct, shuffled). left/right are yaw turns; up/down are pitch
        // tilts measured against the employee's own frontal baseline.
        'pose_pool'  => ['left', 'right', 'up', 'down'],
        'pose_count' => 2,

        // Squared-free euclidean distance a challenged-pose frame must sit from
        // the straight-ahead master embedding. A genuine head turn moves the
        // embedding well past this; a flat image rotated in front of the lens
        // barely moves it. Kept modest so a subtle but real turn still passes.
        'min_pose_shift' => 0.05,

        // Upper bound on the face captures in a payload, so the endpoint
        // cannot be used to ship megabytes of vectors. Must stay at least
        // min_neutral_frames + count(pose_pool) — 5 + 4 = 9 at the pool's full
        // extent. The flash samples are NOT counted here: they are light
        // readings rather than embeddings, and are capped separately in the
        // controller's 'flash.samples' rule.
        'max_frames' => 12,

        // The frames must span at least this long — a human takes time to
        // perform two gestures; a payload assembled in one instant does not.
        'min_duration_ms' => 1200,
        'max_duration_ms' => 40000,

        // Seconds a challenge stays usable. Single-use regardless.
        'challenge_ttl' => 90,

        // A weak secondary check: consecutive frames of a live face are never
        // identical, while a photo held perfectly still is. This only catches a
        // rigidly-held still image; the real work is done by the pose challenge
        // and the anti-spoof model. Kept low so it never rejects a live employee.
        'min_variation' => 0.02,

        /*
        | ACTIVE ILLUMINATION ("flash") CHALLENGE
        |
        | The kiosk turns its own screen into a light source: it paints a
        | server-chosen sequence of full-screen colours and measures what comes
        | back off the subject. This is the same principle commercial passive
        | liveness uses, and it is what closes the gap the pose challenge leaves
        | open — a coached video replay can follow spoken instructions, but it
        | cannot make a recording react to a light that was never shining when
        | the recording was made.
        |
        | THREE independent things are verified, because any one of them alone
        | has a plausible way around it:
        |
        |   1. Brightness ('min_flash_delta'). The face is brighter under the
        |      white segment than under the dark one. Beaten by: an attacker
        |      who happens to be waving the phone, or ambient flicker.
        |
        |   2. Face-vs-background differential ('min_face_bg_delta'). Light
        |      falls off with distance, so the kiosk's own screen lights the
        |      face much more than it lights the wall behind. When the "face"
        |      is really a phone held to the lens, the whole frame IS that
        |      phone's display: face region and surrounding region carry the
        |      same emitted light and move together, so the differential
        |      collapses toward zero. This is the check that a replay device
        |      cannot satisfy by any amount of coaching, and it is the strongest
        |      signal here.
        |
        |   3. Colour response ('min_chroma_shift'). Under a red segment a real
        |      face reflects proportionally more red; under green, more green.
        |      A screen showing a recording keeps whatever colour balance was
        |      recorded. Measured as a ratio between channels, not an absolute,
        |      so it survives white balance and exposure drifting.
        |
        | 'flash_count' is how many segments are drawn. 0 is the kill switch —
        | checkFlash() treats an empty sequence as nothing to verify, exactly as
        | checkPoses() no-ops on an empty pose list.
        */

        'flash_count' => 4,

        // Segments the sequence is drawn from. 'white'/'dark' carry the
        // brightness and differential checks; the colours carry the chroma
        // check. issueFlash() always includes at least one white, one dark and
        // one colour, then fills the rest at random — so every attempt tests
        // all three properties in an order the client cannot predict.
        'flash_palette' => ['white', 'dark', 'red', 'green', 'blue'],

        // Face luma (0-255) under 'white' minus under 'dark'. Same scale as
        // 'min_brightness' below. Kept low: in a bright hall the screen adds
        // little next to daylight, and this check is not carrying the weight
        // on its own.
        'min_flash_delta' => 8,

        // How much more the FACE must respond than the background does, in the
        // same luma units. The one number worth tuning first if a real screen
        // spoof ever gets through — raise it. Lower it only if employees at a
        // kiosk mounted very close to a wall are being turned away, since a
        // wall right behind the head shrinks the natural gap.
        'min_face_bg_delta' => 4,

        // Rise in a colour's share of the face's total RGB under that colour's
        // segment, versus that share averaged across the whole sequence.
        // Dimensionless (a ratio of ratios), so exposure and white balance
        // drift do not move it. 0.03 is roughly a 3-point shift on a channel
        // that normally sits near a third of the total.
        'min_chroma_shift' => 0.03,

        // Milliseconds the screen holds each colour before the sample is taken:
        // enough for the panel to repaint and the camera's auto-exposure to
        // start reacting.
        //
        // ALSO A SAFETY FLOOR, not only a timing one. Full-screen flashing
        // between roughly 3 Hz and 30 Hz can provoke seizures in people with
        // photosensitive epilepsy. At 320ms per segment the screen changes at
        // about 2.5 Hz — deliberately under that band. Do not lower this below
        // ~340ms without re-checking that arithmetic; the security value of a
        // faster sequence is not worth the risk.
        'flash_settle_ms' => 320,
    ],

    /*
    |--------------------------------------------------------------------------
    | Anti-spoofing (MiniFASNet)
    |--------------------------------------------------------------------------
    |
    | A trained model (public/models/arcface/antispoof.onnx) that looks at the
    | face crop and its border and judges whether it is a live person or a
    | picture of one — a printed photo, or a face shown on a phone or monitor.
    | This is what the frame-variation check above cannot do: a photo waved in
    | front of the lens varies frame to frame just like a face, but it still
    | looks like a photo to this model.
    |
    | It runs in the browser (the pixels are only there; no image is sent to the
    | server). In the locked kiosk WebView that is a real defence. The score is
    | also sent to the server, which enforces the same threshold and logs it —
    | so the policy lives here, in one place.
    |
    | 'min_real' is the probability-of-live floor on the AVERAGE across the
    | captured frames. A genuine face scores very high (~0.95+); a photo or
    | screen scores low. Raised from the model's 0.5 default after phone/print
    | spoofs were seen slipping past it. LOWER IT back toward 0.5 if real
    | employees on a poor camera are being turned away. Watch the logged
    | 'liveness' values to tune.
    |
    | 'min_real_frame' is a floor on the single WORST frame. An average can be
    | dragged over the line by a couple of lucky frames; a live face never
    | produces a frame the model is confident is a spoof, so one such frame is
    | disqualifying on its own.
    |
    | 'require_score' makes the check fail CLOSED: a punch that arrives without
    | a score is refused instead of waved through. Without this, a tampered
    | client bypasses anti-spoofing by simply omitting the field. Turn it off
    | only if a kiosk genuinely cannot load the anti-spoof model.
    |
    */

    'antispoof' => [
        'enabled'        => true,
        'min_real'       => 0.70,
        'min_real_frame' => 0.35,
        'require_score'  => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser-side capture gates
    |--------------------------------------------------------------------------
    |
    | Passed to the JavaScript so the thresholds live in one place. See
    | resources/views/script/faceRegistrationScript.blade.php for how each is
    | applied.
    |
    */

    'client' => [
        // SCRFD confidence below which we refuse to capture. SCRFD's usual
        // operating threshold is 0.5; confident real faces score well above it.
        'min_detection_score' => 0.50,

        // Face box width as a fraction of the video width. Under this the face
        // is too far away for a usable embedding.
        'min_face_ratio' => 0.20,

        // Mean luma (0-255) inside the face box. Under this it is too dark.
        'min_brightness' => 55,

        // Variance of a Laplacian filter over the face crop (64x64 grayscale).
        // Motion blur flattens edges and drags this toward zero; a blurred frame
        // yields a mushy embedding that degrades every later match, so it is
        // cheaper to refuse the frame than to enrol it. Deliberately low — it is
        // an obvious-blur catch, not a focus meter. Kept low so a cheap phone
        // camera is not made to re-capture "hold still" over and over — that
        // re-capture loop is dead time an employee reads as the portal hanging.
        'min_sharpness' => 14,

        // Yaw is now the nose tip's offset from the eye midpoint in units of
        // interocular distance (SCRFD gives 5 landmarks, not 68). ~0 facing the
        // camera, roughly ±0.5 at a strong turn — a different scale from the old
        // jaw-based measure, hence the re-tuned values below.

        // |yaw| under this counts as looking straight at the camera.
        'front_yaw_max' => 0.15,

        // |yaw| over this counts as a deliberate head turn.
        'turn_yaw_min' => 0.22,

        // Pitch is the nose tip's vertical offset from the eye midpoint in
        // units of interocular distance. It varies per face, so up/down are
        // judged as a CHANGE from the employee's own frontal baseline (captured
        // during the straight-ahead frames): a tilt counts once the pitch has
        // moved this far from that baseline.
        'turn_pitch_min' => 0.12,

        // Landmark travel, in fractions of face width, that counts as movement.
        // NOTE: blink detection is gone with the engine swap — 5 landmarks carry
        // no eye contour — so the registration "movement" step is satisfied by
        // head movement alone.
        'movement_min' => 0.06,

        // Yaw is measured on the raw, un-mirrored camera frame, where a negative
        // value means the subject turned toward their own left.
        //
        // Flip this if "turn left" / "turn right" read backwards on your cameras
        // during registration. Set true because on the deployment cameras the
        // nose-offset yaw sign came out mirrored: the app was asking for the turn
        // opposite to the arrow shown.
        'yaw_invert' => true,
    ],

];
