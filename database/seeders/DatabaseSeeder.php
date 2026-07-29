<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Bootstraps a usable HRIS - LGU Mabinay database.
 *
 *   php artisan db:seed
 *
 * Every seeder is idempotent (updateOrCreate), so it is safe to re-run.
 * Order matters: employees need offices, and settings needs employees.
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            OfficeSeeder::class,
            StatusSeeder::class,
            QualificationSeeder::class,

            EmployeeSeeder::class,      // 2. people (needs offices)
            UserSeeder::class,

            SettingSeeder::class,       // 3. workflow wiring (needs employees)
            PsbMemberSeeder::class,     //    selection board (matches employees by surname)
        ]);

        $default = config('auth.default_password');

        $this->command->newLine();
        $this->command->info('Seeded.');
        $this->command->table(
            ['Sign in at', 'Username / Email', 'Password', 'Role'],
            [
                ['/hr-admin', 'admin',                       'admin123', 'Administrator'],
                ['/hr-admin', 'hradmin',                     'admin123', 'HR Administrator'],
                ['/',         "the employee's e-mail address", $default, 'Employee'],
            ]
        );
        $this->command->line(
            'Employees are the real personnel from "HRIS EMPLOYEES - REGULAR .pdf". Each new '.
            'account starts on the default password and is held on the change-password screen '.
            'until it is replaced. Assign the Mayor, Vice Mayor and HR signatory under Settings '.
            'before staff file leave.'
        );
    }
}
