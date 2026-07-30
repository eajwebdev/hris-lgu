<?php
namespace Tests\Feature;

use App\Services\FlashFrameVerifier;
use Tests\TestCase;

/**
 * The screen-flash challenge, verified from the submitted frames rather than
 * from numbers the browser reports.
 *
 * Frames are synthesised so the physics being relied on is explicit:
 *
 *   a real head    - the screen lights the face far more than the wall behind it
 *                    (light falls off with distance), and the face takes the cast
 *   a flat print   - takes the cast, but face and surround are the same distance
 *                    from the screen, so both brighten equally
 *   a recording    - genuinely three-dimensional, but its colours were fixed
 *                    before the server chose the sequence
 */
class FlashFrameVerifierTest extends TestCase
{
    private FlashFrameVerifier $v;

    /** The face oval inside the synthetic 320x240 frame. */
    private array $box = [100, 45, 220, 195];

    protected function setUp(): void
    {
        parent::setUp();
        $this->v = new FlashFrameVerifier();
    }

    private function segment(string $name): array
    {
        return [
            'white' => [1.00, 1.00, 1.00],
            'dark'  => [0.05, 0.05, 0.05],
            'red'   => [1.00, 0.12, 0.12],
            'green' => [0.12, 1.00, 0.12],
            'blue'  => [0.12, 0.12, 1.00],
        ][$name];
    }

    /** @param bool $flat true = a print, where nothing is nearer than anything else */
    private function frame(string $segment, bool $flat, int $n): string
    {
        [$sr, $sg, $sb] = $this->segment($segment);
        $ambient = 46.0;
        $faceGain = 110.0;
        $bgGain = $flat ? 110.0 : 14.0;

        $im = imagecreatetruecolor(320, 240);

        $bg = imagecolorallocate($im,
            (int) min(255, $ambient + $bgGain * $sr),
            (int) min(255, $ambient + $bgGain * $sg),
            (int) min(255, $ambient + $bgGain * $sb));
        imagefilledrectangle($im, 0, 0, 320, 240, $bg);

        $face = imagecolorallocate($im,
            (int) min(255, $ambient + $faceGain * $sr),
            (int) min(255, $ambient + $faceGain * $sg),
            (int) min(255, $ambient + $faceGain * $sb));
        imagefilledellipse($im, 160 + ($n % 3) - 1, 120 + (($n + 1) % 3) - 1, 120, 150, $face);

        // Per-frame texture, so genuinely distinct frames hash differently.
        for ($i = 0; $i < 40; $i++) {
            imagesetpixel($im, 110 + ($i * 7 + $n * 3) % 100, 60 + ($i * 11 + $n * 5) % 120,
                imagecolorallocate($im, 20 + $n, 20 + $n, 20 + $n));
        }

        ob_start();
        imagejpeg($im, null, 92);
        $bin = ob_get_clean();
        imagedestroy($im);

        return $bin;
    }

    private function attempt(array $issued, array $shown, bool $flat = false): array
    {
        $measured = [];
        foreach ($shown as $i => $s) {
            $measured[] = $this->v->measure($this->frame($s, $flat, $i), $this->box);
        }

        return $this->v->verify($issued, $measured);
    }

    public function test_a_live_face_reacting_to_the_sequence_passes(): void
    {
        $seq = ['white', 'red', 'dark', 'blue'];
        $result = $this->attempt($seq, $seq);

        $this->assertTrue($result['ok'], 'reason: '.($result['reason'] ?? ''));
        $this->assertGreaterThan(6, $result['detail']['flash_delta']);
        $this->assertGreaterThan(3, $result['detail']['face_bg_delta']);
    }

    /**
     * A print brightens just as much as a real face, so brightness alone would
     * admit it. It is the face-vs-background falloff that gives it away.
     */
    public function test_a_flat_photograph_is_rejected_despite_reacting_to_the_light(): void
    {
        $seq = ['white', 'red', 'dark', 'blue'];
        $result = $this->attempt($seq, $seq, flat: true);

        $this->assertFalse($result['ok']);
        $this->assertSame('flat_surface', $result['reason']);
        // It did brighten — which is exactly why the falloff check is needed.
        $this->assertGreaterThan(6, $result['detail']['flash_delta']);
        $this->assertLessThan(3, $result['detail']['face_bg_delta']);
    }

    /**
     * The strongest check. A recording is of a real three-dimensional person, so
     * it satisfies both brightness and falloff — but its colours were fixed
     * before the server picked the sequence.
     */
    public function test_a_recording_made_before_the_challenge_is_rejected(): void
    {
        $issued = ['white', 'red', 'dark', 'blue'];
        $recorded = ['white', 'green', 'dark', 'green'];

        $result = $this->attempt($issued, $recorded);

        $this->assertFalse($result['ok']);
        $this->assertSame('no_colour_response', $result['reason']);
        // It passed the checks a 3-D subject passes.
        $this->assertGreaterThan(3, $result['detail']['face_bg_delta']);
    }

    public function test_the_same_still_resubmitted_is_rejected(): void
    {
        $seq = ['white', 'red', 'dark', 'blue'];
        $still = $this->frame('white', false, 0);

        $measured = array_fill(0, 4, $this->v->measure($still, $this->box));

        $result = $this->v->verify($seq, $measured);

        $this->assertFalse($result['ok']);
        $this->assertSame('no_flash_response', $result['reason']);
    }

    public function test_a_short_or_padded_frame_set_is_rejected(): void
    {
        $seq = ['white', 'red', 'dark', 'blue'];

        $short = $this->attempt($seq, ['white', 'red']);
        $this->assertSame('frame_count', $short['reason']);
    }

    public function test_an_undecodable_frame_is_rejected_not_ignored(): void
    {
        $this->assertNull($this->v->measure('this is not an image', $this->box));
    }

    public function test_a_face_box_too_small_to_measure_is_rejected(): void
    {
        $frame = $this->frame('white', false, 0);

        $this->assertNull($this->v->measure($frame, [10, 10, 14, 14]));
    }

    public function test_every_refusal_has_wording_for_the_operator(): void
    {
        foreach (['frame_count', 'frame_unreadable', 'no_flash_response',
                  'flat_surface', 'no_colour_response', 'duplicate_frames'] as $reason) {
            $this->assertNotSame(
                'Liveness could not be confirmed. Please try again.',
                $this->v->explain($reason),
                "no specific wording for '{$reason}'"
            );
        }
    }
}
