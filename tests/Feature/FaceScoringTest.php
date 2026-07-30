<?php
namespace Tests\Feature;

use App\Services\FaceScoringClient;
use Tests\TestCase;

/**
 * The point of server-side scoring is that the client can no longer assert its
 * own liveness. These lock in the two properties that matter: it fails closed,
 * and a capture too poor to enrol from is refused.
 */
class FaceScoringTest extends TestCase
{
    private FaceScoringClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new FaceScoringClient();
    }

    public function test_it_is_off_by_default_so_nothing_changes_until_enabled(): void
    {
        config(['face.scoring.enabled' => false]);

        $this->assertFalse($this->client->enabled());
        $this->assertSame('scoring_disabled', $this->client->score('x')['reason']);
    }

    /** The whole point: an unreachable scorer must not fall back to trusting the client. */
    public function test_it_fails_closed_when_the_service_is_unreachable(): void
    {
        config([
            'face.scoring.enabled'  => true,
            'face.scoring.required' => true,
            'face.scoring.url'      => 'http://127.0.0.1:9',
            'face.scoring.timeout'  => 2,
        ]);

        $result = $this->client->score(base64_encode('not-an-image'));

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['unavailable']);
        $this->assertSame('service_unreachable', $result['reason']);
        $this->assertTrue($this->client->required(), 'default must be fail-closed');
    }

    /** A capture good enough to enrol from, as the sidecar reports one. */
    private function goodCapture(array $override = []): array
    {
        return array_replace_recursive([
            'quality'   => ['face_px' => 220, 'sharpness' => 800, 'focus' => 0.14, 'brightness' => 130],
            'antispoof' => 0.98,
            'flags'     => [],
        ], $override);
    }

    public function test_enrolment_gate_rejects_a_face_that_is_too_small(): void
    {
        $gate = $this->client->meetsEnrolmentQuality(
            $this->goodCapture(['quality' => ['face_px' => 40]])
        );

        $this->assertFalse($gate['ok']);
        $this->assertSame('face_too_small', $gate['reason']);
        $this->assertStringContainsString('closer', $this->client->explain('face_too_small'));
    }

    /**
     * Blur is judged on 'focus' — the Laplacian variance divided by the crop's
     * own contrast — and NOT on raw sharpness. This capture carries a high raw
     * number precisely because a blurred picture of a high-contrast scene does;
     * that is why the raw number was never a usable blur gate.
     */
    public function test_enrolment_gate_rejects_a_blurred_capture(): void
    {
        $gate = $this->client->meetsEnrolmentQuality(
            $this->goodCapture(['quality' => ['sharpness' => 950, 'focus' => 0.009]])
        );

        $this->assertFalse($gate['ok']);
        $this->assertSame('too_blurry', $gate['reason']);
    }

    /** A lens cap or a blank wall: no edge content at all, whatever the contrast. */
    public function test_enrolment_gate_rejects_a_frame_with_no_detail(): void
    {
        $gate = $this->client->meetsEnrolmentQuality(
            $this->goodCapture(['quality' => ['sharpness' => 1.0, 'focus' => 0.5]])
        );

        $this->assertFalse($gate['ok']);
        $this->assertSame('no_detail', $gate['reason']);
    }

    public function test_enrolment_gate_rejects_darkness_and_glare(): void
    {
        $dark = $this->client->meetsEnrolmentQuality($this->goodCapture(['quality' => ['brightness' => 10]]));
        $bright = $this->client->meetsEnrolmentQuality($this->goodCapture(['quality' => ['brightness' => 250]]));

        $this->assertSame('too_dark', $dark['reason']);
        $this->assertSame('too_bright', $bright['reason']);
    }

    /** A photograph or a screen must not become an enrolled template. */
    public function test_enrolment_gate_rejects_a_frame_the_model_calls_a_spoof(): void
    {
        $gate = $this->client->meetsEnrolmentQuality($this->goodCapture(['antispoof' => 0.10]));

        $this->assertFalse($gate['ok']);
        $this->assertSame('not_live', $gate['reason']);
    }

    /**
     * The forensic checks are the layer that does not depend on the anti-spoof
     * CNN agreeing. Measured against real photographs and simulated panels the
     * CNN scored screens as high as 1.0000 — so a frame flagged by the spectrum
     * must be refused on that alone, with the CNN still saying it is live.
     */
    public function test_enrolment_gate_rejects_a_screen_the_model_thought_was_live(): void
    {
        $gate = $this->client->meetsEnrolmentQuality(
            $this->goodCapture(['antispoof' => 0.99, 'flags' => ['screen_moire']])
        );

        $this->assertFalse($gate['ok']);
        $this->assertSame('screen_moire', $gate['reason']);
        $this->assertStringContainsString('screen', $this->client->explain('screen_moire'));
    }

    public function test_forensic_rejection_can_be_switched_off_for_recalibration(): void
    {
        config(['face.scoring.reject_forensic_flags' => false]);

        $gate = $this->client->meetsEnrolmentQuality(
            $this->goodCapture(['flags' => ['screen_moire']])
        );

        $this->assertTrue($gate['ok']);
    }

    public function test_a_good_capture_passes(): void
    {
        $gate = $this->client->meetsEnrolmentQuality($this->goodCapture());

        $this->assertTrue($gate['ok']);
        $this->assertNull($gate['reason']);
    }

    /**
     * A batch must come back one result per frame, in order. Anything else and
     * a caller would pair one capture's verdict with another's embedding, which
     * is worse than an outage because it looks like success.
     */
    public function test_a_batch_that_cannot_be_lined_up_fails_closed(): void
    {
        config([
            'face.scoring.enabled' => true,
            'face.scoring.url'     => 'http://127.0.0.1:9',
            'face.scoring.timeout' => 2,
        ]);

        $results = $this->client->scoreMany(['a', 'b', 'c']);

        $this->assertCount(3, $results);

        foreach ($results as $r) {
            $this->assertFalse($r['ok']);
            $this->assertTrue($r['unavailable']);
        }
    }

    public function test_scoring_an_empty_batch_is_not_an_error(): void
    {
        $this->assertSame([], $this->client->scoreMany([]));
    }

    public function test_every_refusal_reason_has_wording_for_the_operator(): void
    {
        foreach (['no_face', 'multiple_faces', 'face_too_small', 'too_blurry', 'no_detail',
                  'too_dark', 'too_bright', 'not_live', 'screen_moire', 'unalignable_face',
                  'undecodable', 'service_unreachable', 'scoring_disabled'] as $reason) {
            $this->assertNotSame(
                'The capture could not be verified. Please try again.',
                $this->client->explain($reason),
                "no specific wording for '{$reason}'"
            );
        }
    }
}
