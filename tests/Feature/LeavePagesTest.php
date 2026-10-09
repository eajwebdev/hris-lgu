<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeavePagesTest extends TestCase
{
    use DatabaseTransactions;

    private function application(Employee $employee, array $attributes = []): LeaveApplication
    {
        return LeaveApplication::create(array_merge([
            'transnum' => 'LEAVE-REGRESSION',
            'empid' => $employee->emp_ID,
            'leave_type' => '1',
            'leave_purpose' => 1,
            'leave_detail' => 'Local travel',
            'date_range' => '2026-10-12 to 2026-10-14',
            'date_filing' => '2026-10-01 09:00:00',
            'days' => 3,
            'emp_esign' => '2',
            'status' => '1',
            'history' => 1,
            'remarks_stat' => '0',
            'holiday' => 0,
            'day_wpay' => 0,
            'hr' => null,
            'supervisor' => null,
        ], $attributes));
    }

    public static function missingSignatories(): array
    {
        return [
            'no HR Head' => [true, false],
            'no supervisor' => [false, true],
            'neither signatory' => [true, true],
        ];
    }

    #[DataProvider('missingSignatories')]
    public function test_status_keeps_applications_with_missing_signatories(bool $missingHr, bool $missingSupervisor): void
    {
        $employee = Employee::findOrFail(4);
        $signatory = Employee::where('id', '!=', 4)->firstOrFail();
        Setting::query()->update(['hr' => $missingHr ? null : $signatory->id]);
        $leave = $this->application($employee, [
            'hr' => $missingHr ? null : $signatory->id,
            'supervisor' => $missingSupervisor ? null : $signatory->id,
        ]);

        $this->actingAs(User::where('role', 'Administrator')->firstOrFail(), 'web')
            ->get('/leave/status/4')
            ->assertOk()
            ->assertViewHas('leavesapp', fn ($rows) => $rows->contains('id', $leave->id))
            ->assertSee('LEAVE-REGRESSION');
    }

    public function test_history_for_employee_four_renders_without_signatories(): void
    {
        Setting::query()->update(['hr' => null, 'mayor' => null]);
        $leave = $this->application(Employee::findOrFail(4), ['history' => 2, 'status' => '4']);

        $response = $this->actingAs(User::where('role', 'Administrator')->firstOrFail(), 'web')
            ->get('/leave/history/4')
            ->assertOk()
            ->assertViewHas('leaveApplication', fn ($rows) => $rows->contains('id', $leave->id))
            ->assertSee('LEAVE-REGRESSION')
            ->assertSee('Approved');

        file_put_contents(sys_get_temp_dir() . '/hris-leave-history-tested.html', $response->getContent());
    }

    public function test_employee_status_renders_without_any_settings_row(): void
    {
        Setting::query()->delete();
        $employee = Employee::findOrFail(4);
        $leave = $this->application($employee);

        $this->actingAs($employee, 'employee')->get('/leave/status')
            ->assertOk()
            ->assertViewHas('leavesapp', fn ($rows) => $rows->contains('id', $leave->id))
            ->assertSee('LEAVE-REGRESSION');
    }

    public function test_employee_history_includes_applications_they_supervised(): void
    {
        $supervisor = Employee::findOrFail(4);
        $filer = Employee::where('id', '!=', 4)->firstOrFail();
        $leave = $this->application($filer, ['history' => 2, 'status' => '4', 'supervisor' => $supervisor->id]);

        $this->actingAs($supervisor, 'employee')->get('/leave/history')
            ->assertOk()
            ->assertViewHas('leaveApplication1', fn ($rows) => $rows->contains('id', $leave->id))
            ->assertSee('Signed as supervisor')
            ->assertSee($filer->lname);
    }
}
