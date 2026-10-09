<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationsChanged implements ShouldBroadcastNow
{
    public function __construct(public string $channel)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return 'notifications.changed';
    }

    public function broadcastWith(): array
    {
        // Clients fetch their own authorized feed; no personal data goes over the socket.
        return ['refresh' => true];
    }

    public static function forRecipients(Collection $recipients): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        DB::afterCommit(function () use ($recipients) {
            try {
                $channels = [];
                if ($recipients->contains('utype', 'hr')) {
                    $channels[] = 'notifications.hr';
                }

                $employeeIds = $recipients->where('utype', 'employee')->pluck('empid')->filter()->unique();
                if ($employeeIds->isNotEmpty()) {
                    foreach (Employee::whereIn('emp_ID', $employeeIds)->pluck('id') as $id) {
                        $channels[] = 'notifications.employee.' . $id;
                    }
                }

                foreach ($channels as $channel) {
                    event(new self($channel));
                }
            } catch (\Throwable $exception) {
                // A transport outage must not undo a saved application or approval.
                report($exception);
            }
        });
    }
}
