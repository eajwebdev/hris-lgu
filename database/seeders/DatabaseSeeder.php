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

        $this->command->newLine();
        $this->command->info('Seeded. Sign-in accounts — change these passwords:');
        $this->command->table(
            ['Sign in at', 'Username / Email', 'Password', 'Role'],
            [
                ['/hr-admin', 'admin',                    'admin123',    'Administrator'],
                ['/hr-admin', 'hradmin',                  'admin123',    'HR Administrator'],
                ['/',         'mayor@mabinay.gov.ph',     'password123', 'Mayor (approves leave)'],
                ['/',         'vicemayor@mabinay.gov.ph', 'password123', 'Vice Mayor (approves leave)'],
                ['/',         'hr@mabinay.gov.ph',        'password123', 'HR head'],
                ['/',         'employee@mabinay.gov.ph',  'password123', 'Employee'],
            ]
        );
    }
}
