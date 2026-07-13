<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Bootstrap personnel. The Mayor, Vice Mayor and HR head are required because
 * the leave workflow routes through them (see SettingSeeder).
 *
 * Every account below uses the password: password123
 * Change these immediately after the first sign-in.
 *
 * NOTE: Employee::boot() hashes the password on `creating`, so a password
 * passed through the model would be hashed twice on insert (and left in plain
 * text on update). The hash is therefore written straight to the table below,
 * which behaves the same whether the row is new or existing.
 */
class EmployeeSeeder extends Seeder
{
    public function run()
    {

        $people = [
            [
                'emp_ID'     => '2026-0001',
                'fname'      => 'Juan',
                'mname'      => 'Santos',
                'lname'      => 'Dela Cruz',
                'position'   => 'Municipal Mayor',
                'emp_dept'   => 3,            // Office of the Mayor
                'username'   => 'mayor@mabinay.gov.ph',
                'org_email'  => 'mayor@mabinay.gov.ph',
                'supervisor' => null,
            ],
            [
                'emp_ID'     => '2026-0002',
                'fname'      => 'Maria',
                'mname'      => 'Reyes',
                'lname'      => 'Bautista',
                'position'   => 'Municipal Vice Mayor',
                'emp_dept'   => 4,            // Office of the Vice Mayor
                'username'   => 'vicemayor@mabinay.gov.ph',
                'org_email'  => 'vicemayor@mabinay.gov.ph',
                'supervisor' => null,
            ],
            [
                'emp_ID'     => '2026-0003',
                'fname'      => 'Ana',
                'mname'      => 'Lim',
                'lname'      => 'Villanueva',
                'position'   => 'Human Resource Management Officer',
                'emp_dept'   => 6,            // HRMO
                'username'   => 'hr@mabinay.gov.ph',
                'org_email'  => 'hr@mabinay.gov.ph',
                'supervisor' => null,
            ],
            [
                'emp_ID'     => '2026-0004',
                'fname'      => 'Pedro',
                'mname'      => 'Cruz',
                'lname'      => 'Ramos',
                'position'   => 'Administrative Aide IV',
                'emp_dept'   => 6,            // HRMO
                'username'   => 'employee@mabinay.gov.ph',
                'org_email'  => 'employee@mabinay.gov.ph',
                'supervisor' => null,         // set below, once HR head has an id
            ],
        ];

        // The PDS page expects one row per section to already exist — the same
        // rows EmployeeController::empCreate() writes when HR adds an employee.
        $pdsSections = ['FamilyBg', 'EducBg', 'OtherInfo', 'InfoQuestion', 'PdsReference', 'GovId', 'OfficialTime'];

        foreach ($people as $person) {
            Employee::updateOrCreate(
                ['emp_ID' => $person['emp_ID']],
                array_merge($person, [
                    'role'        => 'employee',
                    'emp_status'  => 1,       // Permanent
                    'emp_salary'  => 0,
                    'stat_1'      => 1,       // active
                    'dpn'         => 0,       // data-privacy notice not yet accepted
                    'profile'     => 'default.png',
                    'vl'          => 15,
                    'sl'          => 15,
                ])
            );

            // Written directly, bypassing the model's creating() hook.
            DB::table('employees')
                ->where('emp_ID', $person['emp_ID'])
                ->update(['password' => Hash::make('password123')]);

            foreach ($pdsSections as $section) {
                $model = "App\\Models\\{$section}";
                $model::firstOrCreate(['empid' => $person['emp_ID']]);
            }
        }

        // The sample employee reports to the HR head.
        $hrHead = Employee::where('emp_ID', '2026-0003')->first();
        Employee::where('emp_ID', '2026-0004')->update(['supervisor' => $hrHead->id]);

        // Give each office a head so leave routing and office lists work.
        \App\Models\Office::where('id', 3)->update(['office_head_id' => Employee::where('emp_ID', '2026-0001')->value('id')]);
        \App\Models\Office::where('id', 4)->update(['office_head_id' => Employee::where('emp_ID', '2026-0002')->value('id')]);
        \App\Models\Office::where('id', 6)->update(['office_head_id' => $hrHead->id]);
    }
}
