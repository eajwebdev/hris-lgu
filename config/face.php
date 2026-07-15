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
    | is move like a living face.
    |
    | This build is FRONTAL ONLY — no head turns are asked for. The employee faces
    | the camera and holds still while several frames are taken a moment apart, and
    | the server proves liveness from the natural frame-to-frame drift of a real
    | face: a living person is never perfectly still, so their descriptors vary,
    | whereas a flat static photo held to the lens yields nearly the same vector
    | every time (see 'min_variation'). The single-use challenge nonce is still
    | issued and redeemed, so a captured payload cannot be replayed.
    |
    | None of this is enforced in the browser. The browser is untrusted — it only
    | gathers frames. Every check below runs on the server, against descriptors it
    | can compare to what HR enrolled.
    |
    | Honest limit: frontal-only liveness defeats a printed or on-screen STILL
    | photo. It does NOT defeat a video / live replay of the employee, which drifts
    | like a real face. Where that matters, the QR path (encrypted token + 1:1 face
    | verify) is the stronger option. Catching a replay attack would need a model
    | that looks at pixels, which is not part of this system.
    |
    */

    'liveness' => [
        // Frontal frames the client gathers while the employee faces the camera.
        // More frames give the variation test below more to read and average the
        // identity match over a steadier signal; five keeps the whole capture
        // inside a second or two.
        'min_neutral_frames' => 5,

        // Upper bound on the whole payload, so the endpoint cannot be used to
        // ship megabytes of vectors.
        'max_frames' => 12,

        // The frames must span at least this long — a spread of moments, not one
        // instant cloned. No head turn to perform now, so this is short; it only
        // rules out a payload whose frames all carry the same timestamp.
        'min_duration_ms' => 500,
        'max_duration_ms' => 40000,

        // Seconds a challenge stays usable. Single-use regardless.
        'challenge_ttl' => 90,

        // The heart of the frontal anti-spoof. Consecutive frames of a live face
        // never come out identical — the head drifts, the eyes move, sensor noise
        // differs. A still photo held to the lens very nearly does. This is the
        // floor on the largest pairwise distance among the frames: below it, the
        // capture is treated as a static image and refused.
        //
        // ArcFace scale. ArcFace embeddings are steadier frame-to-frame than the
        // old face-api descriptors were, and a flat photo is flatter still, so
        // the floor stays low. FIELD-TUNE THIS FIRST if honest employees see
        // "please use your face, not a photo": watch the logged distances and
        // move the floor just under what live faces actually produce.
        'min_variation' => 0.04,
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

        // Landmark travel, in fractions of face width, that counts as movement.
        // NOTE: blink detection is gone with the engine swap — 5 landmarks carry
        // no eye contour — so the registration "movement" step is satisfied by
        // head movement alone.
        'movement_min' => 0.06,

        // Yaw is measured on the raw, un-mirrored camera frame, where a negative
        // value means the subject turned toward their own left.
        //
        // Shared by BOTH the registration module and the attendance portal, and
        // it has to stay that way: the portal's liveness check compares a turned
        // frame against the capture that registration filed as "left". If the two
        // disagreed about which way left is, every honest employee would fail the
        // check. Flip this once, here, if it reads backwards on your cameras.
        'yaw_invert' => false,
    ],

];
