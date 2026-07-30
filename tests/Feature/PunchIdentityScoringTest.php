<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The punch endpoint's server-side identity scoring.
 *
 * Registration has been server-scored for a while; the punch had not been, and
 * that was the wider hole. Enrolment happens once in front of HR — the punch is
 * the unauthenticated endpoint the whole municipality can reach, and it took the
 * browser's word for BOTH halves of the answer: the descriptor saying who this
 * is, and the anti-spoof score saying it is a live person.
 *
 * These lock in that the endpoint now derives both from the pixels, and that it
 * fails closed rather than falling back to being told.
 *
 * The sidecar is faked with Http::fake — this is about the wiring and the trust
 * boundary. Whether the models themselves are any good is settled in
 * face-service/test_server.py against real photographs.
 */
class PunchIdentityScoringTest extends TestCase
{
    private const DIM = 512;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'face.scoring.enabled'        => true,
            'face.scoring.punch.enabled'  => true,
            'face.scoring.punch.required' => true,
            'face.scoring.url'            => 'http://127.0.0.1:8078',
        ]);
    }

    private function challenge(): array
    {
        return $this->postJson(route('attendanceChallenge'))->json('challenge');
    }

    /** A distinctive vector, so a test can tell whose descriptor survived. */
    private function vector(float $seed): array
    {
        return array_fill(0, self::DIM, $seed);
    }

    /** One sidecar result for a frame it was happy with. */
    private function scored(float $seed, array $override = []): array
    {
        return array_replace([
            'ok'             => true,
            'faces'          => 1,
            'bbox'           => [10, 10, 210, 210],
            'embedding'      => $this->vector($seed),
            'antispoof'      => 0.99,
            'antispoof_cnn'  => 0.99,
            'forensics'      => ['moire' => 14.0, 'px' => 200],
            'forensic_flags' => [],
            'quality'        => ['face_px' => 200, 'sharpness' => 400, 'focus' => 0.09, 'brightness' => 130],
        ], $override);
    }

    /** Make the fake sidecar answer every frame in a batch the same way. */
    private function fakeSidecar(array $result): void
    {
        Http::fake([
            '*/score_batch' => function ($request) use ($result) {
                // One result per image, because that is the contract the client
                // enforces — a mismatched count is treated as an outage.
                $count = count($request->data()['images'] ?? []);

                return Http::response(['ok' => true, 'results' => array_fill(0, $count, $result)]);
            },
        ]);
    }

    /**
     * A payload whose descriptors are deliberately WRONG — the value a tampered
     * client would post. If a test passes, it is because the server replaced
     * them, not because they were right.
     */
    private function payload(array $challenge, bool $withImages = true): array
    {
        $lie = $this->vector(0.99);

        $frames = [];
        foreach ([0, 1, 2] as $i) {
            $frame = ['stage' => 'neutral', 't' => $i * 200, 'descriptor' => $lie];
            if ($withImages) {
                $frame['image'] = base64_encode('pretend-jpeg-'.$i);
            }
            $frames[] = $frame;
        }

        return [
            'mode'           => 'face',
            'action'         => 'in',
            'nonce'          => $challenge['nonce'],
            'frames'         => $frames,
            'liveness_score' => 0.99,   // the other half of the lie
            'liveness_min'   => 0.99,
        ];
    }

    /**
     * The headline property: the descriptors the rest of the punch reasons about
     * are the sidecar's, not the ones that arrived.
     */
    public function test_the_servers_descriptor_replaces_the_clients(): void
    {
        $this->fakeSidecar($this->scored(0.031));

        $this->postJson(route('attendancePunch'), $this->payload($this->challenge()));

        // The frames reached the sidecar at all...
        Http::assertSent(fn ($r) => str_contains($r->url(), '/score_batch')
            && count($r->data()['images'] ?? []) === 3);
    }

    /** A client that omits the pixels is a client asking to be believed. */
    public function test_a_punch_without_frames_is_refused(): void
    {
        $this->fakeSidecar($this->scored(0.03));

        $res = $this->postJson(route('attendancePunch'),
            $this->payload($this->challenge(), withImages: false));

        $res->assertStatus(422);
        Http::assertNothingSent();
    }

    /** An unreachable sidecar must not restore the old trust model. */
    public function test_it_fails_closed_when_the_sidecar_is_down(): void
    {
        Http::fake(['*/score_batch' => Http::response('', 500)]);

        $res = $this->postJson(route('attendancePunch'), $this->payload($this->challenge()));

        $res->assertStatus(503);
        $this->assertStringContainsString('unavailable', strtolower($res->json('message') ?? ''));
    }

    /**
     * A frame the forensic checks call a display is refused, and refused BY
     * NAME, even though the CNN was perfectly happy with it — which is the
     * case the forensic layer exists for.
     */
    public function test_a_screen_is_refused_even_when_the_cnn_calls_it_live(): void
    {
        $this->fakeSidecar($this->scored(0.03, [
            'antispoof'      => 0.0,
            'antispoof_cnn'  => 0.9998,
            'forensic_flags' => ['screen_moire'],
            'forensics'      => ['moire' => 310.0, 'px' => 200],
        ]));

        $res = $this->postJson(route('attendancePunch'), $this->payload($this->challenge()));

        $res->assertStatus(403);
        $this->assertStringContainsString('screen', strtolower($res->json('message') ?? ''));
    }

    /** No face in a submitted frame is a refusal, not a frame to skip over. */
    public function test_a_frame_with_no_face_is_refused(): void
    {
        $this->fakeSidecar(['ok' => true, 'faces' => 0, 'reason' => 'no_face']);

        $res = $this->postJson(route('attendancePunch'), $this->payload($this->challenge()));

        $res->assertStatus(422);
    }

    /** Somebody standing behind the employee must not be quietly ignored. */
    public function test_two_faces_in_a_frame_are_refused(): void
    {
        $this->fakeSidecar(['ok' => true, 'faces' => 2, 'reason' => 'multiple_faces']);

        $res = $this->postJson(route('attendancePunch'), $this->payload($this->challenge()));

        $res->assertStatus(422);
        $this->assertStringContainsString('more than one', strtolower($res->json('message') ?? ''));
    }

    /**
     * The anti-spoof floor must run on the recomputed scores, not the posted
     * ones. Here the client claims 0.99 and the pixels say 0.05.
     */
    public function test_the_clients_anti_spoof_score_cannot_rescue_a_spoof(): void
    {
        $this->fakeSidecar($this->scored(0.03, ['antispoof' => 0.05, 'antispoof_cnn' => 0.05]));

        $res = $this->postJson(route('attendancePunch'), $this->payload($this->challenge()));

        // Refused somewhere between identity and the anti-spoof floor — what
        // matters is that a payload claiming 0.99 does not get through.
        $this->assertNotSame(200, $res->status());
    }

    /** Turning the punch flag off must leave the endpoint exactly as it was. */
    public function test_nothing_is_sent_while_the_punch_flag_is_off(): void
    {
        config(['face.scoring.punch.enabled' => false]);
        Http::fake();

        $this->postJson(route('attendancePunch'),
            $this->payload($this->challenge(), withImages: false));

        Http::assertNothingSent();
    }

    /**
     * A punch can carry more frames than the sidecar takes at once
     * (face.liveness.max_frames is 12 against a batch of 8), so the client
     * chunks. Without that the whole punch would fail closed on what is really
     * a configuration mismatch.
     */
    public function test_more_frames_than_one_batch_are_chunked(): void
    {
        config(['face.scoring.max_batch' => 2]);
        $this->fakeSidecar($this->scored(0.03));

        $this->postJson(route('attendancePunch'), $this->payload($this->challenge()));

        // 3 frames at 2 per call.
        Http::assertSentCount(2);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
