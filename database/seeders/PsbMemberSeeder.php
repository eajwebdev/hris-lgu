<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PsbMember;
use Illuminate\Database\Seeder;

/**
 * The Personnel Selection Board that signs the Comparative Assessment Form,
 * as named in "PSB Forms.docx".
 *
 * Seeded, not hardcoded: a board changes with the administration, and HR
 * maintains it under Settings. Each row is matched to an employee record by
 * surname where one exists, but the printed name is stored on the row so a
 * past assessment still prints correctly after a member leaves.
 */
class PsbMemberSeeder extends Seeder
{
    public function run(): void
    {
        $board = [
            ['name' => 'Ernie T. Uy',        'credentials' => 'RN, JD', 'role' => 'Chairperson', 'lname' => 'Uy'],
            ['name' => 'Elan N. Cadayday',   'credentials' => null,     'role' => 'Member',      'lname' => 'Cadayday'],
            ['name' => 'Lucrecia C. Nicolas','credentials' => null,     'role' => 'Member',      'lname' => 'Nicolas'],
            ['name' => 'Dindo M. Amorganda', 'credentials' => 'Ph.D.',  'role' => 'Member',      'lname' => 'Amorganda'],
            ['name' => 'Marjorie A. Abrio',  'credentials' => null,     'role' => 'Member',      'lname' => 'Abrio'],
            ['name' => 'Lelanie Malacapay',  'credentials' => null,     'role' => 'Member',      'lname' => 'Malacapay'],
        ];

        foreach ($board as $i => $member) {
            $employeeId = Employee::where('lname', $member['lname'])->value('id');

            PsbMember::updateOrCreate(
                ['name' => $member['name']],
                [
                    'credentials' => $member['credentials'],
                    'role'        => $member['role'],
                    'employee_id' => $employeeId,
                    'sort_order'  => $i,
                    'active'      => true,
                ]
            );
        }

        $this->command?->info('Personnel Selection Board seeded: '.count($board).' members.');
    }
}
