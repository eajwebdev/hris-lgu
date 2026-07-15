<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\Employee;
use App\Services\FaceEmbeddingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Face descriptors are simulated here, and the simulation matters, so it is worth
 * being explicit about it.
 *
 * A person is a random 128-vector (their identity). A live capture is that vector
 * sampled several times with fresh per-frame noise, so consecutive frames drift
 * apart — which is the property the FRONTAL-ONLY liveness check leans on. A still
 * photograph is that same identity vector barely changing between frames, and that
 * flatness is exactly what marks it as not a live face.
 *
 * Note the honest limit this suite now encodes: frontal-only liveness catches a
 * *still* photo, not a photo waved hard enough to fake frame-to-frame variation,
 * nor a video replay. The head-turn challenge that used to catch those is gone by
 * design; the QR path is the stronger option where that gap matters.
 */
class AttendancePortalTest extends TestCase
{
    use DatabaseTransactions;

    /** How far a head turn moves the descriptor, relative to identity. */
    private const POSE_STRENGTH = 0.15;

    private Employee $alice;
    private Employee $bob;
    private FaceEmbeddingService $faces;

    protected function setUp(): void
    {
        parent::setUp();

        // Challenges, the vector index and the rate limiter all live in the cache.
        Cache::flush();

        $this->faces = app(FaceEmbeddingService::class);

        [$this->alice, $this->bob] = Employee::orderBy('id')->take(2)->get()->all();

        $this->alice->forceFill(['stat_1' => 1])->save();
        $this->bob->forceFill(['stat_1' => 1])->save();
    }

    // ---------------------------------------------------------------- fixtures

    private function randomVector(int $seed): array
    {
        mt_srand($seed);

        $vector = [];

        for ($i = 0; $i < 128; $i++) {
            $vector[] = (mt_rand() / mt_getrandmax()) - 0.5;
        }

        return $vector;
    }

    /** The direction a left / right head turn pushes any face's descriptor. */
    private function poseDirection(string $pose): array
    {
        return $this->randomVector($pose === 'left' ? 770001 : 770002);
    }

    /**
     * One frame of one person.
     *
     * $pose null means looking straight ahead — which is all a photograph can ever
     * produce, no matter how it is held.
     */
    private function frame(int $person, ?string $pose, int $jitter, float $amplitude = 0.03): array
    {
        $vector = $this->randomVector($person);

        if ($pose !== null) {
            $direction = $this->poseDirection($pose);

            for ($i = 0; $i < 128; $i++) {
                $vector[$i] += self::POSE_STRENGTH * $direction[$i];
            }
        }

        $noise = $this->randomVector($jitter);

        for ($i = 0; $i < 128; $i++) {
            $vector[$i] += $noise[$i] * $amplitude * 2;
        }

        return $vector;
    }

    private function enrol(Employee $employee, int $person): void
    {
        $captures = [
            ['type' => 'front',    'embedding' => $this->frame($person, null,    $person + 1, 0.02)],
            ['type' => 'left',     'embedding' => $this->frame($person, 'left',  $person + 2, 0.02)],
            ['type' => 'right',    'embedding' => $this->frame($person, 'right', $person + 3, 0.02)],
            ['type' => 'movement', 'embedding' => $this->frame($person, null,    $person + 4, 0.03)],
        ];

        $master = $this->faces->masterEmbedding(array_column($captures, 'embedding'));

        $employee->face_embeddings = $this->faces->payload($captures, $master, 1, 'Test HR');
        $employee->save();

        $this->faces->storeVector($employee->id, $master);
    }

    // ---------------------------------------------------------------- flow

    private function challenge(): array
    {
        return $this->postJson(route('attendanceChallenge'))->assertOk()->json('challenge');
    }

    /** Frames as a living person facing the camera would produce them. */
    private function liveFrames(int $person, int $spacing = 400): array
    {
        $frames = [];
        $t      = 0;

        for ($i = 0; $i < 5; $i++) {
            $frames[] = [
                'stage'      => 'neutral',
                'pose'       => null,
                't'          => $t,
                // Fresh noise each frame, so the run drifts enough to clear the
                // min_variation floor — the thing that separates a face from a photo.
                'descriptor' => $this->frame($person, null, $person + 100 + $i, 0.05),
            ];

            $t += $spacing;
        }

        return $frames;
    }

    /**
     * Frames as a still *photograph* held to the lens would produce them: the same
     * face, barely changing frame to frame. That flatness is what the frontal
     * liveness check reads as "not a live face". A tiny $jitter keeps the frames
     * from being byte-identical without lifting them over the variation floor.
     */
    private function photoFrames(int $person, float $jitter = 0.003): array
    {
        $frames = [];
        $t      = 0;

        for ($i = 0; $i < 5; $i++) {
            $frames[] = [
                'stage'      => 'neutral',
                'pose'       => null,
                't'          => $t,
                'descriptor' => $this->frame($person, null, $person + 300 + $i, $jitter),
            ];

            $t += 400;
        }

        return $frames;
    }

    private function punch(array $payload)
    {
        return $this->postJson(route('attendancePunch'), $payload);
    }

    /** The happy path, end to end: challenge, live frames, punch. */
    private function livePunch(int $person, string $action = 'in', array $extra = [])
    {
        $challenge = $this->challenge();

        return $this->punch(array_merge([
            'mode'   => 'face',
            'action' => $action,
            'nonce'  => $challenge['nonce'],
            'frames' => $this->liveFrames($person),
        ], $extra));
    }

    private function todayFor(Employee $employee): ?Dtr
    {
        return Dtr::where('emp_ID', $employee->emp_ID)
            ->where('date', now()->toDateString())
            ->latest('id')
            ->first();
    }

    private function name(Employee $employee): string
    {
        return trim("{$employee->fname} {$employee->lname}");
    }

    // ================================================================ ANTI-SPOOF

    /**
     * The headline requirement: a still photograph must not clock anyone in. Its
     * frames barely differ, so it fails the frontal variation check.
     */
    public function test_a_still_photo_cannot_clock_in(): void
    {
        $this->enrol($this->alice, 100);

        $challenge = $this->challenge();

        $this->punch([
            'mode'   => 'face',
            'action' => 'in',
            'nonce'  => $challenge['nonce'],
            'frames' => $this->photoFrames(100),
        ])->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /** A captured payload cannot be sent twice: the challenge is burned on use. */
    public function test_a_challenge_cannot_be_replayed(): void
    {
        $this->enrol($this->alice, 130);

        $challenge = $this->challenge();
        $frames    = $this->liveFrames(130);

        $this->punch(['mode' => 'face', 'action' => 'in', 'nonce' => $challenge['nonce'], 'frames' => $frames])
            ->assertOk();

        $this->punch(['mode' => 'face', 'action' => 'in', 'nonce' => $challenge['nonce'], 'frames' => $frames])
            ->assertStatus(419);
    }

    /** A failed attempt burns the challenge too — otherwise it could be ground at. */
    public function test_a_failed_attempt_also_burns_the_challenge(): void
    {
        $this->enrol($this->alice, 140);

        $challenge = $this->challenge();

        $this->punch([
            'mode'   => 'face',
            'action' => 'in',
            'nonce'  => $challenge['nonce'],
            'frames' => $this->photoFrames(140),
        ])->assertStatus(403);

        // Same nonce, now with good frames: still refused.
        $this->punch([
            'mode'   => 'face',
            'action' => 'in',
            'nonce'  => $challenge['nonce'],
            'frames' => $this->liveFrames(140),
        ])->assertStatus(419);
    }

    public function test_an_unknown_nonce_is_rejected(): void
    {
        $this->enrol($this->alice, 150);

        $this->punch([
            'mode'   => 'face',
            'action' => 'in',
            'nonce'  => 'made-up-nonce',
            'frames' => $this->liveFrames(150),
        ])->assertStatus(419);
    }

    /** All the frames landing in one instant is a payload, not a capture. */
    public function test_frames_submitted_too_quickly_are_rejected(): void
    {
        $this->enrol($this->alice, 160);

        $challenge = $this->challenge();

        $this->punch([
            'mode'   => 'face',
            'action' => 'in',
            'nonce'  => $challenge['nonce'],
            'frames' => $this->liveFrames(160, 10),
        ])->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /**
     * Faces enrolled on the retired device have only a centroid, no per-pose
     * captures. Frontal-only liveness needs neither — the identity still verifies
     * against the stored vector — so these employees can punch without re-enrolling.
     */
    public function test_a_legacy_enrolment_can_still_clock_in(): void
    {
        $vectors = [$this->frame(170, null, 1, 0.02), $this->frame(170, null, 2, 0.02)];

        $this->alice->face_embeddings = [
            'vecs'     => $vectors,
            'centroid' => $this->faces->masterEmbedding($vectors),
        ];
        $this->alice->save();

        $this->faces->storeVector($this->alice->id, $this->faces->masterEmbedding($vectors));

        $this->livePunch(170)
            ->assertOk()
            ->assertJsonPath('recorded', true);

        $this->assertNotNull($this->todayFor($this->alice));
    }

    // ================================================================ HAPPY PATH

    public function test_a_live_face_clocks_in(): void
    {
        $this->enrol($this->alice, 200);

        $this->livePunch(200, 'in')
            ->assertOk()
            ->assertJsonPath('action', 'CLOCK IN')
            ->assertJsonPath('recorded', true)
            ->assertJsonPath('employee.name', $this->name($this->alice));

        $row = $this->todayFor($this->alice);

        $this->assertNotEmpty($row->time_in);
        $this->assertEmpty($row->time_out);
    }

    public function test_a_live_face_clocks_out(): void
    {
        $this->enrol($this->alice, 210);

        $this->livePunch(210, 'out')
            ->assertOk()
            ->assertJsonPath('action', 'CLOCK OUT');

        $row = $this->todayFor($this->alice);

        $this->assertNotEmpty($row->time_out);
        $this->assertEmpty($row->time_in);
    }

    public function test_clocking_out_right_after_clocking_in_is_allowed(): void
    {
        $this->enrol($this->alice, 220);

        $this->livePunch(220, 'in')->assertOk();
        $this->livePunch(220, 'out')->assertOk();

        $row = $this->todayFor($this->alice);

        $this->assertNotEmpty($row->time_in);
        $this->assertNotEmpty($row->time_out);
    }

    public function test_the_same_action_twice_is_throttled(): void
    {
        $this->enrol($this->alice, 230);

        $this->livePunch(230, 'in')->assertOk();
        $this->livePunch(230, 'in')->assertStatus(429);

        $this->assertCount(1, explode(',', $this->todayFor($this->alice)->time_in));
    }

    public function test_the_right_person_is_picked_from_two_enrolled_faces(): void
    {
        $this->enrol($this->alice, 240);
        $this->enrol($this->bob, 241);

        $this->livePunch(241)
            ->assertOk()
            ->assertJsonPath('employee.name', $this->name($this->bob));

        $this->assertNull($this->todayFor($this->alice));
        $this->assertNotNull($this->todayFor($this->bob));
    }

    public function test_an_unenrolled_face_is_not_recognised(): void
    {
        $this->enrol($this->alice, 250);

        $this->livePunch(9999)->assertStatus(404);

        $this->assertNull($this->todayFor($this->alice));
    }

    public function test_an_inactive_employee_cannot_punch(): void
    {
        $this->enrol($this->alice, 260);

        $this->alice->forceFill(['stat_1' => 0])->save();
        $this->faces->forgetIndex();

        $this->livePunch(260)->assertStatus(404);

        $this->assertNull($this->todayFor($this->alice));
    }

    // ================================================================ QR MODE

    public function test_a_valid_qr_returns_the_employee_name(): void
    {
        $this->enrol($this->alice, 300);

        $this->postJson(route('attendanceQrCheck'), ['qr' => shortEncrypt($this->alice->emp_ID)])
            ->assertOk()
            ->assertJsonPath('employee.name', $this->name($this->alice));
    }

    public function test_a_garbage_qr_is_refused(): void
    {
        $this->postJson(route('attendanceQrCheck'), ['qr' => 'not-a-real-token'])->assertStatus(404);
    }

    public function test_qr_plus_a_live_matching_face_clocks_in(): void
    {
        $this->enrol($this->alice, 310);

        $this->livePunch(310, 'in', [
            'mode' => 'qr',
            'qr'   => shortEncrypt($this->alice->emp_ID),
        ])->assertOk()->assertJsonPath('employee.name', $this->name($this->alice));

        $this->assertNotNull($this->todayFor($this->alice));
    }

    /** Holding somebody else's badge must not clock them in. */
    public function test_someone_elses_qr_with_your_face_is_refused(): void
    {
        $this->enrol($this->alice, 320);
        $this->enrol($this->bob, 321);

        $this->livePunch(321, 'in', [   // Bob's face
            'mode' => 'qr',
            'qr'   => shortEncrypt($this->alice->emp_ID), // Alice's badge
        ])->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
        $this->assertNull($this->todayFor($this->bob));
    }

    /** QR mode is not a way around the liveness check. */
    public function test_qr_plus_a_photo_is_refused(): void
    {
        $this->enrol($this->alice, 330);

        $challenge = $this->challenge();

        $this->punch([
            'mode'   => 'qr',
            'action' => 'in',
            'qr'     => shortEncrypt($this->alice->emp_ID),
            'nonce'  => $challenge['nonce'],
            'frames' => $this->photoFrames(330),
        ])->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    // ================================================================ HARDENING

    /** The client is never allowed to say who it is. */
    public function test_an_employee_id_in_the_body_is_ignored(): void
    {
        $this->enrol($this->alice, 400);
        $this->enrol($this->bob, 401);

        $this->livePunch(400, 'in', [
            'emp_ID'      => $this->bob->emp_ID,
            'employee_id' => $this->bob->id,
        ])
            ->assertOk()
            ->assertJsonPath('employee.name', $this->name($this->alice));

        $this->assertNull($this->todayFor($this->bob));
        $this->assertNotNull($this->todayFor($this->alice));
    }

    public function test_a_punch_without_a_nonce_is_rejected(): void
    {
        $this->enrol($this->alice, 410);

        $this->punch([
            'mode'   => 'face',
            'action' => 'in',
            'frames' => $this->liveFrames(410),
        ])->assertStatus(422);
    }

    public function test_a_malformed_descriptor_is_rejected(): void
    {
        $this->enrol($this->alice, 420);

        $challenge = $this->challenge();
        $frames    = $this->liveFrames(420);

        $frames[0]['descriptor'] = array_slice($frames[0]['descriptor'], 0, 64);

        $this->punch(['mode' => 'face', 'action' => 'in', 'nonce' => $challenge['nonce'], 'frames' => $frames])
            ->assertStatus(422);

        $this->assertNull($this->todayFor($this->alice));
    }

    public function test_the_portal_is_publicly_reachable(): void
    {
        $this->get(route('attendancePortal'))
            ->assertOk()
            ->assertSee('CLOCK IN')
            ->assertSee('CLOCK OUT')
            ->assertSee('js/face-api/face-api.min.js')
            ->assertSee('js/jsqr/jsQR.min.js');
    }

    public function test_the_response_never_carries_a_face_vector(): void
    {
        $this->enrol($this->alice, 430);

        $body = $this->livePunch(430)->assertOk()->json();

        $this->assertSame(['name', 'position', 'initials'], array_keys($body['employee']));
        $this->assertStringNotContainsString('embedding', json_encode($body));
    }
}
