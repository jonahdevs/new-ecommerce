<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $user) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsNotification('notify_new_user')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(): MailMessage
    {
        $adminUrl = route('admin.customers.index');

        return (new MailMessage)
            ->subject("New Customer Registered — {$this->user->name}")
            ->greeting('New customer registration')
            ->line('A new customer has registered on your store.')
            ->line("**Name:** {$this->user->name}")
            ->line("**Email:** {$this->user->email}")
            ->line('**Phone:** '.($this->user->phone_number ?? 'Not provided'))
            ->line("**Registered:** {$this->user->created_at->format('M j, Y g:i A')}")
            ->action('View Customers', $adminUrl)
            ->salutation('Sheffield Africa · Customer Success Team');
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user->id,
            'title' => 'New Customer Registered',
            'message' => "{$this->user->name} ({$this->user->email}) has registered",
            'url' => route('admin.customers.index'),
        ];
    }
}
