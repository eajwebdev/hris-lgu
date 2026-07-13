<?php

namespace Database\Seeders;

use App\Models\Qualification;
use Illuminate\Database\Seeder;

/**
 * Qualifications are paired with the "Part-time/JO" employment status on the
 * employee form. Adjust the list to match the LGU's own classifications.
 */
class QualificationSeeder extends Seeder
{
    public function run()
    {
        $qualifications = [
            'Job Order',
            'Contract of Service',
            'Casual',
            'Consultant',
            'Volunteer',
        ];

        foreach ($qualifications as $i => $qualification) {
            Qualification::updateOrCreate(
                ['id' => $i + 1],
                ['qualification' => $qualification]
            );
        }
    }
}
