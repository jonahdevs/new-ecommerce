<?php

namespace App\Services;

use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus as EnumsPaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Payment\PaymentService;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession,
        private readonly PaymentService $paymentService,
    ) {
    }

    // ================================================
    // PATH A - Normal sales order
    //
    // Called when:
    // - Cart has no requires_quotation products
    // - Shipping method_type is flat, distance, or PUS (NOT quote)
    //
    // What it does
    // 1. Runs pre-flight checks (cart, address, shipping selected)
    // 2. Resumes an existing failed-but-not-expired order if one exists (avoids duplicate orders on M-pesa / gateway retries)
    // 3. Wraps everything in a DB transaction
    //  - Creates the Order record (document_type = sales_order)
    //  - Validates + decrements stock per item (row-level lock)
    //  - Creates the Payment record
    // 4. Calls the payment gateway OUTSIDE the transaction (external HTTP calls must never hold DB locks open)
    // 5. On gateway failure - cancels the order and restores stock
    //
    //  Returns a PaymentResponse. The order-summary component decides whether to redirect to the gateway, show the STK modal, etc.
    // ================================================

    public function initiateCheckout(): PaymentResponse
    {
        $user = auth()->user();
        $cart = $this->cartService->getCart();

        // Pre-flight checks
        if (!$cart || !$cart->items()->exists()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        if ($user->addresses()->doesntExist()) {
            throw new \RuntimeException('Please add a shipping address to continue.');
        }

        if (!$this->checkoutSession->isComplete()) {
            throw new \RuntimeException('Shipping not selected. Please select a shipping method.');
        }

        // Resume existing pending order if within expiry window
        //
        //  If the customer previously placed an order but the payment failed and the order hasn't expired yet, we resume it rather than creating a duplicate. Handles M-Pesa timeout retries without double-charging.
        $existingOrder = Order::where('user_id', $user->id)
            ->where('status', OrdersStatus::PENDING)
            ->where('payment_status', EnumsPaymentStatus::FAILED)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existingOrder) {
            Log::info('Resuming existing pending order', [
                'order_id' => $existingOrder->id,
                'reference' => $existingOrder->reference,
            ]);

            return $this->paymentService->initiate($existingOrder, $existingOrder->payment);
        }

        // Resolve delivery address
        //
        // Priority: session address -> default address -> oldest address
        // The session address is set when the customer picks an address on the checkout address step.
        $addressId = $this->checkoutSession->getAddressId()
            ?? $user->addresses()->where('is_default', true)->value('id')
            ?? $user->addresses()->oldest()->value('id');

        $address = Address::with(['county', 'area', 'shippingZone'])->findOrFail($addressId);
        $cartItems = $cart->items()->with('product.brand')->get();
        $cartSummary = $this->cartService->summary($cart);
        $shippingData = $this->checkoutSession->getShipping();

        // All money is stored as integer cents to avoid floating-point errors.
        // round() before casting handles the 0.005 edge case correctly
        $subtotalCents = (int) round($cartSummary['subtotal'] * 100);
        $discountCents = (int) round($cartSummary['discount'] * 100);
        $shippingCents = (int) round($shippingData['cost'] * 100);
        $totalCents = max(0, $subtotalCents - $discountCents + $shippingCents);

        // DB Transaction
        //
        // Order creation, item creation, stock decrement, and payment record
        // All run in a single atomic transaction. Any exception rolls back everything - no orphaned orders, no over-decrement stock.

        $order = DB::transaction(function () use ($user, $cartItems, $cart, $address, $subtotalCents, $discountCents, $shippingCents, $totalCents, $shippingData) {
            // Create the sales order document
            $order = Order::create([
                'user_id' => $user->id,
                'reference' => Order::generateReference('sales_order'),
                'document_type' => 'sales_order',
                'quotation_type' => null, // only set on quotation documents
                'status' => OrdersStatus::PENDING,
                'payment_status' => EnumsPaymentStatus::PENDING,
                'currency' => 'KES',
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'shipping_cents' => $shippingCents,
                'tax_cents' => 0,
                'total_cents' => $totalCents,
                'shipping_address' => $this->snapshotAddress($address),
                'billing_address' => $this->snapshotAddress($address),

                // Snapshot the full shipping selection at order time
                // Rates may change later so we preserve exactly what was shown to the customer at checkout
                'shipping_snapshot' => [
                    'method_id' => $shippingData['method_id'],
                    'method_name' => $shippingData['method_name'],
                    'method_code' => $shippingData['method_code'],
                    'method_type' => $shippingData['method_type'],
                    'zone_id' => $shippingData['zone_id'],
                    'rate_id' => $shippingData['rate_id'],
                    'station_id' => $shippingData['station_id'],
                    'station_name' => $shippingData['station_name'],
                    'cost' => $shippingData['cost'],
                    'cost_breakdown' => $shippingData['cost_breakdown'],
                    'delivery_window' => $shippingData['delivery_window'],
                    'weight_kg' => $this->cartService->getWeight($cart),
                ],

                // Order expires after 30 minutes - prevents stock being held indefinitely if the customer abandons the payment step
                'expires_at' => now()->addMinutes(30),
            ]);

            // Seed the status history audit trail
            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrdersStatus::PENDING->value,
                'changed_by_user_id' => auth()->id(),
                'changed_by_type' => 'user',
                'notes' => 'Order placed by customer',
            ]);


            // Stock validation + item creation
            //
            // lockForUpdate() places a row-level perrimistic lock on each product row. Concurrent checkout on the same product will queue behind each other - only one can pass the quantity check and decrement at a time. Prevents overselling under load.

            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product->stock_quantity < $item->quantity) {
                    throw new \RuntimeException(
                        "{$product->name} only has {$product->stock_quantity} units available."
                    );
                }

                // Snapshot the product at purchase time
                // Name, price, sku, and image may all change after the order is placed - the snapshot preserves what the customer saw
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => (int) round($item->product->final_price * 100),
                    'unit_tax_cents' => 0,
                    'discount_cents' => (int) round(
                        ($item->product->price - $item->product->final_price) * 100 * $item->quantity
                    ),
                    'total_cents' => (int) round($item->product->final_price * 100 * $item->quantity),
                    'product_snapshot' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'sku' => $item->product->sku,
                        'slug' => $item->product->slug,
                        'image_path' => $item->product->image_path,
                        'price' => $item->product->price,
                        'sale_price' => $item->product->sale_price,
                        'final_price' => $item->product->final_price,
                        'weight_kg' => $item->product->weight ?? 0.5,
                        'brand' => $item->product->brand?->name,
                    ],
                ]);

                // Decrement stock only AFTER the order item row is committed
                $product->decrement('stock_quantity', $item->quantity);
            }

            // Create the Payment record so the gatway has something to attach its response to. The actual gateway call happens outside this transaction to avoid holding locks during a slow HTTP call.
            Payment::create([
                'order_id' => $order->id,
                'amount_cents' => $totalCents,
                'currency' => 'KES',
                'status' => EnumsPaymentStatus::PENDING,
                'gateway' => $this->paymentService->activeGateway(),
                'expires_at' => now()->addMinutes(30),
                'meta' => [
                    'payment_method' => $this->checkoutSession->getPaymentMethod() ?? 'card',
                ],
            ]);

            return $order;
        });

        // GATEWAY CALL - intentionally outside the transaction
        //
        // External API calls (Stripe, M-Pesa, Pesawise) must never run inside a transaction - a slow response would hold row locks open for seconds
        // If the gateway fails we cancel the order and restore stock so the customer isn't blocked from retrying again
        try {
            $response = $this->paymentService->initiate($order, $order->payment);

            if ($response->isFailed()) {
                $order->transitionTo(
                    OrdersStatus::CANCELLED,
                    notes: 'Payment initiation failed: ' . $response->message,
                    changedByType: 'system'
                );
                $order->update(['payment_status' => EnumsPaymentStatus::FAILED]);

                // Restore stock for every item on the now-cancelled order
                foreach ($order->items()->with('product')->get() as $item) {
                    $item->product?->increment('stock_quantity', $item->quantity);
                }

                Log::error('Payment initiation failed after order created', [
                    'order_id' => $order->id,
                    'message' => $response->message,
                ]);
            }

            return $response;

        } catch (\Throwable $e) {
            Log::error('Payment initiation threw exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    // ================================================
    // Address snapshot helper
    //
    // Captures address fields at order time as a plain array for JSON storage.
    // This means the delivery address on an order is permanently fixed even if the customer later edits or deletes the address from their book.
    // ================================================

    private function snapshotAddress(Address $address): array
    {
        return [
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'full_name' => $address->full_name,
            'phone_number' => $address->phone_number,
            'address' => $address->address,
            'area' => $address->area?->name,
            'county' => $address->county?->name,
            'zone' => $address->shippingZone?->name,
        ];
    }
}
