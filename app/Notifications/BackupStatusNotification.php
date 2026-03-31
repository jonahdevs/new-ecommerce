<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $type,
        private array $result,
        private bool $success
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->success
            ? "✅ Backup Successful - {$this->type}"
            : "❌ Backup Failed - {$this->type}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Sheffield Backup System')
            ->line("Backup Type: " . ucfirst($this->type))
            ->line("Status: " . ($this->success ? 'SUCCESS' : 'FAILED'))
            ->line("Message: {$this->result['message']}");

        if (isset($this->result['duration'])) {
            $message->line("Duration: {$this->result['duration']} seconds");
        }

        if (!$this->success) {
            $message->line('Please check the backup logs for more details.')
                ->action('View Logs', url('/admin/logs'));
        }

        return $message->line('This is an automated notification from your Sheffield backup system.');
    }
}