<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CartReminderSettings extends Settings
{
    /** Master switch for the whole abandoned-cart reminder feature. */
    public bool $enabled = true;

    /** Hours of cart inactivity before the first reminder. */
    public int $first_delay_hours = 4;

    /** Hours of cart inactivity before the second reminder. 0 disables it. */
    public int $second_delay_hours = 24;

    /** Skip carts whose subtotal is below this (in cents). 0 = remind any cart. */
    public int $min_subtotal_cents = 0;

    /** Stop reminding once the cart has been idle longer than this (hours). */
    public int $stop_after_hours = 168;

    public static function group(): string
    {
        return 'cart_reminders';
    }

    /**
     * The configured reminder delays, in stage order, with disabled (0) stages
     * removed. Drives both the scheduler and the cap on reminders per cart.
     *
     * @return list<int>
     */
    public function stageDelays(): array
    {
        return array_values(array_filter(
            [$this->first_delay_hours, $this->second_delay_hours],
            fn (int $hours): bool => $hours > 0,
        ));
    }
}
