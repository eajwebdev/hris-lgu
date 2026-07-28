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
        ]);

        $default = config('auth.default_password');

        $this->command->newLine();
        $this->command->info('Seeded. Employee accounts are held on the change-password screen until the default is replaced:');
        $this->command->table(
            ['Sign in at', 'Username / Email', 'Password', 'Role'],
            [
                ['/hr-admin', 'admin',                    'admin123', 'Administrator'],
                ['/hr-admin', 'hradmin',                  'admin123', 'HR Administrator'],
                ['/',         'mayor@mabinay.gov.ph',     $default,   'Mayor (approves leave)'],
                ['/',         'vicemayor@mabinay.gov.ph', $default,   'Vice Mayor (approves leave)'],
                ['/',         'hr@mabinay.gov.ph',        $default,   'HR head'],
                ['/',         'employee@mabinay.gov.ph',  $default,   'Employee'],
            ]
        );
    }
}
