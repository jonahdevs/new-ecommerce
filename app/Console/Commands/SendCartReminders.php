<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Notifications\Marketing\AbandonedCartReminder;
use App\Settings\CartReminderSettings;
use App\Settings\NotificationSettings;
use Illuminate\Console\Command;

/**
 * Emails customers a reminder about items left idle in their cart. Runs on a
 * short schedule; the per-cart state ({@see Cart::$reminders_sent}) makes it
 * idempotent so a cart is never reminded for the same stage twice.
 */
class SendCartReminders extends Command
{
    protected $signature = 'cart:remind-abandoned';

    protected $description = 'Email customers a reminder about items left in their cart';

    public function handle(CartReminderSettings $settings): int
    {
        if (! $settings->enabled) {
            $this->info('Cart reminders are disabled.');

            return self::SUCCESS;
        }

        // Marketing must be allowed at the store level for any reminder to send.
        if (! app(NotificationSettings::class)->customer_marketing_email) {
            $this->info('Marketing email is disabled — no cart reminders sent.');

            return self::SUCCESS;
        }

        $stages = $settings->stageDelays();

        if ($stages === []) {
            return self::SUCCESS;
        }

        $now = now();
        $firstDueAt = $now->subHours($stages[0]);
        $staleCutoff = $now->subHours($settings->stop_after_hours);

        $carts = Cart::query()
            ->whereNotNull('user_id')
            ->where('reminders_sent', '<', count($stages))
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<=', $firstDueAt)
            ->where('last_activity_at', '>=', $staleCutoff)
            ->whereHas('items')
            ->with(['user', 'items.product', 'items.variant'])
            ->get();

        $sent = 0;

        foreach ($carts as $cart) {
            $stage = $cart->reminders_sent + 1;
            $delayHours = $stages[$stage - 1];

            // Not idle long enough for this stage yet.
            if ($cart->last_activity_at->gt($now->subHours($delayHours))) {
                continue;
            }

            if ($cart->subtotalCents() < $settings->min_subtotal_cents) {
                continue;
            }

            $user = $cart->user;

            // Opt-IN marketing: skip (without consuming a stage) until the
            // customer enables marketing email — they may opt in later.
            if (! $user || ($user->notification_preferences['marketing'] ?? false) !== true) {
                continue;
            }

            $user->notify(new AbandonedCartReminder($cart, $stage));

            $cart->forceFill([
                'reminders_sent' => $stage,
                'last_reminded_at' => $now,
            ])->save();

            $sent++;
        }

        $this->info("Sent {$sent} cart reminder(s).");

        return self::SUCCESS;
    }
}
