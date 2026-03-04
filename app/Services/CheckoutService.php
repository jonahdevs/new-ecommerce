<?php

namespace App\Services;

use App\Enums\OrdersStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Payment\PaymentService;
use App\Services\Payment\ValueObjects\PaymentResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * The main checkout entry point.
     *
     * Creates: Order → OrderItems → Payment + shipping_snapshot
     * Then initiates payment and returns a PaymentResponse.
     *
     * DeliveryOrder is NOT created here — it is created when
     * admin processes the order after payment is confirmed.
     *
     * Cart is NOT cleared here — it is cleared in each gateway's
     * markPaid() after payment is confirmed via webhook.
     */
    public function initiateCheckout(): PaymentResponse
    {
        $user = auth()->user();
        $cart = $this->cartService->getCart();

        // ── Pre-flight checks ─

        if (!$cart || !$cart->items()->exists()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        if ($user->addresses()->doesntExist()) {
            throw new \RuntimeException('Please add a shipping address to continue.');
        }

        if (!$this->checkoutSession->isComplete()) {
            throw new \RuntimeException('Shipping not selected. Please select a shipping method.');
        }

        // ── Check for existing pending order 
        // If customer cancelled mid-payment and tries again within 30 minutes,
        // resume the existing order instead of creating a duplicate.

        $existingOrder = Order::where('user_id', $user->id)
            ->where('status', OrdersStatus::PENDING)
            ->where('payment_status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existingOrder) {
            Log::info('Resuming existing pending order', [
                'order_id'  => $existingOrder->id,
                'reference' => $existingOrder->reference,
            ]);

            $payment  = $existingOrder->payment;
            $response = $this->paymentService->initiate($existingOrder, $payment);
            return $response;
        }

        //  Resolve address 

        $addressId = $this->checkoutSession->getAddressId()
            ?? $user->addresses()->where('is_default', true)->value('id')
            ?? $user->addresses()->oldest()->value('id');

        $address = Address::with(['county', 'area', 'shippingZone'])->findOrFail($addressId);

        //  Build cart summary 

        $cartItems    = $cart->items()->with('product.brand')->get();
        $cartSummary  = $this->cartService->summary($cart);
        $shippingData = $this->checkoutSession->getShipping();

        $subtotalCents = (int) round($cartSummary['subtotal'] * 100);
        $discountCents = (int) round($cartSummary['discount'] * 100);
        $shippingCents = (int) round($shippingData['cost'] * 100);
        $totalCents    = max(0, $subtotalCents - $discountCents + $shippingCents);

        //  Create everything in a transaction 

        $order = DB::transaction(function () use (
            $user,
            $cartItems,
            $cart,
            $address,
            $subtotalCents,
            $discountCents,
            $shippingCents,
            $totalCents,
            $shippingData
        ) {
            // 1. Validate + lock stock for all items before committing
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product->stock_quantity < $item->quantity) {
                    throw new \RuntimeException(
                        "{$product->name} only has {$product->stock_quantity} units available."
                    );
                }
            }

            // 2. Create the Order
            $order = Order::create([
                'user_id'          => $user->id,
                'reference'        => $this->generateReference(),
                'status'           => OrdersStatus::PENDING,
                'payment_status'   => 'pending',
                'currency'         => 'KES',
                'subtotal_cents'   => $subtotalCents,
                'discount_cents'   => $discountCents,
                'shipping_cents'   => $shippingCents,
                'tax_cents'        => 0,
                'total_cents'      => $totalCents,
                'shipping_address' => $this->snapshotAddress($address),
                'billing_address'  => $this->snapshotAddress($address),
                'shipping_snapshot' => [
                    'method_id'       => $shippingData['method_id'],
                    'method_name'     => $shippingData['method_name'],
                    'method_code'     => $shippingData['method_code'],
                    'method_type'     => $shippingData['method_type'],
                    'zone_id'         => $shippingData['zone_id'],
                    'rate_id'         => $shippingData['rate_id'],
                    'station_id'      => $shippingData['station_id'],
                    'station_name'    => $shippingData['station_name'],
                    'cost'            => $shippingData['cost'],
                    'cost_breakdown'  => $shippingData['cost_breakdown'],
                    'delivery_window' => $shippingData['delivery_window'],
                    'weight_kg'       => $this->cartService->getWeight($cart),
                ],
                'expires_at' => now()->addMinutes(30),
            ]);

            // 3. Create OrderItems + decrement stock
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                $order->items()->create([
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'quantity'           => $item->quantity,
                    'unit_price_cents'   => (int) round($item->product->final_price * 100),
                    'unit_tax_cents'     => 0,
                    'discount_cents'     => (int) round(
                        ($item->product->price - $item->product->final_price) * 100 * $item->quantity
                    ),
                    'total_cents'        => (int) round($item->product->final_price * 100 * $item->quantity),
                    'product_snapshot'   => [
                        'id'          => $item->product->id,
                        'name'        => $item->product->name,
                        'sku'         => $item->product->sku,
                        'slug'        => $item->product->slug,
                        'image_path'  => $item->product->image_path,
                        'price'       => $item->product->price,
                        'sale_price'  => $item->product->sale_price,
                        'final_price' => $item->product->final_price,
                        'weight_kg'   => $item->product->weight ?? 0.5,
                        'brand'       => $item->product->brand?->name,
                    ],
                ]);

                // Decrement stock using the locked product instance
                $product->decrement('stock_quantity', $item->quantity);
            }

            // 4. Create Payment record
            Payment::create([
                'order_id'     => $order->id,
                'amount_cents' => $totalCents,
                'currency'     => 'KES',
                'status'       => 'pending',
                'gateway'      => $this->paymentService->activeGateway(),
                'expires_at'   => now()->addMinutes(30),
            ]);

            return $order;
        });

        //  Initiate payment (outside transaction — external API call) 

        try {
            $payment  = $order->payment;
            $response = $this->paymentService->initiate($order, $payment);

            if ($response->isFailed()) {
                // Cancel order and restore stock
                $order->transitionTo(
                    OrdersStatus::CANCELLED,
                    notes: 'Payment initiation failed: ' . $response->message,
                    changedByType: 'system'
                );
                $order->update(['payment_status' => 'failed']);

                // Restore stock
                foreach ($order->items()->with('product')->get() as $item) {
                    $item->product?->increment('stock_quantity', $item->quantity);
                }

                Log::error('Payment initiation failed after order created', [
                    'order_id' => $order->id,
                    'message'  => $response->message,
                ]);
            }

            // Note: Cart and session are NOT cleared here.
            // They are cleared in each gateway's markPaid() after
            // payment is confirmed via webhook/callback.

            return $response;
        } catch (\Throwable $e) {
            Log::error('Payment initiation threw exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    // Private helpers 

    private function generateReference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Snapshot the address at order placement time.
     * Stored as JSON so it's immutable even if the address is later edited.
     */
    private function snapshotAddress(Address $address): array
    {
        return [
            'first_name'   => $address->first_name,
            'last_name'    => $address->last_name,
            'full_name'    => $address->full_name,
            'phone_number' => $address->phone_number,
            'address'      => $address->address,
            'area'         => $address->area?->name,
            'county'       => $address->county?->name,
            'zone'         => $address->shippingZone?->name,
        ];
    }
}
