<?php

namespace Tests\Feature;

use App\Models\AttendancePunchLog;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\LeaveApplication;
use App\Models\LeaveCredit;
use App\Models\OfficialTime;
use App\Models\Setting;
use Carbon\Carbon;
use Database\Seeders\TimeLeaveReportDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimeLeaveReportDemoSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seeder_is_repeatable_and_preserves_existing_report_edits_and_signatories(): void
    {
        $signatory = Employee::findOrFail(4);
        Setting::query()->update(['hr' => $signatory->id, 'mayor' => $signatory->id]);
        $this->seed(TimeLeaveReportDemoSeeder::class);
        $employee = Employee::where('emp_ID', 'DEMO-RPT-001')->firstOrFail();
        $employee->update(['vl' => 11.5]);
        $leave = LeaveApplication::where('transnum', 'like', 'DEMO-%-APPROVED-VL')->firstOrFail();
        $leave->update(['remarks_details' => 'Edited during demo testing']);
        $counts = $this->counts();

        $this->seed(TimeLeaveReportDemoSeeder::class);

        $this->assertSame($counts, $this->counts());
        $this->assertSame(11.5, (float) $employee->fresh()->vl);
        $this->assertSame('Edited during demo testing', $leave->fresh()->remarks_details);
        $this->assertSame($signatory->id, Setting::first()->hr);
        $this->assertSame($signatory->id, Setting::first()->mayor);
    }

    public function test_demo_data_covers_report_prerequisites_and_attendance_variants(): void
    {
        Setting::query()->update(['hr' => null, 'mayor' => null]);
        $this->seed(TimeLeaveReportDemoSeeder::class);
        $employee = Employee::where('emp_ID', 'DEMO-RPT-001')->firstOrFail();
        $this->assertNotNull($employee->supervisor);
        $this->assertNotNull(Setting::first()->hr);
        $this->assertNotNull(Setting::first()->mayor);
        $this->assertNotNull(OfficialTime::where('empid', $employee->emp_ID)->first());
        $this->assertGreaterThan(0, Dtr::where('emp_ID', $employee->emp_ID)->where('time_over', '!=', '')->count());
        $this->assertGreaterThan(0, AttendancePunchLog::where('emp_ID', 'like', 'DEMO-RPT-%')->where('out_of_range', true)->count());
        $this->assertGreaterThan(0, AttendancePunchLog::where('emp_ID', 'like', 'DEMO-RPT-%')->whereNull('lat')->count());
        $this->assertGreaterThan(0, EventLog::where('empid', 'like', 'DEMO-RPT-%')->whereNotNull('in')->whereNull('out')->count());
        $month = Carbon::today('Asia/Manila')->subMonthNoOverflow()->startOfMonth();
        $this->assertGreaterThan(0, Dtr::where('emp_ID', $employee->emp_ID)->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->count());
        foreach ([0, 2, 4] as $outcome) {
            $this->assertGreaterThan(0, LeaveApplication::where('empid', $employee->emp_ID)->where('history', 2)->where('remarks_stat', $outcome)->count());
        }
    }

    private function counts(): array
    {
        return [
            Employee::where('emp_ID', 'like', 'DEMO-RPT-%')->count(),
            Dtr::where('emp_ID', 'like', 'DEMO-RPT-%')->count(),
            AttendancePunchLog::where('emp_ID', 'like', 'DEMO-RPT-%')->count(),
            LeaveApplication::where('transnum', 'like', 'DEMO-%')->count(),
            LeaveCredit::where('empid', 'like', 'DEMO-RPT-%')->count(),
            Event::where('title', 'like', 'DEMO - %')->count(),
            EventLog::where('empid', 'like', 'DEMO-RPT-%')->count(),
        ];
    }
}
