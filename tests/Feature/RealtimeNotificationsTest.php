<?php

namespace Tests\Feature;

use App\Events\NotificationsChanged;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([NotificationsChanged::class]);
    }

    private function notification(string $type = 'employee', ?Employee $employee = null): Notification
    {
        $employee ??= Employee::findOrFail(4);
        return Notification::create([
            'empid' => $employee->emp_ID,
            'lapp_id' => LeaveApplication::where('empid', $employee->emp_ID)->value('id'),
            'module' => 'leave', 'category' => 1, 'utype' => $type, 'status' => 0,
        ]);
    }

    public function test_creation_broadcasts_to_the_correct_private_channels(): void
    {
        $this->notification('hr');
        $this->notification('employee');
        Event::assertDispatched(NotificationsChanged::class, fn ($event) => $event->channel === 'notifications.hr');
        Event::assertDispatched(NotificationsChanged::class, fn ($event) => $event->channel === 'notifications.employee.4');
        $event = new NotificationsChanged('notifications.hr');
        $this->assertSame('private-notifications.hr', $event->broadcastOn()[0]->name);
        $this->assertSame(['refresh' => true], $event->broadcastWith());
    }

    public function test_bulk_read_updates_and_deletes_broadcast_feed_changes(): void
    {
        $notification = $this->notification();
        Event::fake([NotificationsChanged::class]);
        Notification::whereKey($notification->id)->update(['status' => 1]);
        Event::assertDispatchedTimes(NotificationsChanged::class, 1);
        Notification::whereKey($notification->id)->delete();
        Event::assertDispatchedTimes(NotificationsChanged::class, 2);
    }

    public function test_rolled_back_notifications_are_never_broadcast(): void
    {
        DB::beginTransaction();
        $notification = $this->notification('hr');
        Event::assertNotDispatched(NotificationsChanged::class);
        DB::rollBack();
        Event::assertNotDispatched(NotificationsChanged::class);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_committed_notifications_are_broadcast_after_commit(): void
    {
        DB::beginTransaction();
        $this->notification('hr');
        Event::assertNotDispatched(NotificationsChanged::class);
        DB::commit();
        Event::assertDispatchedTimes(NotificationsChanged::class, 1);
    }

    public function test_employee_feed_is_scoped_and_read_changes_refresh_the_count(): void
    {
        $employee = Employee::findOrFail(4);
        $own = $this->notification('employee', $employee);
        $other = $this->notification('employee', Employee::where('id', '!=', 4)->firstOrFail());
        $this->actingAs($employee, 'employee');
        $this->get('/leave/history')->assertOk()->assertSee('data-channel="private-notifications.employee.4"', false);
        $response = $this->getJson('/notification/refresh')->assertOk();
        $this->assertStringContainsString('data-notification-id="' . $own->id . '"', $response->json('html'));
        $this->assertStringNotContainsString('data-notification-id="' . $other->id . '"', $response->json('html'));
        $this->postJson('/notification/mark-all-read')->assertOk();
        $this->getJson('/notification/refresh')->assertOk()->assertJsonPath('count', 0);
        $this->assertSame(0, (int) $other->fresh()->status);
        $this->getJson('/notification/load')->assertForbidden();
    }

    public function test_hr_feed_refreshes_in_both_layouts(): void
    {
        $notification = $this->notification('hr');
        $this->actingAs(User::where('role', 'Administrator')->firstOrFail(), 'web');
        foreach (['0', '1'] as $legacy) {
            $response = $this->getJson('/notification/refresh?legacy=' . $legacy)->assertOk();
            $this->assertStringContainsString('data-notification-id="' . $notification->id . '"', $response->json('html'));
        }
        $this->get('/leave/history/4')->assertOk()->assertSee('notificationRealtime');
        $this->get('/settings')->assertOk()->assertSee('notificationRealtime');
    }

    public function test_private_channels_reject_guests_and_other_employees(): void
    {
        config(['broadcasting.default' => 'pusher']);
        require base_path('routes/channels.php');
        $payload = ['socket_id' => '123.456', 'channel_name' => 'private-notifications.employee.4'];
        $this->postJson('/broadcasting/auth', $payload)->assertUnauthorized();
        $this->actingAs(Employee::findOrFail(4), 'employee');
        $this->postJson('/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);
        $payload['channel_name'] = 'private-notifications.employee.5';
        $this->postJson('/broadcasting/auth', $payload)->assertForbidden();
        $payload['channel_name'] = 'private-notifications.hr';
        $this->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }

    public function test_hr_channel_accepts_web_users_and_rejects_employee_channels(): void
    {
        config(['broadcasting.default' => 'pusher']);
        require base_path('routes/channels.php');
        $this->actingAs(User::where('role', 'Administrator')->firstOrFail(), 'web');
        $this->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-notifications.hr'])
            ->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-notifications.employee.4'])
            ->assertForbidden();
    }
}
