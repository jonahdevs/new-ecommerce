<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus as EnumsPaymentStatus;
use App\Enums\SapSyncStatus;
use App\Enums\ShippingMethodStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Settings\LocalizationSettings;
use App\Settings\OrderSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession,
        private readonly PaymentService $paymentService,
        private readonly InventoryService $inventoryService,
        private readonly TaxService $taxService,
        private readonly LocalizationSettings $localization,
        private readonly OrderSettings $orderSettings,
    ) {}

    // =====================================================
    // Initiate checkout — single path, cart orders only
    //
    // Responsibilities:
    //  1. Pre-flight checks
    //  2. Resume existing failed order if within expiry window
    //  3. DB transaction — Order + OrderItems + Payment
    //  4. Payment gateway call (outside transaction)
    //
    // NOT responsible for:
    //  - Confirming payment (that is the gateway webhook)
    //  - Dispatching SAP sync (that fires in the gateway's
    //    handleSucceeded() after payment is confirmed)
    //  - Clearing the cart (that also fires in handleSucceeded())
    // =====================================================

    public function initiateCheckout(): PaymentResponse
    {
        $user = auth()->user();

        // Prevent concurrent checkouts from the same account (e.g. double-click, duplicate tabs).
        // The lock is held for 30 seconds — long enough to cover the full DB transaction + gateway call.
        $lock = Cache::lock("checkout:user:{$user->id}", 30);
        if (! $lock->get()) {
            throw new \RuntimeException('A checkout is already in progress. Please wait a moment and try again.');
        }

        try {
            return $this->doInitiateCheckout($user);
        } finally {
            $lock->release();
        }
    }

    private function doInitiateCheckout(User $user): PaymentResponse
    {
        $cart = $this->cartService->getCart();

        if (! $cart || ! $cart->items()->exists()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        if ($user->addresses()->doesntExist()) {
            throw new \RuntimeException('Please add a shipping address to continue.');
        }

        if (! $this->checkoutSession->isComplete()) {
            throw new \RuntimeException('Shipping not selected. Please select a shipping method.');
        }

        // Minimum order amount guard
        $minimum = $this->orderSettings->minimum_order_amount;
        if ($minimum !== null && $minimum > 0) {
            $cartSummaryPreFlight = $this->cartService->summary($cart);
            if ($cartSummaryPreFlight['subtotal'] < $minimum) {
                $formatted = number_format($minimum, 2);
                throw new \RuntimeException("Your order total does not meet the minimum order amount of {$this->localization->currency} {$formatted}.");
            }
        }

        // Pre-flight stock availability check
        $unavailable = $this->inventoryService->checkAvailability($cart);
        if (! empty($unavailable)) {
            $items = collect($unavailable)->pluck('product')->implode(', ');
            throw new \RuntimeException("Some items are out of stock: {$items}");
        }

        // Resume a failed order within the expiry window.
        // Handles M-Pesa timeout retries without duplicating orders.
        $existingOrder = Order::where('user_id', $user->id)
            ->where('status', OrderStatus::PENDING)
            ->where('payment_status', EnumsPaymentStatus::FAILED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existingOrder) {
            Log::info('Resuming existing failed order', [
                'order_id' => $existingOrder->id,
                'reference' => $existingOrder->reference,
            ]);

            return $this->paymentService->initiate($existingOrder, $existingOrder->payment);
        }

        $addressId = $this->checkoutSession->getAddressId()
            ?? $user->addresses()->where('is_default', true)->value('id')
            ?? $user->addresses()->oldest()->value('id');

        $address = Address::with(['county', 'area', 'shippingZone'])->find($addressId);
        if (! $address) {
            $this->checkoutSession->clearAddressId();
            throw new \RuntimeException('Your selected delivery address no longer exists. Please choose another address.');
        }

        $cartItems = $cart->items()->with('product.brand')->get();
        $cartSummary = $this->cartService->summary($cart);
        $shippingData = $this->checkoutSession->getShipping();

        // Validate the chosen shipping method is still active.
        $shippingMethodStillActive = ShippingMethod::where('id', $shippingData['method_id'])
            ->where('status', ShippingMethodStatus::ACTIVE)
            ->exists();

        if (! $shippingMethodStillActive) {
            $this->checkoutSession->clearShipping();
            throw new \RuntimeException('The selected shipping method is no longer available. Please go back and choose another.');
        }

        $subtotalCents = (int) round($cartSummary['subtotal'] * 100);
        $discountCents = (int) round($cartSummary['discount'] * 100);
        $shippingCents = (int) round($shippingData['cost'] * 100);

        // Calculate tax based on settings (inclusive extracts, exclusive adds)
        $taxableSubtotal = $subtotalCents - $discountCents;
        $taxBreakdown = $this->taxService->calculateOrderTax($taxableSubtotal, $shippingCents);
        $taxCents = $taxBreakdown['total_tax'];

        // For exclusive tax, add tax to total; for inclusive, total stays the same
        $totalCents = $this->taxService->isInclusive()
            ? max(0, $subtotalCents - $discountCents + $shippingCents)
            : max(0, $subtotalCents - $discountCents + $shippingCents + $taxCents);

        // -------------------------------------------------------
        // DB transaction — Order + OrderItems + Payment
        // -------------------------------------------------------
        $order = DB::transaction(function () use ($user, $cart, $cartItems, $address, $subtotalCents, $discountCents, $shippingCents, $taxCents, $totalCents, $shippingData) {
            $order = Order::create([
                'user_id' => $user->id,
                'reference' => Order::generateReference(),
                'status' => OrderStatus::from($this->orderSettings->default_order_status),
                'payment_status' => EnumsPaymentStatus::PENDING,
                'currency' => $this->localization->currency,
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'shipping_cents' => $shippingCents,
                'tax_cents' => $taxCents,
                'total_cents' => $totalCents,
                'shipping_address' => $this->snapshotAddress($address),
                'billing_address' => $this->snapshotAddress($address),
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
                'sap_sync_status' => SapSyncStatus::PENDING,
                'sap_sync_attempts' => 0,
                'expires_at' => now()->addMinutes(30),
            ]);

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::PENDING->value,
                'changed_by_user_id' => auth()->id(),
                'changed_by_type' => 'user',
                'notes' => 'Order placed by customer.',
            ]);

            foreach ($cartItems as $item) {
                // Use variant price if available, otherwise product price
                $unitPrice = $item->variant?->final_price ?? $item->product->final_price;
                $originalPrice = $item->variant?->price ?? $item->product->price;
                $unitPriceCents = (int) round($unitPrice * 100);
                $unitTaxCents = $this->taxService->calculateTax($unitPriceCents);

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'unit_tax_cents' => $unitTaxCents,
                    'discount_cents' => (int) round(
                        ($originalPrice - $unitPrice) * 100 * $item->quantity
                    ),
                    'total_cents' => (int) round($unitPrice * 100 * $item->quantity),
                    'product_snapshot' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'sku' => $item->variant?->sku ?? $item->product->sku,
                        'slug' => $item->product->slug,
                        'image_path' => $item->product->image_path,
                        'price' => $originalPrice,
                        'sale_price' => $item->variant?->sale_price ?? $item->product->sale_price,
                        'final_price' => $unitPrice,
                        'weight_kg' => $item->product->weight ?? 0.5,
                        'brand' => $item->product->brand?->name,
                        'variant' => $item->variant ? [
                            'id' => $item->variant->id,
                            'sku' => $item->variant->sku,
                            'attributes' => $item->variant->attributeValues?->mapWithKeys(
                                fn ($av) => [$av->attribute->name => $av->label ?: $av->value]
                            )->toArray() ?? [],
                        ] : null,
                    ],
                ]);
            }

            // Reserve stock (soft lock) — prevents overselling while payment is processing.
            // Stock is deducted for real only after payment is confirmed by the gateway webhook.

            Payment::create([
                'order_id' => $order->id,
                'amount_cents' => $totalCents,
                'currency' => $this->localization->currency,
                'status' => EnumsPaymentStatus::PENDING,
                'gateway' => $this->paymentService->activeGateway(),
                'expires_at' => now()->addMinutes(30),
                'meta' => [
                    'payment_method' => $this->checkoutSession->getPaymentMethod(),
                ],
            ]);

            // Reserve stock inside the transaction — if reservation fails the
            // entire order creation rolls back, preventing orphaned orders.
            $this->inventoryService->reserveStock($order);

            return $order;
        });

        // -------------------------------------------------------
        // Gateway call — outside the transaction.
        //
        // On failure: cancel the order and restore stock.
        // On success: return the response to the Livewire component.
        //
        // SAP sync is NOT dispatched here. It fires inside the
        // gateway's handleSucceeded() after payment is confirmed.
        // -------------------------------------------------------
        try {
            $response = $this->paymentService->initiate($order, $order->payment);

            if ($response->isFailed()) {
                $order->transitionTo(
                    OrderStatus::CANCELLED,
                    notes: 'Payment initiation failed: '.$response->message,
                    changedByType: 'system',
                );
                $order->update(['payment_status' => EnumsPaymentStatus::FAILED]);

                // Release stock reservation on payment initiation failure
                $this->inventoryService->releaseReservation($order);

                Log::error('Payment initiation failed after order created', [
                    'order_id' => $order->id,
                    'message' => $response->message,
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::error('Payment gateway threw exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

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
