<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The single settings row. The leave workflow reads it on every filing and
 * approval (Setting::first()), so the system needs exactly one row with the
 * approving officials wired up.
 *
 * mayor / vice_mayor: either may approve a leave application, whoever is
 * available. hr: signs the leave application on behalf of the HR office.
 * All three hold employees.id values.
 */
class SettingSeeder extends Seeder
{
    public function run()
    {
        $id = fn (string $empId) => Employee::where('emp_ID', $empId)->value('id');

        Setting::updateOrCreate(
            ['id' => 1],
            [
                'mayor'                => $id('2026-0001'),
                'vice_mayor'           => $id('2026-0002'),
                'hr'                   => $id('2026-0003'),
                'records_office_email' => 'records@mabinay.gov.ph',
                'job_portal_email'     => 'careers@mabinay.gov.ph',
                'maintenance'          => 0,
                'sync_backups'         => 0,
                'te_rstrct_lvl'        => 0,
            ]
        );
    }
}
