<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The punch endpoint's server-side flash verification.
 *
 * The point is provenance: the light readings the liveness thresholds run
 * against must be measured from the submitted pixels, not reported by the
 * browser. These drive the real endpoint so the wiring is covered, not just the
 * verifier in isolation.
 */
class PunchFlashImagesTest extends TestCase
{
    private function challenge(): array
    {
        $res = $this->postJson(route('attendanceChallenge'));
        $res->assertOk();

        return $res->json('challenge');
    }

    /** A frame whose face region reacts to the segment, or doesn't if $flat. */
    private function frame(string $segment, bool $flat, int $n): string
    {
        [$sr, $sg, $sb] = [
            'white' => [1.0, 1.0, 1.0],
            'dark'  => [0.05, 0.05, 0.05],
            'red'   => [1.0, 0.12, 0.12],
            'green' => [0.12, 1.0, 0.12],
            'blue'  => [0.12, 0.12, 1.0],
        ][$segment];

        $ambient = 46.0;
        $faceGain = 110.0;
        $bgGain = $flat ? 110.0 : 14.0;

        $im = imagecreatetruecolor(320, 240);
        imagefilledrectangle($im, 0, 0, 320, 240, imagecolorallocate($im,
            (int) min(255, $ambient + $bgGain * $sr),
            (int) min(255, $ambient + $bgGain * $sg),
            (int) min(255, $ambient + $bgGain * $sb)));
        imagefilledellipse($im, 160 + ($n % 3) - 1, 120, 120, 150, imagecolorallocate($im,
            (int) min(255, $ambient + $faceGain * $sr),
            (int) min(255, $ambient + $faceGain * $sg),
            (int) min(255, $ambient + $faceGain * $sb)));
        for ($i = 0; $i < 40; $i++) {
            imagesetpixel($im, 110 + ($i * 7 + $n * 3) % 100, 60 + ($i * 11 + $n * 5) % 120,
                imagecolorallocate($im, 20 + $n, 20 + $n, 20 + $n));
        }

        ob_start();
        imagejpeg($im, null, 92);
        $bin = ob_get_clean();
        imagedestroy($im);

        return base64_encode($bin);
    }

    /**
     * Build a payload for the issued sequence. $shown lets a test present
     * different colours than the ones demanded, which is the replay case.
     */
    private function payload(array $challenge, bool $flat = false, ?array $shown = null, bool $withImages = true): array
    {
        $issued = $challenge['flash'];
        $shown ??= $issued;

        $samples = [];
        foreach ($issued as $i => $seg) {
            $sample = [
                'seg'  => $seg,
                't'    => $i * 120,
                'face' => [200, 200, 200],   // what a lying client would claim
                'bg'   => [60, 60, 60],
            ];
            if ($withImages) {
                $sample['image'] = $this->frame($shown[$i] ?? $seg, $flat, $i);
                $sample['box'] = [100, 45, 220, 195];
            }
            $samples[] = $sample;
        }

        $descriptor = array_fill(0, (int) config('face.dimension', 512), 0.02);

        return [
            'mode'   => 'face',
            'action' => 'in',
            'nonce'  => $challenge['nonce'],
            'frames' => array_map(fn ($i) => [
                'stage' => 'neutral', 't' => $i * 100, 'descriptor' => $descriptor,
            ], [0, 1, 2]),
            'flash'  => ['samples' => $samples],
        ];
    }

    public function test_a_flat_photograph_is_refused_by_the_endpoint(): void
    {
        config(['face.liveness_flash_frames.require_images' => true]);

        $res = $this->postJson(route('attendancePunch'), $this->payload($this->challenge(), flat: true));

        $res->assertStatus(422);
        $this->assertStringContainsString('flat', strtolower($res->json('message') ?? ''));
    }

    public function test_frames_showing_the_wrong_colours_are_refused(): void
    {
        config(['face.liveness_flash_frames.require_images' => true]);

        $challenge = $this->challenge();
        // A recording: every segment shows green regardless of what was demanded.
        $shown = array_fill(0, count($challenge['flash']), 'green');

        $res = $this->postJson(route('attendancePunch'), $this->payload($challenge, shown: $shown));

        $res->assertStatus(422);
    }

    /** Omitting the images must refuse, not fall back to trusting the client. */
    public function test_missing_images_are_refused_when_required(): void
    {
        config(['face.liveness_flash_frames.require_images' => true]);

        $res = $this->postJson(route('attendancePunch'),
            $this->payload($this->challenge(), withImages: false));

        // Rejected either by validation (422) or by the fail-closed branch.
        $this->assertContains($res->status(), [422]);
    }

    /** With the flag off, a client that sends no images is not blocked by this. */
    public function test_images_are_optional_while_the_flag_is_off(): void
    {
        config(['face.liveness_flash_frames.require_images' => false]);

        $res = $this->postJson(route('attendancePunch'),
            $this->payload($this->challenge(), withImages: false));

        // It will still fail later (no enrolled face), but not on flash images.
        $this->assertStringNotContainsString('liveness', strtolower($res->json('message') ?? ''));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
