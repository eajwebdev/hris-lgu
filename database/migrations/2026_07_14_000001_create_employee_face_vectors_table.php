<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per employee holding the single vector we search against.
 *
 * employees.face_embeddings stays the system of record for the raw captures.
 * This table is the projection that face matching reads: one master embedding
 * per employee, nothing else, so a 3,000-employee scan stays a scan of 3,000
 * short rows instead of 3,000 blobs of four descriptors each.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_face_vectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->longText('master_embedding')->nullable();
            $table->unsignedInteger('embedding_dimension')->nullable();
            $table->timestamps();

            $table->unique('employee_id');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_face_vectors');
    }

    /**
     * Carry across every face already enrolled through the old device API.
     *
     * Those rows are shaped {"vecs": [...], "centroid": [...]}; the centroid is
     * the same quantity this module calls a master embedding, so employees who
     * were enrolled on the handset keep their registration instead of having to
     * queue up and do it again.
     */
    private function backfill(): void
    {
        $dimension = (int) config('face.dimension', 128);
        $now = now();

        DB::table('employees')
            ->select('id', 'face_embeddings')
            ->whereNotNull('face_embeddings')
            ->orderBy('id')
            ->chunk(200, function ($employees) use ($dimension, $now) {
                $rows = [];

                foreach ($employees as $employee) {
                    $master = $this->masterFrom($employee->face_embeddings, $dimension);

                    if ($master === null) {
                        continue;
                    }

                    $rows[] = [
                        'employee_id'         => $employee->id,
                        'master_embedding'    => json_encode($master),
                        'embedding_dimension' => $dimension,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }

                if ($rows) {
                    DB::table('employee_face_vectors')->insert($rows);
                }
            });
    }

    /**
     * Pull a master embedding out of either storage shape.
     *
     * Kept self-contained rather than calling FaceEmbeddingService: a migration
     * has to keep producing the same result years from now, even if the service
     * is refactored around it.
     */
    private function masterFrom(?string $json, int $dimension): ?array
    {
        $raw = json_decode((string) $json, true);

        if (! is_array($raw)) {
            return null;
        }

        $isVector = fn ($v) => is_array($v)
            && count($v) === $dimension
            && ! in_array(false, array_map(fn ($x) => is_numeric($x) && is_finite((float) $x), $v), true);

        // Current shape, and the legacy centroid, are both already a single vector.
        foreach (['master_embedding', 'centroid'] as $key) {
            if ($isVector($raw[$key] ?? null)) {
                return $this->normalize(array_map('floatval', $raw[$key]), $dimension);
            }
        }

        // Otherwise average whatever captures are on the row.
        $vectors = [];

        foreach ($raw['captures'] ?? [] as $capture) {
            if ($isVector($capture['embedding'] ?? null)) {
                $vectors[] = $this->normalize(array_map('floatval', $capture['embedding']), $dimension);
            }
        }

        foreach ($raw['vecs'] ?? [] as $vector) {
            if ($isVector($vector)) {
                $vectors[] = $this->normalize(array_map('floatval', $vector), $dimension);
            }
        }

        if (! $vectors) {
            return null;
        }

        $sum = array_fill(0, $dimension, 0.0);

        foreach ($vectors as $vector) {
            for ($i = 0; $i < $dimension; $i++) {
                $sum[$i] += $vector[$i];
            }
        }

        $count = count($vectors);

        for ($i = 0; $i < $dimension; $i++) {
            $sum[$i] /= $count;
        }

        return $this->normalize($sum, $dimension);
    }

    private function normalize(array $vector, int $dimension): array
    {
        $sum = 0.0;

        foreach ($vector as $x) {
            $sum += $x * $x;
        }

        $norm = sqrt(max($sum, 1e-12));

        for ($i = 0; $i < $dimension; $i++) {
            $vector[$i] = $vector[$i] / $norm;
        }

        return $vector;
    }
};
