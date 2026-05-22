<?php

use App\Enums\OrderStatus;
use App\Http\Controllers\Admin\AdminQuotationPdfController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Orders\OrderReceiptController;
use App\Http\Controllers\Orders\PackingSlipController;
use App\Http\Controllers\Orders\QuotationPdfController;
use App\Http\Controllers\Payment\CallbackController;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPdf\Facades\Pdf;

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

Route::livewire('/', 'pages::home')->name('home');

// ----------------------------------------------------------------------------
// Legal Pages
// ----------------------------------------------------------------------------

Route::view('/terms-of-service', 'pages.legal.terms')->name('terms');
Route::view('/privacy-policy', 'pages.legal.privacy')->name('privacy');

// ----------------------------------------------------------------------------
// Catalog — Shop, Categories, Products
// ----------------------------------------------------------------------------

Route::prefix('shop')->name('shop.')->group(function () {
    Route::livewire('/', 'pages::shop')->name('index');
    Route::livewire('/category/{category:slug}', 'pages::category-products')->name('category');
});

Route::prefix('products')->name('products.')->group(function () {
    Route::livewire('/compare', 'pages::product-compare')->name('compare');
    Route::livewire('/{product:slug}/reviews', 'pages::product-reviews')->name('reviews');
    Route::livewire('/{product:slug}', 'pages::product-details')->name('show');
});

// ----------------------------------------------------------------------------
// Wishlist & Cart
// ----------------------------------------------------------------------------

Route::livewire('/wishlist', 'pages::wishlist')->name('wishlist');
Route::livewire('/cart', 'pages::cart')->name('cart');
Route::livewire('/quote', 'pages::quote')->name('quote');

// ----------------------------------------------------------------------------
// Social Auth
// ----------------------------------------------------------------------------

Route::middleware('guest')->controller(SocialiteController::class)->prefix('auth')->name('socialite.')->group(function () {
    Route::get('/{provider}/redirect', 'redirect')->name('redirect')->where('provider', 'google|facebook');

    Route::get('/{provider}/callback', 'callback')->name('callback')->where('provider', 'google|facebook');
});

// ----------------------------------------------------------------------------
// Payment Callbacks (gateway redirects back to site)
// ----------------------------------------------------------------------------

Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/callback/success', [CallbackController::class, 'success'])->name('callback.success');
    Route::get('/callback/cancel', [CallbackController::class, 'cancel'])->name('callback.cancel');
});

// ============================================================================
// CHECKOUT — Authenticated customers only
// ============================================================================

// Payment pages — auth + customer only (no cart_not_empty check, order already exists)
Route::middleware(['auth', 'customer'])
    ->prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        Route::livewire('/pay/{order}', 'pages::checkout.pay')->name('pay');
        Route::livewire('/quote-pay/{order}', 'pages::checkout.quote-pay')->name('quote-pay');
    });

// Checkout flow — auth + customer + must have items in cart
Route::middleware(['auth', 'customer', 'cart_not_empty'])->prefix('checkout')->name('checkout.')->group(function () {
    Route::livewire('/shipping', 'pages::checkout.shipping')->name('shipping');
    Route::livewire('/summary', 'pages::checkout.summary')->name('summary');
    Route::livewire('/payment-methods', 'pages::checkout.payment-methods')->name('payment-methods');

    Route::prefix('addresses')->name('addresses.')->group(function () {
        Route::livewire('/', 'pages::checkout.address.index')->name('index');
        Route::livewire('/create', 'pages::checkout.address.create')->name('create');
        Route::livewire('/{address}/edit', 'pages::checkout.address.edit')->name('edit');
    });
});

Route::middleware(['auth', 'customer'])
    ->prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        Route::livewire('/quote-success/{reference}', 'pages::checkout.quote-success')->name('quote-success');
    });

// ============================================================================
// CUSTOMER PORTAL — Authenticated, verified customers
// ============================================================================

Route::middleware(['auth', 'customer', 'verified'])->name('customer.')->group(function () {
    Route::livewire('/account', 'pages::customer.account')->name('account');
    Route::livewire('/recently-viewed', 'pages::customer.recently-viewed')->name('recently-viewed');
    Route::livewire('/pending-reviews', 'pages::customer.pending-reviews')->name('pending-reviews');
    Route::livewire('/inbox', 'pages::customer.inbox')->name('inbox');

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::livewire('/', 'pages::customer.orders.index')->name('index');
        Route::livewire('/{order}', 'pages::customer.orders.show')->name('show');
        Route::livewire('/{order}/confirmation', 'pages::customer.orders.confirmation')->name('confirmation');
        Route::livewire('/{order}/tracking', 'pages::customer.orders.tracking')->name('tracking');
        Route::get('/{order}/receipt', OrderReceiptController::class)->name('receipt');
    });

    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::livewire('/', 'pages::customer.quotations.index')->name('index');
        Route::livewire('/{quote}', 'pages::customer.quotations.show')->name('show');
        Route::get('/{quote}/pdf', QuotationPdfController::class)->name('pdf');
    });

    // Address Book
    Route::prefix('address-book')->name('address-book.')->group(function () {
        Route::livewire('/', 'pages::customer.address-book.index')->name('index');
        Route::livewire('/create', 'pages::customer.address-book.create')->name('create');
        Route::livewire('/{address}/edit', 'pages::customer.address-book.edit')->name('edit');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::livewire('/', 'pages::customer.settings.profile')->name('profile');
        Route::livewire('/security', 'pages::customer.settings.security')->name('security');
        Route::livewire('/notifications', 'pages::customer.settings.notifications')->name('notifications');
        Route::livewire('/privacy', 'pages::customer.settings.privacy')->name('privacy');
    });
});

// ============================================================================
// ADMIN PANEL — Authenticated, verified staff only
// ============================================================================

Route::middleware(['auth', 'staff', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('notifications', 'pages::admin.notifications.index')->name('notifications');
    Route::livewire('coming-soon', 'pages::admin.coming-soon')->name('coming-soon');

    // --------------------------------------------------------------------
    // System
    // --------------------------------------------------------------------

    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::livewire('/', 'pages::admin.activity-logs.index')->name('index');
    });

    // --------------------------------------------------------------------
    // Changelog
    // --------------------------------------------------------------------

    Route::livewire('/changelog/{modelType}/{id}', 'pages::admin.changelog.model-changelog')->name('changelog');

    // --------------------------------------------------------------------
    // Sales
    // --------------------------------------------------------------------

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::livewire('/', 'pages::admin.sales.orders.index')->name('index');
        Route::livewire('/create', 'pages::admin.sales.orders.create')->name('create');
        Route::livewire('/{order}', 'pages::admin.sales.orders.show')->name('show');
        Route::get('/{order}/packing-slip', PackingSlipController::class)->name('packing-slip');
    });

    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::livewire('/', 'pages::admin.sales.quotations.index')->name('index');
        Route::livewire('/{quote}', 'pages::admin.sales.quotations.show')->name('show');
        Route::get('/{quote}/pdf', AdminQuotationPdfController::class)->name('pdf');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::livewire('/', 'pages::admin.sales.payments.index')->name('index');
        Route::livewire('/{payment}', 'pages::admin.sales.payments.show')->name('show');
    });

    // --------------------------------------------------------------------
    // Catalog
    // --------------------------------------------------------------------

    Route::prefix('catalog')->name('catalog.')->group(function () {

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::livewire('/', 'pages::admin.catalog.categories.index')->name('index');
            Route::livewire('/create', 'pages::admin.catalog.categories.create')->name('create');
            Route::livewire('/{category}/edit', 'pages::admin.catalog.categories.edit')->name('edit');
        });

        Route::prefix('products')->name('products.')->group(function () {
            Route::livewire('/', 'pages::admin.catalog.products.index')->name('index');
            Route::livewire('/create', 'pages::admin.catalog.products.create')->name('create');
            Route::livewire('/{product}/edit', 'pages::admin.catalog.products.edit')->name('edit');
        });

        Route::prefix('brands')->name('brands.')->group(function () {
            Route::livewire('/', 'pages::admin.catalog.brands.index')->name('index');
            Route::livewire('/create', 'pages::admin.catalog.brands.create')->name('create');
            Route::livewire('/{brand}/edit', 'pages::admin.catalog.brands.edit')->name('edit');
        });

        Route::prefix('attributes')->name('attributes.')->group(function () {
            Route::livewire('/', 'pages::admin.catalog.attributes.index')->name('index');
            Route::livewire('/{attribute}/values', 'pages::admin.catalog.attributes.values')->name('values');
        });

        Route::prefix('tags')->name('tags.')->group(function () {
            Route::livewire('/', 'pages::admin.catalog.tags.index')->name('index');
            Route::livewire('/create', 'pages::admin.catalog.tags.create')->name('create');
            Route::livewire('/{tag}/edit', 'pages::admin.catalog.tags.edit')->name('edit');
        });
    });

    // --------------------------------------------------------------------
    // Logistics
    // --------------------------------------------------------------------

    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::livewire('/overview', 'pages::admin.logistics.dashboard')->name('overview');

        Route::prefix('delivery-orders')->name('delivery-orders.')->group(function () {
            Route::livewire('/{deliveryOrder}', 'pages::admin.logistics.delivery-orders.show')->name('show');
        });

        Route::prefix('configuration')->name('configuration.')->group(function () {
            Route::prefix('providers')->name('providers.')->group(function () {
                Route::livewire('/', 'pages::admin.logistics.configuration.providers.index')->name('index');
                Route::livewire('/{logisticsProvider}', 'pages::admin.logistics.configuration.providers.show')->name('show');
            });

            Route::prefix('methods')->name('methods.')->group(function () {
                Route::livewire('/', 'pages::admin.logistics.configuration.methods.index')->name('index');
                Route::livewire('/{shippingMethod}', 'pages::admin.logistics.configuration.methods.show')->name('show');
            });

            Route::prefix('pickup-stations')->name('pickup-stations.')->group(function () {
                Route::livewire('/', 'pages::admin.logistics.configuration.pickup-stations.index')->name('index');
                Route::livewire('/{pickupStation}', 'pages::admin.logistics.configuration.pickup-stations.show')->name('show');
            });

            Route::prefix('zones')->name('zones.')->group(function () {
                Route::livewire('/', 'pages::admin.logistics.configuration.zones.index')->name('index');
                Route::livewire('/{shippingZone}', 'pages::admin.logistics.configuration.zones.show')->name('show');
            });

            Route::livewire('/resolver', 'pages::admin.logistics.configuration.resolver')->name('resolver');

            Route::prefix('locations')->name('locations.')->group(function () {
                Route::prefix('counties')->name('counties.')->group(function () {
                    Route::livewire('/', 'pages::admin.logistics.configuration.locations.counties.index')->name('index');
                    Route::livewire('/{county}', 'pages::admin.logistics.configuration.locations.counties.show')->name('show');
                });

                Route::prefix('sub-counties')->name('sub-counties.')->group(function () {
                    Route::livewire('/', 'pages::admin.logistics.configuration.locations.sub-counties.index')->name('index');
                    Route::livewire('/{subCounty}', 'pages::admin.logistics.configuration.locations.sub-counties.show')->name('show');
                });

                Route::prefix('towns')->name('towns.')->group(function () {
                    Route::livewire('/', 'pages::admin.logistics.configuration.locations.towns.index')->name('index');
                    Route::livewire('/{town}', 'pages::admin.logistics.configuration.locations.towns.show')->name('show');
                });
            });
        });

        Route::prefix('pricing')->name('pricing.')->group(function () {
            Route::livewire('/matrix', 'pages::admin.logistics.pricing.matrix')->name('matrix');

            Route::prefix('surcharges')->name('surcharges.')->group(function () {
                Route::livewire('/', 'pages::admin.logistics.pricing.surcharges.index')->name('index');
                Route::livewire('/{shippingRateAddon}', 'pages::admin.logistics.pricing.surcharges.show')->name('show');
            });

            Route::prefix('free-shipping')->name('free-shipping.')->group(function () {
                Route::livewire('/', 'pages::admin.logistics.pricing.free-shipping.index')->name('index');
                Route::livewire('/{freeShippingRule}', 'pages::admin.logistics.pricing.free-shipping.show')->name('show');
            });
        });

    });

    // --------------------------------------------------------------------
    // Engagement
    // --------------------------------------------------------------------

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::livewire('/', 'pages::admin.engagement.customers.index')->name('index');
        Route::livewire('/create', 'pages::admin.engagement.customers.create')->name('create');
        Route::livewire('/{customer}/edit', 'pages::admin.engagement.customers.edit')->name('edit');
        // Customer show page - coming soon
        Route::get('/{customer}', function () {
            return redirect()->route('admin.coming-soon');
        })->name('show');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::livewire('/', 'pages::admin.engagement.reviews.index')->name('index');
        Route::livewire('/{review}', 'pages::admin.engagement.reviews.show')->name('show');
    });

    // --------------------------------------------------------------------
    // Marketing
    // --------------------------------------------------------------------

    Route::prefix('marketing')->name('marketing.')->group(function () {
        // Campaigns
        Route::get('/campaigns', function () {
            return redirect()->route('admin.coming-soon');
        })->name('campaigns.index');

        // Coupons & Discounts
        Route::get('/coupons', function () {
            return redirect()->route('admin.coming-soon');
        })->name('coupons.index');

        // Newsletter
        Route::get('/newsletter', function () {
            return redirect()->route('admin.coming-soon');
        })->name('newsletter.index');
    });

    // --------------------------------------------------------------------
    // Content Management
    // --------------------------------------------------------------------

    Route::prefix('content')->name('content.')->group(function () {
        // Blog Posts
        Route::get('/blog', function () {
            return redirect()->route('admin.coming-soon');
        })->name('blog.index');

        // FAQ Management
        Route::get('/faq', function () {
            return redirect()->route('admin.coming-soon');
        })->name('faq.index');

        // Pages (About, Contact, Terms, etc.)
        Route::get('/pages', function () {
            return redirect()->route('admin.coming-soon');
        })->name('pages.index');
    });

    // --------------------------------------------------------------------
    // Legacy /reports/* URLs redirect to the dashboard (analytics consolidated into it).
    // --------------------------------------------------------------------

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::redirect('/overview', '/admin/dashboard')->name('overview');
        Route::redirect('/customers', '/admin/dashboard')->name('customers');
        Route::redirect('/inventory', '/admin/dashboard')->name('inventory');
    });

    // --------------------------------------------------------------------
    // Access Control
    // --------------------------------------------------------------------

    Route::prefix('access-control')->name('access-control.')->group(function () {
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::livewire('/', 'pages::admin.access-control.roles.index')->name('index');
            Route::livewire('/{role}/edit', 'pages::admin.access-control.roles.edit')->name('edit');
        });

        Route::livewire('/permissions', 'pages::admin.access-control.permissions.index')->name('permissions');

        Route::prefix('users')->name('users.')->group(function () {
            Route::livewire('/create', 'pages::admin.access-control.users.create')->name('create');
            Route::livewire('/{user}/edit', 'pages::admin.access-control.users.edit')->name('edit');
        });
    });

    // --------------------------------------------------------------------
    // Email Templates
    // --------------------------------------------------------------------

    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::livewire('/', 'pages::admin.email-templates.index')->name('index');
        Route::livewire('/{type}/edit', 'pages::admin.email-templates.edit')->name('edit');
    });
});

// ============================================================================
// DEVELOPMENT ONLY
// ============================================================================

if (app()->isLocal()) {
    Route::get('test-error/{code}', function ($code) {
        abort($code);
    });

    // ── Email previews ────────────────────────────────────────────────────────

    Route::get('dev/mail/order-status/{status?}', function (string $status = 'shipped') {
        $order = Order::with('items', 'payment', 'user')->latest()->first();
        $newStatus = OrderStatus::from($status);

        return view('mails.orders.status-update', [
            'order' => $order,
            'newStatus' => $newStatus,
            'customerName' => $order->user?->name ?? 'Customer',
            'orderUrl' => route('customer.orders.show', $order),
            'subject' => "Order {$newStatus->label()} — {$order->reference}",
        ]);
    })->name('dev.mail.order-status');

    Route::get('dev/mail/order-confirmation', function () {
        $order = Order::with('items.product', 'payment', 'user', 'deliveryOrder.shippingMethod', 'deliveryOrder.pickupStation')
            ->whereNotNull('kra_cu_number')
            ->latest()->first()
            ?? Order::with('items.product', 'payment', 'user', 'deliveryOrder.shippingMethod', 'deliveryOrder.pickupStation')->latest()->first();

        $delivery = $order->deliveryOrder;
        $deliveryWindow = null;
        if ($delivery) {
            $min = $delivery->shippingRate?->estimated_days_min;
            $max = $delivery->shippingRate?->estimated_days_max;
            if ($min && $max) {
                $deliveryWindow = $min === $max ? "{$min} business days" : "{$min}–{$max} business days";
            } elseif ($delivery->estimated_delivery_at) {
                $deliveryWindow = 'By '.$delivery->estimated_delivery_at->format('D, M j');
            }
        }

        $paymentLabel = match ($order->payment?->gateway) {
            'mpesa' => 'M-Pesa',
            'stripe' => 'Card',
            'pesawise' => 'Pesawise',
            'pesapal' => 'Pesapal',
            'paypal' => 'PayPal',
            default => ucfirst($order->payment?->gateway ?? 'Online'),
        };

        return view('mails.orders.confirmation', [
            'order' => $order,
            'customerName' => $order->user?->name ?? 'Customer',
            'orderUrl' => route('customer.orders.show', $order),
            'deliveryWindow' => $deliveryWindow,
            'paymentLabel' => $paymentLabel,
        ]);
    })->name('dev.mail.order-confirmation');

    Route::get('dev/mail/quote-sent', function () {
        $quote = Quote::with('items', 'user')
            ->whereNotNull('quoted_at')->latest()->first()
            ?? Quote::factory()->sent()->withItems(3)->create();
        $quote->load('items', 'user');

        return view('mails.quotes.sent', [
            'quote' => $quote,
            'customerName' => $quote->user?->name ?? 'Customer',
            'portalUrl' => route('customer.quotations.show', $quote),
        ]);
    })->name('dev.mail.quote-sent');

    Route::get('dev/mail/quote-expiring/{days?}', function (int $days = 2) {
        $quote = Quote::with('items', 'user')
            ->whereNotNull('quoted_at')->latest()->first()
            ?? Quote::factory()->sent()->withItems(2)->create();
        $quote->load('items', 'user');

        $urgency = $days <= 1 ? 'expires tomorrow' : "expires in {$days} days";

        return view('mails.quotes.expiring', [
            'quote' => $quote,
            'customerName' => $quote->user?->name ?? 'Customer',
            'daysLeft' => $days,
            'urgency' => $urgency,
            'portalUrl' => route('customer.quotations.show', $quote),
        ]);
    })->name('dev.mail.quote-expiring');

    Route::get('dev/mail/password-reset', function () {
        $user = User::where('is_staff', false)->latest()->first();

        return view('mails.auth.password-reset', [
            'user' => $user,
            'resetUrl' => url('/reset-password/fake-token-for-preview'),
        ]);
    })->name('dev.mail.password-reset');

    Route::get('dev/mail/verify-email', function () {
        $user = User::where('is_staff', false)->latest()->first();

        return view('mails.auth.verify-email', [
            'user' => $user,
            'verificationUrl' => url('/verify-email/fake-id/fake-hash-for-preview'),
        ]);
    })->name('dev.mail.verify-email');

    // Preview the quotation Blade template in the browser (no PDF conversion).
    // Uses the most recent sent quote, or a factory quote if none exists.
    Route::get('dev/quotation-preview', function () {
        $quote = Quote::with('items.product', 'user')
            ->whereNotNull('quoted_at')
            ->latest()
            ->first();

        if (! $quote) {
            $quote = Quote::factory()
                ->sent()
                ->withItems(3)
                ->create();

            $quote->load('items.product', 'user');
        }

        return Pdf::view('pdf.browsershot.quotation', ['quote' => $quote])
            ->format('a4')
            ->footerView('pdf.browsershot.footer', ['order' => null])
            ->margins(0, 0, 40, 0);
    })->name('dev.quotation-preview');

    // Preview the invoice Blade template in the browser (no PDF conversion).
    // Uses the most recent paid order, or a factory order if none exists.
    Route::get('dev/invoice-preview', function () {
        // Try to find an existing order with many items for multi-page testing
        $order = Order::with('items.product', 'payment', 'user')
            ->whereNotNull('kra_cu_number')
            ->whereNotNull('kra_validated_at')
            ->has('items', '>=', 5) // At least 5 items for good testing
            ->latest()
            ->first();

        // If no suitable order exists, create one
        if (! $order) {
            $order = Order::factory()
                ->confirmed()
                ->withItems(25) // Create 25 items to test multi-page layout
                ->withPayment()
                ->create([
                    'kra_cu_number' => 'CU-PREVIEW-'.now()->timestamp,
                    'kra_validated_at' => now(),
                ]);

            $order->load('items.product', 'payment', 'user');
        }

        return Pdf::view('pdf.browsershot.invoice', ['order' => $order])
            ->format('a4')
            ->footerView('pdf.browsershot.footer', ['order' => $order])
            ->margins(0, 0, 40, 0);
    })->name('dev.invoice-preview');
}

// ============================================================================
// ADDITIONAL ROUTE FILES
// ============================================================================

require __DIR__.'/settings.php';
