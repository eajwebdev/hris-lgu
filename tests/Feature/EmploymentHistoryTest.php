<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmploymentHistory;
use App\Models\Office;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Employment history: an employee who stops working one year and is hired
 * again the next, in a different position, keeps one record with two
 * periods of service.
 */
class EmploymentHistoryTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Office $firstOffice;
    private Office $secondOffice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'Administrator')->firstOrFail();
        [$this->firstOffice, $this->secondOffice] = Office::orderBy('id')->take(2)->get()->all();
    }

    private function employee(): Employee
    {
        $employee = Employee::create([
            'fname' => 'JUAN', 'lname' => 'TESTCRUZ', 'emp_ID' => 'EH-' . uniqid(), 'username' => 'eh-' . uniqid(),
            'password' => 'secret', 'position' => 'Utility Worker I', 'emp_dept' => $this->firstOffice->id,
            'emp_status' => 6, 'item_no' => 'UW-1', 'date_hired' => '2023-06-15', 'stat_1' => 1,
        ]);

        // The PDS rows EmployeeController::empCreate makes beside every new
        // employee, which the page's side panel reads.
        foreach (['FamilyBg', 'EducBg', 'OtherInfo', 'InfoQuestion', 'PdsReference', 'GovId', 'OfficialTime'] as $model) {
            ("App\\Models\\{$model}")::create(['empid' => $employee->emp_ID]);
        }

        return $employee;
    }

    public function test_a_new_employee_starts_with_an_open_period_from_their_record(): void
    {
        $employee = $this->employee();

        $period = $employee->currentEmployment;
        $this->assertNotNull($period);
        $this->assertSame('Utility Worker I', $period->position);
        $this->assertSame($this->firstOffice->id, (int) $period->office_id);
        $this->assertSame('2023-06-15', $period->date_start->toDateString());
        $this->assertFalse($employee->isSeparated());
    }

    public function test_editing_the_record_corrects_the_open_period(): void
    {
        $employee = $this->employee();

        $employee->update(['position' => 'Utility Worker II']);

        $this->assertSame('Utility Worker II', $employee->fresh()->currentEmployment->position);
        $this->assertSame(1, $employee->employmentHistory()->count());
    }

    public function test_separated_this_year_and_rehired_next_year_in_a_different_position(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin, 'web')
            ->post(route('employeeSeparate', $employee->id), [
                'form' => 'separate', 'date_end' => '2026-11-30', 'separation_reason' => 'End of contract / term', 'remarks' => 'Contract not renewed',
            ])->assertRedirect()->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertTrue($employee->isSeparated());
        $this->assertSame(2, (int) $employee->stat_1, 'separation turns the sign-in off');

        // Turning the account back on by itself is refused.
        $this->actingAs($this->admin, 'web')
            ->post(route('toggleAcctStat'), ['id' => $employee->id, 'stat_1' => 1])
            ->assertJson(['success' => false]);
        $this->assertSame(2, (int) $employee->fresh()->stat_1);

        $this->actingAs($this->admin, 'web')
            ->post(route('employeeRehire', $employee->id), [
                'form' => 'rehire', 'date_hired' => '2027-02-01', 'position' => 'Administrative Aide IV',
                'emp_dept' => $this->secondOffice->id, 'emp_status' => 2, 'item_no' => 'AA4-7',
            ])->assertRedirect()->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertFalse($employee->isSeparated());
        $this->assertSame(1, (int) $employee->stat_1, 'rehire turns the sign-in on');
        $this->assertSame('Administrative Aide IV', $employee->position);
        $this->assertSame($this->secondOffice->id, (int) $employee->emp_dept);
        $this->assertSame('2027-02-01', (string) $employee->date_hired);

        $periods = $employee->employmentHistory()->get();
        $this->assertCount(2, $periods);

        [$now, $before] = [$periods[0], $periods[1]];
        $this->assertNull($now->date_end);
        $this->assertSame('Administrative Aide IV', $now->position);
        $this->assertSame('2027-02-01', $now->date_start->toDateString());

        $this->assertSame('Utility Worker I', $before->position, 'the old position is kept');
        $this->assertSame($this->firstOffice->id, (int) $before->office_id);
        $this->assertSame('2026-11-30', $before->date_end->toDateString());
        $this->assertSame('End of contract / term', $before->separation_reason);

        // The page shows both periods and the gap between them.
        $this->actingAs($this->admin, 'web')->get(route('employmentHistory', $employee->id))
            ->assertOk()
            ->assertSee('Administrative Aide IV')
            ->assertSee('Utility Worker I')
            ->assertSee('Separated: End of contract / term')
            ->assertSee('Out of service for 2 months');
    }

    public function test_a_closed_period_is_never_rewritten_by_a_later_edit(): void
    {
        $employee = $this->employee();

        // Read the open period, so the model holds it loaded, then close it
        // and open the next one behind the model's back.
        $first = $employee->currentEmployment;
        $first->update(['date_end' => '2026-11-30', 'separation_reason' => 'Resignation']);
        $employee->employmentHistory()->create(['position' => 'Clerk', 'date_start' => '2027-02-01']);

        $employee->update(['position' => 'Clerk II', 'date_hired' => '2027-02-01']);

        $this->assertSame('Utility Worker I', $first->fresh()->position);
        $this->assertSame('2023-06-15', $first->fresh()->date_start->toDateString());
        $this->assertSame('Clerk II', $employee->currentEmployment()->first()->position);
    }

    public function test_a_rehire_cannot_start_before_the_last_separation(): void
    {
        $employee = $this->employee();
        $this->actingAs($this->admin, 'web')->post(route('employeeSeparate', $employee->id), [
            'date_end' => '2026-11-30', 'separation_reason' => 'Resignation',
        ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('employeeRehire', $employee->id), [
                'date_hired' => '2026-10-01', 'position' => 'Clerk', 'emp_dept' => $this->firstOffice->id, 'emp_status' => 1,
            ])->assertSessionHasErrors('date_hired');

        $this->assertTrue($employee->fresh()->isSeparated());
    }

    public function test_an_employed_person_cannot_be_rehired_and_a_separated_one_cannot_be_separated_again(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin, 'web')->post(route('employeeRehire', $employee->id), [
            'date_hired' => '2027-01-01', 'position' => 'Clerk', 'emp_dept' => $this->firstOffice->id, 'emp_status' => 1,
        ])->assertSessionHas('error');
        $this->assertSame(1, $employee->employmentHistory()->count());

        $this->actingAs($this->admin, 'web')->post(route('employeeSeparate', $employee->id), ['date_end' => '2026-11-30', 'separation_reason' => 'Retirement']);
        $this->actingAs($this->admin, 'web')->post(route('employeeSeparate', $employee->id), ['date_end' => '2026-12-30', 'separation_reason' => 'Retirement'])
            ->assertSessionHas('error');
        $this->assertSame('2026-11-30', $employee->employmentHistory()->first()->date_end->toDateString());
    }

    public function test_employees_cannot_open_or_change_the_history(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee, 'employee')->get(route('employmentHistory', $employee->id))->assertForbidden();
        $this->actingAs($employee, 'employee')->post(route('employeeSeparate', $employee->id), [
            'date_end' => '2026-11-30', 'separation_reason' => 'Resignation',
        ])->assertForbidden();

        $this->assertFalse($employee->fresh()->isSeparated());
    }
}
