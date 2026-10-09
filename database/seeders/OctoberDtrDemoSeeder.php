<?php

namespace Database\Seeders;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Fdevice;
use App\Models\OfficialTime;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** October 2026 fixtures for testing both halves of the DTR. */
class OctoberDtrDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing']) && !config('app.demo')) {
            throw new \RuntimeException('DTR demo data requires a local, testing, or demo environment.');
        }

        DB::transaction(function () {
            $employees = Employee::where(function ($query) {
                $query->where('fname', 'Clyde')->where('lname', 'Abendan');
            })->orWhereIn('emp_ID', ['DEMO-RPT-001', 'DEMO-RPT-002', 'DEMO-RPT-003', 'DEMO-RPT-004'])->get();
            $device = Fdevice::firstOrCreate(['device_id' => 'DEMO-OCTOBER-DTR'], ['label' => 'Demo October DTR']);

            foreach ($employees as $employee) {
                $hours = OfficialTime::firstOrNew(['empid' => $employee->emp_ID]);
                foreach (['mon', 'tue', 'wed', 'thu', 'fri'] as $weekday) {
                    foreach (['morn' => '08:00-12:00', 'aft' => '13:00-17:00'] as $part => $range) {
                        $field = $part . '_' . $weekday;
                        if (!$hours->{$field}) {
                            $hours->{$field} = $range;
                        }
                    }
                }
                $hours->save();

                for ($date = Carbon::parse('2026-10-01'); $date->lte('2026-10-31'); $date->addDay()) {
                    if ($date->isWeekend() || ($employee->emp_ID === 'DEMO-RPT-001' && in_array($date->day, [14, 15]))) {
                        continue;
                    }
                    $late = $date->day % 6 === 1;
                    $overtime = $date->day % 6 === 3;
                    Dtr::firstOrCreate(['emp_ID' => $employee->emp_ID, 'date' => $date->toDateString()], [
                        'time_in' => $late ? '08:17:00,13:09:00' : '07:55:00,12:55:00',
                        'time_out' => '12:03:00,17:06:00',
                        'time_over' => $overtime ? '17:30:00,19:30:00' : '',
                        'device_id' => $device->id,
                        'device_id_in' => $device->id . ',' . $device->id,
                        'device_id_out' => $device->id . ',' . $device->id,
                        'device_id_over' => $overtime ? $device->id . ',' . $device->id : '',
                    ]);
                }
            }
            $this->command?->info('October 2026 DTR examples ready for both halves: ' . $employees->pluck('fname')->implode(', ') . '.');
        });
    }
}
