<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * The whole leave application, through every person who touches it:
 * employee files -> HR checks the days -> employee e-signs -> HRMO approves
 * -> supervisor signs (credits deducted) -> Mayor or Vice Mayor approves.
 *
 * Also the ways out (refusals, returns, cancelling) and that nobody can act
 * at a stop that is not theirs.
 */
class LeaveWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private Employee $applicant;
    private Employee $supervisor;
    private Employee $hrHead;
    private Employee $mayor;
    private Employee $viceMayor;
    private Employee $stranger;
    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->applicant, $this->supervisor, $this->hrHead, $this->mayor, $this->viceMayor, $this->stranger]
            = Employee::where('emp_status', 1)->orderBy('id')->take(6)->get()->all();

        $esign = Crypt::encrypt($this->png());
        foreach ([$this->applicant, $this->supervisor, $this->hrHead, $this->mayor, $this->viceMayor, $this->stranger] as $person) {
            $person->esign = $esign;
            $person->supervisor = null;
            $person->save();
        }

        $this->applicant->supervisor = $this->supervisor->id;
        $this->applicant->vl = 10;
        $this->applicant->sl = 10;
        $this->applicant->save();

        // Elected officials do not earn leave, but they sign it.
        $this->mayor->emp_status = 5;
        $this->mayor->save();

        LeaveApplication::where('empid', $this->applicant->emp_ID)->delete();

        Setting::query()->update([
            'hr' => $this->hrHead->id,
            'mayor' => $this->mayor->id,
            'vice_mayor' => $this->viceMayor->id,
        ]);

        // Seeded HR account: no access flags were ever set.
        $this->hr = User::where('role', 'HR Administrator')->firstOrFail();
        $this->hr->access = null;
        $this->hr->save();
    }

    protected function tearDown(): void
    {
        foreach (LeaveApplication::whereNotNull('gen_app')->where('empid', $this->applicant->emp_ID ?? '')->pluck('gen_app') as $path) {
            @unlink(storage_path('app/public/' . $path));
        }

        parent::tearDown();
    }

    /**
     * One person at a time, as in a browser: actingAs() alone keeps the
     * previous guard signed in, and the app checks the web guard first.
     */
    private function as($user, string $guard): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, $guard);
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(20, 10);
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return ob_get_clean();
    }

    private function file(int $type = 1, float $days = 3): LeaveApplication
    {
        $this->as($this->applicant, 'employee')
            ->from('/leave')
            ->post('/leave/create', [
                'empid' => $this->applicant->emp_ID,
                'leave_type' => $type,
                'leave_purpose' => $type == 3 ? 4 : 1,
                'leave_detail' => ['', 'Dumaguete', '', ''],
                'date_range' => '2026-12-01 to 2026-12-03',
                'days' => $days,
                'date_filing' => '2026-11-09',
            ])
            ->assertRedirect('/leave')
            ->assertSessionHas('success');

        return LeaveApplication::where('empid', $this->applicant->emp_ID)->latest('id')->firstOrFail();
    }

    /** Filed, checked by HR, signed by the employee, approved by the HRMO. */
    private function toSupervisor(int $type = 1, float $days = 3, float $holiday = 0, float $withoutPay = 0): LeaveApplication
    {
        $leave = $this->file($type, $days);

        $this->as($this->hr, 'web')
            ->postJson('/leave/leave-wpay', ['id' => $leave->id, 'holiday' => $holiday, 'day_wpay' => $withoutPay])
            ->assertOk()->assertJson(['success' => true]);

        $this->as($this->applicant, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 0])
            ->assertOk();

        $this->as($this->hr, 'web')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 1])
            ->assertOk();

        return $leave->fresh();
    }

    private function toMayor(): LeaveApplication
    {
        $leave = $this->toSupervisor();

        $this->as($this->supervisor, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])
            ->assertOk();

        return $leave->fresh();
    }

    public function test_the_whole_application_from_filing_to_the_mayors_approval(): void
    {
        // ---- the employee files
        $leave = $this->file(1, 3);
        $this->assertSame('1', (string) $leave->status);
        $this->assertSame('0', (string) $leave->emp_esign);
        $this->assertSame('0', (string) $leave->remarks_stat);
        $this->assertSame($this->hrHead->id, (int) $leave->hr);
        $this->assertSame($this->mayor->id, (int) $leave->approver);
        $this->assertSame('Dumaguete', $leave->leave_detail);

        // ---- HR sees it as new and checks the days
        $this->as($this->hr, 'web')->get('/leave/status/' . $this->applicant->id)
            ->assertOk()->assertSee('Check the days');

        $this->as($this->applicant, 'employee')
            ->postJson('/leave/leave-wpay', ['id' => $leave->id, 'holiday' => 1, 'day_wpay' => 0])
            ->assertForbidden();

        $this->as($this->hr, 'web')
            ->postJson('/leave/leave-wpay', ['id' => $leave->id, 'holiday' => 1, 'day_wpay' => 0])
            ->assertOk();
        $leave->refresh();
        $this->assertEquals(2, (float) $leave->days);
        $this->assertEquals(2, (float) $leave->less_vl);
        $this->assertSame('1', (string) $leave->emp_esign);
        $this->assertTrue(Notification::where('lapp_id', $leave->id)->where('utype', 'employee')->where('category', 1)->exists());

        // ---- the employee e-signs (nobody else can)
        $this->as($this->stranger, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 0])
            ->assertForbidden();

        $this->as($this->applicant, 'employee')->get('/leave/status')
            ->assertOk()->assertSee('E-sign');
        $this->as($this->applicant, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 0])
            ->assertOk();
        $this->assertSame('2', (string) $leave->fresh()->emp_esign);

        // ---- the HRMO approves (an employee cannot, the supervisor not yet)
        $this->as($this->applicant, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 1])
            ->assertForbidden();
        $this->as($this->supervisor, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])
            ->assertStatus(422);

        $this->as($this->hr, 'web')->get('/leave/status/' . $this->applicant->id)
            ->assertOk()->assertSee('Waiting on you');
        $this->as($this->hr, 'web')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 1])
            ->assertOk();
        $this->assertSame('2', (string) $leave->fresh()->status);
        $this->assertTrue(Notification::where('lapp_id', $leave->id)->where('empid', $this->supervisor->emp_ID)
            ->where('utype', 'employee')->where('category', 5)->where('status', 0)->exists());

        // ---- the supervisor sees it and signs; the credits come off once
        $this->as($this->supervisor, 'employee')->get('/leave/status')
            ->assertOk()
            ->assertViewHas('leavesapphead', fn ($rows) => collect($rows)->contains('id', $leave->id))
            ->assertSee('Waiting on you');

        $this->as($this->mayor, 'employee')
            ->postJson('/leave/approve-pres', ['id' => $leave->id, 'by' => 3])
            ->assertStatus(422);

        $this->as($this->supervisor, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])
            ->assertOk();
        $this->assertSame('3', (string) $leave->fresh()->status);
        $this->assertEquals(8, (float) $this->applicant->fresh()->vl);

        $this->as($this->supervisor, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])
            ->assertStatus(422);
        $this->as($this->hr, 'web')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])
            ->assertStatus(422);
        $this->assertEquals(8, (float) $this->applicant->fresh()->vl, 'credits must come off only once');
        $this->assertTrue(Notification::where('lapp_id', $leave->id)->where('empid', $this->mayor->emp_ID)
            ->where('category', 6)->where('status', 0)->exists());

        // ---- the Mayor approves; nobody else can
        $this->as($this->applicant, 'employee')
            ->postJson('/leave/approve-pres', ['id' => $leave->id, 'by' => 3])
            ->assertForbidden();
        $this->as($this->applicant, 'employee')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 3])
            ->assertForbidden();

        $this->as($this->mayor, 'employee')->get('/leave/status')
            ->assertOk()
            ->assertViewHas('leavesapphead', fn ($rows) => collect($rows)->contains('id', $leave->id))
            ->assertSee('For your signature');

        $this->as($this->mayor, 'employee')
            ->postJson('/leave/approve-pres', ['id' => $leave->id, 'by' => 3])
            ->assertOk();

        $leave->refresh();
        $this->assertSame('4', (string) $leave->status);
        $this->assertSame(2, (int) $leave->history);
        $this->assertSame('Mayor', $leave->approver_role);
        $this->assertEquals(8, (float) $this->applicant->fresh()->vl);
        $this->assertTrue(Notification::where('lapp_id', $leave->id)->where('empid', $this->applicant->emp_ID)
            ->where('category', 2)->exists());

        // ---- it is in everyone's history, and the form opens for them only
        $this->as($this->applicant, 'employee')->get('/leave/history')
            ->assertOk()->assertViewHas('leaveApplication', fn ($rows) => $rows->contains('id', $leave->id));
        $this->as($this->mayor, 'employee')->get('/leave/history')
            ->assertOk()->assertViewHas('leaveApplication1', fn ($rows) => $rows->contains('id', $leave->id));

        $this->as($this->applicant, 'employee')->get('/leave/preview-leave/' . $leave->id)->assertOk();
        $this->as($this->hr, 'web')->get('/leave/preview-leave/' . $leave->id)->assertOk();
        $this->as($this->stranger, 'employee')->get('/leave/preview-leave/' . $leave->id)->assertForbidden();

        // ---- and the employee may file the next one
        $this->file(3, 1);
    }

    public function test_the_vice_mayor_can_give_final_approval(): void
    {
        $leave = $this->toMayor();

        $this->as($this->viceMayor, 'employee')
            ->postJson('/leave/approve-pres', ['id' => $leave->id, 'by' => 3])
            ->assertOk();

        $leave->refresh();
        $this->assertSame('4', (string) $leave->status);
        $this->assertSame('Vice Mayor', $leave->approver_role);
        $this->assertSame($this->viceMayor->id, (int) $leave->approver);
    }

    public function test_the_mayors_disapproval_gives_the_credits_back(): void
    {
        $leave = $this->toMayor();
        $this->assertEquals(7, (float) $this->applicant->fresh()->vl);

        $this->as($this->mayor, 'employee')
            ->postJson('/leave/dis-approve', ['id' => $leave->id, 'by' => 3, 'remarks' => 'Busy season'])
            ->assertOk();

        $leave->refresh();
        $this->assertSame('3', (string) $leave->remarks_stat);
        $this->assertSame('Busy season', $leave->remarks_details1);
        $this->assertSame(2, (int) $leave->history);
        $this->assertEquals(10, (float) $this->applicant->fresh()->vl);
        $this->assertTrue(Notification::where('lapp_id', $leave->id)->where('empid', $this->applicant->emp_ID)
            ->where('category', 3)->exists());

        $this->as($this->applicant, 'employee')->get('/leave/history')
            ->assertOk()->assertSee('Busy season');
    }

    public function test_the_supervisors_disapproval_takes_nothing_and_frees_the_employee_to_file_again(): void
    {
        $leave = $this->toSupervisor();

        $this->as($this->stranger, 'employee')
            ->postJson('/leave/dis-approve', ['id' => $leave->id, 'by' => 2, 'remarks' => 'No'])
            ->assertForbidden();

        $this->as($this->supervisor, 'employee')
            ->postJson('/leave/dis-approve', ['id' => $leave->id, 'by' => 2, 'remarks' => 'Short staffed'])
            ->assertOk();

        $leave->refresh();
        $this->assertSame('2', (string) $leave->remarks_stat);
        $this->assertSame(2, (int) $leave->history);
        $this->assertEquals(10, (float) $this->applicant->fresh()->vl);

        $this->file();
    }

    public function test_returns_go_back_one_stop_and_restore_credits(): void
    {
        $leave = $this->toMayor();
        $this->assertEquals(7, (float) $this->applicant->fresh()->vl);

        // The Mayor returns it to the supervisor: credits back.
        $this->as($this->mayor, 'employee')
            ->postJson('/leave/return', ['id' => $leave->id, 'to' => 3])
            ->assertOk();
        $this->assertSame('2', (string) $leave->fresh()->status);
        $this->assertEquals(10, (float) $this->applicant->fresh()->vl);

        // The supervisor returns it to the HRMO.
        $this->as($this->supervisor, 'employee')
            ->postJson('/leave/return', ['id' => $leave->id, 'to' => 2])
            ->assertOk();
        $this->assertSame('1', (string) $leave->fresh()->status);

        // HR returns it to the employee to sign again.
        $this->as($this->hr, 'web')
            ->postJson('/leave/return', ['id' => $leave->id, 'to' => 1])
            ->assertOk();
        $this->assertSame('1', (string) $leave->fresh()->emp_esign);

        // ... and round again, deducting only once.
        $this->as($this->applicant, 'employee')->postJson('/leave/approve', ['id' => $leave->id, 'by' => 0])->assertOk();
        $this->as($this->hr, 'web')->postJson('/leave/approve', ['id' => $leave->id, 'by' => 1])->assertOk();
        $this->as($this->supervisor, 'employee')->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])->assertOk();
        $this->assertEquals(7, (float) $this->applicant->fresh()->vl);
    }

    public function test_checking_the_days_again_does_not_take_the_holidays_off_twice(): void
    {
        $leave = $this->file(1, 5);

        $this->as($this->hr, 'web')->postJson('/leave/leave-wpay', ['id' => $leave->id, 'holiday' => 1, 'day_wpay' => 0])->assertOk();
        $this->as($this->hr, 'web')->postJson('/leave/leave-wpay', ['id' => $leave->id, 'holiday' => 1, 'day_wpay' => 1])->assertOk();

        $leave->refresh();
        $this->assertEquals(4, (float) $leave->days);
        $this->assertEquals(3, (float) $leave->less_vl);

        $this->as($this->hr, 'web')
            ->postJson('/leave/leave-wpay', ['id' => $leave->id, 'holiday' => 0, 'day_wpay' => 9])
            ->assertStatus(422);
    }

    public function test_sick_leave_beyond_sick_credits_draws_on_vacation(): void
    {
        $this->applicant->sl = 1;
        $this->applicant->save();

        $leave = $this->toSupervisor(3, 3);
        $this->assertEquals(1, (float) $leave->less_sl);
        $this->assertEquals(2, (float) $leave->less_vl);

        $this->as($this->supervisor, 'employee')->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])->assertOk();
        $applicant = $this->applicant->fresh();
        $this->assertEquals(0, (float) $applicant->sl);
        $this->assertEquals(8, (float) $applicant->vl);
    }

    public function test_hr_cancelling_an_approved_leave_gives_the_credits_back(): void
    {
        $leave = $this->toMayor();
        $this->as($this->mayor, 'employee')->postJson('/leave/approve-pres', ['id' => $leave->id, 'by' => 3])->assertOk();
        $this->assertEquals(7, (float) $this->applicant->fresh()->vl);

        $this->as($this->applicant, 'employee')
            ->postJson('/leave/dis-approve', ['id' => $leave->id, 'by' => 4, 'remarks' => 'Mine'])
            ->assertForbidden();

        $this->as($this->hr, 'web')
            ->postJson('/leave/dis-approve', ['id' => $leave->id, 'by' => 4, 'remarks' => 'Reported back early'])
            ->assertOk();

        $this->assertSame('4', (string) $leave->fresh()->remarks_stat);
        $this->assertEquals(10, (float) $this->applicant->fresh()->vl);

        // Cancelling twice gives nothing more back.
        $this->as($this->hr, 'web')
            ->postJson('/leave/dis-approve', ['id' => $leave->id, 'by' => 4, 'remarks' => 'Again'])
            ->assertStatus(422);
        $this->assertEquals(10, (float) $this->applicant->fresh()->vl);
    }

    public function test_hr_cancelling_an_application_in_progress_gives_back_what_it_took(): void
    {
        $leave = $this->toMayor();

        $this->as($this->applicant, 'employee')->postJson('/leave/cacelLeave/' . $leave->id)->assertForbidden();
        $this->as($this->hr, 'web')->postJson('/leave/cacelLeave/' . $leave->id)->assertOk();

        $this->assertNull(LeaveApplication::find($leave->id));
        $this->assertEquals(10, (float) $this->applicant->fresh()->vl);
    }

    public function test_hr_forwarding_to_the_mayor_needs_the_days_checked(): void
    {
        $leave = $this->file();

        $this->as($this->hr, 'web')
            ->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])
            ->assertStatus(422);

        $this->as($this->hr, 'web')->postJson('/leave/leave-wpay', ['id' => $leave->id, 'holiday' => 0, 'day_wpay' => 0])->assertOk();
        $this->as($this->hr, 'web')->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])->assertOk();

        $this->assertSame('3', (string) $leave->fresh()->status);
        $this->assertEquals(7, (float) $this->applicant->fresh()->vl);
    }

    public function test_the_office_oic_signs_in_the_supervisors_place(): void
    {
        $office = \App\Models\Office::where('id', '>', 2)->firstOrFail();
        $office->update(['oic_id' => $this->stranger->id]);
        $this->applicant->emp_dept = $office->id;
        $this->applicant->save();
        $leave = $this->toSupervisor();

        $this->as($this->stranger, 'employee')->get('/leave/status')
            ->assertOk()
            ->assertViewHas('leavesapphead', fn ($rows) => collect($rows)->contains('id', $leave->id))
            ->assertSee('Waiting on you');

        $this->as($this->stranger, 'employee')->postJson('/leave/approve', ['id' => $leave->id, 'by' => 2])->assertOk();

        $leave->refresh();
        $this->assertSame('3', (string) $leave->status);
        $this->assertSame($this->stranger->id, (int) $leave->oic);
    }

    public function test_filing_explains_missing_setup_instead_of_failing(): void
    {
        Setting::query()->update(['hr' => null]);

        $this->as($this->applicant, 'employee')
            ->from('/leave')
            ->post('/leave/create', [
                'empid' => $this->applicant->emp_ID,
                'leave_type' => 1,
                'date_range' => '2026-12-01 to 2026-12-01',
                'days' => 1,
            ])
            ->assertRedirect('/leave')
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Settings'));

        $this->applicant->supervisor = null;
        $this->applicant->save();
        Setting::query()->update(['hr' => $this->hrHead->id]);

        $this->as($this->applicant, 'employee')
            ->from('/leave')
            ->post('/leave/create', [
                'empid' => $this->applicant->emp_ID,
                'leave_type' => 1,
                'date_range' => '2026-12-01 to 2026-12-01',
                'days' => 1,
            ])
            ->assertSessionHas('error', 'No Supervisor Assigned');

        $this->assertSame(0, LeaveApplication::where('empid', $this->applicant->emp_ID)->count());
    }

    public function test_an_employee_cannot_file_for_someone_else(): void
    {
        $this->as($this->stranger, 'employee')
            ->from('/leave')
            ->post('/leave/create', [
                'empid' => $this->applicant->emp_ID,
                'leave_type' => 1,
                'date_range' => '2026-12-01 to 2026-12-01',
                'days' => 1,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, LeaveApplication::where('empid', $this->applicant->emp_ID)->count());
    }

    public function test_an_elected_mayor_reaches_the_leave_screens(): void
    {
        $this->as($this->mayor, 'employee')->get('/dashboard')
            ->assertOk()
            ->assertSee(route('leaveStatus'), false);

        $this->as($this->mayor, 'employee')->get('/leave/status')->assertOk();
    }

    public function test_a_casual_employee_can_open_the_filing_screen(): void
    {
        $this->applicant->emp_status = 2;
        $this->applicant->save();

        $this->as($this->applicant, 'employee')->get('/leave')->assertOk();
    }

    public function test_hr_queues_count_a_new_application(): void
    {
        $this->file();

        $this->as($this->hr, 'web')->get('/pending/1/0.1')
            ->assertOk()
            ->assertViewHas('employees', fn ($rows) => collect($rows)->contains('empid', $this->applicant->emp_ID))
            ->assertViewHas('leaveappCount', fn ($count) => $count >= 1);
    }

    public function test_the_mayors_transmittal_lists_what_awaits_final_approval(): void
    {
        $this->toMayor();

        $this->as($this->hr, 'web')
            ->post('/leave/leaves-report', ['date' => now()->toDateString()])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
