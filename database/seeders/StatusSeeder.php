<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

/**
 * Employment statuses for an LGU plantilla.
 *
 * 'Part-time/JO' must keep that exact spelling — the employee forms filter on
 * the literal string and pair it with a qualification instead.
 */
class StatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            'Permanent',
            'Casual',
            'Co-terminous',
            'Contractual',
            'Elective',
            'Job Order',
            'Part-time/JO',
        ];

        foreach ($statuses as $i => $status) {
            Status::updateOrCreate(
                ['id' => $i + 1],
                ['status_name' => $status]
            );
        }
    }
}
