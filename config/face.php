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
    | Require the QR badge at the kiosk
    |--------------------------------------------------------------------------
    |
    | When true the punch endpoint accepts mode=qr ONLY: the employee scans
    | their badge, which names them, and then simply looks at the camera to
    | prove they are that person and alive. The face-only path — a 1:N search
    | across every enrolled face — is refused.
    |
    | This is a security improvement, not just a workflow one, and it is what
    | lets the capture be as gentle as it now is:
    |
    |   * 1:1 beats 1:N. Face-only had to pick one employee out of everybody,
    |     so it needed the ratio test to refuse a stranger who sat at a mediocre
    |     distance from the whole roster. With the badge naming the employee
    |     first, the only question left is "is this that person", which is a far
    |     easier question to answer correctly and fails honest employees much
    |     less often.
    |
    |   * The error rate of a 1:N search grows with headcount; a 1:1 verify does
    |     not. This LGU's roster will only get bigger.
    |
    |   * Two factors instead of one: something you carry and something you are.
    |     A stolen badge alone is useless without the face, and a photograph of
    |     the face is useless without the badge.
    |
    | Enforced SERVER-SIDE in AttendancePortalController::punch(), because the
    | kiosk JavaScript can be edited by whoever is holding the device — hiding
    | the "use face only" button is presentation, not policy.
    |
    | Set false to allow badge-less face punching again (the old 1:N behaviour),
    | and expect to re-tighten face.match.ratio if you do.
    |
    */

    'require_qr' => env('FACE_REQUIRE_QR', true),

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

    /*
    | Pass-1 index shape
    |
    | The index is cached, and CACHE_DRIVER here is 'file', so its shape is
    | serialised to disk and read back on EVERY punch. Held as a nested PHP
    | array of 512 floats per employee that round trip was the whole cost:
    |
    |     employees   blob      unserialize   scan     per punch
    |        89       1.3 MB        21 ms      1.0 ms      22 ms
    |       500       7.0 MB       113 ms      5.6 ms     119 ms
    |      2000      28.1 MB       513 ms     25.2 ms     538 ms
    |
    | It is now one packed float32 blob: 3.9 MB at 2000 employees, ~3 ms to
    | read, and identify() end-to-end measures 48 ms instead of 538 ms with peak
    | memory down from 126 MB to 54 MB. See FaceEmbeddingService::vectorIndex().
    |
    | 'shortlist' is how many candidates pass 1 hands to pass 2. Pass 2 re-scores
    | those at full precision against each employee's four captures, which is
    | why float32 in pass 1 costs nothing in accuracy.
    */

    /*
    | RELAXED after employees were unable to punch at all. 1.10/0.78 is the
    | textbook ArcFace operating point, measured on frontal studio captures; a
    | kiosk camera in a corridor is not that, and the gap between "enrolled in
    | good light" and "punching under a ceiling fluorescent" was landing real
    | employees past 1.10. 1.24 is cosine ≈ 0.23 — still far outside the
    | different-people band this model produces, and the ratio test below is
    | what actually stops a stranger being handed someone's identity.
    |
    | If a wrong employee is ever matched, tighten 'ratio' first (down toward
    | 0.78), not 'distance' — ratio is the discriminating check.
    */
    'match' => [
        'distance'  => 1.24,
        'ratio'     => 0.88,
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
        // RELAXED 5 -> 3. Every one of these frames has to clear the framing
        // gate AND hold |yaw| under front_yaw_max, each with its own 10s
        // deadline in captureAt() — so five of them was up to 50 seconds of
        // standing still before the gestures even started, and one bad frame
        // restarted the lot. Three still gives min_variation something to read.
        // 3 -> 4. With the gesture stage retired these are the ONLY identity
        // frames in a punch, and they are also what the anti-spoof average is
        // taken over, so one extra is worth having. Still far quicker than the
        // old five-plus-two-gestures sequence.
        'min_neutral_frames' => 4,

        // Gestures the challenge can demand, and how many are drawn per attempt
        // (distinct, shuffled). left/right are yaw turns; up/down are pitch
        // tilts measured against the employee's own frontal baseline.
        //
        // 0 = OFF, and that is deliberate policy here, not a tuning slip.
        //
        // The punch is now QR + look-at-the-camera: the employee scans their
        // code and simply looks, with no gesture to perform. Everything the
        // gestures used to prove is now proven by the ACTIVE ILLUMINATION
        // challenge below, which is fully passive from the employee's side —
        // the screen does the work while they stand still — and, with
        // liveness_flash_frames.require_images on, is measured by the SERVER
        // from the submitted pixels rather than asserted by the browser.
        //
        // That is a straight upgrade against the two attacks that matter here.
        // A gesture challenge is beaten by a coached live video replay (the
        // person on the call can be told to turn their head). The flash
        // challenge is not: a video call cannot make its face brighten more
        // than the wall behind it, and cannot take on a colour the server chose
        // after the recording started.
        //
        // checkPoses() and the client's pose loop both no-op on an empty list,
        // so this switches the whole stage off cleanly.
        //
        // NOTE: this does NOT affect registration, which still captures
        // front/left/right/movement — see face.captures. A richer enrolment
        // template is what makes the 1:1 verify below forgiving of head angle,
        // and enrolment is supervised and one-off, so the cost is paid once.
        'pose_pool'  => ['left', 'right', 'up', 'down'],
        'pose_count' => 0,

        // Squared-free euclidean distance a challenged-pose frame must sit from
        // the straight-ahead master embedding. A genuine head turn moves the
        // embedding well past this; a flat image rotated in front of the lens
        // barely moves it. Kept modest so a subtle but real turn still passes.
        // RELAXED 0.05 -> 0.035. With pose_count at 1 a single marginal-but-real
        // turn now fails the whole punch rather than one of two, so the floor is
        // eased to match. Still an order of magnitude above where a flat image
        // swivelled on the spot lands.
        'min_pose_shift' => 0.035,

        // Upper bound on the face captures in a payload, so the endpoint
        // cannot be used to ship megabytes of vectors. Must stay at least
        // min_neutral_frames + count(pose_pool) — 5 + 4 = 9 at the pool's full
        // extent. The flash samples are NOT counted here: they are light
        // readings rather than embeddings, and are capped separately in the
        // controller's 'flash.samples' rule.
        'max_frames' => 12,

        // The frames must span at least this long — a human takes time to
        // perform two gestures; a payload assembled in one instant does not.
        // RELAXED 1200 -> 500. This spans the identity frames only (checkTiming
        // reads 'frames', not the flash samples), and with the gestures gone
        // those are four frames taken 180ms apart — roughly 750ms end to end,
        // uncomfortably close to the old floor. A cooperative employee being
        // refused for punching TOO FAST is the one failure this number must
        // never produce. 500ms still catches a payload assembled in one instant,
        // which is all it was ever for.
        'min_duration_ms' => 500,
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

        // RELAXED 4 -> 3. Three is the floor for a nonzero count (issueFlash()
        // raises anything lower so the seeded white/dark/colour trio still
        // fits), so all three properties are still tested every attempt — this
        // only drops the one extra random segment and the ~600ms it cost.
        // Back to 4. It was cut to 3 to save ~600ms when the employee also had
        // two gestures to perform; with the gestures gone the whole punch is
        // shorter than it has ever been, and the extra segment is the cheapest
        // anti-spoof available — one more colour the recording has to have
        // predicted. All three properties are tested at 3; the 4th widens the
        // chroma sample.
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
        // RELAXED 8 -> 4. This was already documented as the weakest of the
        // three flash checks and the one daylight defeats: in a bright hall the
        // kiosk screen adds almost nothing next to the windows, so a real
        // employee fails it through no fault of their own. min_face_bg_delta
        // below is the check actually carrying the replay-device defence.
        //
        // RAISED BACK 4 -> 7 now that the gestures are gone. These numbers were
        // loosened while the pose challenge was still carrying liveness and
        // this was a secondary check; it is not secondary any more. They are
        // also now applied to SERVER-MEASURED luma rather than whatever the
        // browser reported, so a threshold here finally means something.
        'min_flash_delta' => 7,

        // How much more the FACE must respond than the background does, in the
        // same luma units. The one number worth tuning first if a real screen
        // spoof ever gets through — raise it. Lower it only if employees at a
        // kiosk mounted very close to a wall are being turned away, since a
        // wall right behind the head shrinks the natural gap.
        // RAISED BACK 2 -> 4. THIS IS THE VIDEO-CALL DEFENCE and, with the
        // gestures retired, the single most important number in this file.
        //
        // Light falls off with distance, so a kiosk screen inches from a real
        // face and metres from the wall lights the two very differently. When
        // the "employee" is a video call held up to the lens, the entire frame
        // IS that phone's display: face region and background region carry the
        // same emitted light and rise together, so this differential collapses
        // toward zero no matter how convincing the person on the call is.
        //
        // Lower it ONLY for a kiosk mounted hard against a wall, and retest
        // with an actual phone afterwards.
        'min_face_bg_delta' => 4,

        // Rise in a colour's share of the face's total RGB under that colour's
        // segment, versus that share averaged across the whole sequence.
        // Dimensionless (a ratio of ratios), so exposure and white balance
        // drift do not move it. 0.03 is roughly a 3-point shift on a channel
        // that normally sits near a third of the total.
        // RAISED BACK 0.018 -> 0.028, the second half of the video-call
        // defence. The server picks the colour sequence AFTER the attempt
        // starts, so a recording — however live the person in it looks — was
        // made before the answer existed and keeps whatever colour balance it
        // was recorded with. A real face reflects the screen's red under red.
        //
        // Held just under the original 0.03 because a dark-skinned or strongly
        // side-lit face genuinely reflects proportionally less of the cast, and
        // that must not read as a spoof.
        'min_chroma_shift' => 0.028,

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

    /*
    | RELAXED per the note above ("LOWER IT back toward 0.5 if real employees on
    | a poor camera are being turned away") — which is exactly what happened.
    | 0.55 is still above the model's own 0.5 decision point, so a frame this
    | passes is one MiniFASNet calls live, not one it is merely unsure about.
    |
    | 'require_score' stays TRUE. That flag is not a strictness dial — turning it
    | off would let any client skip anti-spoofing entirely by omitting a field,
    | which is a hole rather than a relaxation.
    |
    | WHY THIS STAYS PERMISSIVE, now that the gestures are gone.
    |
    | It is tempting to raise this floor to compensate. Don't — it would buy
    | almost no security and cost real employees their punch. Unless the sidecar
    | is running, THIS SCORE IS COMPUTED IN THE BROWSER AND MERELY REPORTED: the
    | pixels never reach the server, so an attacker who has patched the client
    | posts 0.99 whatever the threshold is. Raising it filters honest employees
    | on cheap cameras and nobody else.
    |
    | The check that actually stops a print or a video call is the active
    | illumination sequence above, because with liveness_flash_frames
    | .require_images on the server measures it from submitted pixels instead of
    | believing a number. Treat MiniFASNet as a cheap first filter and put the
    | tuning effort into min_face_bg_delta and min_chroma_shift.
    */
    'antispoof' => [
        'enabled'        => true,
        'min_real'       => 0.55,
        'min_real_frame' => 0.20,
        'require_score'  => true,

        /*
        | ENROLMENT floor, deliberately HIGHER than the punch floor above.
        |
        | The asymmetry is on purpose. A punch is one moment and a rejected one
        | costs an employee thirty seconds; a template is on file for years and
        | every future match is measured against it, so a photograph enrolled
        | once quietly degrades every punch that employee ever makes. Refusing a
        | marginal capture here is cheap, and the employee is sitting in front
        | of the camera able to retry immediately.
        |
        | This ran nowhere until now: registration only scored captures when the
        | Python sidecar was enabled, so with FACE_SCORING_ENABLED off — the
        | default — self-enrolment had NO liveness check whatsoever. That gap
        | mattered more once the dashboard prompt made self-enrolment the normal
        | way in.
        |
        | Same honest caveat as min_real: without the sidecar this score is
        | computed in the browser and merely reported, so it stops a casual
        | photo rather than a patched client. Enrolment is the place where
        | turning the sidecar on pays for itself most — it is four frames, once
        | per employee, not a per-punch cost.
        */
        'enrolment_min_real' => 0.60,

        /*
        | Fail closed at enrolment: a capture arriving with no anti-spoof score
        | is refused rather than stored. Without this, omitting one field is all
        | it takes to enrol a photograph.
        */
        'enrolment_require_score' => true,
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
        //
        // RELAXED 0.50 -> 0.45 to match detectFull()'s own scoreThreshold. At
        // 0.50 a face scoring 0.45-0.50 was detected by the engine and then
        // refused by this gate, so the portal sat on "Please position your face
        // properly" with nothing the employee could do about it.
        'min_detection_score' => 0.45,

        // Face box width as a fraction of the video width. Under this the face
        // is too far away for a usable embedding.
        //
        // RELAXED 0.20 -> 0.14. Kiosk and laptop webcams are wide-angle, so a
        // face at normal standing distance covers well under a fifth of the
        // frame and the portal just repeated "move closer" indefinitely.
        'min_face_ratio' => 0.14,

        // Mean luma (0-255) inside the face box. Under this it is too dark.
        //
        // RELAXED 55 -> 38, and this is the single most likely cause of the
        // portal hanging: an ordinary office under ceiling fluorescents puts a
        // face in the 40s, so gateOf() returned "Lighting is insufficient" on
        // every frame until the 10s deadline expired, on every retry. Brightness
        // is a capture-quality floor, not a security check — a photograph is
        // perfectly well lit.
        'min_brightness' => 38,

        // Variance of a Laplacian filter over the face crop (64x64 grayscale).
        // Motion blur flattens edges and drags this toward zero; a blurred frame
        // yields a mushy embedding that degrades every later match, so it is
        // cheaper to refuse the frame than to enrol it. Deliberately low — it is
        // an obvious-blur catch, not a focus meter. Kept low so a cheap phone
        // camera is not made to re-capture "hold still" over and over — that
        // re-capture loop is dead time an employee reads as the portal hanging.
        // RELAXED 14 -> 7. This config already documents (under scoring.enrolment)
        // that raw Laplacian variance scales with scene contrast, so a sharp
        // capture of a dark face scores below a blurred capture of a bright one
        // and no threshold on it cleanly separates the two. It is an
        // obvious-blur catch only, and 14 was doing real work refusing honest
        // frames on low-contrast cameras.
        'min_sharpness' => 7,

        // Yaw is now the nose tip's offset from the eye midpoint in units of
        // interocular distance (SCRFD gives 5 landmarks, not 68). ~0 facing the
        // camera, roughly ±0.5 at a strong turn — a different scale from the old
        // jaw-based measure, hence the re-tuned values below.

        // |yaw| under this counts as looking straight at the camera.
        //
        // RELAXED 0.15 -> 0.18. Every straight-ahead frame must hold this, so a
        // kiosk mounted slightly off to one side made all of them hard at once.
        //
        // MUST STAY BELOW turn_yaw_min. If these two overlap, a single head
        // position counts as both "facing front" and "a deliberate turn", which
        // would let one static pose satisfy the gesture challenge — the exact
        // property the challenge exists to prove. The gap below is deliberate.
        'front_yaw_max' => 0.18,

        // |yaw| over this counts as a deliberate head turn. Left alone: easing
        // the front gate above already shortens the slow part of the capture,
        // and lowering this would close the gap described above.
        'turn_yaw_min' => 0.22,

        // Pitch is the nose tip's vertical offset from the eye midpoint in
        // units of interocular distance. It varies per face, so up/down are
        // judged as a CHANGE from the employee's own frontal baseline (captured
        // during the straight-ahead frames): a tilt counts once the pitch has
        // moved this far from that baseline.
        // RELAXED 0.12 -> 0.09. A nod is a much smaller landmark movement than a
        // yaw turn, and it is measured against a baseline averaged over the
        // straight-ahead frames — now three rather than five, so that baseline
        // is slightly noisier and the margin above it should not be tight.
        'turn_pitch_min' => 0.09,

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

    /*
    |--------------------------------------------------------------------------
    | Server-side scoring
    |--------------------------------------------------------------------------
    |
    | The browser computes a descriptor, an anti-spoof score and flash luma
    | readings, then posts the numbers. Every one of those is an assertion by
    | code an attacker controls, so the gestures, the flash and the anti-spoof
    | model were all guarding a door that could be walked around by posting
    | fabricated values.
    |
    | With this enabled, the raw frame goes to the Python sidecar in
    | face-service/ instead, which runs the same three ONNX models the browser
    | does and returns a result the client cannot influence.
    |
    | 'required' fails CLOSED: if the sidecar is unreachable, the capture is
    | refused rather than falling back to trusting the client — which would
    | reopen the exact hole this closes. Turn it off only where the sidecar
    | genuinely cannot run, and accept the old trust model if you do.
    |
    | The sidecar must stay on localhost. Anything that can reach it can ask it
    | to score arbitrary images.
    |
    */

    'scoring' => [
        'enabled'  => env('FACE_SCORING_ENABLED', false),
        'required' => env('FACE_SCORING_REQUIRED', true),
        'url'      => env('FACE_SCORING_URL', 'http://127.0.0.1:8078'),
        'token'    => env('FACE_SERVICE_TOKEN', ''),
        'timeout'  => (int) env('FACE_SCORING_TIMEOUT', 20),

        // Frames per call to the sidecar. Must not exceed the service's own
        // FACE_MAX_BATCH; FaceScoringClient chunks anything larger, so this is
        // a throughput knob rather than a limit anyone has to respect.
        'max_batch' => (int) env('FACE_SCORING_MAX_BATCH', 8),

        /*
        | THE PUNCH PATH
        |
        | Registration was server-scored before this; the punch was not. That
        | asymmetry was the remaining hole, and it was the wider one: enrolment
        | happens once under supervision, while the punch is the unauthenticated
        | endpoint anyone can reach. With scoring off for the punch, the browser
        | still asserts both halves of the answer — the descriptor that says WHO
        | this is, and the anti-spoof score that says it is a live person — so a
        | tampered client could post a stolen descriptor with a perfect score and
        | the server had nothing to check it against.
        |
        | With this on, the punch endpoint sends the captured frames to the
        | sidecar and REPLACES both: every identity descriptor and the flash
        | burst's descriptor become vectors the server computed, and the
        | anti-spoof average and minimum are recomputed from the pixels. The
        | gesture challenge, the flash challenge and the 1:N search downstream
        | are all unchanged — they simply stop running on numbers the client
        | chose.
        |
        | 'required' fails CLOSED. A punch whose frames cannot be scored is
        | refused rather than falling back to the browser's word, because
        | falling back is precisely the hole being closed.
        |
        | COST: one sidecar pass per frame, so about 25 ms x (neutral frames +
        | gestures + 1). At the default 5 + 2 that is roughly 200 ms added to a
        | punch, in one batched call. face.scoring.timeout is sized for that.
        */
        'punch' => [
            'enabled'  => env('FACE_PUNCH_SCORING_ENABLED', false),
            'required' => env('FACE_PUNCH_SCORING_REQUIRED', true),
        ],

        // Gates applied to an enrolment frame. A bad template matches badly for
        // as long as it is on file, so these are deliberately strict.
        //
        // 'min_focus' is the blur gate, and it replaced a raw edge-variance one
        // that could not work. Raw Laplacian variance scales with how much
        // contrast a picture happens to have, so a crisp capture of a dark face
        // scores below a blurred capture of a bright one: measured over real
        // photographs, sharp frames spanned 17-3414 and visibly blurred ones
        // 6-58, which overlap, and no threshold on that number separates them.
        // Dividing by the crop's own intensity variance removes the contrast
        // term. On the same frames that gives sharp 0.009-1.054 against blurred
        // 0.003-0.013, so 0.02 sits about 1.5x above the worst blurred frame
        // and below every sharp one but a single dark, soft capture — which was
        // measured at brightness 59 and raw detail 17 against medians of 140
        // and 230, i.e. genuinely poor and worth refusing at enrolment rather
        // than living with as a template.
        //
        // 'min_sharpness' is kept as a floor on the raw number only to catch a
        // frame with almost no edge content at all (a lens cap, a wall). It is
        // deliberately far below the real-face range and is NOT the blur gate.
        'enrolment' => [
            'min_face_px'    => 90,    // shortest side of the detected face box
            'min_focus'      => 0.02,  // contrast-normalised Laplacian variance
            'min_sharpness'  => 5,     // raw floor; near-featureless frames only
            'min_brightness' => 45,
            'max_brightness' => 235,
            'min_antispoof'  => 0.70,  // same floor as face.antispoof.min_real
        ],

        // Refuse a capture the sidecar's forensic checks flagged as a display
        // or a print, independently of what the anti-spoof CNN said about it.
        // The sidecar already reports antispoof=0 for a flagged frame, so this
        // is belt-and-braces: it makes the refusal reason specific ("this looks
        // like a screen") instead of a generic "not live", which is the
        // difference between an operator retrying pointlessly and understanding
        // what was rejected.
        'reject_forensic_flags' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Flash frame verification (shared hosting)
    |--------------------------------------------------------------------------
    |
    | Thresholds for App\Services\FlashFrameVerifier, which measures the
    | screen-flash response from the submitted IMAGE FRAMES using GD, in pure
    | PHP. No ONNX, no Python, no sidecar, no long-running process — so this is
    | the one meaningful hardening available on shared web hosting.
    |
    | It replaces trust in numbers the browser reported with a measurement the
    | server takes itself. Three checks, because each catches what the others
    | miss (figures below are from tests/Feature/FlashFrameVerifierTest.php):
    |
    |   min_delta          The face brightens under a bright segment. A held-up
    |                      print brightens too (~105), so this alone admits it.
    |
    |   min_face_bg_delta  The face brightens MORE than the background, because
    |                      light falls off with distance. A print is flat, so
    |                      both brighten equally (~0) and it is refused here.
    |
    |   min_hue_shift      The face takes the segment's colour cast. The
    |                      strongest check: a recording is of a real 3-D person
    |                      and passes both checks above, but its colours were
    |                      fixed before the server chose the sequence.
    |
    | Raise these if genuine punches are being refused; lower them only with a
    | physical retest, since they are what stands between a photo and a punch.
    |
    */

    'liveness_flash_frames' => [
        // When true the punch endpoint REQUIRES an image per flash sample and
        // measures the light response itself, ignoring the numbers the browser
        // reports. Fail-closed: a client that omits the images is refused.
        //
        // Left off by default because the portal script must be updated to send
        // frames first — turning it on before that refuses every punch. Once the
        // client sends them, turn it on: until you do, liveness is still only as
        // trustworthy as the browser claiming it.
        // NOW ON BY DEFAULT. The comment above used to say this was left off
        // "because the portal script must be updated to send frames first" —
        // that update has since landed (runFlash() attaches a JPEG and a face
        // box to every sample), so the reason for the default no longer holds.
        //
        // Turning it on is what makes the flash challenge real. Until now the
        // server was grading the browser's own homework: a patched client could
        // report whatever face/bg luma numbers passed. With this on, the server
        // decodes the submitted JPEGs with GD and measures the light response
        // itself, then overwrites the client's claims before any threshold is
        // applied.
        //
        // REQUIRES PHP'S GD EXTENSION. It is enabled in this deployment's
        // php.ini; if you move to a host without it, FlashFrameVerifier cannot
        // decode a frame and every punch fails closed.
        'require_images'    => env('FACE_FLASH_IMAGES_REQUIRED', true),

        'min_delta'         => 6.0,
        'min_face_bg_delta' => 3.0,
        'min_hue_shift'     => 0.02,
    ],

];
