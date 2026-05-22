<?php

use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteReceivedNotification;
use App\Notifications\QuoteRequestedNotification;
use App\Services\QuotationService;
use App\Settings\CustomerNotificationSettings;
use App\Settings\NotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function setQuoteNotifSettings(array $overrides = []): void
{
    $defaults = [
        'notify_new_order' => true,
        'notify_low_stock' => false,
        'notify_new_review' => false,
        'notify_new_user' => false,
        'notify_failed_payment' => false,
        'notify_out_of_stock' => false,
        'notify_new_quote' => true,
        'notify_quote_accepted' => true,
        'notify_quote_rejected' => true,
        'email_notifications_enabled' => true,
        'sms_notifications_enabled' => false,
        'push_notifications_enabled' => false,
        'admin_notification_email' => 'admin@test.com',
    ];

    app(NotificationSettings::class)->fill(array_merge($defaults, $overrides))->save();
}

// ─── Notification dispatch ─────────────────────────────────────────────────────

it('sends QuoteRequestedNotification to all staff users when enabled', function () {
    Notification::fake();
    setQuoteNotifSettings(['notify_new_quote' => true]);

    $staff = User::factory()->create(['is_staff' => true, 'notification_preferences' => ['notify_new_quote' => true]]);
    $quote = Quote::factory()->pending()->create();

    app(QuotationService::class)->notifyRequested($quote);

    Notification::assertSentTo($staff, QuoteRequestedNotification::class);
});

it('does not send QuoteRequestedNotification when setting is disabled', function () {
    Notification::fake();
    setQuoteNotifSettings(['notify_new_quote' => false]);

    $staff = User::factory()->create(['is_staff' => true]);
    $quote = Quote::factory()->pending()->create();

    app(QuotationService::class)->notifyRequested($quote);

    Notification::assertNothingSent();
});

it('does not send notification when no staff users exist', function () {
    Notification::fake();
    setQuoteNotifSettings(['notify_new_quote' => true]);

    $quote = Quote::factory()->pending()->create();

    app(QuotationService::class)->notifyRequested($quote);

    Notification::assertNothingSent();
});

it('sends to multiple staff users', function () {
    Notification::fake();
    setQuoteNotifSettings(['notify_new_quote' => true]);

    $staff1 = User::factory()->create(['is_staff' => true, 'notification_preferences' => ['notify_new_quote' => true]]);
    $staff2 = User::factory()->create(['is_staff' => true, 'notification_preferences' => ['notify_new_quote' => true]]);
    $customer = User::factory()->create(['is_staff' => false]);
    $quote = Quote::factory()->pending()->create();

    app(QuotationService::class)->notifyRequested($quote);

    Notification::assertSentTo($staff1, QuoteRequestedNotification::class);
    Notification::assertSentTo($staff2, QuoteRequestedNotification::class);
    Notification::assertNotSentTo($customer, QuoteRequestedNotification::class);
});

// ─── Notification channels ─────────────────────────────────────────────────────

it('always includes database channel for staff users', function () {
    $staff = User::factory()->create(['is_staff' => true, 'notification_preferences' => ['notify_new_quote' => false]]);
    $quote = Quote::factory()->pending()->create();

    $channels = (new QuoteRequestedNotification($quote))->via($staff);

    expect($channels)->toContain('database');
});

it('includes mail channel when staff user has notify_new_quote preference enabled', function () {
    $staff = User::factory()->create(['is_staff' => true, 'notification_preferences' => ['notify_new_quote' => true]]);
    $quote = Quote::factory()->pending()->create();

    $channels = (new QuoteRequestedNotification($quote))->via($staff);

    expect($channels)->toContain('database')->toContain('mail');
});

it('excludes mail channel when staff user has notify_new_quote preference disabled', function () {
    $staff = User::factory()->create(['is_staff' => true, 'notification_preferences' => ['notify_new_quote' => false]]);
    $quote = Quote::factory()->pending()->create();

    $channels = (new QuoteRequestedNotification($quote))->via($staff);

    expect($channels)->toContain('database')->not->toContain('mail');
});

// ─── Payload ──────────────────────────────────────────────────────────────────

it('notification toArray includes expected keys', function () {
    $quote = Quote::factory()->pending()->create(['delivery_type' => 'pickup']);

    $data = (new QuoteRequestedNotification($quote))->toArray();

    expect($data)
        ->toHaveKey('quote_id', $quote->id)
        ->toHaveKey('reference', $quote->reference)
        ->toHaveKey('title')
        ->toHaveKey('message')
        ->toHaveKey('url');
});

// ─── QuoteReceivedNotification ────────────────────────────────────────────────

function setCustomerQuoteNotifSettings(array $overrides = []): void
{
    $defaults = [
        'order_confirmation' => true,
        'order_updates' => true,
        'abandoned_cart' => false,
        'abandoned_cart_delay' => 1,
        'review_request' => false,
        'review_request_delay' => 3,
        'quote_received' => true,
        'quote_sent' => true,
        'quote_expiring_reminder' => true,
        'quote_expiring_days' => 2,
    ];

    app(CustomerNotificationSettings::class)->fill(array_merge($defaults, $overrides))->save();
}

it('sends QuoteReceivedNotification to authenticated customer when enabled', function () {
    Notification::fake();
    setCustomerQuoteNotifSettings(['quote_received' => true]);

    $customer = User::factory()->create([
        'is_staff' => false,
        'notification_preferences' => ['quote_received' => ['email' => true]],
    ]);
    $quote = Quote::factory()->pending()->create(['user_id' => $customer->id]);

    app(QuotationService::class)->notifyCustomerReceived($quote);

    Notification::assertSentTo($customer, QuoteReceivedNotification::class);
});

it('does not send QuoteReceivedNotification when system setting is disabled', function () {
    Notification::fake();
    setCustomerQuoteNotifSettings(['quote_received' => false]);

    $customer = User::factory()->create(['is_staff' => false]);
    $quote = Quote::factory()->pending()->create(['user_id' => $customer->id]);

    app(QuotationService::class)->notifyCustomerReceived($quote);

    Notification::assertNothingSent();
});

it('includes mail when user has quote_received preference enabled', function () {
    $customer = User::factory()->create([
        'notification_preferences' => ['quote_received' => ['email' => true]],
    ]);
    $quote = Quote::factory()->pending()->create();

    $channels = (new QuoteReceivedNotification($quote))->via($customer);

    expect($channels)->toContain('mail');
});

it('excludes mail when user has quote_received preference disabled', function () {
    $customer = User::factory()->create([
        'notification_preferences' => ['quote_received' => ['email' => false]],
    ]);
    $quote = Quote::factory()->pending()->create();

    $channels = (new QuoteReceivedNotification($quote))->via($customer);

    expect($channels)->not->toContain('mail');
});

it('always sends mail for guest (anonymous notifiable)', function () {
    $quote = Quote::factory()->pending()->create(['user_id' => null, 'guest_info' => ['name' => 'Guest', 'email' => 'guest@test.com', 'phone' => '0700000000']]);
    $notifiable = Notification::route('mail', 'guest@test.com');

    $channels = (new QuoteReceivedNotification($quote))->via($notifiable);

    expect($channels)->toContain('mail');
});

it('QuoteReceivedNotification toArray includes expected keys', function () {
    $quote = Quote::factory()->pending()->create(['delivery_type' => 'delivery']);

    $data = (new QuoteReceivedNotification($quote))->toArray();

    expect($data)
        ->toHaveKey('quote_id', $quote->id)
        ->toHaveKey('reference', $quote->reference)
        ->toHaveKey('title')
        ->toHaveKey('message')
        ->toHaveKey('url');
});
