<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Back-office accounts (the "web" guard) used to sign in at /hr-admin.
 *
 * SIGN-IN IS BY USERNAME. The `users` table has no email column — see
 * LoginAuthController, which resolves an administrator with
 * User::where('username', $login). The Administrator's username is therefore
 * set to the address itself, which is why it can be typed in as an email and
 * still work. (Employees are the ones who may use either a username or an
 * org_email; administrators cannot.)
 *
 * CHANGE THE PASSWORDS AFTER THE FIRST SIGN-IN. They are defaults committed to
 * a git repository, so they are only ever a bootstrap, never a credential. Both
 * can be overridden without editing this file:
 *
 *     SEED_ADMIN_PASSWORD=...      SEED_HRADMIN_PASSWORD=...
 *
 * EnsurePasswordChanged holds any account still on config('auth.default_password')
 * at the change-password screen until it picks a new one — so if you want that
 * hold to catch these accounts too, point that config value at the same string.
 */
class UserSeeder extends Seeder
{
    public function run()
    {
        $adminPassword   = Hash::make(env('SEED_ADMIN_PASSWORD', 'hrisadmin@2026'));
        $hradminPassword = Hash::make(env('SEED_HRADMIN_PASSWORD', 'hrpassword@2026'));

        $users = [
            [
                'username'  => 'abriljredwin@gmail.com',
                'fname'     => 'System',
                'mname'     => 'A',
                'lname'     => 'Administrator',
                'gender'    => 'Male',
                'role'      => 'Administrator',
                'password'  => $adminPassword,
            ],
            [
                'username'  => 'hradmin',
                'fname'     => 'HR',
                'mname'     => 'B',
                'lname'     => 'Administrator',
                'gender'    => 'Female',
                'role'      => 'HR Administrator',
                'password'  => $hradminPassword,
            ],
        ];

        // Keyed on username, so re-running this reseeds the password of an
        // account that already exists rather than creating a duplicate.
        //
        // NOTE for an existing deployment: the Administrator username changed
        // from 'admin' to the address above, so on a database seeded before
        // this change updateOrCreate() finds no 'admin' row to match and
        // creates a SECOND administrator instead of renaming the first. Rename
        // or remove the old 'admin' row by hand if you do not want both.
        foreach ($users as $user) {
            User::updateOrCreate(['username' => $user['username']], $user);
        }
    }
}
