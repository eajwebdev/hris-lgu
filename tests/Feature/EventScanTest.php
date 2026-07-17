<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The event QR attendance scanner (web, Admin/HR only).
 *
 * The badge carries an encrypted emp_ID — the browser never names the attendee,
 * the server decrypts and decides whose row moves. First scan clocks the
 * attendee in; every later scan moves the clock-out to now, so the last scan of
 * the event is the one that sticks. Only an enrolled attendee (an EventLog row)
 * can be recorded, and only an Administrator / HR Administrator may operate it.
 */
class EventScanTest extends TestCase
{
    use DatabaseTransactions;

    private Employee $alice;   // enrolled to the event
    private Employee $bob;     // not enrolled
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->alice, $this->bob] = Employee::orderBy('id')->take(2)->get()->all();

        $this->event = Event::create([
            'title'       => 'Flag Ceremony',
            'venue'       => 'Municipal Grounds',
            'start'       => now()->toDateTimeString(),
            'end'         => now()->addHours(2)->toDateTimeString(),
            'emp_status'  => 0,
            'bg_color'    => '#187744',
            'org_dept'    => 'HRMO',
            'event_stat'  => 1,
        ]);

        // Alice is on the attendee list; Bob is not.
        EventLog::create(['event_id' => $this->event->id, 'empid' => $this->alice->emp_ID]);
    }

    private function admin(): User
    {
        return User::where('role', 'Administrator')->firstOrFail();
    }

    private function scan(string $qr, ?int $eventId = null)
    {
        return $this->postJson(route('eventScanPunch'), [
            'event_id' => $eventId ?? $this->event->id,
            'qr'       => $qr,
        ]);
    }

    private function badge(Employee $e): string
    {
        return shortEncrypt($e->emp_ID);
    }

    private function log(): EventLog
    {
        return EventLog::where('event_id', $this->event->id)
            ->where('empid', $this->alice->emp_ID)
            ->firstOrFail();
    }

    // ---------------------------------------------------------------- access

    public function test_a_guest_is_sent_to_login(): void
    {
        // Both the page and the punch endpoint sit behind login_auth, which
        // bounces an unauthenticated request to the sign-in page.
        $this->get(route('eventScan'))->assertRedirectToRoute('getLogin');
        $this->scan($this->badge($this->alice))->assertRedirectToRoute('getLogin');

        $this->assertNull($this->log()->in);
    }

    public function test_an_employee_cannot_reach_the_scanner(): void
    {
        $this->actingAs($this->alice, 'employee');

        $this->get(route('eventScan'))->assertStatus(403);
        $this->scan($this->badge($this->alice))->assertStatus(403);
    }

    public function test_an_admin_sees_the_scanner(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->get(route('eventScan'))
            ->assertOk()
            ->assertSee('Event QR Attendance')
            ->assertSee($this->event->title);
    }

    // ---------------------------------------------------------------- punch flow

    public function test_first_scan_clocks_in(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->scan($this->badge($this->alice))
            ->assertOk()
            ->assertJsonPath('action', 'CLOCK IN')
            ->assertJsonPath('recorded', true);

        $log = $this->log();
        $this->assertNotNull($log->in);
        $this->assertNull($log->out);
    }

    public function test_the_last_scan_clocks_out(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->scan($this->badge($this->alice))->assertJsonPath('action', 'CLOCK IN');

        // A later scan, past the anti-double-count cooldown, clocks out.
        $this->travel(30)->seconds();
        $this->scan($this->badge($this->alice))->assertJsonPath('action', 'CLOCK OUT');

        $firstOut = (string) $this->log()->out;
        $this->assertNotEmpty($firstOut);

        // The last scan wins: a still-later scan moves the clock-out forward.
        $this->travel(30)->seconds();
        $this->scan($this->badge($this->alice))->assertJsonPath('action', 'CLOCK OUT');

        // 'out' is stored as a plain datetime string; a later scan sorts after.
        $this->assertTrue((string) $this->log()->out > $firstOut);

        $this->travelBack();
    }

    public function test_a_repeat_scan_within_the_cooldown_is_not_double_counted(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->scan($this->badge($this->alice))->assertJsonPath('action', 'CLOCK IN');

        // Same badge again immediately: acknowledged but not recorded, and the
        // fresh clock-in is not flipped to a clock-out.
        $this->scan($this->badge($this->alice))
            ->assertOk()
            ->assertJsonPath('recorded', false);

        $this->assertNull($this->log()->out);
    }

    // ---------------------------------------------------------------- edges

    public function test_an_employee_not_on_the_list_is_refused(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->scan($this->badge($this->bob))->assertStatus(409);

        $this->assertSame(0, EventLog::where('event_id', $this->event->id)
            ->where('empid', $this->bob->emp_ID)->count());
    }

    public function test_a_garbled_qr_is_refused(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->scan('not-a-real-token')->assertStatus(422);
    }

    public function test_scanning_against_an_inactive_event_is_refused(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->event->forceFill(['event_stat' => 0])->save();

        $this->scan($this->badge($this->alice))->assertStatus(404);
    }

    public function test_the_response_never_leaks_more_than_the_display_card(): void
    {
        $this->actingAs($this->admin(), 'web');

        $body = $this->scan($this->badge($this->alice))->assertOk()->json();

        $this->assertSame(['name', 'position', 'id', 'initials'], array_keys($body['employee']));
    }
}
