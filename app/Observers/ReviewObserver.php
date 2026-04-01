<?php

namespace App\Observers;

use App\Models\Review;
use App\Notifications\NewReviewNotification;
use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReviewObserver
{
    public function __construct(
        private readonly NotificationSettings $notificationSettings
    ) {}

    public function created(Review $review): void
    {
        if (!$this->notificationSettings->notify_new_review) {
            return;
        }

        try {
            $adminEmail = $this->notificationSettings->admin_notification_email
                ?? config('mail.from.address');

            Notification::route('mail', $adminEmail)
                ->notify(new NewReviewNotification($review));

            Log::info('New review notification sent to admin', [
                'review_id' => $review->id,
                'product_id' => $review->product_id,
                'admin_email' => $adminEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send new review notification to admin', [
                'review_id' => $review->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
