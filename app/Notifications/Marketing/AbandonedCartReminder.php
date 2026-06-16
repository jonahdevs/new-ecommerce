<?php

namespace App\Notifications\Marketing;

use App\Models\Cart;
use App\Models\User;
use App\Settings\NotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Reminds a customer about items left in their cart. Marketing, so opt-IN: only
 * delivered when the store allows marketing email AND the customer has
 * explicitly enabled marketing email (an absent preference is treated as not
 * opted in, unlike transactional notifications which default on).
 */
class AbandonedCartReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Cart $cart, public int $stage = 1) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        if (! app(NotificationSettings::class)->customer_marketing_email) {
            return [];
        }

        $optedIn = $notifiable instanceof User
            && (($notifiable->notification_preferences['marketing'] ?? false) === true);

        return $optedIn ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cart = $this->cart->loadMissing('items.product');
        $itemCount = (int) $cart->items->sum('quantity');

        // A signed, expiring link that rehydrates the cart into the session.
        $restoreUrl = URL::temporarySignedRoute(
            'cart.restore',
            now()->addDays(30),
            ['cart' => $cart->id],
        );

        $mail = (new MailMessage)
            ->subject($this->stage > 1
                ? 'Still thinking it over? Your cart is waiting'
                : 'You left something in your cart')
            ->greeting('Hi '.($notifiable->name ?? 'there').',')
            ->line('You left '.$itemCount.' item'.($itemCount === 1 ? '' : 's').' in your cart at '.config('app.name').'.');

        foreach ($cart->items->take(5) as $item) {
            $mail->line('• '.($item->product?->name ?? 'Item').' × '.$item->quantity);
        }

        return $mail
            ->action('Return to your cart', $restoreUrl)
            ->line("Items may sell out — complete your order while they're still available.");
    }
}
