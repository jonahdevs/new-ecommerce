<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Internal alert sent to the store's contact inbox when a visitor submits the
 * contact form. Carries the enquiry details and sets the reply-to to the
 * customer so staff can respond directly.
 */
class ContactEnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{reference: string, inquiry: string, name: string, business: ?string, email: string, phone: ?string, location: ?string, message: string}  $enquiry
     */
    public function __construct(public array $enquiry) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $enquiry = $this->enquiry;

        $mail = (new MailMessage)
            ->subject('New contact enquiry — '.$enquiry['inquiry'].' ['.$enquiry['reference'].']')
            ->greeting('New contact enquiry')
            ->line('**Type:** '.$enquiry['inquiry'])
            ->line('**Name:** '.$enquiry['name'].($enquiry['business'] ? ' — '.$enquiry['business'] : ''))
            ->line('**Email:** '.$enquiry['email']);

        if ($enquiry['phone']) {
            $mail->line('**Phone:** '.$enquiry['phone']);
        }

        if ($enquiry['location']) {
            $mail->line('**Nearest showroom:** '.$enquiry['location']);
        }

        return $mail
            ->line('**Message:**')
            ->line($enquiry['message'])
            ->line('Reference: '.$enquiry['reference'])
            ->replyTo($enquiry['email'], $enquiry['name']);
    }
}
