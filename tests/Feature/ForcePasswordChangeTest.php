<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePasswordChanged;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The issued password is a placeholder, not a credential: everyone gets the
 * same one over the counter. An account still carrying it may sign in — else
 * the employee has no way to fix it — and may go nowhere else until it is
 * replaced.
 */
class ForcePasswordChangeTest extends TestCase
{
    use DatabaseTransactions;

    private string $default;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default  = (string) config('auth.default_password');
        $this->employee = Employee::orderBy('id')->firstOrFail();

        $this->employee->forceFill(['username' => 'pw.test@mabinay.gov.ph', 'stat_1' => 1])->save();
    }

    /**
     * Written straight to the table: Employee::boot() hashes on `creating`, so
     * a hash assigned to the model would be double-hashed on the way out.
     */
    private function setPassword(string $plain): void
    {
        DB::table('employees')
            ->where('id', $this->employee->id)
            ->update(['password' => Hash::make($plain)]);

        $this->employee->refresh();
    }

    private function signIn(string $password)
    {
        return $this->post(route('postLogin'), [
            'login'    => 'pw.test@mabinay.gov.ph',
            'password' => $password,
        ]);
    }

    // ---------------------------------------------------------------- detection

    public function test_the_issued_password_is_recognised(): void
    {
        $this->assertTrue(EnsurePasswordChanged::isDefault(Hash::make($this->default)));
        $this->assertFalse(EnsurePasswordChanged::isDefault(Hash::make('something-else-entirely')));
    }

    /** An account with no password set at all has nothing worth keeping. */
    public function test_a_blank_password_counts_as_default(): void
    {
        $this->assertTrue(EnsurePasswordChanged::isDefault(null));
        $this->assertTrue(EnsurePasswordChanged::isDefault(''));
    }

    // ---------------------------------------------------------------- the hold

    public function test_signing_in_with_the_issued_password_lands_on_the_change_screen(): void
    {
        $this->setPassword($this->default);

        $this->signIn($this->default)->assertRedirect(route('password.change'));

        $this->assertTrue(auth()->guard('employee')->check());
        $this->assertTrue(session(EnsurePasswordChanged::SESSION_KEY));
    }

    public function test_a_held_account_cannot_reach_the_rest_of_the_system(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->get(route('dashboard'))->assertRedirect(route('password.change'));
        $this->get(route('PDS', $this->employee->id))->assertRedirect(route('password.change'));
    }

    public function test_a_held_account_can_still_reach_the_change_screen_and_sign_out(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->get(route('password.change'))
            ->assertOk()
            ->assertSee('Set a new password');

        $this->post(route('logout'))->assertRedirect();
    }

    /** An AJAX caller cannot follow a redirect into HTML, so it gets JSON. */
    public function test_a_held_ajax_request_is_answered_with_json(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->getJson(route('dashboard'))
            ->assertStatus(423)
            ->assertJsonPath('status', 423);
    }

    public function test_a_normal_password_is_not_held(): void
    {
        $this->setPassword('MyOwnPassword1');

        $this->signIn('MyOwnPassword1')->assertRedirect(route('dashboard'));

        $this->assertNull(session(EnsurePasswordChanged::SESSION_KEY));
        $this->get(route('dashboard'))->assertOk();
    }

    // ---------------------------------------------------------------- changing it

    public function test_changing_the_password_lifts_the_hold(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->post(route('password.change.update'), [
            'password'              => 'BrandNewPass1',
            'password_confirmation' => 'BrandNewPass1',
        ])->assertRedirect(route('dashboard'));

        $this->assertNull(session(EnsurePasswordChanged::SESSION_KEY));

        $this->employee->refresh();
        $this->assertTrue(Hash::check('BrandNewPass1', $this->employee->password));

        // And the new password is what actually signs them in next time.
        $this->post(route('logout'));
        $this->signIn('BrandNewPass1')->assertRedirect(route('dashboard'));
    }

    /** Setting it straight back to the issued one would defeat the whole thing. */
    public function test_the_issued_password_cannot_be_reused_as_the_new_one(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->post(route('password.change.update'), [
            'password'              => $this->default,
            'password_confirmation' => $this->default,
        ])->assertSessionHasErrors('password');

        $this->employee->refresh();
        $this->assertTrue(Hash::check($this->default, $this->employee->password));
    }

    /**
     * The current password is no longer asked for, and sending one anyway must
     * not change the outcome.
     *
     * This asserted the opposite until the field was dropped from the screen.
     * For the case this flow exists to serve it proved nothing: the account is
     * still on the password HR issued, and every account is issued the same
     * one. Retyping a shared secret demonstrated no knowledge an attacker
     * lacked, while costing every employee a field to mistype on a phone.
     *
     * A stray value is simply ignored rather than rejected, so a cached form or
     * a password manager filling the old field cannot lock anyone out.
     */
    public function test_the_current_password_is_no_longer_required(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        // Omitted entirely.
        $this->post(route('password.change.update'), [
            'password'              => 'BrandNewPass1',
            'password_confirmation' => 'BrandNewPass1',
        ])->assertRedirect(route('dashboard'));

        $this->employee->refresh();
        $this->assertTrue(Hash::check('BrandNewPass1', $this->employee->password));
    }

    public function test_a_stray_current_password_value_is_ignored(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->post(route('password.change.update'), [
            'current_password'      => 'not-my-password',
            'password'              => 'BrandNewPass1',
            'password_confirmation' => 'BrandNewPass1',
        ])->assertRedirect(route('dashboard'));

        $this->employee->refresh();
        $this->assertTrue(Hash::check('BrandNewPass1', $this->employee->password));
    }

    public function test_the_two_new_passwords_must_match(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->post(route('password.change.update'), [
            'password'              => 'BrandNewPass1',
            'password_confirmation' => 'DifferentPass1',
        ])->assertSessionHasErrors('password');
    }

    public function test_a_short_password_is_refused(): void
    {
        $this->setPassword($this->default);
        $this->signIn($this->default);

        $this->post(route('password.change.update'), [
            'password'              => 'ab1',
            'password_confirmation' => 'ab1',
        ])->assertSessionHasErrors('password');
    }

    // ---------------------------------------------------------------- admins

    public function test_an_admin_on_the_issued_password_is_held_too(): void
    {
        $admin = User::whereNotNull('username')->firstOrFail();

        DB::table('users')->where('id', $admin->id)->update(['password' => Hash::make($this->default)]);

        $this->post(route('postLogin'), ['login' => $admin->username, 'password' => $this->default])
            ->assertRedirect(route('password.change'));

        $this->assertTrue(auth()->guard('web')->check());
        $this->get(route('dashboard'))->assertRedirect(route('password.change'));
    }

    // ---------------------------------------------------------------- issuing

    /** What HR's add-employee form hands out is the configured default. */
    public function test_a_new_employee_is_issued_the_default_password(): void
    {
        $admin = User::where('role', 'Administrator')->firstOrFail();

        $this->actingAs($admin, 'web')->post(route('empCreate'), [
            'emp_ID' => '2026-PWTEST',
            'lname'  => 'Testcase',
            'fname'  => 'Password',
            'sex'    => 'Male',
        ])->assertRedirect();

        $created = Employee::where('emp_ID', '2026-PWTEST')->firstOrFail();

        $this->assertTrue(Hash::check($this->default, $created->password));
        $this->assertTrue(EnsurePasswordChanged::isDefault($created->password));
    }
}
