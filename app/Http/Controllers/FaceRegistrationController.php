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
        $empid = $id ?: auth()->guard($guard)->user()->id;

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

        $validated = $request->validate([
            'captures'             => ['required', 'array', 'size:' . count($required)],
            'captures.*.type'      => ['required', 'string', Rule::in($required)],
            'captures.*.embedding' => ['required', 'array', 'size:' . $dimension],
            'captures.*.embedding.*' => ['required', 'numeric'],
        ], [
            'captures.size'   => 'All ' . count($required) . ' captures are required to complete registration.',
            'captures.*.embedding.size' => 'A face descriptor was malformed. Please redo the registration.',
        ]);

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
            ]);

            return $this->fail('This face is already registered to another employee.', 422);
        }

        $actor = auth()->guard('web')->user();
        $actorName = trim("{$actor->fname} {$actor->lname}");

        DB::transaction(function () use ($employee, $ordered, $master, $actor, $actorName, $request) {
            $employee->face_embeddings = $this->faces->payload($ordered, $master, $actor->id, $actorName);
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
