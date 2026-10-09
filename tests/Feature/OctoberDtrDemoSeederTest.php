<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\OfficialTime;
use Database\Seeders\OctoberDtrDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OctoberDtrDemoSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_october_seed_fills_both_halves_and_preserves_existing_attendance_and_hours(): void
    {
        $employee = Employee::where('fname', 'Clyde')->where('lname', 'Abendan')->firstOrFail();
        $hours = OfficialTime::firstOrNew(['empid' => $employee->emp_ID]);
        $hours->morn_mon = '07:00-11:00';
        $hours->aft_mon = null;
        $hours->save();
        $existing = Dtr::firstOrCreate(['emp_ID' => $employee->emp_ID, 'date' => '2026-10-02']);
        $existing->update(['time_in' => '07:42:00,12:42:00']);
        $otherMonths = Dtr::where('emp_ID', $employee->emp_ID)->whereNotBetween('date', ['2026-10-01', '2026-10-31'])->count();

        $this->seed(OctoberDtrDemoSeeder::class);
        $this->seed(OctoberDtrDemoSeeder::class);

        $rows = Dtr::where('emp_ID', $employee->emp_ID)->whereBetween('date', ['2026-10-01', '2026-10-31'])->get();
        $this->assertCount(22, $rows);
        $this->assertCount(11, $rows->where('date', '<=', '2026-10-15'));
        $this->assertCount(11, $rows->where('date', '>=', '2026-10-16'));
        $this->assertSame('07:42:00,12:42:00', $existing->fresh()->time_in);
        $this->assertSame('07:00-11:00', $hours->fresh()->morn_mon);
        $this->assertSame('13:00-17:00', $hours->fresh()->aft_mon);
        $this->assertSame($otherMonths, Dtr::where('emp_ID', $employee->emp_ID)->whereNotBetween('date', ['2026-10-01', '2026-10-31'])->count());
    }
}
