<?php

namespace App\Http\Controllers;

use App\Models\AttendancePunchLog;
use App\Models\AttendanceStation;
use App\Models\Employee;
use App\Models\Notification;
use App\Services\AttendanceService;
use App\Services\FaceEmbeddingService;
use App\Services\GeoService;
use App\Services\LivenessVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * The employee-facing attendance portal.
 *
 * Deliberately unauthenticated: this page *is* the login. An employee walks up,
 * the camera identifies them, and the punch is written. There is no session to
 * establish and none is established.
 *
 * The security property that makes that safe to expose is that the client never
 * names the employee. It sends a face descriptor — and, in QR mode, an encrypted
 * token it cannot forge — and the *server* decides whose attendance row moves.
 * There is no "clock in employee 37" endpoint anywhere in this controller, so
 * there is nothing to replay one at.
 */
class AttendancePortalController extends Controller
{
    public function __construct(
        private FaceEmbeddingService $faces,
        private AttendanceService $attendance,
        private LivenessVerifier $liveness,
        private GeoService $geo,
    ) {
        parent::__construct();
    }

    public function show()
    {
        return view('attendance.portal', [
            // The SCRFD detector + ArcFace recogniser, run in-browser on ONNX
            // Runtime Web. ortPath is where the runtime's .wasm binaries live.
            'modelsUrl' => asset('models/arcface'),
            'ortPath'   => asset('js/onnx') . '/',

            // The active stations, so the kiosk can show live "how far am I"
            // feedback before the punch. Coordinates of public government
            // buildings — nothing sensitive — and only what the HUD needs.
            // The judgement that matters is still made server-side in
            // GeoService against this same table; the HUD is a courtesy.
            'stations'  => AttendanceStation::active()
                ->get(['name', 'lat', 'lng', 'radius_m'])
                ->map(fn ($s) => [
                    'name'     => $s->name,
                    'lat'      => (float) $s->lat,
                    'lng'      => (float) $s->lng,
                    'radius_m' => (int) $s->radius_m,
                ])
                ->values(),
        ]);
    }

    /**
     * Hand out a single-use liveness challenge.
     *
     * The browser cannot choose its own poses — if it could, an attacker would
     * simply pick the one they have a photo of.
     */
    public function challenge(Request $request): JsonResponse
    {
        return response()->json([
            'status'    => 200,
            'challenge' => $this->liveness->issue($request->ip()),
        ]);
    }

    /**
     * QR pre-step: turn a scanned token into a name to put on screen.
     *
     * Confirms the QR is real and the employee is active. It does not punch, and
     * it does not hand back anything the face step could be skipped with — the
     * caller still has to present a matching face, and the token is re-checked
     * server-side when they do.
     */
    public function checkQr(Request $request): JsonResponse
    {
        $request->validate(['qr' => ['required', 'string', 'max:512']]);

        $employee = $this->employeeFromQr($request->input('qr'));

        if (! $employee) {
            return $this->fail('This QR code is not valid.', 404);
        }

        if (! $this->faces->describe($employee->face_embeddings)['registered']) {
            return $this->fail('No face is registered for this employee. Please see HR.', 409);
        }

        return response()->json([
            'status'   => 200,
            'employee' => $this->card($employee),
        ]);
    }

    /**
     * Identify, prove the face is alive, and punch — in one indivisible step.
     *
     * Splitting this up would create exactly the hole the design exists to avoid:
     * an endpoint that takes an employee id, or one that takes the browser's word
     * for it that somebody blinked.
     */
    public function punch(Request $request): JsonResponse
    {
        $dimension = (int) config('face.dimension', 128);
        $maxFrames = (int) config('face.liveness.max_frames', 12);

        // The badge is mandatory unless face.require_qr is switched off. Applied
        // as a validation rule so the refusal is uniform with every other
        // malformed punch, and applied HERE rather than in the kiosk script
        // because the script is editable by whoever holds the device — the
        // hidden "use face only" button is presentation, the rule is policy.
        $modes = config('face.require_qr', true) ? ['qr'] : ['face', 'qr'];

        $validated = $request->validate([
            'mode'                 => ['required', Rule::in($modes)],
            'action'               => ['required', Rule::in(['in', 'out'])],
            'nonce'                => ['required', 'string', 'max:64'],
            'frames'               => ['required', 'array', 'min:3', 'max:' . $maxFrames],
            // Straight-ahead 'neutral' frames first, then one 'pose' frame per
            // gesture the challenge demanded (random per attempt).
            'frames.*.stage'       => ['required', Rule::in(['neutral', 'pose'])],
            'frames.*.pose'        => ['nullable', Rule::in(['left', 'right', 'up', 'down'])],
            'frames.*.t'           => ['required', 'numeric'],
            'frames.*.descriptor'  => ['required', 'array', 'size:' . $dimension],
            'frames.*.descriptor.*'=> ['required', 'numeric'],
            // The frame the descriptor above was taken from. When punch scoring
            // is on the server re-derives the descriptor from this and throws
            // the client's copy away; see verifyIdentityFromFrames().
            'frames.*.image'       => [
                config('face.scoring.enabled') && config('face.scoring.punch.enabled') ? 'required' : 'nullable',
                'string', 'max:400000',
            ],
            // The active-illumination samples: what the face and the
            // background reflected under each screen colour, plus one
            // embedding taken during the burst to bind those readings to the
            // same person the frames above identified. Carried separately from
            // 'frames' because these are light measurements, not face captures
            // — only one of them costs an embedding.
            'flash'                => ['nullable', 'array'],
            'flash.samples'        => ['nullable', 'array', 'max:12'],
            'flash.samples.*.seg'  => ['required', 'string', 'max:16'],
            'flash.samples.*.t'    => ['required', 'numeric'],
            'flash.samples.*.face' => ['required', 'array', 'size:3'],
            'flash.samples.*.face.*'=> ['required', 'numeric', 'between:0,255'],
            'flash.samples.*.bg'   => ['required', 'array', 'size:3'],
            'flash.samples.*.bg.*' => ['required', 'numeric', 'between:0,255'],
            // The frame each sample was taken from, so the server can measure
            // the light response itself instead of believing the two arrays
            // above. Required when face.liveness_flash_frames.require_images is
            // on; see verifyFlashFromFrames().
            'flash.samples.*.image' => [
                config('face.liveness_flash_frames.require_images') ? 'required' : 'nullable',
                'string', 'max:400000',
            ],
            'flash.samples.*.box'   => ['nullable', 'array', 'size:4'],
            'flash.samples.*.box.*' => ['required_with:flash.samples.*.box', 'numeric'],
            'flash.descriptor'     => ['nullable', 'array', 'size:' . $dimension],
            'flash.descriptor.*'   => ['required', 'numeric'],
            'qr'                   => ['nullable', 'string', 'max:512', 'required_if:mode,qr'],
            // Live-face probability from the browser's anti-spoof model: the
            // average across frames, and the single worst frame. Nullable at the
            // validation layer, but when antispoof.require_score is on a missing
            // average is refused below — fail closed, not open.
            'liveness_score'       => ['nullable', 'numeric', 'between:0,1'],
            'liveness_min'         => ['nullable', 'numeric', 'between:0,1'],
            // Shape-optional; whether a missing fix is *acceptable* is a policy
            // question answered by geofenceBlock() below, not by this rule.
            'geo'                  => ['nullable', 'array'],
            'geo.lat'              => ['required_with:geo', 'numeric', 'between:-90,90'],
            'geo.lng'              => ['required_with:geo', 'numeric', 'between:-180,180'],
            'geo.accuracy'         => ['nullable', 'numeric', 'between:0,100000'],
        ]);

        // The perimeter is judged first, and deliberately before the challenge
        // is redeemed: standing in the wrong place says nothing about whether
        // the face is alive, so it must not burn the employee's single-use
        // challenge on their way to being told to walk closer.
        $geoLat = isset($validated['geo']['lat']) ? (float) $validated['geo']['lat'] : null;
        $geoLng = isset($validated['geo']['lng']) ? (float) $validated['geo']['lng'] : null;
        $geoTag = $this->geo->resolve($geoLat, $geoLng);

        if ($refusal = $this->geofenceBlock($geoLat, $geoLng, $geoTag)) {
            return $this->fail($refusal, 403);
        }

        // Burned on the first attempt, pass or fail — see LivenessVerifier::redeem.
        $challenge = $this->liveness->redeem($validated['nonce'], $request->ip());

        if (! $challenge) {
            return $this->fail('This attempt expired. Please try again.', 419);
        }

        // Re-measure the flash response from the submitted frames, so the rest
        // of this method judges the light on numbers the server took rather than
        // numbers the browser reported. Everything downstream is unchanged — it
        // is the same checkFlash(), fed trustworthy input.
        if ($refusal = $this->verifyFlashFromFrames($validated, $challenge)) {
            return $this->fail($refusal, 422);
        }

        // Re-derive WHO this is, and whether it is a live person, from the
        // pixels. Until this ran, both answers were the browser's to invent.
        // Like the flash step above, nothing downstream changes — identify(),
        // LivenessVerifier and the anti-spoof floor all run exactly as before,
        // on numbers the server now owns.
        [$refusal, $status] = $this->verifyIdentityFromFrames($validated);

        if ($refusal !== null) {
            return $this->fail($refusal, $status);
        }

        $frames = $validated['frames'];

        foreach ($frames as $frame) {
            if (! $this->faces->isValidVector($frame['descriptor'])) {
                return $this->fail('The face reading was invalid. Please try again.');
            }
        }

        // Identify off the straight-ahead frames only. The turned ones sit further
        // from enrolment by design and would drag a 1:N search around.
        $neutral = array_values(array_filter($frames, fn ($f) => $f['stage'] === 'neutral'));

        if (! $neutral) {
            return $this->fail('Face check incomplete. Please try again.');
        }

        $probe = $this->faces->masterEmbedding(array_column($neutral, 'descriptor'));

        if ($probe === null) {
            return $this->fail('The face reading was invalid. Please try again.');
        }

        if ($validated['mode'] === 'qr') {
            // The QR named someone; the face has to agree. Re-read the token here
            // rather than trusting whatever checkQr() told the browser earlier.
            $employee = $this->employeeFromQr($validated['qr']);

            if (! $employee) {
                return $this->fail('This QR code is not valid.', 404);
            }

            $distance = $this->faces->verify($employee, $probe);

            if ($distance === null) {
                return $this->fail('Your face does not match this QR code.', 403);
            }
        } else {
            $match = $this->faces->identify($probe);

            if (! $match) {
                // Genuinely ambiguous, not an error: an unenrolled face, or one
                // too close to two people to call. Saying "not recognised" is the
                // honest answer and refusing to punch is the safe one.
                return $this->fail('Face not recognised. Try again, or use the QR option.', 404);
            }

            $employee = $match['employee'];
            $distance = $match['distance'];
        }

        // Only now, with an identity in hand, ask the questions a picture cannot
        // answer: does this face drift frame-to-frame the way a living one does,
        // and did it perform the random gestures this challenge demanded, in the
        // order it demanded them?
        $refusal = $this->liveness->check($employee, $frames, $challenge, (array) ($validated['flash'] ?? []));

        if ($refusal !== null) {
            Log::warning('Portal liveness check failed.', [
                'emp_ID' => $employee->emp_ID,
                'reason' => $refusal,
                'ip'     => $request->ip(),
            ]);

            return $this->fail($refusal, 403);
        }

        // Anti-spoof. The browser already blocked an obvious photo/screen locally;
        // this enforces the same thresholds server-side and — more importantly —
        // logs the scores, so a run of low scores from one spot shows up for HR.
        // The server cannot recompute the score (the pixels never leave the
        // browser), which is exactly why a missing score is refused rather than
        // waved through: omitting the field must not disable the check.
        $liveness    = $validated['liveness_score'] ?? null;
        $livenessMin = $validated['liveness_min'] ?? null;

        if (config('face.antispoof.enabled', true)) {
            if ($liveness === null && config('face.antispoof.require_score', true)) {
                Log::warning('Portal anti-spoof score missing.', [
                    'emp_ID' => $employee->emp_ID,
                    'ip'     => $request->ip(),
                ]);

                return $this->fail('The face security check did not run. Please reload the page and try again.', 403);
            }

            $meanLow  = $liveness !== null
                && (float) $liveness < (float) config('face.antispoof.min_real', 0.7);
            $frameLow = $livenessMin !== null
                && (float) $livenessMin < (float) config('face.antispoof.min_real_frame', 0.35);

            if ($meanLow || $frameLow) {
                Log::warning('Portal anti-spoof rejected.', [
                    'emp_ID'       => $employee->emp_ID,
                    'liveness'     => $liveness !== null ? round((float) $liveness, 3) : null,
                    'liveness_min' => $livenessMin !== null ? round((float) $livenessMin, 3) : null,
                    'ip'           => $request->ip(),
                ]);

                return $this->fail('Spoof detected. Please use your real face, not a photo or screen.', 403);
            }
        }

        if ((int) $employee->stat_1 !== 1) {
            return $this->fail('This employee record is inactive. Please see HR.', 403);
        }

        $action = $validated['action'] === 'out'
            ? AttendanceService::CLOCK_OUT
            : AttendanceService::CLOCK_IN;

        $result = $this->attendance->punch($employee->emp_ID, $action);

        if (! $result['recorded'] && ($result['limit'] ?? false)) {
            $max = (int) config('attendance.max_punches_per_day', 5);

            return response()->json([
                'status'   => 429,
                'message'  => 'Daily limit reached — ' . $max . ' ' . strtolower($result['action'])
                             . ' entries already recorded today.',
                'employee' => $this->card($employee),
            ], 429);
        }

        if (! $result['recorded'] && $result['wait'] > 0) {
            return response()->json([
                'status'   => 429,
                'message'  => 'Already recorded a moment ago. Please wait ' . $result['wait'] . 's.',
                'employee' => $this->card($employee),
            ], 429);
        }

        $location = $this->tagLocation($request, $employee, $validated, $result, $geoTag);

        Log::info('Portal attendance punch.', [
            'emp_ID'   => $employee->emp_ID,
            'action'   => $result['action'],
            'mode'     => $validated['mode'],
            'distance' => round((float) $distance, 4),
            'liveness' => $liveness !== null ? round((float) $liveness, 3) : null,
            'station'  => $location['station_name'],
            'ip'       => $request->ip(),
        ]);

        return response()->json([
            'status'   => 200,
            'employee' => $this->card($employee),
            'action'   => $result['action'],
            'time'     => $result['time'],
            'date'     => $result['date'],
            'recorded' => $result['recorded'],
            'location' => $location,
            'message'  => $result['recorded']
                ? $result['action'] . ' recorded'
                : 'Already recorded',
        ]);
    }

    /**
     * The perimeter, when the operator has one configured and turned on.
     *
     * Returns null when the punch may proceed, or a human-readable reason when
     * it may not. Unlike every other refusal in this controller the reason here
     * is specific on purpose: "you are 2.3km from Municipal Hall" is something
     * the employee can act on by walking, and it leaks nothing an attacker
     * standing there does not already know.
     *
     * No active stations at all never blocks. Nothing is configured to be
     * inside of yet, and refusing every punch in the municipality because
     * somebody has not filled in the stations table would be a misconfiguration
     * taking attendance down, not a security control.
     */
    /**
     * Measure the screen-flash response from the submitted frames, server-side,
     * and overwrite the client's readings with what was actually in the pixels.
     *
     * The challenge was never the weak part: LivenessVerifier issues a shuffled,
     * single-use colour sequence an attacker cannot predict. The weak part was
     * that the BROWSER measured how the face reacted and posted the numbers, so
     * a tampered client simply reported the answer the server wanted.
     *
     * Here the server decodes each frame with GD and takes its own measurement.
     * The existing checkFlash() thresholds then run against those, so the
     * downstream logic and the tuning in config/face.php are untouched — only
     * the provenance of the numbers changes.
     *
     * On top of the brightness test checkFlash() already does, FlashFrameVerifier
     * adds two things only the pixels can answer: whether the face brightened
     * MORE than the background (a flat print does not), and whether it took the
     * segment's colour cast (a recording made before the sequence was chosen
     * cannot). See tests/Feature/FlashFrameVerifierTest.php.
     *
     * @param  array  $validated  the request payload, mutated in place
     * @return string|null  refusal message, or null to continue
     */
    private function verifyFlashFromFrames(array &$validated, array $challenge): ?string
    {
        $required = (bool) config('face.liveness_flash_frames.require_images', false);
        $samples = $validated['flash']['samples'] ?? [];

        if (! $samples) {
            // No flash stage in this attempt; nothing to re-measure. Whether the
            // flash is mandatory at all is already decided by checkFlash().
            return null;
        }

        $withImages = array_filter($samples, fn ($s) => ! empty($s['image']));

        if (count($withImages) !== count($samples)) {
            // Fail closed when images are mandatory: a client that omits them
            // would otherwise be back to being believed.
            return $required
                ? 'This device did not send the images needed to verify liveness. Please try again.'
                : null;
        }

        $verifier = app(\App\Services\FlashFrameVerifier::class);

        $measured = [];
        $sequence = [];

        foreach ($samples as $i => $sample) {
            $binary = base64_decode($this->stripDataUrl($sample['image']), true);

            if ($binary === false || $binary === '') {
                return 'A captured frame could not be read. Please try again.';
            }

            // The box the client says the face occupies. A wrong box measures the
            // wrong region, which fails the face-vs-background test rather than
            // passing it — so lying here does not help an attacker.
            $box = $sample['box'] ?? null;

            if (! is_array($box) || count($box) !== 4) {
                return $required
                    ? 'This device did not report where the face was. Please try again.'
                    : null;
            }

            $reading = $verifier->measure($binary, array_map('floatval', array_values($box)));

            if ($reading === null) {
                return 'A captured frame could not be read. Please try again.';
            }

            // Replace what the browser claimed with what the server measured.
            $validated['flash']['samples'][$i]['face'] = $reading['face'];
            $validated['flash']['samples'][$i]['bg'] = $reading['bg'];

            $measured[] = $reading;
            $sequence[] = (string) $sample['seg'];
        }

        // The sequence the server issued, not the one the client says it showed.
        $issued = array_values((array) ($challenge['flash'] ?? []));

        if ($issued && $sequence !== $issued) {
            return 'The captured frames did not match the light sequence. Please try again.';
        }

        $result = $verifier->verify($issued ?: $sequence, $measured);

        if (! $result['ok']) {
            Log::warning('Punch refused by server-side flash verification.', [
                'reason' => $result['reason'],
                'detail' => $result['detail'],
                'ip'     => request()->ip(),
            ]);

            return $verifier->explain($result['reason']);
        }

        return null;
    }

    /**
     * Re-derive every face descriptor, and the anti-spoof scores, from the
     * submitted frames — so identity stops being something the client asserts.
     *
     * WHY THIS EXISTS
     * ---------------
     * Registration has been server-scored for a while; the punch had not been,
     * and that was the wider hole of the two. Enrolment happens once, in front
     * of HR. The punch is the unauthenticated endpoint the whole municipality
     * can reach, and it was taking the browser's word for BOTH halves of the
     * answer: the descriptor saying who this is, and the anti-spoof score
     * saying it is a live person. A tampered client could post a stolen
     * descriptor alongside a 0.99 "real" score and nothing here could disagree.
     *
     * The gestures, the flash sequence and the 1:N search were never the weak
     * part — they were all reasoning correctly about numbers the attacker
     * supplied. This replaces the numbers.
     *
     * WHAT IS REPLACED
     *   * Every frame's descriptor, with one the sidecar computed from that
     *     frame's pixels.
     *   * The flash burst's descriptor, taken from a flash frame that has
     *     already been decoded for the light measurement.
     *   * liveness_score and liveness_min, recomputed across the frames.
     *
     * WHAT IS NOT
     * Nothing downstream. identify(), LivenessVerifier and the anti-spoof floor
     * all run exactly as they did — which is the point. The security property
     * changes; the logic does not, so the tuning in config/face.php keeps its
     * meaning.
     *
     * @param  array  $validated  the request payload, mutated in place
     * @return array{0:?string,1:int}  refusal message and HTTP status, or [null, 200]
     */
    private function verifyIdentityFromFrames(array &$validated): array
    {
        $scoring = app(\App\Services\FaceScoringClient::class);

        if (! $scoring->enabled() || ! config('face.scoring.punch.enabled', false)) {
            return [null, 200];
        }

        $required = (bool) config('face.scoring.punch.required', true);
        $frames = $validated['frames'];

        $images = [];

        foreach ($frames as $i => $frame) {
            if (empty($frame['image'])) {
                // Fail closed: a client that omits the pixels is a client asking
                // to be believed, which is the whole thing being prevented.
                return $required
                    ? ['This device did not send the images needed to verify identity. Please reload and try again.', 422]
                    : [null, 200];
            }

            $images[$i] = $this->stripDataUrl($frame['image']);
        }

        // The flash burst carries its own descriptor binding the light readings
        // to a face. Derive that from a flash frame too, preferring the white
        // segment: the coloured and dark segments are deliberately badly lit,
        // and recognition on them is needlessly poor.
        $samples = $validated['flash']['samples'] ?? [];
        $flashSlot = null;

        if ($samples) {
            $pick = null;

            foreach ($samples as $j => $sample) {
                if (empty($sample['image'])) {
                    continue;
                }
                if ($pick === null || ($sample['seg'] ?? '') === 'white') {
                    $pick = $j;
                }
                if (($sample['seg'] ?? '') === 'white') {
                    break;
                }
            }

            if ($pick !== null) {
                $flashSlot = count($images);
                $images[] = $this->stripDataUrl($samples[$pick]['image']);
            }
        }

        $scored = $scoring->scoreMany(array_values($images));

        $reals = [];

        foreach (array_keys($frames) as $position => $i) {
            $result = $scored[$position] ?? null;

            if ($result === null || (! empty($result['unavailable']))) {
                Log::error('Punch refused: face scoring service unavailable.', [
                    'reason' => $result['reason'] ?? 'missing_result',
                    'ip'     => request()->ip(),
                ]);

                return $required
                    ? ['The face security service is unavailable. Please try again shortly.', 503]
                    : [null, 200];
            }

            if (! $result['ok']) {
                return [$scoring->explain($result['reason']), 422];
            }

            // A frame the forensic checks call a display is refused here, by
            // name. The anti-spoof floor further down would catch it too — the
            // sidecar reports such a frame at 0 — but this says what was seen.
            if (! empty($result['flags']) && config('face.scoring.reject_forensic_flags', true)) {
                Log::warning('Punch refused by frame forensics.', [
                    'flags'     => $result['flags'],
                    'forensics' => $result['forensics'] ?? null,
                    'ip'        => request()->ip(),
                ]);

                return [$scoring->explain((string) $result['flags'][0]), 403];
            }

            if (! $this->faces->isValidVector($result['embedding'] ?? [])) {
                return ['A face reading could not be computed. Please try again.', 422];
            }

            // The server's vector replaces the browser's. Everything after this
            // line — the 1:N search, the pose shifts, the neutral spread — is
            // reasoning about pixels rather than about assertions.
            $validated['frames'][$i]['descriptor'] = $result['embedding'];

            if ($result['antispoof'] !== null) {
                $reals[] = (float) $result['antispoof'];
            }
        }

        if ($flashSlot !== null) {
            $flashResult = $scored[$flashSlot] ?? null;

            // A flash frame that cannot be recognised is not fatal on its own —
            // it was taken under a deliberately odd light — but the descriptor
            // must then be absent rather than the client's, or omitting a
            // readable frame would be a way to keep asserting one.
            if ($flashResult !== null && ($flashResult['ok'] ?? false)
                && $this->faces->isValidVector($flashResult['embedding'] ?? [])) {
                $validated['flash']['descriptor'] = $flashResult['embedding'];
            } else {
                unset($validated['flash']['descriptor']);
            }
        }

        // The anti-spoof statistics are recomputed the same way the browser
        // computed them — mean and worst — so face.antispoof's thresholds keep
        // meaning what they meant. Flash frames are excluded on purpose:
        // MiniFASNet is calibrated on normally-lit faces, and folding a
        // deliberately over- or under-exposed one in would drag the average
        // down for honest employees.
        if ($reals) {
            $validated['liveness_score'] = array_sum($reals) / count($reals);
            $validated['liveness_min'] = min($reals);
        }

        return [null, 200];
    }

    /** Accepts a bare base64 payload or a full data: URL. */
    private function stripDataUrl(string $value): string
    {
        if (str_starts_with($value, 'data:') && ($comma = strpos($value, ',')) !== false) {
            return substr($value, $comma + 1);
        }

        return $value;
    }

    private function geofenceBlock(?float $lat, ?float $lng, array $tag): ?string
    {
        if (! config('attendance.geofence.enforce', true)) {
            return null;
        }

        if ($lat === null || $lng === null) {
            if (! AttendanceStation::active()->exists()) {
                return null;
            }

            return 'Location is required to clock in here. Please enable location access and try again.';
        }

        // resolve() already answers "was there anything to compare against":
        // station_id stays null only when no active station exists, since a
        // real fix always matches some nearest one once any station does.
        if ($tag['station_id'] === null) {
            return null;
        }

        if ($tag['out_of_range'] === true) {
            $distance = $tag['distance_m'] >= 1000
                ? round($tag['distance_m'] / 1000, 1) . 'km'
                : $tag['distance_m'] . 'm';

            return "You are {$distance} from {$tag['station_name']} — you must be within {$tag['radius_m']}m to clock in.";
        }

        return null;
    }

    /**
     * Tag the punch with where it happened and keep the row HR's monitor reads.
     *
     * The geometry is passed in rather than re-resolved: geofenceBlock() above
     * already computed it to decide whether this punch was allowed at all, and
     * a punch should be recorded against the exact tag it was judged by.
     *
     * With enforcement on, an out-of-range punch never reaches this method —
     * it was refused before the employee was even identified. The flag-and-
     * record path below therefore only runs when an operator has explicitly
     * turned enforcement off, which is a supported mode, not a leftover.
     */
    private function tagLocation(Request $request, Employee $employee, array $validated, array $result, array $tag): array
    {
        $lat = isset($validated['geo']['lat']) ? (float) $validated['geo']['lat'] : null;
        $lng = isset($validated['geo']['lng']) ? (float) $validated['geo']['lng'] : null;

        if ($result['recorded']) {
            $log = AttendancePunchLog::create([
                'employee_id'  => $employee->id,
                'emp_ID'       => $employee->emp_ID,
                'action'       => $validated['action'],
                'mode'         => $validated['mode'],
                'lat'          => $lat,
                'lng'          => $lng,
                'accuracy_m'   => isset($validated['geo']['accuracy'])
                    ? (int) round((float) $validated['geo']['accuracy'])
                    : null,
                'station_id'   => $tag['station_id'],
                'station_name' => $tag['station_name'],
                'distance_m'   => $tag['distance_m'],
                'out_of_range' => $tag['out_of_range'],
                'ip_address'   => $request->ip(),
            ]);

            // An out-of-range punch is recorded, never blocked — field work is a
            // fact of LGU life. What it does do is ring HR's bell, so the "where
            // were you" conversation starts from a notification with the distance
            // on it rather than from someone's memory a week later.
            if ($tag['out_of_range'] === true) {
                Notification::create([
                    'empid'    => $employee->emp_ID,
                    'lapp_id'  => $log->id,
                    // 'category' is NOT NULL with no default in this table, and
                    // the attendance module does not use it, so it is set to 0.
                    'category' => 0,
                    'utype'    => 'hr',
                    'module'   => 'attendance',
                    'status'   => 0,
                ]);
            }
        }

        return [
            'has_location' => $lat !== null,
            'station_name' => $tag['station_name'],
            'distance_m'   => $tag['distance_m'],
            'out_of_range' => $tag['out_of_range'],
        ];
    }

    /**
     * The QR carries an encrypted emp_ID, matching what the employee QR card
     * prints. A garbled scan decrypts to nothing rather than to somebody else.
     */
    private function employeeFromQr(?string $token): ?Employee
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        try {
            $empId = trim((string) shortDecrypt(trim($token)));
        } catch (\Throwable) {
            return null;
        }

        if ($empId === '') {
            return null;
        }

        return Employee::where('emp_ID', $empId)->where('stat_1', 1)->first();
    }

    /** The only employee details the portal is allowed to put on screen. */
    private function card(Employee $employee): array
    {
        $name = trim(preg_replace('/\s+/', ' ', "{$employee->fname} {$employee->lname}"));

        return [
            'name'     => $name,
            'position' => $employee->position ?: null,
            'initials' => strtoupper(substr($employee->fname, 0, 1) . substr($employee->lname, 0, 1)),
        ];
    }

    private function fail(string $message, int $code = 422): JsonResponse
    {
        return response()->json(['status' => $code, 'message' => $message], $code);
    }
}
