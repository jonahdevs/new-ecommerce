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
     * @param  array{reference: string, inquiry: string, name: string, business: ?string, email: string, phone: ?string, message: string}  $enquiry
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

        return (new MailMessage)
            ->subject('New contact enquiry — '.$enquiry['inquiry'].' ['.$enquiry['reference'].']')
            ->replyTo($enquiry['email'], $enquiry['name'])
            ->markdown('mails.staff.contact-enquiry', [
                'inquiry' => $enquiry['inquiry'],
                'reference' => $enquiry['reference'],
                'name' => $enquiry['name'],
                'business' => $enquiry['business'] ?? null,
                'email' => $enquiry['email'],
                'phone' => $enquiry['phone'] ?? null,
                'message' => $enquiry['message'],
            ]);
    }
}
