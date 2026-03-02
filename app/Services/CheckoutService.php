<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Models\Address;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PickupStation;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\PaymentService;
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
    ) {
    }

    /**
     * The main checkout entry point.
     *
     * Creates: Order → OrderItems → DeliveryOrder → Payment
     * Then initiates payment and returns a PaymentResponse.
     *
     * The Livewire component handles the PaymentResponse to either:
     *   - Redirect (Pesawise/Pesapal)
     *   - Show iframe (Pesawise iframe)
     *   - Show STK push waiting screen (M-Pesa)
     *   - Show Stripe Elements (Card)
     */
    public function initiateCheckout(): PaymentResponse
    {
        $user = auth()->user();
        $cart = $this->cartService->getCart();

        //  Pre-flight checks

        if (!$cart || !$cart->items()->exists()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        if ($user->addresses()->doesntExist()) {
            throw new \RuntimeException('Please add a shipping address to continue.');
        }

        if (!$this->checkoutSession->isComplete()) {
            throw new \RuntimeException('Shipping not selected. Please select a shipping method.');
        }

        //  Resolve address

        $addressId = $this->checkoutSession->getAddressId()
            ?? $user->addresses()->where('is_default', true)->value('id')
            ?? $user->addresses()->oldest()->value('id');

        $address = Address::with(['county', 'area', 'shippingZone'])->findOrFail($addressId);

        //  Build cart summary

        $cartItems = $cart->items()->with('product')->get();
        $cartSummary = $this->cartService->summary($cart);
        $shippingData = $this->checkoutSession->getShipping();

        $subtotalCents = (int) round($cartSummary['subtotal'] * 100);
        $discountCents = (int) round($cartSummary['discount'] * 100);
        $shippingCents = (int) round($shippingData['cost'] * 100);
        $totalCents = max(0, $subtotalCents - $discountCents + $shippingCents);

        //  Create everything in a transaction

        $order = DB::transaction(function () use ($user, $cartItems, $cart, $address, $subtotalCents, $discountCents, $shippingCents, $totalCents, $shippingData) {
            // 1. Validate stock for all items before committing
            foreach ($cartItems as $item) {
                if ($item->product->stock_quantity < $item->quantity) {
                    throw new \RuntimeException(
                        "{$item->product->name} only has {$item->product->stock_quantity} units available."
                    );
                }
            }

            // 2. Create the Order
            $order = Order::create([
                'user_id' => $user->id,
                'reference' => $this->generateReference(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'currency' => 'KES',
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'shipping_cents' => $shippingCents,
                'tax_cents' => 0,
                'total_cents' => $totalCents,
                'shipping_address' => $this->snapshotAddress($address),
                'billing_address' => $this->snapshotAddress($address),
                'placed_at' => now(),
                'expires_at' => now()->addMinutes(30),
            ]);

            // 3. Create OrderItems + decrement stock
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'sku' => $item->product->sku,
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => (int) round($item->product->final_price * 100),
                    'unit_tax_cents' => 0,
                    'discount_cents' => (int) round(
                        ($item->product->price - $item->product->final_price) * 100 * $item->quantity
                    ),
                    'total_cents' => (int) round($item->product->final_price * 100 * $item->quantity),
                ]);

                // Decrement stock
                $item->product->decrement('stock_quantity', $item->quantity);
            }

            // 4. Create DeliveryOrder
            $shippingMethod = ShippingMethod::find($shippingData['method_id']);
            $shippingRate = $shippingData['rate_id']
                ? ShippingRate::find($shippingData['rate_id'])
                : null;

            $collectionDeadline = null;
            if ($shippingData['station_id']) {
                $station = PickupStation::find($shippingData['station_id']);
                $collectionDeadline = $station?->collectionDeadline()->format('Y-m-d H:i:s');
            }

            DeliveryOrder::create([
                'order_id' => $order->id,
                'logistics_provider_id' => $shippingMethod?->logistics_provider_id,
                'shipping_method_id' => $shippingData['method_id'],
                'shipping_zone_id' => $shippingData['zone_id'],
                'shipping_rate_id' => $shippingData['rate_id'],
                'vehicle_rate_id' => null,
                'pickup_station_id' => $shippingData['station_id'],
                'shipping_cost' => $shippingData['cost'],
                'cost_breakdown' => $shippingData['cost_breakdown'],
                'package_weight_kg' => $this->cartService->getWeight($cart),
                'is_return' => false,
                'status' => DeliveryOrderStatus::PENDING->value,
                'estimated_delivery_at' => now()->addDays(
                    $shippingRate?->estimated_days_max ?? 5
                ),
                'collection_deadline_at' => $collectionDeadline,
            ]);

            // 5. Create Payment record
            Payment::create([
                'order_id' => $order->id,
                'amount_cents' => $totalCents,
                'currency' => 'KES',
                'status' => 'pending',
                'gateway' => $this->paymentService->activeGateway(),
                'expires_at' => now()->addMinutes(30),
            ]);

            return $order;
        });

        //  Initiate payment (outside transaction — external API call)

        try {
            $payment = $order->payment;
            $response = $this->paymentService->initiate($order, $payment);

            if ($response->isFailed()) {
                Log::error('Payment initiation failed after order created', [
                    'order_id' => $order->id,
                    'message' => $response->message,
                ]);
            }

            // Clear cart + session ONLY after successful payment initiation
            if (!$response->isFailed()) {
                $this->cartService->clear();
                $this->checkoutSession->clear();
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

    //  Private helpers

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
