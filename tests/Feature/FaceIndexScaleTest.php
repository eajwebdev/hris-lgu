<?php
namespace Tests\Feature;

use App\Services\FaceEmbeddingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The pass-1 index is cached, and the cache driver is 'file' — so its shape is
 * serialised to disk and read back on every punch. Held as a nested PHP array
 * that round trip cost 513 ms at 2,000 employees and dominated everything else;
 * packed as float32 it is ~3 ms.
 *
 * These lock in the two properties that make that safe: the packing is exact
 * enough that the shortlist does not change, and a malformed row cannot shift
 * the vectors that follow it in the blob.
 */
class FaceIndexScaleTest extends TestCase
{
    private const N = 400;      // enough to be representative, quick to seed

    private FaceEmbeddingService $svc;
    private array $seeded = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(FaceEmbeddingService::class);
        Cache::forget('face:master_vectors');
    }

    private function unit(int $seed, int $d = 512): array
    {
        mt_srand($seed);
        $v = [];
        $n = 0.0;
        for ($i = 0; $i < $d; $i++) {
            $x = mt_rand(-1000, 1000) / 1000;
            $v[$i] = $x;
            $n += $x * $x;
        }
        $n = sqrt($n);

        return array_map(fn ($x) => $x / $n, $v);
    }

    private function seedVectors(int $count): void
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $id = 900000 + $i;                       // out of the way of real employees
            $this->seeded[] = $id;
            $rows[] = ['employee_id' => $id, 'master_embedding' => json_encode($this->unit($i))];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('employee_face_vectors')->insert($chunk);
        }
        Cache::forget('face:master_vectors');
    }

    private function index(): array
    {
        $m = (new \ReflectionClass($this->svc))->getMethod('vectorIndex');
        $m->setAccessible(true);

        return $m->invoke($this->svc);
    }

    public function test_the_index_is_a_packed_float32_blob_not_a_php_array(): void
    {
        $this->seedVectors(self::N);
        $index = $this->index();

        $this->assertIsString($index['blob']);
        $this->assertSame(512, $index['dim']);
        // 4 bytes per component, exactly — no padding, no per-row overhead.
        $this->assertSame(count($index['ids']) * 512 * 4, strlen($index['blob']));
    }

    /**
     * float32 must not change the outcome that matters: the true match still
     * ranks first, and every distance agrees with full precision to far tighter
     * than the 1.10 threshold cares about.
     *
     * Exact shortlist ORDER is deliberately not asserted. These fixtures are
     * uniformly random unit vectors, so every unrelated pair sits at almost
     * exactly sqrt(2) and a 1e-7 rounding delta reshuffles ties that were
     * arbitrary to begin with. Real embeddings of different people are far more
     * spread out than this; pinning the order here would be testing the
     * fixture, not the packing.
     */
    public function test_float32_packing_preserves_the_match_and_the_distances(): void
    {
        $this->seedVectors(self::N);
        $index = $this->index();
        $dim = $index['dim'];
        $stride = $dim * 4;

        foreach ([3, 97, 400] as $target) {
            $probe = $this->unit($target);

            $packed = [];
            foreach ($index['ids'] as $slot => $id) {
                $v = unpack('g*', substr($index['blob'], $slot * $stride, $stride));
                $s = 0.0;
                for ($k = 1; $k <= $dim; $k++) {
                    $d = $probe[$k - 1] - $v[$k];
                    $s += $d * $d;
                }
                $packed[$id] = $s;
            }
            asort($packed);

            $reference = [];
            foreach (DB::table('employee_face_vectors')->whereIn('employee_id', $this->seeded)
                ->select('employee_id', 'master_embedding')->get() as $row) {
                $v = json_decode($row->master_embedding, true);
                $s = 0.0;
                for ($k = 0; $k < $dim; $k++) {
                    $d = $probe[$k] - $v[$k];
                    $s += $d * $d;
                }
                $reference[$row->employee_id] = $s;
            }
            asort($reference);

            // The true owner ranks first under both.
            $expected = 900000 + $target;
            $this->assertSame($expected, array_key_first($packed), "packed lost the match for {$target}");
            $this->assertSame($expected, array_key_first($reference), "reference lost the match for {$target}");

            // Every distance agrees to well inside the threshold's resolution.
            $worst = 0.0;
            foreach ($reference as $id => $exact) {
                $worst = max($worst, abs($packed[$id] - $exact));
            }
            $this->assertLessThan(
                1e-4,
                $worst,
                "float32 distance error {$worst} is larger than expected for probe {$target}"
            );
        }
    }

    /**
     * A vector of the wrong length would shift every vector after it in the
     * blob, silently mis-identifying everyone. It must be skipped.
     */
    public function test_a_malformed_row_is_skipped_rather_than_shifting_the_blob(): void
    {
        $this->seedVectors(20);

        $bad = 900999;
        $this->seeded[] = $bad;
        DB::table('employee_face_vectors')->insert([
            'employee_id'      => $bad,
            'master_embedding' => json_encode(array_fill(0, 64, 0.1)),   // wrong dimension
        ]);
        Cache::forget('face:master_vectors');

        $index = $this->index();

        $this->assertNotContains($bad, $index['ids'], 'a short vector must not enter the index');
        $this->assertSame(count($index['ids']) * $index['dim'] * 4, strlen($index['blob']));
    }

    public function test_a_probe_of_the_wrong_dimension_is_refused(): void
    {
        $this->seedVectors(20);

        $this->assertNull($this->svc->identify(array_fill(0, 128, 0.1)));
    }

    protected function tearDown(): void
    {
        if ($this->seeded) {
            DB::table('employee_face_vectors')->whereIn('employee_id', $this->seeded)->delete();
        }
        Cache::forget('face:master_vectors');
        parent::tearDown();
    }
}
