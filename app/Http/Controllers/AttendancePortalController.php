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

        $validated = $request->validate([
            'mode'                 => ['required', Rule::in(['face', 'qr'])],
            'action'               => ['required', Rule::in(['in', 'out'])],
            'nonce'                => ['required', 'string', 'max:64'],
            'frames'               => ['required', 'array', 'min:3', 'max:' . $maxFrames],
            // Frontal-only capture: every frame is a straight-ahead 'neutral'. The
            // 'pose' union is kept so an older client mid-rollout still validates.
            'frames.*.stage'       => ['required', Rule::in(['neutral', 'pose'])],
            'frames.*.pose'        => ['nullable', Rule::in(['left', 'right'])],
            'frames.*.t'           => ['required', 'numeric'],
            'frames.*.descriptor'  => ['required', 'array', 'size:' . $dimension],
            'frames.*.descriptor.*'=> ['required', 'numeric'],
            'qr'                   => ['nullable', 'string', 'max:512', 'required_if:mode,qr'],
            // Live-face probability from the browser's anti-spoof model. Nullable:
            // the model may be absent, in which case the check does not apply.
            'liveness_score'       => ['nullable', 'numeric', 'between:0,1'],
            // Optional on purpose: an employee with location services off can
            // still punch — the missing fix is itself recorded for HR to see.
            'geo'                  => ['nullable', 'array'],
            'geo.lat'              => ['required_with:geo', 'numeric', 'between:-90,90'],
            'geo.lng'              => ['required_with:geo', 'numeric', 'between:-180,180'],
            'geo.accuracy'         => ['nullable', 'numeric', 'between:0,100000'],
        ]);

        // Burned on the first attempt, pass or fail — see LivenessVerifier::redeem.
        $challenge = $this->liveness->redeem($validated['nonce'], $request->ip());

        if (! $challenge) {
            return $this->fail('This attempt expired. Please try again.', 419);
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

        // Only now, with an identity in hand, ask the question a still photo cannot
        // answer: does this face drift frame-to-frame the way a living one does?
        $refusal = $this->liveness->check($employee, $frames);

        if ($refusal !== null) {
            Log::warning('Portal liveness check failed.', [
                'emp_ID' => $employee->emp_ID,
                'reason' => $refusal,
                'ip'     => $request->ip(),
            ]);

            return $this->fail($refusal, 403);
        }

        // Anti-spoof. The browser already blocked an obvious photo/screen locally;
        // this enforces the same threshold server-side and — more importantly —
        // logs the score, so a run of low scores from one spot shows up for HR.
        // A null score means the model was not available, so the check is skipped
        // (the server cannot recompute it — the pixels never leave the browser).
        $liveness = $validated['liveness_score'] ?? null;

        if (config('face.antispoof.enabled', true) && $liveness !== null
            && (float) $liveness < (float) config('face.antispoof.min_real', 0.5)) {
            Log::warning('Portal anti-spoof rejected.', [
                'emp_ID'   => $employee->emp_ID,
                'liveness' => round((float) $liveness, 3),
                'ip'       => $request->ip(),
            ]);

            return $this->fail('Spoof detected. Please use your real face, not a photo or screen.', 403);
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

        $location = $this->tagLocation($request, $employee, $validated, $result);

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
     * Tag the punch with where it happened and keep the row HR's monitor reads.
     *
     * The tag never blocks anything — the punch has already been written by the
     * time this runs. It exists so a punch made far from every station carries
     * its distance on the record, and nobody has to have the "where were you"
     * conversation from memory.
     */
    private function tagLocation(Request $request, Employee $employee, array $validated, array $result): array
    {
        $lat = isset($validated['geo']['lat']) ? (float) $validated['geo']['lat'] : null;
        $lng = isset($validated['geo']['lng']) ? (float) $validated['geo']['lng'] : null;

        $tag = $this->geo->resolve($lat, $lng);

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
