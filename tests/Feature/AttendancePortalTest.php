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
 * A person is a random vector at the configured embedding dimension (their
 * identity). A live capture is that vector
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

        // Most of this class exercises the 1:N face path — identify(), the
        // ratio test, "not recognised" — which the kiosk no longer offers by
        // default now that face.require_qr makes the badge mandatory. The code
        // is still live and still reachable with the flag off, so the coverage
        // is kept by pinning the flag here rather than deleting the tests.
        // test_face_only_is_refused_when_the_badge_is_required() covers the
        // default the kiosk actually ships with.
        //
        // require_images is pinned off for the same reason: these fixtures post
        // modelled face/bg luma to exercise the CHALLENGE and THRESHOLD logic,
        // while PunchFlashImagesTest drives the same endpoint with real JPEGs
        // and is where the server-measures-it-itself guarantee is proven.
        config([
            'face.require_qr' => false,
            'face.liveness_flash_frames.require_images' => false,
        ]);

        $this->faces = app(FaceEmbeddingService::class);

        [$this->alice, $this->bob] = Employee::orderBy('id')->take(2)->get()->all();

        $this->alice->forceFill(['stat_1' => 1])->save();
        $this->bob->forceFill(['stat_1' => 1])->save();
    }

    // ---------------------------------------------------------------- fixtures

    /** The embedding dimension under test — whatever the config says it is. */
    private function dim(): int
    {
        return (int) config('face.dimension');
    }

    private function randomVector(int $seed): array
    {
        mt_srand($seed);

        $vector = [];

        for ($i = 0; $i < $this->dim(); $i++) {
            $vector[] = (mt_rand() / mt_getrandmax()) - 0.5;
        }

        return $vector;
    }

    /** The direction each head gesture pushes any face's descriptor. */
    private function poseDirection(string $pose): array
    {
        $seed = [
            'left'  => 770001,
            'right' => 770002,
            'up'    => 770003,
            'down'  => 770004,
        ][$pose] ?? 770001;

        return $this->randomVector($seed);
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

            for ($i = 0; $i < $this->dim(); $i++) {
                $vector[$i] += self::POSE_STRENGTH * $direction[$i];
            }
        }

        $noise = $this->randomVector($jitter);

        for ($i = 0; $i < $this->dim(); $i++) {
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

    /**
     * A neutral run followed by one frame per gesture the challenge demanded,
     * in the order it demanded them — the shape a real capture now produces.
     * Each pose frame is pushed in that gesture's direction so it sits clear of
     * the straight-ahead master (the min_pose_shift the server enforces).
     * Finally one frame per screen-flash segment, carrying the face luma the
     * server diffs across segments.
     */
    private function challengedFrames(int $person, array $challenge, int $spacing = 400): array
    {
        $frames = $this->liveFrames($person, $spacing);
        $t      = 5 * $spacing;

        foreach (($challenge['poses'] ?? []) as $pose) {
            $frames[] = [
                'stage'      => 'pose',
                'pose'       => $pose,
                't'          => $t,
                'descriptor' => $this->frame($person, $pose, $person + 500 + $t, 0.02),
            ];

            $t += 600;
        }

        return $frames;
    }

    /**
     * The illumination samples a real face in front of the kiosk produces.
     *
     * Modelled rather than hand-waved, so the fixture exercises what the server
     * actually reads: the face tracks the screen colour, the background barely
     * does (it is metres further from the panel), and under a coloured segment
     * the matching channel dominates what the face sends back.
     */
    private function flashPayload(int $person, array $challenge, array $overrides = []): ?array
    {
        $sequence = $challenge['flash'] ?? [];

        if (! $sequence) {
            return null;
        }

        $samples = [];
        $t       = 6000;

        foreach ($sequence as $seg) {
            $samples[] = [
                'seg'  => $seg,
                't'    => $t,
                'face' => $overrides['face'][$seg] ?? $this->litFace($seg),
                'bg'   => $overrides['bg'][$seg]   ?? $this->litBackground($seg),
            ];

            $t += 400;
        }

        return [
            'samples'    => $overrides['samples'] ?? $samples,
            'descriptor' => $overrides['descriptor'] ?? $this->frame($person, null, $person + 700, 0.02),
        ];
    }

    /** What a real face reflects under each screen colour. */
    private function litFace(string $seg): array
    {
        return match ($seg) {
            'white' => [180.0, 180.0, 180.0],
            'dark'  => [40.0, 40.0, 40.0],
            'red'   => [190.0, 60.0, 55.0],
            'green' => [60.0, 190.0, 60.0],
            'blue'  => [55.0, 60.0, 190.0],
            default => [120.0, 120.0, 120.0],
        };
    }

    /** The wall behind: far enough from the panel that it hardly moves. */
    private function litBackground(string $seg): array
    {
        return $seg === 'white' ? [52.0, 52.0, 52.0] : [46.0, 46.0, 46.0];
    }

    /** A full, valid live payload for a given challenge (frames + spoof scores). */
    private function livePayload(int $person, array $challenge, string $action, array $extra = []): array
    {
        return array_merge([
            'mode'           => 'face',
            'action'         => $action,
            'nonce'          => $challenge['nonce'],
            'frames'         => $this->challengedFrames($person, $challenge),
            'flash'          => $this->flashPayload($person, $challenge),
            // The browser's anti-spoof scores. A live face scores high on both
            // the average and the worst frame; the server enforces both floors.
            'liveness_score' => 0.97,
            'liveness_min'   => 0.90,
        ], $extra);
    }

    /** The happy path, end to end: challenge, live frames + gestures, punch. */
    private function livePunch(int $person, string $action = 'in', array $extra = [])
    {
        $challenge = $this->challenge();

        return $this->punch($this->livePayload($person, $challenge, $action, $extra));
    }

    private function todayFor(Employee $employee): ?Dtr
    {
        return Dtr::where('emp_ID', $employee->emp_ID)
            ->where('date', now()->toDateString())
            ->latest('id')
            ->first();
    }

    private function employeeName(Employee $employee): string
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

    /**
     * The flash sequence is the server's to choose, like the poses. Answering a
     * different one — even a well-formed one — is not a capture, it is a guess.
     */
    /** The sequence is the server's to choose; answering a different one is a guess. */
    public function test_a_flash_sequence_in_the_wrong_order_is_rejected(): void
    {
        $this->enrol($this->alice, 110);

        $challenge = $this->challenge();
        $payload   = $this->livePayload(110, $challenge, 'in');

        $payload['flash']['samples'] = array_reverse($payload['flash']['samples']);

        // Reversing the list alone would still match a palindrome; re-stamp the
        // timestamps so the order the server sorts by is genuinely different.
        $t = 6000;

        foreach ($payload['flash']['samples'] as $i => $sample) {
            $payload['flash']['samples'][$i]['t'] = $t;
            $t += 400;
        }

        if (array_column($payload['flash']['samples'], 'seg') === $challenge['flash']) {
            $this->markTestSkipped('Palindromic sequence drawn; nothing to reorder.');
        }

        $this->punch($payload)->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /**
     * The headline requirement: a screen replaying a recording — a saved clip,
     * a live video call — is self-luminous. Its brightness is whatever was
     * recorded, so it does not rise when the kiosk paints its own screen white.
     */
    public function test_a_face_that_does_not_react_to_the_light_is_rejected(): void
    {
        $this->enrol($this->alice, 120);

        $challenge = $this->challenge();

        $flat = [];

        foreach (['white', 'dark', 'red', 'green', 'blue'] as $seg) {
            $flat[$seg] = [128.0, 128.0, 128.0];   // identical under every colour
        }

        $this->punch($this->livePayload(120, $challenge, 'in', [
            'flash' => $this->flashPayload(120, $challenge, ['face' => $flat]),
        ]))->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /**
     * The check a replay device cannot pass however bright it is. A phone held
     * to the lens fills the frame with its own display, so the region beside
     * the face carries the same emitted light and rises with it. On a real
     * person the wall behind is metres further from the panel and barely moves.
     */
    public function test_a_background_that_brightens_with_the_face_is_rejected(): void
    {
        $this->enrol($this->alice, 121);

        $challenge = $this->challenge();

        // Background tracking the face exactly — the signature of a screen.
        $bg = [];

        foreach (['white', 'dark', 'red', 'green', 'blue'] as $seg) {
            $bg[$seg] = $this->litFace($seg);
        }

        $this->punch($this->livePayload(121, $challenge, 'in', [
            'flash' => $this->flashPayload(121, $challenge, ['bg' => $bg]),
        ]))->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /**
     * Brightness alone is not enough: a screen bright enough to clear the luma
     * floor still cannot change its colour balance to match a hue it was never
     * showing.
     */
    public function test_a_face_that_does_not_take_on_the_colour_is_rejected(): void
    {
        $this->enrol($this->alice, 122);

        $challenge = $this->challenge();

        // Tracks brightness convincingly, but stays grey under every colour.
        $greyish = [
            'white' => [190.0, 190.0, 190.0],
            'dark'  => [35.0, 35.0, 35.0],
            'red'   => [120.0, 120.0, 120.0],
            'green' => [120.0, 120.0, 120.0],
            'blue'  => [120.0, 120.0, 120.0],
        ];

        $payload = $this->livePayload(122, $challenge, 'in', [
            'flash' => $this->flashPayload(122, $challenge, ['face' => $greyish]),
        ]);

        // Only meaningful when the drawn sequence actually contains a colour —
        // issueFlash() guarantees one, but assert rather than assume.
        $this->assertNotEmpty(array_intersect($challenge['flash'], ['red', 'green', 'blue']));

        $this->punch($payload)->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /** Omitting the samples entirely must not skip the check. */
    public function test_a_punch_with_no_illumination_samples_is_rejected(): void
    {
        $this->enrol($this->alice, 123);

        $challenge = $this->challenge();

        $this->punch($this->livePayload(123, $challenge, 'in', ['flash' => null]))
            ->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /** The samples must belong to the employee the poses identified. */
    public function test_illumination_samples_from_another_face_are_rejected(): void
    {
        $this->enrol($this->alice, 124);
        $this->enrol($this->bob, 126);

        $challenge = $this->challenge();

        $this->punch($this->livePayload(124, $challenge, 'in', [
            'flash' => $this->flashPayload(124, $challenge, [
                'descriptor' => $this->frame(126, null, 999, 0.02),   // Bob's face
            ]),
        ]))->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
    }

    /** flash_count = 0 turns the whole check off, poses and all else unchanged. */
    public function test_the_illumination_check_can_be_disabled(): void
    {
        config(['face.liveness.flash_count' => 0]);

        $this->enrol($this->alice, 125);

        $challenge = $this->challenge();

        $this->assertSame([], $challenge['flash']);

        $this->punch($this->livePayload(125, $challenge, 'in'))
            ->assertOk()
            ->assertJsonPath('recorded', true);
    }

    /**
     * Every attempt must be able to test all three properties, so the draw is
     * seeded rather than uniform. Exercised against the service directly: the
     * HTTP endpoint is rate-limited to 20/min and this needs more draws than
     * that to be worth anything.
     */
    public function test_every_issued_sequence_contains_white_dark_and_a_colour(): void
    {
        $liveness = app(\App\Services\LivenessVerifier::class);

        for ($i = 0; $i < 50; $i++) {
            $flash = $liveness->issue('127.0.0.1')['flash'];

            $this->assertContains('white', $flash, 'no white segment to measure brightness against');
            $this->assertContains('dark', $flash, 'no dark segment to measure brightness against');
            $this->assertNotEmpty(
                array_intersect($flash, ['red', 'green', 'blue']),
                'no colour segment, so the chroma check would never run'
            );
        }
    }

    /** A captured payload cannot be sent twice: the challenge is burned on use. */
    public function test_a_challenge_cannot_be_replayed(): void
    {
        $this->enrol($this->alice, 130);

        $challenge = $this->challenge();
        $payload   = $this->livePayload(130, $challenge, 'in');

        $this->punch($payload)->assertOk();

        $this->punch($payload)->assertStatus(419);
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
            ->assertJsonPath('employee.name', $this->employeeName($this->alice));

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

    public function test_a_live_face_records_overtime(): void
    {
        $this->enrol($this->alice, 215);

        $this->livePunch(215, 'ot')
            ->assertOk()
            ->assertJsonPath('action', 'OVERTIME');

        $row = $this->todayFor($this->alice);

        // Its own column, and it must not leak into the ordinary day's times.
        $this->assertNotEmpty($row->time_over);
        $this->assertEmpty($row->time_in);
        $this->assertEmpty($row->time_out);
    }

    /**
     * Overtime is ONE column: the start and the end of the stretch both append
     * to time_over and are told apart by order, so a second OT punch extends
     * the list rather than writing anywhere else.
     */
    public function test_a_second_overtime_punch_appends_to_the_same_column(): void
    {
        config(['attendance.cooldown_seconds' => 0]);

        $this->enrol($this->alice, 216);

        $this->livePunch(216, 'ot')->assertOk()->assertJsonPath('recorded', true);

        // Times are stored to the second, and the service drops an exact
        // duplicate. Without this the two punches land in the same second and
        // the second one is correctly discarded — which made this test pass or
        // fail depending on where the wall clock happened to be.
        $this->travel(2)->seconds();

        $this->livePunch(216, 'ot')->assertOk()->assertJsonPath('recorded', true);

        $row = $this->todayFor($this->alice);

        $this->assertCount(2, explode(',', $row->time_over));
        $this->assertEmpty($row->time_in);
        $this->assertEmpty($row->time_out);
    }

    /**
     * Overtime is a punch like any other — the new button is not a new door.
     * A still photograph fails the same frontal-variation check it fails on a
     * clock-in.
     */
    public function test_overtime_still_requires_a_live_face(): void
    {
        $this->enrol($this->alice, 217);

        $challenge = $this->challenge();

        $this->punch([
            'mode'   => 'face',
            'action' => 'ot',
            'nonce'  => $challenge['nonce'],
            'frames' => $this->photoFrames(217),
        ])->assertStatus(403);

        $this->assertNull($this->todayFor($this->alice));
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
            ->assertJsonPath('employee.name', $this->employeeName($this->bob));

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
            ->assertJsonPath('employee.name', $this->employeeName($this->alice));
    }

    public function test_a_garbage_qr_is_refused(): void
    {
        $this->postJson(route('attendanceQrCheck'), ['qr' => 'not-a-real-token'])->assertStatus(404);
    }

    /**
     * The badge requirement is POLICY, enforced by the endpoint.
     *
     * setUp() pins face.require_qr off so this class can keep exercising the
     * 1:N code; this test restores the shipped default and proves a badge-less
     * punch is refused even with a perfect live face. Hiding the "use face
     * only" button in the kiosk script is presentation — the script is editable
     * by whoever holds the device, so the rule has to live here.
     */
    public function test_face_only_is_refused_when_the_badge_is_required(): void
    {
        config(['face.require_qr' => true]);

        $this->enrol($this->alice, 305);

        $this->livePunch(305, 'in')->assertStatus(422);

        $this->assertNull(
            $this->todayFor($this->alice),
            'a badge-less punch must not record attendance'
        );
    }

    /** With the badge, the same live face goes through. */
    public function test_the_badge_path_still_works_when_it_is_required(): void
    {
        config(['face.require_qr' => true]);

        $this->enrol($this->alice, 306);

        $this->livePunch(306, 'in', [
            'mode' => 'qr',
            'qr'   => shortEncrypt($this->alice->emp_ID),
        ])->assertOk()->assertJsonPath('recorded', true);

        $this->assertNotNull($this->todayFor($this->alice));
    }

    public function test_qr_plus_a_live_matching_face_clocks_in(): void
    {
        $this->enrol($this->alice, 310);

        $this->livePunch(310, 'in', [
            'mode' => 'qr',
            'qr'   => shortEncrypt($this->alice->emp_ID),
        ])->assertOk()->assertJsonPath('employee.name', $this->employeeName($this->alice));

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
            ->assertJsonPath('employee.name', $this->employeeName($this->alice));

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
            ->assertSee('js/onnx/ort.wasm.min.js')
            ->assertSee('js/face-engine/face-engine.js')
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
