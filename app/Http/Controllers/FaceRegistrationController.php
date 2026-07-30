<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\FaceAuditLog;
use App\Services\FaceEmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Phase 1: enrolment only.
 *
 * Nothing here authenticates anybody or clocks anybody in. It captures four
 * poses, derives one master embedding, and refuses the write if that face
 * already belongs to a different employee.
 *
 * Raw imagery never reaches this controller. The browser sends 128-float
 * descriptors; there is no code path on which a JPEG, a PNG or a base64 frame
 * is accepted, and none on which one is stored.
 */
class FaceRegistrationController extends Controller
{
    public function __construct(private FaceEmbeddingService $faces)
    {
        // The base constructor shares the notification bell and job-application
        // dropdown with every full HTML page. Declaring a constructor here for
        // dependency injection overrides it, and the master layout then blows up
        // on an undefined $notificationsCount — so hand control back.
        parent::__construct();
    }

    /**
     * The Face Recognition page — its own entry in the PDS submenu, alongside
     * E-Signature. Biometric enrolment is not part of an employee's personal
     * information and does not belong bolted onto that form.
     */
    public function page($id = null)
    {
        $guard = auth()->guard('web')->check() ? 'web' : 'employee';

        // An employee only ever sees their own enrolment. The middleware already
        // 403s a foreign {id}; ignoring the parameter here means the page cannot
        // even render somebody else's state by accident.
        // (->user()->id, not ->id(): the employee guard's auth identifier is the
        // email, so ->id() would not return a primary key.)
        $empid = $guard === 'employee'
            ? auth()->guard('employee')->user()->id
            : ($id ?: auth()->guard($guard)->user()->id);

        $employee = Employee::find($empid);

        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

        return view('emp.face-recognition', compact('employee', 'guard', 'empid'));
    }

    /**
     * Registration state for one employee, for the panel to refresh itself
     * without a page reload.
     *
     * Deliberately returns no vectors. An embedding is biometric data; the UI
     * only ever needs to know that one exists, when, and who put it there.
     */
    public function status(Employee $employee): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'face'   => $this->faces->summary($employee),
        ]);
    }

    /**
     * Store a completed four-capture registration.
     */
    public function store(Request $request, Employee $employee): JsonResponse
    {
        $required = (array) config('face.captures', ['front', 'left', 'right', 'movement']);
        $dimension = (int) config('face.dimension', 128);

        $scoring = app(\App\Services\FaceScoringClient::class);

        $validated = $request->validate([
            'captures'             => ['required', 'array', 'size:' . count($required)],
            'captures.*.type'      => ['required', 'string', Rule::in($required)],
            'captures.*.embedding' => ['required', 'array', 'size:' . $dimension],
            'captures.*.embedding.*' => ['required', 'numeric'],
            // Sent only when server-side scoring is on. The frame is what makes
            // the enrolment verifiable: without it the embedding below is just a
            // number the browser asserts.
            'captures.*.frame'     => [$scoring->enabled() ? 'required' : 'nullable', 'string'],
            // The browser's anti-spoof probability for this capture. Shape is
            // validated here; whether a MISSING one is acceptable is policy,
            // decided just below so the refusal can say why.
            'captures.*.real'      => ['nullable', 'numeric', 'between:0,1'],
        ], [
            'captures.size'   => 'All ' . count($required) . ' captures are required to complete registration.',
            'captures.*.embedding.size' => 'A face descriptor was malformed. Please redo the registration.',
            'captures.*.frame.required' => 'This capture did not include an image, so it cannot be verified. Please redo the registration.',
        ]);

        // Anti-spoof, enforced on every capture before anything is stored.
        //
        // This runs whether or not the sidecar is available. When scoring IS
        // enabled the block below re-derives the score from the pixels and this
        // is merely a fast pre-filter; when it is not — the default — this is
        // the only thing standing between a held-up photograph and a permanent
        // face template. Previously there was nothing here at all.
        if (config('face.antispoof.enabled', true)) {
            // NOT named $required — that already holds the configured capture
            // list and is needed intact further down.
            $floor          = (float) config('face.antispoof.enrolment_min_real', 0.60);
            $mustHaveScore  = (bool) config('face.antispoof.enrolment_require_score', true);

            foreach ($validated['captures'] as $capture) {
                $real = $capture['real'] ?? null;

                if ($real === null) {
                    if (! $mustHaveScore) {
                        continue;
                    }

                    return $this->fail(
                        ucfirst($capture['type']).' capture: the face security check did not run. '
                        .'Please reload the page and register again.'
                    );
                }

                if ((float) $real < $floor) {
                    Log::info('Face enrolment capture refused by anti-spoof.', [
                        'employee_id' => $employee->id,
                        'capture'     => $capture['type'],
                        'real'        => round((float) $real, 3),
                        'floor'       => $floor,
                    ]);

                    return $this->fail(
                        ucfirst($capture['type']).' capture: please use your real face, '
                        .'not a photo or a screen.'
                    );
                }
            }
        }

        // Score every frame on the server before anything is stored.
        //
        // Two things are being prevented. A photograph or a phone screen cannot
        // be enrolled, because the anti-spoof model sees the frame rather than
        // being told a score. And a poor capture — blurred, too distant, too
        // dark — is refused now instead of becoming a template that matches
        // badly for as long as it is on file.
        //
        // The embedding stored is the one the SERVER computed. The browser's
        // copy is only used when scoring is switched off.
        if ($scoring->enabled()) {
            // One round trip for all four captures. Nothing can be said to the
            // operator until every one of them is back, so scoring them one at
            // a time only adds latency to the same answer.
            $scoredAll = $scoring->scoreMany(array_column($validated['captures'], 'frame'));

            foreach ($validated['captures'] as $i => $capture) {
                $scored = $scoredAll[$i] ?? ['ok' => false, 'reason' => 'service_error', 'unavailable' => true];

                if (! empty($scored['unavailable']) && $scoring->required()) {
                    Log::error('Face registration refused: scoring service unavailable.', [
                        'employee_id' => $employee->id,
                        'reason'      => $scored['reason'],
                    ]);

                    return $this->fail($scoring->explain($scored['reason']), 503);
                }

                if (! $scored['ok']) {
                    return $this->fail(
                        ucfirst($capture['type']).' capture: '.$scoring->explain($scored['reason'])
                    );
                }

                $gate = $scoring->meetsEnrolmentQuality($scored);

                if (! $gate['ok']) {
                    // Worth a line in the log: a rejected enrolment is the one
                    // moment the forensic numbers can be tied to a frame
                    // somebody was standing in front of, which is what a real
                    // threshold calibration needs.
                    Log::info('Face enrolment capture refused.', [
                        'employee_id' => $employee->id,
                        'capture'     => $capture['type'],
                        'reason'      => $gate['reason'],
                        'quality'     => $scored['quality'] ?? null,
                        'forensics'   => $scored['forensics'] ?? null,
                        'antispoof'   => $scored['antispoof'] ?? null,
                    ]);

                    return $this->fail(
                        ucfirst($capture['type']).' capture: '.$scoring->explain($gate['reason'])
                    );
                }

                if (! $this->faces->isValidVector($scored['embedding'] ?? [])) {
                    return $this->fail('The '.$capture['type'].' capture could not be turned into a face signature. Please try again.');
                }

                // Replace what the browser sent with what the server derived,
                // in place, so a capture's verdict can never be paired with a
                // different capture's vector.
                $validated['captures'][$i] = [
                    'type'      => $capture['type'],
                    'embedding' => $scored['embedding'],
                ];
            }
        }

        $captures = [];

        foreach ($validated['captures'] as $capture) {
            // The browser is not trusted to have sent finite numbers: a NaN here
            // would propagate silently into the master embedding and make every
            // future distance comparison against this employee return NaN.
            if (! $this->faces->isValidVector($capture['embedding'])) {
                return $this->fail('A face descriptor was invalid. Please redo the registration.');
            }

            $captures[$capture['type']] = [
                'type'      => $capture['type'],
                'embedding' => $this->faces->normalize(array_map('floatval', $capture['embedding'])),
            ];
        }

        // size:4 stops four captures of three types; this stops four captures of
        // the wrong three types.
        if (array_diff($required, array_keys($captures))) {
            return $this->fail('Every capture step must be completed exactly once.');
        }

        // Ordered as configured, so the stored JSON reads front/left/right/movement
        // regardless of what order the browser finished them in.
        $ordered = array_map(fn ($type) => $captures[$type], $required);

        $master = $this->faces->masterEmbedding(array_column($ordered, 'embedding'));

        if ($master === null) {
            return $this->fail('Could not derive a face signature from these captures. Please try again.');
        }

        $duplicate = $this->faces->findDuplicate($master, $employee->id);

        if ($duplicate) {
            $owner = $duplicate['employee'];

            // The message the employee-facing operator sees stays generic on
            // purpose — naming the other employee would leak who is enrolled.
            Log::warning('Face registration rejected as duplicate.', [
                'employee_id'  => $employee->id,
                'conflicts_with' => $owner?->id,
                'distance'     => round($duplicate['distance'], 4),
                'performed_by' => auth()->guard('web')->id(),
                'self_service' => auth()->guard('web')->guest(),
            ]);

            return $this->fail('This face is already registered to another employee.', 422);
        }

        // A registrar on the web guard, or the employee enrolling themselves.
        // registered_by holds a users.id, so a self-enrolment leaves it null
        // rather than writing an employees.id into the wrong id space — the
        // name is what the panel displays either way.
        $registrar = auth()->guard('web')->user();
        $actor = $registrar ?? auth()->guard('employee')->user();
        $actorName = trim("{$actor->fname} {$actor->lname}") . ($registrar ? '' : ' (self)');

        DB::transaction(function () use ($employee, $ordered, $master, $registrar, $actorName, $request) {
            $employee->face_embeddings = $this->faces->payload($ordered, $master, $registrar?->id, $actorName);
            $employee->save();

            $this->faces->storeVector($employee->id, $master);

            FaceAuditLog::record($employee->id, FaceAuditLog::REGISTERED, $request, [
                'captures'      => count($ordered),
                'dimension'     => (int) config('face.dimension', 128),
                'emp_ID'        => $employee->emp_ID,
            ]);
        });

        // storeVector() drops the index inside the transaction; do it again once
        // the write is actually visible to other connections.
        $this->faces->forgetIndex();

        return response()->json([
            'status'  => 200,
            'message' => 'Face registered successfully.',
            'face'    => $this->faces->summary($employee->refresh()),
        ]);
    }

    /**
     * Erase an employee's face data.
     *
     * The captures and the master embedding both go. Nothing is soft-deleted:
     * the point of removal is that the biometric is gone. The audit row that
     * says it happened is what remains.
     */
    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        if (! $this->faces->describe($employee->face_embeddings)['registered']) {
            return $this->fail('This employee has no face data to remove.', 404);
        }

        DB::transaction(function () use ($employee, $request) {
            $employee->face_embeddings = null;
            $employee->save();

            $this->faces->clearVector($employee->id);

            FaceAuditLog::record($employee->id, FaceAuditLog::REMOVED, $request, [
                'emp_ID' => $employee->emp_ID,
            ]);
        });

        $this->faces->forgetIndex();

        return response()->json([
            'status'  => 200,
            'message' => 'Face recognition data removed.',
            'face'    => $this->faces->summary($employee->refresh()),
        ]);
    }

    private function fail(string $message, int $code = 422): JsonResponse
    {
        return response()->json([
            'status'  => $code,
            'message' => $message,
        ], $code);
    }
}
