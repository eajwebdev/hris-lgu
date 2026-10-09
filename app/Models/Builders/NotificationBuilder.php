<?php

namespace App\Models\Builders;

use App\Events\NotificationsChanged;
use Illuminate\Database\Eloquent\Builder;

class NotificationBuilder extends Builder
{
    public function update(array $values)
    {
        // Existing controllers mark notifications read with bulk queries, which skip model events.
        $recipients = (clone $this)->get(['utype', 'empid']);
        $affected = parent::update($values);
        if ($affected) {
            NotificationsChanged::forRecipients($recipients);
        }

        return $affected;
    }

    public function delete()
    {
        $recipients = (clone $this)->get(['utype', 'empid']);
        $affected = parent::delete();
        if ($affected) {
            NotificationsChanged::forRecipients($recipients);
        }

        return $affected;
    }
}
