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
        private readonly CartService     $cartService,
        private readonly CheckoutSession $checkoutSession,
        private readonly PaymentService  $paymentService,
    ) {}

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
        $existingOrder = Order::where('user_id', $user->id)
            ->where('status', OrdersStatus::PENDING)
            ->where('payment_status', EnumsPaymentStatus::FAILED)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existingOrder) {
            Log::info('Resuming existing pending order', [
                'order_id'  => $existingOrder->id,
                'reference' => $existingOrder->reference,
            ]);

            return $this->paymentService->initiate($existingOrder, $existingOrder->payment);
        }

        // Resolve address
        $addressId = $this->checkoutSession->getAddressId()
            ?? $user->addresses()->where('is_default', true)->value('id')
            ?? $user->addresses()->oldest()->value('id');

        $address     = Address::with(['county', 'area', 'shippingZone'])->findOrFail($addressId);
        $cartItems   = $cart->items()->with('product.brand')->get();
        $cartSummary = $this->cartService->summary($cart);
        $shippingData = $this->checkoutSession->getShipping();

        $subtotalCents = (int) round($cartSummary['subtotal'] * 100);
        $discountCents = (int) round($cartSummary['discount'] * 100);
        $shippingCents = (int) round($shippingData['cost'] * 100);
        $totalCents    = max(0, $subtotalCents - $discountCents + $shippingCents);

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
            // Create Order
            $order = Order::create([
                'user_id'           => $user->id,
                'reference'         => $this->generateReference(),
                'status'            => OrdersStatus::PENDING,
                'payment_status'    => 'pending',
                'currency'          => 'KES',
                'subtotal_cents'    => $subtotalCents,
                'discount_cents'    => $discountCents,
                'shipping_cents'    => $shippingCents,
                'tax_cents'         => 0,
                'total_cents'       => $totalCents,
                'shipping_address'  => $this->snapshotAddress($address),
                'billing_address'   => $this->snapshotAddress($address),
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

            $order->statusHistories()->create([
                'from_status'        => null,
                'to_status'          => OrdersStatus::PENDING->value,
                'changed_by_user_id' => auth()->id(),
                'changed_by_type'    => 'user',
                'notes'              => 'Order placed by customer',
            ]);

            // Fix 1 — single loop: validate + create + decrement under one lock
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product->stock_quantity < $item->quantity) {
                    throw new \RuntimeException(
                        "{$product->name} only has {$product->stock_quantity} units available."
                    );
                }

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

                $product->decrement('stock_quantity', $item->quantity);
            }

            // Fix 2 — store payment_method in meta at creation time
            Payment::create([
                'order_id'     => $order->id,
                'amount_cents' => $totalCents,
                'currency'     => 'KES',
                'status'       => 'pending',
                'gateway'      => $this->paymentService->activeGateway(),
                'expires_at'   => now()->addMinutes(30),
                'meta'         => [
                    'payment_method' => $this->checkoutSession->getPaymentMethod() ?? 'card',
                ],
            ]);

            return $order;
        });

        // Initiate payment outside transaction — external API call
        try {
            $payment  = $order->payment;
            $response = $this->paymentService->initiate($order, $payment);

            if ($response->isFailed()) {
                $order->transitionTo(
                    OrdersStatus::CANCELLED,
                    notes: 'Payment initiation failed: ' . $response->message,
                    changedByType: 'system'
                );
                $order->update(['payment_status' => 'failed']);

                foreach ($order->items()->with('product')->get() as $item) {
                    $item->product?->increment('stock_quantity', $item->quantity);
                }

                Log::error('Payment initiation failed after order created', [
                    'order_id' => $order->id,
                    'message'  => $response->message,
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::error('Payment initiation threw exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    private function generateReference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('reference', $reference)->exists());

        return $reference;
    }

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
