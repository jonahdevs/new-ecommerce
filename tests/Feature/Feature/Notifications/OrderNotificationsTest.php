<?php

use App\Enums\OrderStatus;
use App\Events\PaymentConfirmed;
use App\Listeners\SendOrderConfirmationToCustomer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\OrderStatusNotification;
use App\Notifications\RefundProcessedNotification;
use App\Settings\CustomerNotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function setCustomerNotifSettings(array $overrides = []): void
{
    $defaults = [
        'order_confirmation' => true,
        'order_processing' => true,
        'order_shipped' => true,
        'order_delivered' => true,
        'order_cancelled' => true,
        'order_refunded' => true,
        'abandoned_cart' => false,
        'abandoned_cart_delay' => 1,
        'review_request' => false,
        'review_request_delay' => 3,
        'quote_sent' => true,
        'quote_expiring_reminder' => true,
        'quote_expiring_days' => 2,
    ];

    app(CustomerNotificationSettings::class)->fill(array_merge($defaults, $overrides))->save();
}

// ─── OrderConfirmedNotification ───────────────────────────────────────────────

it('sends order confirmation when system setting is enabled', function () {
    Notification::fake();
    setCustomerNotifSettings(['order_confirmation' => true]);

    $user = User::factory()->create(['notification_preferences' => ['order_confirmations' => ['email' => true]]]);
    $order = Order::factory()->for($user)->create();

    app(SendOrderConfirmationToCustomer::class)->handle(new PaymentConfirmed($order));

    Notification::assertSentTo($user, OrderConfirmedNotification::class);
});

it('does not send order confirmation when system setting is disabled', function () {
    Notification::fake();
    setCustomerNotifSettings(['order_confirmation' => false]);

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    app(SendOrderConfirmationToCustomer::class)->handle(new PaymentConfirmed($order));

    Notification::assertNotSentTo($user, OrderConfirmedNotification::class);
});

it('excludes mail from order confirmation when user disables email preference', function () {
    setCustomerNotifSettings(['order_confirmation' => true]);

    $user = User::factory()->create(['notification_preferences' => ['order_confirmations' => ['email' => false]]]);
    $order = Order::factory()->for($user)->create();

    $channels = (new OrderConfirmedNotification($order))->via($user);

    expect($channels)->not->toContain('mail');
});

it('includes mail in order confirmation when user has email enabled', function () {
    setCustomerNotifSettings(['order_confirmation' => true]);

    $user = User::factory()->create(['notification_preferences' => ['order_confirmations' => ['email' => true]]]);
    $order = Order::factory()->for($user)->create();

    $channels = (new OrderConfirmedNotification($order))->via($user);

    expect($channels)->toContain('mail');
});

// ─── OrderStatusNotification ──────────────────────────────────────────────────

it('includes mail and database for shipped when both system and user enabled', function () {
    setCustomerNotifSettings(['order_shipped' => true]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => true]]]);
    $order = Order::factory()->for($user)->create();

    $channels = (new OrderStatusNotification($order, OrderStatus::SHIPPED))->via($user);

    expect($channels)->toContain('mail')->toContain('database');
});

it('excludes mail for shipped when system setting is disabled', function () {
    setCustomerNotifSettings(['order_shipped' => false]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => true]]]);
    $order = Order::factory()->for($user)->create();

    $channels = (new OrderStatusNotification($order, OrderStatus::SHIPPED))->via($user);

    expect($channels)->not->toContain('mail')->toContain('database');
});

it('excludes mail for order status when user disables email', function () {
    setCustomerNotifSettings(['order_shipped' => true]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => false]]]);
    $order = Order::factory()->for($user)->create();

    $channels = (new OrderStatusNotification($order, OrderStatus::SHIPPED))->via($user);

    expect($channels)->not->toContain('mail')->toContain('database');
});

it('always includes database channel regardless of settings', function () {
    setCustomerNotifSettings(['order_cancelled' => false]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => false]]]);
    $order = Order::factory()->for($user)->create();

    $channels = (new OrderStatusNotification($order, OrderStatus::CANCELLED))->via($user);

    expect($channels)->toContain('database');
});

it('checks the correct system setting for each order status', function (OrderStatus $status, string $settingKey) {
    setCustomerNotifSettings([$settingKey => false]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => true]]]);
    $order = Order::factory()->for($user)->create();

    $channels = (new OrderStatusNotification($order, $status))->via($user);

    expect($channels)->not->toContain('mail');
})->with([
    'processing' => [OrderStatus::PROCESSING, 'order_processing'],
    'shipped' => [OrderStatus::SHIPPED, 'order_shipped'],
    'delivered' => [OrderStatus::DELIVERED, 'order_delivered'],
    'cancelled' => [OrderStatus::CANCELLED, 'order_cancelled'],
    'returned' => [OrderStatus::RETURNED, 'order_refunded'],
]);

// ─── RefundProcessedNotification ─────────────────────────────────────────────

it('includes mail for refund when system and user both enabled', function () {
    setCustomerNotifSettings(['order_refunded' => true]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => true]]]);
    $order = Order::factory()->for($user)->create();
    $payment = Payment::create(['order_id' => $order->id, 'amount_cents' => 50000, 'currency' => 'KES', 'status' => 'paid', 'gateway' => 'stripe']);

    $channels = (new RefundProcessedNotification($order, $payment, 500.00, 'Customer request'))->via($user);

    expect($channels)->toContain('mail')->toContain('database');
});

it('excludes mail for refund when system setting is disabled', function () {
    setCustomerNotifSettings(['order_refunded' => false]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => true]]]);
    $order = Order::factory()->for($user)->create();
    $payment = Payment::create(['order_id' => $order->id, 'amount_cents' => 50000, 'currency' => 'KES', 'status' => 'paid', 'gateway' => 'stripe']);

    $channels = (new RefundProcessedNotification($order, $payment, 500.00, 'Customer request'))->via($user);

    expect($channels)->not->toContain('mail')->toContain('database');
});

it('excludes mail for refund when user disables email preference', function () {
    setCustomerNotifSettings(['order_refunded' => true]);

    $user = User::factory()->create(['notification_preferences' => ['order_updates' => ['email' => false]]]);
    $order = Order::factory()->for($user)->create();
    $payment = Payment::create(['order_id' => $order->id, 'amount_cents' => 50000, 'currency' => 'KES', 'status' => 'paid', 'gateway' => 'stripe']);

    $channels = (new RefundProcessedNotification($order, $payment, 500.00, 'Customer request'))->via($user);

    expect($channels)->not->toContain('mail')->toContain('database');
});
