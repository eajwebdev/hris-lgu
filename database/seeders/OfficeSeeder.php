<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

/**
 * Standard offices of a Philippine municipal LGU.
 *
 * Ids 1 and 2 are reserved: the Office model rewrites their abbreviation to
 * "All Office" / "All Employee", so they are used as broadcast targets rather
 * than real offices. Real offices therefore start at id 3.
 */
class OfficeSeeder extends Seeder
{
    public function run()
    {
        $offices = [
            1  => ['ALL OFFICES', 'ALL'],
            2  => ['ALL EMPLOYEES', 'ALL EMP'],
            3  => ["Office of the Mayor", 'OM'],
            4  => ["Office of the Vice Mayor", 'OVM'],
            5  => ['Sangguniang Bayan', 'SB'],
            6  => ['Human Resource Management Office', 'HRMO'],
            7  => ['Municipal Accounting Office', 'MACCO'],
            8  => ['Municipal Budget Office', 'MBO'],
            9  => ['Municipal Treasurer Office', 'MTO'],
            10 => ['Municipal Assessor Office', 'MASSO'],
            11 => ['Municipal Planning and Development Office', 'MPDO'],
            12 => ['Municipal Engineering Office', 'MEO'],
            13 => ['Municipal Health Office', 'MHO'],
            14 => ['Municipal Social Welfare and Development Office', 'MSWDO'],
            15 => ['Municipal Agriculture Office', 'MAO'],
            16 => ['Municipal Civil Registrar', 'MCR'],
            17 => ['Municipal Disaster Risk Reduction and Management Office', 'MDRRMO'],
            18 => ['Municipal Environment and Natural Resources Office', 'MENRO'],
            19 => ['General Services Office', 'GSO'],
            20 => ['Management Information System Office', 'MIS'],
        ];

        foreach ($offices as $id => [$name, $abbr]) {
            Office::updateOrCreate(
                ['id' => $id],
                [
                    'office_name'    => $name,
                    'office_abbr'    => $abbr,
                    'office_head_id' => null,
                    'oic_id'         => null,
                    'group_by'       => 0,
                ]
            );
        }
    }
}
