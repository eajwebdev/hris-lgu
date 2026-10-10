<?php

namespace Database\Seeders;

use App\Models\AttendanceStation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceStationSeeder extends Seeder
{
    public function run(): void
    {
        // Approximate site centres from OpenStreetMap. HR can refine the pins
        // and radius in Face Attendance; repeat runs preserve those edits.
        // https://mapcarta.com/W246344254
        // https://mapcarta.com/W57587240
        DB::transaction(function () {
            foreach ([
                ['name' => 'Mabinay Municipal Hall', 'lat' => 9.73568, 'lng' => 122.92667],
                ['name' => 'Bacolod City Government Center', 'lat' => 10.65881, 'lng' => 122.96675],
            ] as $site) {
                $station = AttendanceStation::firstOrCreate(['name' => $site['name']], [
                    'lat' => $site['lat'], 'lng' => $site['lng'], 'radius_m' => 200, 'active' => true,
                ]);
                $this->command?->line($station->name . ': ' . $station->lat . ', ' . $station->lng
                    . ' (' . $station->radius_m . 'm, ' . ($station->active ? 'active' : 'inactive') . ')');
            }
        });
    }
}
