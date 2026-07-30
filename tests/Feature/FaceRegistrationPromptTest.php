<?php

namespace Tests\Feature;

use App\Listeners\FlagMissingFaceRegistration;
use App\Models\Employee;
use App\Models\EmployeeFaceVector;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The one-time prompt that asks an employee with no face on file to enrol.
 *
 * An employee who never registers cannot use the attendance kiosk, and nothing
 * in the system told them so — the entry point was buried in a PDS submenu they
 * had no reason to open. This nudges them once per sign-in.
 *
 * "Once per sign-in" is the property worth protecting. A prompt on every page
 * load is one people learn to dismiss without reading, at which point it has
 * stopped doing anything except annoying them.
 *
 * DatabaseTransactions, not RefreshDatabase: this suite runs against the
 * working MySQL database and RefreshDatabase would drop it.
 */
class FaceRegistrationPromptTest extends TestCase
{
    use DatabaseTransactions;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = Employee::orderBy('id')->first();

        // Start from "not registered" whatever the working database happens to
        // hold, so the tests do not depend on the state of a real record.
        $this->clearFace();
    }

    private function clearFace(): void
    {
        EmployeeFaceVector::where('employee_id', $this->employee->id)->delete();
        $this->employee->forceFill(['face_embeddings' => null])->save();
        Cache::flush();
    }

    /**
     * Enrol through the real endpoint rather than hand-writing the stored shape.
     *
     * face_embeddings is a structured document — captures, a master embedding,
     * who registered it — and a test that fabricates it by hand is a test that
     * passes while the thing it stands for is malformed. Posting the same
     * payload the UI posts keeps this honest.
     */
    private function enrolFace(): void
    {
        $admin = User::where('role', 'Administrator')->firstOrFail();

        $captures = [];
        mt_srand(4242);

        foreach (['front', 'left', 'right', 'movement'] as $type) {
            $vector = [];
            for ($i = 0; $i < (int) config('face.dimension'); $i++) {
                $vector[] = (mt_rand() / mt_getrandmax()) - 0.5;
            }
            $captures[] = ['type' => $type, 'embedding' => $vector];
        }

        $this->actingAs($admin, 'web')
            ->postJson(route('faceRegister', $this->employee->id), ['captures' => $captures])
            ->assertOk();

        auth()->guard('web')->logout();

        $this->employee = $this->employee->fresh();
        Cache::flush();
    }

    /** Give the employee a password we know, so the real login form can be driven. */
    private function setPassword(string $plain): void
    {
        $this->employee->forceFill(['password' => Hash::make($plain)])->save();
        $this->employee = $this->employee->fresh();
    }

    /**
     * The flag is SET by actually signing in — not merely consumed correctly
     * once something else has set it.
     *
     * This is the half the first version of these tests missed. They seeded the
     * session by hand, so the listener could have been absent entirely and every
     * one of them would still have passed — which is exactly what happened: a
     * stale bootstrap/cache/events.php meant nothing was listening at all, and
     * only this test noticed.
     */
    public function test_signing_in_sets_the_flag(): void
    {
        $this->setPassword('prompt-test-secret');

        $this->post(route('postLogin'), [
            'login'    => $this->employee->username,
            'password' => 'prompt-test-secret',
        ]);

        $this->assertAuthenticated('employee');
        $this->assertTrue(
            session()->get(FlagMissingFaceRegistration::SESSION_KEY),
            'signing in did not flag an employee with no face on file',
        );
    }

    /** An already-enrolled employee signing in must not be flagged. */
    public function test_signing_in_with_a_face_on_file_sets_nothing(): void
    {
        $this->enrolFace();
        $this->setPassword('prompt-test-secret');

        $this->post(route('postLogin'), [
            'login'    => $this->employee->username,
            'password' => 'prompt-test-secret',
        ]);

        $this->assertAuthenticated('employee');
        $this->assertFalse(session()->has(FlagMissingFaceRegistration::SESSION_KEY));
    }

    /**
     * The listener hangs off the framework's Login event rather than off a
     * controller, because there is more than one door: the password form has an
     * afterLogin() and GoogleAuthController has its own separate copy of one.
     * Anything attached to a single controller is invisible to the other, and
     * Google sign-in is not a rare path — it is the button most people press.
     *
     * Both controllers authenticate through a guard, and every guard fires
     * Login, so covering the event covers both without either having to
     * remember.
     */
    public function test_the_listener_is_registered_for_the_login_event(): void
    {
        $raw = app('events')->getRawListeners()[Login::class] ?? [];

        $this->assertStringContainsString(
            'FlagMissingFaceRegistration',
            collect($raw)->map(fn ($l) => is_string($l) ? $l : 'closure')->implode(','),
            'nothing is listening for Login — check bootstrap/cache/events.php is not stale',
        );
    }

    public function test_an_unregistered_employee_is_prompted_after_login(): void
    {
        session([FlagMissingFaceRegistration::SESSION_KEY => true]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('facePromptModal')
            ->assertSee('Register your face');
    }

    /** The whole point of the session flag: it fires once, not on every visit. */
    public function test_the_prompt_does_not_reappear_on_the_next_visit(): void
    {
        session([FlagMissingFaceRegistration::SESSION_KEY => true]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('dashboard'))
            ->assertSee('facePromptModal');

        // Same session, second load — the flag was consumed by the first.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('facePromptModal');
    }

    /** Somebody who already has a face on file is never asked. */
    public function test_a_registered_employee_is_not_prompted(): void
    {
        $this->enrolFace();
        session([FlagMissingFaceRegistration::SESSION_KEY => true]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('facePromptModal');
    }

    /**
     * The flag is set at sign-in, before the employee can act on it. Somebody
     * who enrols and comes back to the dashboard in the same session must not
     * be asked again for a thing they have just done.
     */
    public function test_enrolling_mid_session_cancels_a_pending_prompt(): void
    {
        session([FlagMissingFaceRegistration::SESSION_KEY => true]);
        $this->enrolFace();

        $this->actingAs($this->employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('facePromptModal');
    }

    /** No flag, no prompt — an ordinary navigation to the dashboard is quiet. */
    public function test_no_prompt_without_the_login_flag(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('facePromptModal');
    }

    /**
     * Admin and HR enrol faces through the PDS, on whoever's record they are
     * working on. Prompting them about their own would be prompting them about
     * the wrong thing entirely.
     */
    public function test_an_admin_is_never_prompted(): void
    {
        $admin = User::where('role', 'Administrator')->first()
            ?? User::where('role', 'HR Administrator')->first();

        if (! $admin) {
            $this->markTestSkipped('no administrator account in the working database');
        }

        session([FlagMissingFaceRegistration::SESSION_KEY => true]);

        $this->actingAs($admin, 'web')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('facePromptModal');
    }

    /** The dashboard tile shows enrolment state at a glance, both ways round. */
    public function test_the_quick_action_reflects_registration_state(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('fa-times-circle text-danger', false);

        $this->enrolFace();

        // Re-authenticate with the reloaded record. actingAs pins one model
        // instance for the whole test, so without this the dashboard would read
        // the copy captured before enrolment — an artefact of the test harness,
        // not of the app, which loads the user afresh on every request.
        $this->actingAs($this->employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('fa-check-circle text-success', false);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
