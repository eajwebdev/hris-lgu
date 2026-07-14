<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeFaceVector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Every piece of vector arithmetic in the face module lives here.
 *
 * Two rules hold throughout:
 *
 *   1. Descriptors are stored L2-normalised. Once every vector sits on the unit
 *      sphere, squared-euclidean distance and cosine similarity order candidates
 *      identically, so one threshold works everywhere and comparison is a dot
 *      product rather than a division.
 *
 *   2. The master embedding is derived on the server from the four captures the
 *      browser sends. The client never gets to declare what an employee's face
 *      "is" — it only supplies the raw poses.
 */
class FaceEmbeddingService
{
    /** Cache of the searchable vector set, invalidated on every write. */
    private const CACHE_KEY = 'face:master_vectors';
    private const CACHE_TTL = 900;

    private int $dimension;

    public function __construct()
    {
        $this->dimension = (int) config('face.dimension', 128);
    }

    // ---------------------------------------------------------------- vectors

    /**
     * A descriptor is only usable if it is the right length and every component
     * is a real number. NaN and INF have to be caught here: they survive JSON,
     * poison a centroid, and turn every later distance into NaN.
     */
    public function isValidVector($vector): bool
    {
        if (! is_array($vector) || count($vector) !== $this->dimension) {
            return false;
        }

        foreach ($vector as $component) {
            if (! is_numeric($component) || ! is_finite((float) $component)) {
                return false;
            }
        }

        return true;
    }

    public function normalize(array $vector): array
    {
        $sum = 0.0;

        foreach ($vector as $component) {
            $sum += $component * $component;
        }

        $norm = sqrt(max($sum, 1e-12));
        $out  = [];

        foreach ($vector as $component) {
            $out[] = $component / $norm;
        }

        return $out;
    }

    /**
     * Squared euclidean distance. Squared, because the square root is a
     * monotonic transform: it changes no ordering and no threshold decision, so
     * paying for it on every one of 3,000 comparisons buys nothing.
     */
    public function distanceSquared(array $a, array $b): float
    {
        $sum = 0.0;

        for ($i = 0; $i < $this->dimension; $i++) {
            $d = $a[$i] - $b[$i];
            $sum += $d * $d;
        }

        return $sum;
    }

    /**
     * Fold the captures into the one vector that represents this employee:
     * normalise each pose, average them, then re-normalise so the result lands
     * back on the unit sphere.
     *
     * Averaging front, left, right and movement pulls the vector toward the
     * centre of that employee's pose cluster, which is what makes a single
     * comparison hold up against a face the camera sees at a slight angle.
     */
    public function masterEmbedding(array $vectors): ?array
    {
        $sum   = array_fill(0, $this->dimension, 0.0);
        $count = 0;

        foreach ($vectors as $vector) {
            if (! $this->isValidVector($vector)) {
                continue;
            }

            $vector = $this->normalize(array_map('floatval', $vector));

            for ($i = 0; $i < $this->dimension; $i++) {
                $sum[$i] += $vector[$i];
            }

            $count++;
        }

        if ($count === 0) {
            return null;
        }

        for ($i = 0; $i < $this->dimension; $i++) {
            $sum[$i] /= $count;
        }

        return $this->normalize($sum);
    }

    // ---------------------------------------------------------------- storage

    /**
     * Read whatever is in employees.face_embeddings into one predictable shape.
     *
     * Two shapes exist in the wild. This module writes {captures, master_embedding,
     * registered_at, registered_by}. Rows enrolled through the retired Android
     * device API are {vecs, centroid} — those employees are genuinely registered
     * and must not be reported as unregistered just because the JSON is older.
     */
    public function describe($stored): array
    {
        $blank = [
            'registered'        => false,
            'captures'          => [],
            'capture_count'     => 0,
            'master_embedding'  => null,
            'registered_at'     => null,
            'registered_by'     => null,
            'registered_by_name'=> null,
            'legacy'            => false,
        ];

        $raw = is_string($stored) ? json_decode($stored, true) : $stored;

        if (! is_array($raw)) {
            return $blank;
        }

        $captures = [];

        foreach ($raw['captures'] ?? [] as $capture) {
            if (is_array($capture) && $this->isValidVector($capture['embedding'] ?? null)) {
                $captures[] = [
                    'type'      => (string) ($capture['type'] ?? 'unknown'),
                    'embedding' => array_map('floatval', $capture['embedding']),
                ];
            }
        }

        $legacyVectors = [];

        foreach ($raw['vecs'] ?? [] as $vector) {
            if ($this->isValidVector($vector)) {
                $legacyVectors[] = array_map('floatval', $vector);
            }
        }

        $master = null;

        foreach (['master_embedding', 'centroid'] as $key) {
            if ($this->isValidVector($raw[$key] ?? null)) {
                $master = $this->normalize(array_map('floatval', $raw[$key]));
                break;
            }
        }

        if ($master === null) {
            $master = $this->masterEmbedding(
                $captures ? array_column($captures, 'embedding') : $legacyVectors
            );
        }

        if ($master === null) {
            return $blank;
        }

        $isLegacy = empty($captures) && ! empty($legacyVectors);

        return [
            'registered'         => true,
            'captures'           => $captures,
            'capture_count'      => $isLegacy ? count($legacyVectors) : count($captures),
            'master_embedding'   => $master,
            'registered_at'      => $raw['registered_at'] ?? null,
            'registered_by'      => $raw['registered_by'] ?? null,
            'registered_by_name' => $raw['registered_by_name'] ?? null,
            'legacy'             => $isLegacy,
        ];
    }

    /**
     * Build the JSON written to employees.face_embeddings.
     */
    public function payload(array $captures, array $master, int $actorId, string $actorName): array
    {
        return [
            'captures'           => array_values($captures),
            'master_embedding'   => $master,
            'registered_at'      => now()->toIso8601String(),
            'registered_by'      => $actorId,
            'registered_by_name' => $actorName,
        ];
    }

    /**
     * What the profile panel and the status endpoint both render.
     *
     * Returns metadata only. Embeddings are biometric data and never leave the
     * server — the UI needs to know that a face exists, when it was enrolled and
     * by whom, and nothing more.
     */
    public function summary(Employee $employee): array
    {
        $face = $this->describe($employee->face_embeddings);

        $registeredAt = null;

        if ($face['registered_at']) {
            try {
                $registeredAt = Carbon::parse($face['registered_at'])->format('M d, Y g:i A');
            } catch (\Throwable) {
                $registeredAt = null;
            }
        }

        return [
            'registered'    => $face['registered'],
            'capture_count' => $face['capture_count'],
            'registered_at' => $registeredAt,
            'registered_by' => $face['registered_by_name'],
            // Enrolled through the retired device API: real registration, but it
            // predates the four-pose capture set, so the UI says so instead of
            // claiming a capture count it cannot vouch for.
            'legacy'        => $face['legacy'],
        ];
    }

    // ---------------------------------------------------------------- matching

    /**
     * Is this face already registered to somebody else?
     *
     * Runs against employee_face_vectors — one row and one vector per employee —
     * rather than re-parsing every employee's capture set out of a LONGTEXT
     * column. At 3,000 employees this is 3,000 dot products over a cached
     * matrix, which is milliseconds; when the workforce outgrows that, this is
     * the single method to point at a vector index, because it is the only
     * place a similarity search happens.
     */
    public function findDuplicate(array $master, ?int $excludeEmployeeId = null): ?array
    {
        $threshold = (float) config('face.duplicate_distance', 0.55);
        $limit     = $threshold * $threshold;

        $best     = null;
        $bestDist = INF;

        foreach ($this->vectorIndex() as $row) {
            if ($excludeEmployeeId !== null && (int) $row['employee_id'] === $excludeEmployeeId) {
                continue;
            }

            $distance = $this->distanceSquared($master, $row['vector']);

            if ($distance < $bestDist) {
                $bestDist = $distance;
                $best     = $row;
            }
        }

        if ($best === null || $bestDist >= $limit) {
            return null;
        }

        return [
            'employee' => Employee::find($best['employee_id']),
            'distance' => sqrt($bestDist),
        ];
    }

    /**
     * Who is this? — the 1:N search behind the attendance portal.
     *
     * Two passes, because the two costs are very different:
     *
     *   1. Scan every enrolled employee's master embedding. One vector each, off
     *      a cached matrix, so 3,000 employees is 3,000 dot products.
     *   2. Take only the closest handful and refine those against their four
     *      individual captures, which is where the LONGTEXT reads happen.
     *
     * Acceptance needs both an absolute distance *and* a margin over the
     * runner-up. The margin is what keeps a face that was never enrolled from
     * being handed to whichever employee it happens to sit nearest.
     *
     * Returns null when nobody is confidently identified — which is a correct
     * answer, not an error.
     */
    public function identify(array $probe): ?array
    {
        if (! $this->isValidVector($probe)) {
            return null;
        }

        $probe  = $this->normalize(array_map('floatval', $probe));
        $config = (array) config('face.match');

        $index = $this->vectorIndex();

        if (! $index) {
            return null;
        }

        // Pass 1 — cheap scan over one vector per employee.
        $candidates = [];

        foreach ($index as $row) {
            $candidates[] = [
                'employee_id' => $row['employee_id'],
                'distance2'   => $this->distanceSquared($probe, $row['vector']),
            ];
        }

        usort($candidates, fn ($a, $b) => $a['distance2'] <=> $b['distance2']);

        $shortlist = array_slice($candidates, 0, (int) $config['shortlist']);

        // Pass 2 — refine the shortlist against each employee's actual captures.
        // A head turned slightly away sits closer to the matching left/right
        // capture than to the averaged master, so this both improves the true
        // match and sharpens the gap to the runner-up.
        $rows = DB::table('employees')
            ->whereIn('id', array_column($shortlist, 'employee_id'))
            ->pluck('face_embeddings', 'id');

        $best = null;
        $bestDistance2   = INF;
        $secondDistance2 = INF;

        foreach ($shortlist as $candidate) {
            $local = $candidate['distance2'];

            $face = $this->describe($rows->get($candidate['employee_id']));

            foreach ($face['captures'] as $capture) {
                $distance2 = $this->distanceSquared($probe, $this->normalize($capture['embedding']));

                if ($distance2 < $local) {
                    $local = $distance2;
                }
            }

            if ($local < $bestDistance2) {
                $secondDistance2 = $bestDistance2;
                $bestDistance2   = $local;
                $best            = $candidate['employee_id'];
            } elseif ($local < $secondDistance2) {
                $secondDistance2 = $local;
            }
        }

        if ($best === null || $bestDistance2 > ((float) $config['distance'] ** 2)) {
            return null;
        }

        // With only one enrolled employee there is no runner-up to measure
        // against, so the ratio test has nothing to say and is skipped.
        if (is_finite($secondDistance2) && $secondDistance2 > 0.0) {
            $ratio = sqrt($bestDistance2) / sqrt($secondDistance2);

            if ($ratio > (float) $config['ratio']) {
                return null;
            }
        }

        $employee = Employee::find($best);

        if (! $employee || (int) $employee->stat_1 !== 1) {
            return null;
        }

        return [
            'employee' => $employee,
            'distance' => sqrt($bestDistance2),
        ];
    }

    /**
     * Is this probe the face of this specific employee? — the 1:1 check used
     * after a QR scan has already asserted an identity.
     *
     * No ratio test here: the QR has named the employee, so the only question is
     * whether the face in front of the camera is close enough to theirs.
     */
    public function verify(Employee $employee, array $probe): ?float
    {
        if (! $this->isValidVector($probe)) {
            return null;
        }

        $probe = $this->normalize(array_map('floatval', $probe));
        $face  = $this->describe($employee->face_embeddings);

        if (! $face['registered']) {
            return null;
        }

        $vectors = array_column($face['captures'], 'embedding');
        $vectors[] = $face['master_embedding'];

        $best = INF;

        foreach ($vectors as $vector) {
            $best = min($best, $this->distanceSquared($probe, $this->normalize($vector)));
        }

        $limit = (float) config('face.match.distance') ** 2;

        return $best <= $limit ? sqrt($best) : null;
    }

    /**
     * The searchable set, held as plain arrays so the cache stores floats rather
     * than hydrated Eloquent models.
     */
    private function vectorIndex(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $index = [];

            DB::table('employee_face_vectors')
                ->select('employee_id', 'master_embedding')
                ->whereNotNull('master_embedding')
                ->orderBy('employee_id')
                ->chunk(500, function ($rows) use (&$index) {
                    foreach ($rows as $row) {
                        $vector = json_decode($row->master_embedding, true);

                        if ($this->isValidVector($vector)) {
                            $index[] = [
                                'employee_id' => (int) $row->employee_id,
                                'vector'      => array_map('floatval', $vector),
                            ];
                        }
                    }
                });

            return $index;
        });
    }

    /**
     * Called on every write. The index is a cache of the table, so it is only
     * ever as correct as its last invalidation.
     */
    public function forgetIndex(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ---------------------------------------------------------------- writes

    public function storeVector(int $employeeId, array $master): void
    {
        EmployeeFaceVector::updateOrCreate(
            ['employee_id' => $employeeId],
            [
                'master_embedding'    => $master,
                'embedding_dimension' => $this->dimension,
            ]
        );

        $this->forgetIndex();
    }

    public function clearVector(int $employeeId): void
    {
        EmployeeFaceVector::where('employee_id', $employeeId)
            ->update([
                'master_embedding'    => null,
                'embedding_dimension' => null,
            ]);

        $this->forgetIndex();
    }
}
