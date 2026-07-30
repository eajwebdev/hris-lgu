<?php

namespace Tests\Feature;

use App\Models\AttendancePunchLog;
use App\Models\Dtr;
use App\Models\Employee;
use App\Services\AttendanceHistoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Today's log, as the kiosk shows it after a badge scan.
 *
 * The interesting part is overtime. time_in and time_out are a pair of columns,
 * but overtime is ONE comma-separated column carrying both ends of a stretch —
 * so which entry is a start and which is an end is decided by position, and
 * getting that wrong would tell an employee a different story from the DTR they
 * are paid from.
 */
class AttendanceHistoryTest extends TestCase
{
    use DatabaseTransactions;

    private Employee $employee;
    private AttendanceHistoryService $history;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = Employee::orderBy('id')->firstOrFail();
        $this->history  = app(AttendanceHistoryService::class);

        Dtr::where('emp_ID', $this->employee->emp_ID)
            ->where('date', now()->toDateString())
            ->delete();

        AttendancePunchLog::where('emp_ID', $this->employee->emp_ID)->delete();
    }

    private function dtr(array $columns): void
    {
        Dtr::create(array_merge([
            'emp_ID' => $this->employee->emp_ID,
            'date'   => now()->toDateString(),
        ], $columns));
    }

    private function log(string $action, string $station): void
    {
        AttendancePunchLog::create([
            'employee_id'  => $this->employee->id,
            'emp_ID'       => $this->employee->emp_ID,
            'action'       => $action,
            'mode'         => 'qr',
            'station_name' => $station,
            'out_of_range' => false,
        ]);
    }

    public function test_no_dtr_row_means_an_empty_log(): void
    {
        $this->assertSame([], $this->history->today($this->employee));
    }

    public function test_a_clock_in_reads_as_a_login_with_its_station(): void
    {
        $this->dtr(['time_in' => '08:03:00']);
        $this->log('in', 'Municipal Hall');

        $entries = $this->history->today($this->employee);

        $this->assertCount(1, $entries);
        $this->assertSame('Login', $entries[0]['label']);
        $this->assertSame('8:03 AM', $entries[0]['time']);
        $this->assertSame('Municipal Hall', $entries[0]['station']);
    }

    public function test_afternoon_times_are_shown_in_twelve_hour_form(): void
    {
        $this->dtr(['time_out' => '16:25:00']);

        $entries = $this->history->today($this->employee);

        $this->assertSame('Logout', $entries[0]['label']);
        $this->assertSame('4:25 PM', $entries[0]['time']);
    }

    /**
     * The pairing the single overtime column implies: first entry opens the
     * stretch, second closes it, and so on in pairs.
     */
    public function test_overtime_alternates_between_login_and_logout(): void
    {
        $this->dtr(['time_over' => '18:00:00,20:30:00,21:00:00,22:15:00']);

        $entries = $this->history->today($this->employee);

        $this->assertSame(
            ['Login overtime', 'Logout overtime', 'Login overtime', 'Logout overtime'],
            array_column($entries, 'label')
        );

        $this->assertSame(
            ['6:00 PM', '8:30 PM', '9:00 PM', '10:15 PM'],
            array_column($entries, 'time')
        );
    }

    /** An overtime stretch still open reads as a login with nothing after it. */
    public function test_an_unclosed_overtime_stretch_is_a_login_only(): void
    {
        $this->dtr(['time_over' => '18:00:00']);

        $entries = $this->history->today($this->employee);

        $this->assertCount(1, $entries);
        $this->assertSame('Login overtime', $entries[0]['label']);
    }

    /** The day reads in clock order, not grouped by which column it came from. */
    public function test_entries_are_ordered_by_time_of_day(): void
    {
        $this->dtr([
            'time_in'   => '08:00:00,13:00:00',
            'time_out'  => '12:00:00,17:00:00',
            'time_over' => '18:30:00',
        ]);

        $entries = $this->history->today($this->employee);

        $this->assertSame(
            ['8:00 AM', '12:00 PM', '1:00 PM', '5:00 PM', '6:30 PM'],
            array_column($entries, 'time')
        );

        $this->assertSame(
            ['Login', 'Logout', 'Login', 'Logout', 'Login overtime'],
            array_column($entries, 'label')
        );
    }

    /** Stations line up positionally with the punches of the same action. */
    public function test_station_names_follow_the_order_of_each_action(): void
    {
        $this->dtr(['time_in' => '08:00:00,13:00:00']);
        $this->log('in', 'Municipal Hall');
        $this->log('in', 'Barangay Outpost');

        $entries = $this->history->today($this->employee);

        $this->assertSame('Municipal Hall',   $entries[0]['station']);
        $this->assertSame('Barangay Outpost', $entries[1]['station']);
    }

    /**
     * A punch the portal did not record still appears. The DTR is the record of
     * truth and holds punches from any source; the log only ever sees this
     * kiosk's, so a missing station must not hide the punch itself.
     */
    public function test_a_punch_with_no_matching_log_still_appears_without_a_station(): void
    {
        $this->dtr(['time_in' => '08:00:00']);

        $entries = $this->history->today($this->employee);

        $this->assertCount(1, $entries);
        $this->assertNull($entries[0]['station']);
    }

    // ------------------------------------------------------------- endpoint

    public function test_the_endpoint_returns_todays_entries_for_a_valid_badge(): void
    {
        $this->dtr(['time_in' => '08:03:00', 'time_over' => '18:00:00,20:00:00']);

        $this->postJson(route('attendanceHistory'), ['qr' => shortEncrypt($this->employee->emp_ID)])
            ->assertOk()
            ->assertJsonPath('entries.0.label', 'Login')
            ->assertJsonPath('entries.1.label', 'Login overtime')
            ->assertJsonPath('entries.2.label', 'Logout overtime');
    }

    public function test_the_endpoint_refuses_a_garbage_badge(): void
    {
        $this->postJson(route('attendanceHistory'), ['qr' => 'not-a-real-token'])
            ->assertStatus(404);
    }

    /** The token is the only way in — an employee id must not be accepted. */
    public function test_the_endpoint_will_not_take_an_employee_id(): void
    {
        $this->dtr(['time_in' => '08:00:00']);

        $this->postJson(route('attendanceHistory'), [
            'qr'     => (string) $this->employee->emp_ID,
            'emp_ID' => $this->employee->emp_ID,
        ])->assertStatus(404);
    }

    /** No face vector may ride along on this response either. */
    public function test_the_response_carries_no_face_data(): void
    {
        $this->dtr(['time_in' => '08:00:00']);

        $body = $this->postJson(route('attendanceHistory'), ['qr' => shortEncrypt($this->employee->emp_ID)])
            ->assertOk()
            ->json();

        $this->assertStringNotContainsString('embedding', json_encode($body));
    }
}
