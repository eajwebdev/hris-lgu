<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Passwords on the back-office user page (/user).
 *
 * Two things were broken here, and both were silent. uCreate() hashed
 * request('password') into a local variable and then built its insert without
 * it, so an account created on this page was stored with no usable password and
 * simply could not sign in. uUpdate() validated a password field and then never
 * wrote it, so every password change an administrator made was discarded with a
 * "User updated successfully" message on top of it.
 *
 * Neither could have been noticed from the screen, which is why these assert on
 * Hash::check against the stored column rather than on the response.
 */
class UserPasswordManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'Administrator')->firstOrFail();
    }

    /** The full payload the form posts, so a test only states what it varies. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'fname'    => 'Test',
            'mname'    => 'Q',
            'lname'    => 'Person',
            'gender'   => 'Male',
            'role'     => 'HR Administrator',
            'username' => 'pw.usertest@mabinay.gov.ph',
            'password' => 'InitialPass1',
            'access'   => [],
        ], $overrides);
    }

    // ---------------------------------------------------------------- create

    public function test_a_created_user_gets_a_working_password(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('uCreate'), $this->payload())
            ->assertRedirect();

        $created = User::where('username', 'pw.usertest@mabinay.gov.ph')->first();

        $this->assertNotNull($created, 'the user should have been created');

        // The actual bug: this column used to be empty.
        $this->assertNotEmpty($created->password);
        $this->assertTrue(Hash::check('InitialPass1', $created->password));
    }

    /** Stored hashed, never in the clear. */
    public function test_the_created_password_is_not_stored_as_plain_text(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('uCreate'), $this->payload());

        $created = User::where('username', 'pw.usertest@mabinay.gov.ph')->firstOrFail();

        $this->assertNotSame('InitialPass1', $created->password);
    }

    public function test_a_user_cannot_be_created_without_a_password(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('uCreate'), $this->payload(['password' => '']))
            ->assertSessionHasErrors('password');

        $this->assertNull(User::where('username', 'pw.usertest@mabinay.gov.ph')->first());
    }

    public function test_a_weak_password_is_refused_on_create(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('uCreate'), $this->payload(['password' => 'abc']))
            ->assertSessionHasErrors('password');

        $this->assertNull(User::where('username', 'pw.usertest@mabinay.gov.ph')->first());
    }

    // ---------------------------------------------------------------- update

    public function test_an_administrator_can_change_a_users_password(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('uCreate'), $this->payload());

        $user = User::where('username', 'pw.usertest@mabinay.gov.ph')->firstOrFail();

        $this->post(route('uUpdate'), $this->payload([
            'uid'      => $user->id,
            'password' => 'ChangedPass2',
        ]))->assertRedirect();

        $user->refresh();

        $this->assertTrue(Hash::check('ChangedPass2', $user->password));
        $this->assertFalse(Hash::check('InitialPass1', $user->password));
    }

    /**
     * Blank means "leave it alone" — an administrator correcting a typo in
     * somebody's surname must not silently reissue their credentials.
     */
    public function test_a_blank_password_leaves_the_existing_one_untouched(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('uCreate'), $this->payload());

        $user = User::where('username', 'pw.usertest@mabinay.gov.ph')->firstOrFail();

        $this->post(route('uUpdate'), $this->payload([
            'uid'      => $user->id,
            'lname'    => 'Corrected',
            'password' => '',
        ]))->assertRedirect();

        $user->refresh();

        $this->assertSame('Corrected', $user->lname, 'the rest of the edit must still apply');
        $this->assertTrue(Hash::check('InitialPass1', $user->password));
    }

    /** Optional is not the same as unchecked. */
    public function test_a_weak_password_is_refused_on_update(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('uCreate'), $this->payload());

        $user = User::where('username', 'pw.usertest@mabinay.gov.ph')->firstOrFail();

        $this->post(route('uUpdate'), $this->payload([
            'uid'      => $user->id,
            'password' => 'short',
        ]))->assertSessionHasErrors('password');

        $user->refresh();

        $this->assertTrue(Hash::check('InitialPass1', $user->password));
    }

    // ------------------------------------------------------------- the form

    public function test_the_user_page_renders_a_password_field(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('ulist'))
            ->assertOk()
            ->assertSee('name="password"', false);
    }
}
