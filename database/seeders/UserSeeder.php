<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Back-office accounts (the "web" guard) used to sign in at /hr-admin.
 *
 * Password for both accounts: admin123
 * Change these immediately after the first sign-in.
 */
class UserSeeder extends Seeder
{
    public function run()
    {
        $password = Hash::make('admin123');

        $users = [
            [
                'username'  => 'admin',
                'fname'     => 'System',
                'mname'     => 'A',
                'lname'     => 'Administrator',
                'gender'    => 'Male',
                'role'      => 'Administrator',
            ],
            [
                'username'  => 'hradmin',
                'fname'     => 'HR',
                'mname'     => 'B',
                'lname'     => 'Administrator',
                'gender'    => 'Female',
                'role'      => 'HR Administrator',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                array_merge($user, ['password' => $password])
            );
        }
    }
}
