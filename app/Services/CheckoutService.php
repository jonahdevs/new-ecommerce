<?php

namespace App\Services;

use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus as EnumsPaymentStatus;
use App\Enums\SapSyncStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Payment\PaymentService;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession,
        private readonly PaymentService $paymentService,
    ) {}

    // ================================================
    // PATH A — Normal sales order
    // ================================================

    public function initiateCheckout(): PaymentResponse
    {
        $user = auth()->user();
        $cart = $this->cartService->getCart();

        if (!$cart || !$cart->items()->exists()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        if ($user->addresses()->doesntExist()) {
            throw new \RuntimeException('Please add a shipping address to continue.');
        }

        if (!$this->checkoutSession->isComplete()) {
            throw new \RuntimeException('Shipping not selected. Please select a shipping method.');
        }

        // Resume an existing pending order if one exists within the expiry window.
        // Handles M-Pesa timeout retries without creating duplicate orders.
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

        $addressId = $this->checkoutSession->getAddressId()
            ?? $user->addresses()->where('is_default', true)->value('id')
            ?? $user->addresses()->oldest()->value('id');

        $address      = Address::with(['county', 'area', 'shippingZone'])->findOrFail($addressId);
        $cartItems    = $cart->items()->with('product.brand')->get();
        $cartSummary  = $this->cartService->summary($cart);
        $shippingData = $this->checkoutSession->getShipping();

        $subtotalCents = (int) round($cartSummary['subtotal'] * 100);
        $discountCents = (int) round($cartSummary['discount'] * 100);
        $shippingCents = (int) round($shippingData['cost'] * 100);
        $totalCents    = max(0, $subtotalCents - $discountCents + $shippingCents);

        // -------------------------------------------------------
        // DB Transaction — order, items, stock, payment record.
        // The gateway call and SAP dispatch both happen OUTSIDE
        // this transaction to avoid holding row locks open during
        // slow external HTTP calls.
        // -------------------------------------------------------
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
            $order = Order::create([
                'user_id'          => $user->id,
                'reference'        => Order::generateReference('sales_order'),
                'document_type'    => 'sales_order',
                'quotation_type'   => null,
                'status'           => OrdersStatus::PENDING,
                'payment_status'   => EnumsPaymentStatus::PENDING,
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
                // SAP sync starts as pending — job dispatched after payment succeeds
                'sap_sync_status'   => SapSyncStatus::PENDING,
                'sap_sync_attempts' => 0,
                'expires_at'        => now()->addMinutes(30),
            ]);

            $order->statusHistories()->create([
                'from_status'          => null,
                'to_status'            => OrdersStatus::PENDING->value,
                'changed_by_user_id'   => auth()->id(),
                'changed_by_type'      => 'user',
                'notes'                => 'Order placed by customer',
            ]);

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
                        'id'         => $item->product->id,
                        'name'       => $item->product->name,
                        'sku'        => $item->product->sku,
                        'slug'       => $item->product->slug,
                        'image_path' => $item->product->image_path,
                        'price'      => $item->product->price,
                        'sale_price' => $item->product->sale_price,
                        'final_price' => $item->product->final_price,
                        'weight_kg'  => $item->product->weight ?? 0.5,
                        'brand'      => $item->product->brand?->name,
                    ],
                ]);

                $product->decrement('stock_quantity', $item->quantity);
            }

            Payment::create([
                'order_id'     => $order->id,
                'amount_cents' => $totalCents,
                'currency'     => 'KES',
                'status'       => EnumsPaymentStatus::PENDING,
                'gateway'      => $this->paymentService->activeGateway(),
                'expires_at'   => now()->addMinutes(30),
                'meta'         => [
                    'payment_method' => $this->checkoutSession->getPaymentMethod() ?? 'card',
                ],
            ]);

            return $order;
        });

        // -------------------------------------------------------
        // Gateway call — intentionally outside the transaction.
        // -------------------------------------------------------
        try {
            $response = $this->paymentService->initiate($order, $order->payment);

            if ($response->isFailed()) {
                $order->transitionTo(
                    OrdersStatus::CANCELLED,
                    notes: 'Payment initiation failed: ' . $response->message,
                    changedByType: 'system'
                );
                $order->update(['payment_status' => EnumsPaymentStatus::FAILED]);

                foreach ($order->items()->with('product')->get() as $item) {
                    $item->product?->increment('stock_quantity', $item->quantity);
                }

                Log::error('Payment initiation failed after order created', [
                    'order_id' => $order->id,
                    'message'  => $response->message,
                ]);

                return $response;
            }

            // -------------------------------------------------------
            // SAP dispatch — fires only when payment initiation
            // succeeded. The job handles the full three-step SAP flow:
            //   1. POST /Orders
            //   2. POST /Invoices
            //   3. POST /IncomingPayments
            //
            // It runs asynchronously so checkout returns immediately.
            // Retries: 3 attempts at 1 min / 5 min / 15 min backoff.
            // On final failure the job marks sap_sync_status = failed
            // and alerts administrators.
            // -------------------------------------------------------
            SyncOrderToSapJob::dispatch($order);

            Log::info('SAP sync job dispatched', [
                'order_id'  => $order->id,
                'reference' => $order->reference,
            ]);

            return $response;
        } catch (\Throwable $e) {
            Log::error('Payment initiation threw exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    // ================================================
    // Address snapshot helper
    // ================================================

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
