<?php

use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\Orders\NewOrderReceived;
use App\Notifications\Orders\OrderConfirmed;
use App\Notifications\Quotes\NewQuoteRequested;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Notifications\Quotes\QuoteReadyForReview;
use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

// ==================================================
// HELPER
// ==================================================

function setGlobal(string $key, bool $value): void
{
    $settings = app(NotificationSettings::class);
    $settings->{$key} = $value;
    $settings->save();
}

// ==================================================
// CUSTOMER NOTIFICATIONS
// ==================================================

it('suppresses OrderConfirmed when globally disabled', function () {
    setGlobal('customer_order_confirmation_email', false);

    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $customer->notify(new OrderConfirmed($order));

    Notification::assertNothingSentTo($customer);
});

it('sends OrderConfirmed when globally enabled and user has not opted out', function () {
    setGlobal('customer_order_confirmation_email', true);

    $customer = User::factory()->create([
        'notification_preferences' => ['orders' => ['confirmation' => true]],
    ]);
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $customer->notify(new OrderConfirmed($order));

    Notification::assertSentTo($customer, OrderConfirmed::class);
});

it('suppresses OrderConfirmed when globally enabled but user opted out personally', function () {
    setGlobal('customer_order_confirmation_email', true);

    $customer = User::factory()->create([
        'notification_preferences' => ['orders' => ['confirmation' => false]],
    ]);
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $customer->notify(new OrderConfirmed($order));

    Notification::assertNothingSentTo($customer);
});

it('suppresses QuoteReadyForReview when globally disabled', function () {
    setGlobal('customer_quote_updates_email', false);

    $customer = User::factory()->create();
    $quote = Quote::factory()->create(['user_id' => $customer->id]);

    $customer->notify(new QuoteReadyForReview($quote));

    Notification::assertNothingSentTo($customer);
});

// ==================================================
// STAFF NOTIFICATIONS
// ==================================================

it('suppresses NewOrderReceived when globally disabled', function () {
    setGlobal('staff_new_order_email', false);

    $staff = User::factory()->create();
    $order = Order::factory()->create();

    $staff->notify(new NewOrderReceived($order));

    Notification::assertNothingSentTo($staff);
});

it('sends NewOrderReceived when globally enabled and staff has not opted out', function () {
    setGlobal('staff_new_order_email', true);

    $staff = User::factory()->create([
        'staff_preferences' => ['notifications' => ['new_order' => ['email' => true]]],
    ]);
    $order = Order::factory()->create();

    $staff->notify(new NewOrderReceived($order));

    Notification::assertSentTo($staff, NewOrderReceived::class);
});

it('suppresses NewOrderReceived when globally enabled but staff opted out personally', function () {
    setGlobal('staff_new_order_email', true);

    $staff = User::factory()->create([
        'staff_preferences' => ['notifications' => ['new_order' => ['email' => false]]],
    ]);
    $order = Order::factory()->create();

    $staff->notify(new NewOrderReceived($order));

    Notification::assertNothingSentTo($staff);
});

it('suppresses NewQuoteRequested when globally disabled', function () {
    setGlobal('staff_new_quote_email', false);
    setGlobal('staff_new_quote_inapp', false);

    $staff = User::factory()->create();
    $quote = Quote::factory()->create();

    $staff->notify(new NewQuoteRequested($quote));

    Notification::assertNothingSentTo($staff);
});

it('suppresses QuoteDecisionReceived when globally disabled', function () {
    setGlobal('staff_quote_decision_email', false);

    $staff = User::factory()->create();
    $quote = Quote::factory()->create();

    $staff->notify(new QuoteDecisionReceived($quote));

    Notification::assertNothingSentTo($staff);
});
