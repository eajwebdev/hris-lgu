<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Google sign-in.
 *
 * Google itself is mocked: the real flow needs a browser, a Google account and
 * live OAuth credentials, none of which belong in a test run. What is worth
 * pinning down is everything on THIS side of the callback — which accounts are
 * matched, which are refused, and what happens when the credentials are absent.
 */
class GoogleAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The .env ships these empty; every test that expects the flow to run
        // has to say so explicitly.
        config([
            'services.google.client_id'     => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect'      => 'http://localhost/auth/google/callback',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /** Pretend Google authenticated somebody and sent them back to us. */
    private function googleReturns(string $email): void
    {
        $socialite = (new SocialiteUser())->map(['email' => $email]);

        Socialite::shouldReceive('driver->user')->andReturn($socialite);
    }

    // ---------------------------------------------------------------- config

    public function test_the_sign_in_page_offers_google(): void
    {
        $this->get(route('getLogin'))
            ->assertOk()
            ->assertSee(route('google.login'));
    }

    /**
     * The keys are empty in .env until somebody fills them in. Until then the
     * button must fail inside the app, not dump the employee on a Google error
     * page they cannot get back from.
     */
    public function test_an_unconfigured_google_login_is_refused_gracefully(): void
    {
        config(['services.google.client_id' => '', 'services.google.client_secret' => '']);

        $this->get(route('google.login'))
            ->assertRedirect(route('getLogin'))
            ->assertSessionHas('error');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('getLogin'))
            ->assertSessionHas('error');
    }

    public function test_a_configured_google_login_redirects_to_google(): void
    {
        $response = $this->get(route('google.login'));

        $response->assertRedirectContains('accounts.google.com');
    }

    // ---------------------------------------------------------------- matching

    public function test_an_admin_is_signed_in_by_their_username(): void
    {
        $admin = User::whereNotNull('username')->firstOrFail();

        $this->googleReturns($admin->username);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertTrue(auth()->guard('web')->check());
    }

    public function test_an_employee_is_signed_in_by_their_username(): void
    {
        $employee = Employee::orderBy('id')->firstOrFail();
        $employee->forceFill([
            'username' => 'by.username@mabinay.gov.ph',
            'stat_1'   => 1,
            // Their own password, so the change-password hold (covered in
            // ForcePasswordChangeTest) does not stand in for the landing page.
            'password' => Hash::make('TheirOwnPassword1'),
        ])->save();

        $this->googleReturns('by.username@mabinay.gov.ph');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertTrue(auth()->guard('employee')->check());
    }

    /**
     * The case the password form already handled and this one did not: HR's add
     * form writes the emp_ID into `username` and the address into `org_email`,
     * so every employee created that way was refused by Google sign-in.
     */
    public function test_an_employee_is_signed_in_by_their_org_email(): void
    {
        $employee = Employee::orderBy('id')->firstOrFail();
        $employee->forceFill([
            'username'  => '2026-9999',                  // as empCreate() writes it
            'org_email' => 'by.orgemail@mabinay.gov.ph',
            'stat_1'    => 1,
            'password'  => Hash::make('TheirOwnPassword1'),
        ])->save();

        $this->googleReturns('by.orgemail@mabinay.gov.ph');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertTrue(auth()->guard('employee')->check());
    }

    /**
     * Google vouches for who they are, not for the password on the record.
     * "Continue with Google" must not become a way round the change-password
     * hold that the password form enforces.
     */
    public function test_google_is_not_a_way_around_the_password_hold(): void
    {
        $employee = Employee::orderBy('id')->firstOrFail();
        $employee->forceFill([
            'username' => 'still.default@mabinay.gov.ph',
            'stat_1'   => 1,
            'password' => Hash::make(config('auth.default_password')),
        ])->save();

        $this->googleReturns('still.default@mabinay.gov.ph');

        $this->get('/auth/google/callback')->assertRedirect(route('password.change'));

        $this->assertTrue(auth()->guard('employee')->check());
        $this->get(route('dashboard'))->assertRedirect(route('password.change'));
    }

    // ---------------------------------------------------------------- refusals

    public function test_an_unknown_email_is_refused(): void
    {
        $this->googleReturns('not-an-employee@gmail.com');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('getLogin'))
            ->assertSessionHas('error');

        $this->assertFalse(auth()->guard('web')->check());
        $this->assertFalse(auth()->guard('employee')->check());
    }

    /** A suspended employee must not get in through the side door. */
    public function test_a_suspended_employee_is_refused(): void
    {
        $employee = Employee::orderBy('id')->firstOrFail();
        $employee->forceFill(['username' => 'suspended@mabinay.gov.ph', 'stat_1' => 0])->save();

        $this->googleReturns('suspended@mabinay.gov.ph');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('getLogin'))
            ->assertSessionHas('error');

        $this->assertFalse(auth()->guard('employee')->check());
    }

    public function test_a_google_account_with_no_email_is_refused(): void
    {
        $this->googleReturns('');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('getLogin'))
            ->assertSessionHas('error');

        $this->assertFalse(auth()->guard('employee')->check());
    }
}
