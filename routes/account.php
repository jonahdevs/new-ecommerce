<?php

use App\Http\Controllers\Account\DataExportController;
use App\Models\Order;
use App\Models\Quote;
use App\Services\QuotePdfService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ---------------------------------------------------------------------------
// Customer self-service (authenticated + verified). The `customer` middleware
// keeps staff out — they are redirected to the admin dashboard.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::livewire('account', 'pages::account.dashboard')->name('account.dashboard');
    Route::livewire('account/orders', 'pages::account.orders.index')->name('account.orders.index');
    Route::livewire('account/orders/{order}', 'pages::account.orders.show')->name('account.orders.show');
    Route::livewire('account/orders/{order}/tracking', 'pages::account.orders.tracking')->name('account.orders.tracking');
    Route::get('account/orders/{order}/receipt', function (Order $order) {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->kra_receipt_path && Storage::disk('local')->exists($order->kra_receipt_path), 404);

        return Storage::disk('local')->response(
            $order->kra_receipt_path,
            $order->order_number.'-receipt.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    })->name('account.orders.receipt');
    Route::livewire('account/quotes', 'pages::account.quotes.index')->name('account.quotes.index');
    Route::livewire('account/quotes/{quote}', 'pages::account.quotes.show')->name('account.quotes.show');
    Route::get('account/quotes/{quote}/download', function (Quote $quote) {
        abort_unless($quote->user_id === auth()->id(), 403);

        return app(QuotePdfService::class)->download($quote)
            ?? abort(404, 'Quote document not yet available.');
    })->name('account.quotes.download');
    Route::livewire('account/addresses', 'pages::account.addresses.index')->name('account.addresses.index');
    Route::livewire('account/reviews', 'pages::account.reviews')->name('account.reviews');
    Route::livewire('account/reviews/{product:slug}', 'pages::account.review-form')->name('account.reviews.form');
    Route::livewire('account/recently-viewed', 'pages::account.recently-viewed')->name('account.recently-viewed');
    Route::get('account/data/export', DataExportController::class)->name('account.data.export');
});

// ---------------------------------------------------------------------------
// Settings — URLs live under /account/settings/* but route names are kept
// short (profile.edit / security.edit / appearance.edit) so existing layout
// and component references don't need to change.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'customer'])->group(function () {
    Route::redirect('account/settings', 'account/settings/profile');

    Route::livewire('account/settings/profile', 'pages::account.settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::livewire('account/settings/notifications', 'pages::account.settings.notifications')->name('notifications.edit');
    Route::livewire('account/settings/appearance', 'pages::account.settings.appearance')->name('appearance.edit');
    Route::livewire('account/settings/privacy', 'pages::account.settings.privacy')->name('privacy.edit');

    Route::livewire('account/settings/security', 'pages::account.settings.security')
        ->middleware(['password.confirm'])
        ->name('security.edit');
});
