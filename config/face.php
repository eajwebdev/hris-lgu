<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Descriptor dimension
    |--------------------------------------------------------------------------
    |
    | face-api.js FaceRecognitionNet emits a 128-float descriptor. Every vector
    | we accept, average or compare is checked against this length.
    |
    */

    'dimension' => 128,

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
    | 0.55 euclidean is a deliberately conservative starting point; face-api's
    | own same-person guideline is ~0.6 on raw descriptors.
    |
    */

    'duplicate_distance' => 0.55,

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
    */

    'match' => [
        'distance'  => 0.62,
        'ratio'     => 0.78,
        'shortlist' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Liveness (attendance portal)
    |--------------------------------------------------------------------------
    |
    | A photograph passes every ordinary face check: it is a real face, correctly
    | lit, the right size, looking at the lens. What a photograph cannot do is
    | turn its head on demand.
    |
    | So the server issues a random, single-use challenge naming BOTH head turns
    | in a random order, and verifies each returned frame against that employee's
    | *enrolled* left and right captures. Demanding both poses matters: with only
    | one, an attacker holding a single left-profile photo could simply keep
    | asking for challenges until "left" came up.
    |
    | None of this is enforced in the browser. The browser is untrusted — it only
    | guides the person through the poses. Every check below runs on the server,
    | against descriptors it can compare to what HR enrolled.
    |
    | Honest limit: this defeats printed photos and static images. It does not
    | defeat a video of the employee replayed on a phone screen, which can turn
    | its head. Catching that needs a model that looks at pixels.
    |
    */

    'liveness' => [
        // Frames the client must supply while facing the camera, before the poses.
        // Two is enough to both confirm identity and measure the frame-to-frame
        // variation that outs a static photo, and it shaves one full descriptor
        // pass (plus its inter-frame pause) off every honest punch.
        'min_neutral_frames' => 2,

        // Upper bound on the whole payload, so the endpoint cannot be used to
        // ship megabytes of vectors.
        'max_frames' => 12,

        // A real person takes time to turn their head twice. Anything faster than
        // this was not performed by a human in front of the lens.
        'min_duration_ms' => 900,
        'max_duration_ms' => 40000,

        // Seconds a challenge stays usable. Single-use regardless.
        'challenge_ttl' => 90,

        // Consecutive frames of a live face never come out identical; a photo
        // held in front of a tripod very nearly does. This is the floor on the
        // largest pairwise distance among the neutral frames.
        'min_variation' => 0.045,

        // The turned frame must sit closer to the enrolled capture of the pose
        // that was ASKED FOR than to the opposite one, by at least this margin.
        // This is the check a flat photo cannot pass.
        //
        // Kept small on purpose. A flat photo sits almost exactly equidistant
        // from the left and right enrolled captures no matter how it is waved, so
        // its margin is ~0 and it fails this at any positive value. The cost of an
        // over-large margin is the opposite: it rejects a real, moderate head turn
        // and spends the employee's attempt, which is the main reason an honest
        // match "won't go through". 0.022 clears a genuine turn while still leaving
        // a photo nowhere near.
        'pose_margin' => 0.022,

        // ...and it must actually differ from the neutral frames, i.e. the head
        // genuinely moved rather than the photo being jiggled in place. A photo
        // held still produces ~0 shift, so this stays a reliable tell well below
        // the travel of any deliberate turn.
        'min_pose_shift' => 0.055,

        // A turned head legitimately sits further from enrolment than a straight
        // one, so pose frames get a looser identity threshold than neutrals.
        'pose_distance' => 0.78,
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
        // TinyFaceDetector confidence below which we refuse to capture.
        'min_detection_score' => 0.55,

        // Face box width as a fraction of the video width. Under this the face
        // is too far away for a usable descriptor.
        'min_face_ratio' => 0.20,

        // Mean luma (0-255) inside the face box. Under this it is too dark.
        'min_brightness' => 55,

        // Variance of a Laplacian filter over the face crop (64x64 grayscale).
        // Motion blur flattens edges and drags this toward zero; a blurred frame
        // yields a mushy descriptor that degrades every later match, so it is
        // cheaper to refuse the frame than to enrol it. Deliberately low — it is
        // an obvious-blur catch, not a focus meter. Kept low so a cheap phone
        // camera is not made to re-capture "hold still" over and over — that
        // re-capture loop is dead time an employee reads as the portal hanging.
        'min_sharpness' => 14,

        // |yaw| under this counts as looking straight at the camera.
        'front_yaw_max' => 0.20,

        // |yaw| over this counts as a deliberate head turn.
        'turn_yaw_min' => 0.18,

        // Eye-aspect-ratio under this is a closed eye (used to spot a blink).
        'blink_ear_max' => 0.21,

        // Landmark travel, in fractions of face width, that counts as movement
        // when the employee moves their head instead of blinking.
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
