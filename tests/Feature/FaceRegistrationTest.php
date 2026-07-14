<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeFaceVector;
use App\Models\FaceAuditLog;
use App\Models\User;
use App\Services\FaceEmbeddingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * DatabaseTransactions, not RefreshDatabase: this suite is configured to run
 * against the working MySQL database, and RefreshDatabase would drop it.
 */
class FaceRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    private Employee $employee;
    private Employee $other;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->employee, $this->other] = Employee::orderBy('id')->take(2)->get()->all();
    }

    // ---------------------------------------------------------------- helpers

    /** A deterministic pseudo-descriptor standing in for one person's face. */
    private function face(int $seed, float $noise = 0.0): array
    {
        mt_srand($seed);

        $vector = [];

        for ($i = 0; $i < 128; $i++) {
            $vector[] = (mt_rand() / mt_getrandmax()) - 0.5;
        }

        if ($noise > 0.0) {
            // A different capture of the *same* face: same vector, jittered.
            mt_srand($seed + 9999);

            for ($i = 0; $i < 128; $i++) {
                $vector[$i] += ((mt_rand() / mt_getrandmax()) - 0.5) * $noise;
            }
        }

        return $vector;
    }

    /** Four poses of the same person. */
    private function captures(int $seed): array
    {
        return [
            ['type' => 'front',    'embedding' => $this->face($seed, 0.02)],
            ['type' => 'left',     'embedding' => $this->face($seed, 0.05)],
            ['type' => 'right',    'embedding' => $this->face($seed, 0.05)],
            ['type' => 'movement', 'embedding' => $this->face($seed, 0.03)],
        ];
    }

    private function admin(): User
    {
        return User::where('role', 'Administrator')->firstOrFail();
    }

    // ---------------------------------------------------------------- access

    /**
     * A signed-out visitor is bounced to the login screen, not shown a 403 —
     * login_auth wraps every route in the app and answers first. 403 is reserved
     * for someone who *is* signed in and still may not do this.
     */
    public function test_guests_are_sent_to_login(): void
    {
        $this->post(route('faceRegister', $this->employee->id), ['captures' => $this->captures(1)])
            ->assertRedirect(route('getLogin'));

        $this->assertNull($this->employee->fresh()->face_embeddings);
    }

    public function test_payroll_role_is_forbidden_even_by_direct_url(): void
    {
        $payroll = User::factory()->make(['role' => 'Payroll Administrator']);
        $payroll->id = 999999;

        $this->actingAs($payroll, 'web');

        $this->getJson(route('faceStatus', $this->employee->id))->assertStatus(403);
        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(1)])->assertStatus(403);
        $this->deleteJson(route('faceRemove', $this->employee->id))->assertStatus(403);
    }

    public function test_hr_administrator_is_allowed(): void
    {
        $hr = User::where('role', 'HR Administrator')->firstOrFail();

        $this->actingAs($hr, 'web')
            ->getJson(route('faceStatus', $this->employee->id))
            ->assertOk();
    }

    // ---------------------------------------------------------------- register

    public function test_it_registers_four_captures_and_derives_a_master_embedding(): void
    {
        $this->actingAs($this->admin(), 'web')
            ->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(11)])
            ->assertOk()
            ->assertJsonPath('face.registered', true)
            ->assertJsonPath('face.capture_count', 4);

        $stored = $this->employee->fresh()->face_embeddings;

        // The shape the spec asks for.
        $this->assertSame(['front', 'left', 'right', 'movement'], array_column($stored['captures'], 'type'));
        $this->assertCount(128, $stored['master_embedding']);
        $this->assertNotEmpty($stored['registered_at']);
        $this->assertSame($this->admin()->id, $stored['registered_by']);

        // No image data, under any key, anywhere in the row.
        $this->assertStringNotContainsString('data:image', json_encode($stored));

        $vector = EmployeeFaceVector::where('employee_id', $this->employee->id)->firstOrFail();

        $this->assertSame(128, $vector->embedding_dimension);
        $this->assertCount(128, $vector->master_embedding);

        // Stored on the unit sphere, which is what lets one distance threshold
        // hold everywhere.
        $norm = 0.0;
        foreach ($vector->master_embedding as $component) {
            $norm += $component * $component;
        }
        $this->assertEqualsWithDelta(1.0, sqrt($norm), 1e-6);

        $this->assertDatabaseHas('face_audit_logs', [
            'employee_id'  => $this->employee->id,
            'action'       => FaceAuditLog::REGISTERED,
            'performed_by' => $this->admin()->id,
        ]);
    }

    public function test_it_rejects_a_face_already_registered_to_another_employee(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(21)])
            ->assertOk();

        // Same person, different captures, presented as somebody else.
        $this->postJson(route('faceRegister', $this->other->id), ['captures' => $this->captures(21)])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This face is already registered to another employee.');

        $this->assertNull($this->other->fresh()->face_embeddings);
        $this->assertDatabaseMissing('employee_face_vectors', ['employee_id' => $this->other->id]);
    }

    public function test_a_different_face_registers_normally(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(31)])->assertOk();
        $this->postJson(route('faceRegister', $this->other->id), ['captures' => $this->captures(32)])->assertOk();
    }

    public function test_re_registering_the_same_employee_is_not_a_self_duplicate(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(41)])->assertOk();
        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(41)])->assertOk();
    }

    // ---------------------------------------------------------------- validation

    public function test_it_refuses_an_incomplete_capture_set(): void
    {
        $captures = $this->captures(51);
        array_pop($captures);

        $this->actingAs($this->admin(), 'web')
            ->postJson(route('faceRegister', $this->employee->id), ['captures' => $captures])
            ->assertStatus(422);

        $this->assertNull($this->employee->fresh()->face_embeddings);
    }

    public function test_it_refuses_four_captures_of_the_wrong_poses(): void
    {
        $captures = $this->captures(61);
        $captures[3]['type'] = 'front'; // two fronts, no movement

        $this->actingAs($this->admin(), 'web')
            ->postJson(route('faceRegister', $this->employee->id), ['captures' => $captures])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Every capture step must be completed exactly once.');
    }

    public function test_it_refuses_a_descriptor_of_the_wrong_length(): void
    {
        $captures = $this->captures(71);
        $captures[0]['embedding'] = array_slice($captures[0]['embedding'], 0, 64);

        $this->actingAs($this->admin(), 'web')
            ->postJson(route('faceRegister', $this->employee->id), ['captures' => $captures])
            ->assertStatus(422);
    }

    // ---------------------------------------------------------------- remove

    public function test_it_removes_face_data_and_audits_the_removal(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(81)])->assertOk();

        $this->deleteJson(route('faceRemove', $this->employee->id))
            ->assertOk()
            ->assertJsonPath('face.registered', false);

        $this->assertNull($this->employee->fresh()->face_embeddings);

        $this->assertDatabaseHas('employee_face_vectors', [
            'employee_id'      => $this->employee->id,
            'master_embedding' => null,
        ]);

        $this->assertDatabaseHas('face_audit_logs', [
            'employee_id' => $this->employee->id,
            'action'      => FaceAuditLog::REMOVED,
        ]);
    }

    public function test_a_removed_face_frees_the_identity_for_someone_else(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(91)])->assertOk();
        $this->deleteJson(route('faceRemove', $this->employee->id))->assertOk();

        // The vector is gone, so it can no longer trip the duplicate check.
        $this->postJson(route('faceRegister', $this->other->id), ['captures' => $this->captures(91)])->assertOk();
    }

    // ---------------------------------------------------------------- profile panel

    /**
     * Both status blocks are always in the DOM — the script flips `d-none` so a
     * registration updates the panel without a reload. So "is it registered" is
     * a question about which block is hidden, not which block exists.
     */
    public function test_the_profile_panel_renders_for_admin_and_tracks_state(): void
    {
        $this->actingAs($this->admin(), 'web');

        $before = $this->get(route('faceRecognition', $this->employee->id))
            ->assertOk()
            ->assertSee('FACE RECOGNITION REGISTRATION')
            ->assertSee('Register Face')
            ->assertSee('js/face-api/face-api.min.js')
            ->getContent();

        $this->assertStringContainsString('id="face-status-registered" class="d-none"', $before);
        $this->assertStringContainsString('id="face-status-unregistered" class=""', $before);

        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(111)])->assertOk();

        $after = $this->get(route('faceRecognition', $this->employee->id))
            ->assertOk()
            ->assertSee('Re-register Face')
            ->assertSee('Remove Face Data')
            ->getContent();

        $this->assertStringContainsString('id="face-status-registered" class=""', $after);
        $this->assertStringContainsString('id="face-status-unregistered" class="d-none"', $after);

        // The panel names who enrolled the face and when.
        $this->assertStringContainsString('registered capture(s)', $after);
        $this->assertMatchesRegularExpression('/id="face-capture-count">4</', $after);
    }

    /**
     * The panel ships its settings to the browser as JSON. If that block is ever
     * malformed the script dies silently on JSON.parse and the button does
     * nothing, so it is worth asserting it actually parses.
     */
    public function test_the_panel_emits_parseable_config_and_no_embeddings(): void
    {
        $this->actingAs($this->admin(), 'web');
        $this->postJson(route('faceRegister', $this->employee->id), ['captures' => $this->captures(121)])->assertOk();

        $html = $this->get(route('faceRecognition', $this->employee->id))->assertOk()->getContent();

        preg_match('/<script id="face-config" type="application\/json">(.*?)<\/script>/s', $html, $m);

        $this->assertNotEmpty($m, 'face-config block is missing from the page.');

        $config = json_decode(html_entity_decode($m[1]), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'face-config is not valid JSON.');
        $this->assertSame($this->employee->id, $config['employeeId']);
        $this->assertSame(['front', 'left', 'right', 'movement'], array_keys($config['steps']));
        $this->assertArrayHasKey('min_face_ratio', $config['thresholds']);

        // The rendered page must never carry the biometric itself. Checked against
        // the employee's real stored vector rather than a keyword, because the
        // word "descriptor" legitimately appears in the inline script.
        $stored = $this->employee->fresh()->face_embeddings;

        $this->assertArrayNotHasKey('master_embedding', $config);
        $this->assertArrayNotHasKey('captures', $config);

        foreach (array_slice($stored['master_embedding'], 0, 5) as $component) {
            $this->assertStringNotContainsString(
                (string) round($component, 8),
                $html,
                'A component of the master embedding was leaked into the page.'
            );
        }
    }

    /** The Face Recognition page is Admin/HR only, right down to the URL. */
    public function test_an_employee_cannot_open_the_face_recognition_page(): void
    {
        $this->actingAs($this->employee, 'employee');

        $this->get(route('faceRecognition', $this->employee->id))->assertStatus(403);
    }

    /**
     * Biometric enrolment is its own thing, not a field on the PDS. The panel and
     * its 1.3 MB of face-api must not ride along on Personal Information — which
     * every employee can open.
     */
    public function test_personal_information_no_longer_carries_the_face_panel(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->get(route('PDS', $this->employee->id))
            ->assertOk()
            ->assertSee('PERSONAL INFORMATION')
            ->assertDontSee('FACE RECOGNITION REGISTRATION')
            ->assertDontSee('js/face-api/face-api.min.js');
    }

    /** ...and it now has its own entry in the submenu, next to E-Signature. */
    public function test_the_submenu_links_to_the_face_recognition_page_for_admin(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->get(route('PDS', $this->employee->id))
            ->assertOk()
            ->assertSee('E-Signature')
            ->assertSee('Face Recognition')
            ->assertSee(route('faceRecognition', $this->employee->id));
    }

    // ---------------------------------------------------------------- legacy

    public function test_a_face_enrolled_on_the_retired_device_still_reads_as_registered(): void
    {
        $service = app(FaceEmbeddingService::class);

        $vectors = [$this->face(101, 0.02), $this->face(101, 0.04)];

        $summary = $service->summary(tap($this->employee)->forceFill([
            'face_embeddings' => [
                'vecs'     => $vectors,
                'centroid' => $service->masterEmbedding($vectors),
            ],
        ]));

        $this->assertTrue($summary['registered']);
        $this->assertTrue($summary['legacy']);
        $this->assertSame(2, $summary['capture_count']);
    }
}
