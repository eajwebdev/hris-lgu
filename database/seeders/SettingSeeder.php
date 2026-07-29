<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The single settings row. The leave workflow reads it on every filing and
 * approval (Setting::first()), so the system needs exactly one row.
 *
 * mayor / vice_mayor: either may approve a leave application, whoever is
 * available. hr: signs the leave application on behalf of the HR office.
 * All three hold employees.id values.
 *
 * They are deliberately NOT seeded. The personnel sheet EmployeeSeeder imports
 * carries no position or office column, so there is no way to tell from the
 * data who holds those posts — guessing would silently route every leave
 * application to the wrong person. HR assigns them under Settings after the
 * first sign-in, and the checks below never overwrite that choice on a re-run.
 */
class SettingSeeder extends Seeder
{
    public function run()
    {
        $setting = Setting::firstOrNew(['id' => 1]);

        if (! $setting->exists) {
            $setting->fill([
                'records_office_email' => 'records@mabinay.gov.ph',
                'job_portal_email'     => 'careers@mabinay.gov.ph',
                'maintenance'          => 0,
                'sync_backups'         => 0,
                'te_rstrct_lvl'        => 0,
            ]);
        }

        $setting->id = 1;
        $setting->save();

        $unassigned = array_keys(array_filter([
            'Mayor'      => ! $setting->mayor,
            'Vice Mayor' => ! $setting->vice_mayor,
            'HR'         => ! $setting->hr,
        ]));

        if ($unassigned) {
            $this->command?->warn(
                'Settings: no '.implode(' / ', $unassigned).' assigned yet. '.
                'Leave filing needs them — set them under Settings before staff file leave.'
            );
        }
    }
}
