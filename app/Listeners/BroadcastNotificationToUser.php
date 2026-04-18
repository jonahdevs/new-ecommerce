<?php

namespace App\Listeners;

use App\Events\NotificationReceived;
use Illuminate\Notifications\Events\NotificationSent;

class BroadcastNotificationToUser
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;

        if (! isset($notifiable->id)) {
            return;
        }

        NotificationReceived::dispatch($notifiable->id);
    }
}
