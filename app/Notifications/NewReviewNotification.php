<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Review $review) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsNotification('notify_new_review')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(): MailMessage
    {
        $adminUrl = route('admin.reviews.show', $this->review);
        $customerName = $this->review->user->name;
        $productName = $this->review->product->name;
        $rating = $this->review->rating;

        $mail = (new MailMessage)
            ->subject("New Review Pending — {$productName}")
            ->greeting('New review submitted')
            ->line("{$customerName} has submitted a review for **{$productName}**.")
            ->line("**Rating:** {$rating} stars")
            ->line("**Review:** {$this->review->review_text}");

        if ($this->review->images->count() > 0) {
            $mail->line("**Images:** {$this->review->images->count()} photo(s) attached");
        }

        return $mail
            ->line('Please review and moderate this submission.')
            ->action('View Review', $adminUrl)
            ->salutation('Sheffield Africa · Moderation Team');
    }

    public function toArray(): array
    {
        return [
            'review_id' => $this->review->id,
            'product_id' => $this->review->product_id,
            'title' => 'New Review Pending',
            'message' => "{$this->review->user->name} reviewed {$this->review->product->name} — {$this->review->rating} stars",
            'url' => route('admin.reviews.show', $this->review),
        ];
    }
}
