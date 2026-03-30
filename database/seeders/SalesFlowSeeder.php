<?php

namespace Database\Seeders;

use App\Enums\DeliveryOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteStatusHistory;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic sales flow data:
 * - Quotations in various statuses
 * - Orders (both direct and from quotes)
 * - Delivery orders connected to orders
 * - Full lifecycle with status histories
 */
class SalesFlowSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🛒 Seeding sales flow data...');

        // Get customers
        $customers = User::where('is_staff', false)->get();
        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Creating some...');
            $customers = User::factory()->count(10)->create();
        }

        // Get products
        $products = Product::active()->get();
        if ($products->isEmpty()) {
            $this->command->error('No products found. Run ProductSeeder first.');
            return;
        }

        $this->seedQuotations($customers, $products);
        $this->seedDirectOrders($customers, $products);
        $this->seedQuoteToOrderFlow($customers, $products);

        $this->printSummary();
    }

    private function seedQuotations($customers, $products): void
    {
        $this->command->info('  📝 Creating quotations...');

        // Pending quotes (awaiting admin pricing)
        for ($i = 0; $i < 8; $i++) {
            $this->createQuote($customers->random(), $products, QuoteStatus::PENDING, rand(1, 4));
        }
        $this->command->info('    ✓ 8 pending quotes');

        // Sent quotes (awaiting customer response)
        for ($i = 0; $i < 12; $i++) {
            $this->createQuote($customers->random(), $products, QuoteStatus::SENT, rand(1, 5));
        }
        $this->command->info('    ✓ 12 sent quotes');

        // Expiring soon (within 48 hours)
        for ($i = 0; $i < 4; $i++) {
            $this->createQuote($customers->random(), $products, QuoteStatus::SENT, rand(1, 3), expiringSoon: true);
        }
        $this->command->info('    ✓ 4 expiring soon quotes');

        // Rejected quotes
        for ($i = 0; $i < 6; $i++) {
            $this->createQuote($customers->random(), $products, QuoteStatus::REJECTED, rand(1, 4));
        }
        $this->command->info('    ✓ 6 rejected quotes');

        // Expired quotes
        for ($i = 0; $i < 5; $i++) {
            $this->createQuote($customers->random(), $products, QuoteStatus::EXPIRED, rand(1, 3));
        }
        $this->command->info('    ✓ 5 expired quotes');

        // Cancelled quotes
        for ($i = 0; $i < 3; $i++) {
            $this->createQuote($customers->random(), $products, QuoteStatus::CANCELLED, rand(1, 2));
        }
        $this->command->info('    ✓ 3 cancelled quotes');
    }

    private function seedDirectOrders($customers, $products): void
    {
        $this->command->info('  📦 Creating direct orders (cart checkout)...');

        // Pending orders (awaiting payment)
        for ($i = 0; $i < 5; $i++) {
            $this->createOrder($customers->random(), $products, OrderStatus::PENDING, PaymentStatus::PENDING, rand(1, 4));
        }
        $this->command->info('    ✓ 5 pending orders');

        // Confirmed orders (paid, awaiting processing)
        for ($i = 0; $i < 8; $i++) {
            $this->createOrder($customers->random(), $products, OrderStatus::CONFIRMED, PaymentStatus::PAID, rand(1, 5), withDelivery: true);
        }
        $this->command->info('    ✓ 8 confirmed orders');

        // Processing orders
        for ($i = 0; $i < 6; $i++) {
            $this->createOrder($customers->random(), $products, OrderStatus::PROCESSING, PaymentStatus::PAID, rand(1, 4), withDelivery: true);
        }
        $this->command->info('    ✓ 6 processing orders');

        // Shipped orders
        for ($i = 0; $i < 10; $i++) {
            $this->createOrder($customers->random(), $products, OrderStatus::SHIPPED, PaymentStatus::PAID, rand(1, 5), withDelivery: true);
        }
        $this->command->info('    ✓ 10 shipped orders');

        // Delivered orders (spread over 60 days for charts)
        for ($i = 0; $i < 25; $i++) {
            $this->createOrder($customers->random(), $products, OrderStatus::DELIVERED, PaymentStatus::PAID, rand(1, 6), withDelivery: true, daysAgo: rand(1, 60));
        }
        $this->command->info('    ✓ 25 delivered orders');

        // Cancelled orders
        for ($i = 0; $i < 4; $i++) {
            $this->createOrder($customers->random(), $products, OrderStatus::CANCELLED, PaymentStatus::REFUNDED, rand(1, 3));
        }
        $this->command->info('    ✓ 4 cancelled orders');
    }

    private function seedQuoteToOrderFlow($customers, $products): void
    {
        $this->command->info('  🔄 Creating quote-to-order conversions...');

        // Accepted quotes that converted to orders
        for ($i = 0; $i < 15; $i++) {
            $quote = $this->createQuote($customers->random(), $products, QuoteStatus::ACCEPTED, rand(2, 5));
            $order = $this->convertQuoteToOrder($quote);
            
            // Vary the order status
            $statuses = [
                OrderStatus::CONFIRMED,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::DELIVERED,
                OrderStatus::DELIVERED,
                OrderStatus::DELIVERED,
            ];
            $status = fake()->randomElement($statuses);
            
            $order->update(['status' => $status->value]);
            
            if (in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
                $this->createDeliveryOrder($order, $status);
            }
        }
        $this->command->info('    ✓ 15 quote-to-order conversions');
    }

    private function createQuote(User $customer, $products, QuoteStatus $status, int $itemCount, bool $expiringSoon = false): Quote
    {
        $createdAt = fake()->dateTimeBetween('-60 days', '-1 day');
        $county = fake()->randomElement(['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret']);

        $quote = Quote::create([
            'user_id' => $customer->id,
            'reference' => Quote::generateReference(),
            'status' => $status->value,
            'currency' => 'KES',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'shipping_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'preferred_county' => $county,
            'preferred_area' => fake()->optional()->streetName(),
            'customer_notes' => fake()->optional(0.3)->sentence(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Add items
        $selectedProducts = $products->random(min($itemCount, $products->count()));
        $subtotal = 0;

        foreach ($selectedProducts as $product) {
            $quantity = fake()->numberBetween(1, 10);
            $originalPrice = $product->price * 100;
            $quotedPrice = $status !== QuoteStatus::PENDING
                ? (int) ($originalPrice * fake()->randomFloat(2, 0.85, 1.0))
                : null;

            $effectivePrice = $quotedPrice ?? $originalPrice;
            $lineTotal = $effectivePrice * $quantity;
            $subtotal += $lineTotal;

            QuoteItem::create([
                'quote_id' => $quote->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'original_price_cents' => (int) $originalPrice,
                'quoted_price_cents' => $quotedPrice,
                'discount_cents' => 0,
                'total_cents' => (int) $lineTotal,
                'product_snapshot' => [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image_url' => $product->image_url,
                    'brand' => $product->brand?->name,
                ],
            ]);
        }

        // Set shipping and totals based on status
        $shipping = 0;
        $quotedAt = null;
        $expiresAt = null;
        $acceptedAt = null;
        $rejectedAt = null;
        $rejectionReason = null;

        if ($status !== QuoteStatus::PENDING && $status !== QuoteStatus::CANCELLED) {
            $shipping = fake()->numberBetween(50000, 200000);
            $quotedAt = fake()->dateTimeBetween($createdAt, 'now');
            
            if ($expiringSoon) {
                $expiresAt = now()->addHours(fake()->numberBetween(6, 36));
            } else {
                $expiresAt = (clone $quotedAt)->modify('+7 days');
            }
        }

        if ($status === QuoteStatus::ACCEPTED) {
            $acceptedAt = fake()->dateTimeBetween($quotedAt, min($expiresAt, now()));
        }

        if ($status === QuoteStatus::REJECTED) {
            $rejectedAt = fake()->dateTimeBetween($quotedAt, min($expiresAt, now()));
            $rejectionReason = fake()->randomElement([
                'Price too high',
                'Found better offer elsewhere',
                'Project cancelled',
                'Budget constraints',
            ]);
        }

        if ($status === QuoteStatus::EXPIRED) {
            $quotedAt = fake()->dateTimeBetween('-30 days', '-10 days');
            $expiresAt = (clone $quotedAt)->modify('+7 days');
        }

        $quote->update([
            'subtotal_cents' => (int) $subtotal,
            'shipping_cents' => $shipping,
            'total_cents' => (int) ($subtotal + $shipping),
            'quoted_at' => $quotedAt,
            'expires_at' => $expiresAt,
            'accepted_at' => $acceptedAt,
            'rejected_at' => $rejectedAt,
            'rejection_reason' => $rejectionReason,
        ]);

        // Create status history
        $this->createQuoteStatusHistory($quote, $status, $createdAt, $quotedAt, $acceptedAt, $rejectedAt);

        return $quote;
    }

    private function createQuoteStatusHistory(Quote $quote, QuoteStatus $status, $createdAt, $quotedAt, $acceptedAt, $rejectedAt): void
    {
        // Initial pending
        QuoteStatusHistory::create([
            'quote_id' => $quote->id,
            'from_status' => null,
            'to_status' => QuoteStatus::PENDING->value,
            'changed_by_type' => 'user',
            'changed_by_user_id' => $quote->user_id,
            'created_at' => $createdAt,
        ]);

        if ($quotedAt && $status !== QuoteStatus::PENDING && $status !== QuoteStatus::CANCELLED) {
            QuoteStatusHistory::create([
                'quote_id' => $quote->id,
                'from_status' => QuoteStatus::PENDING->value,
                'to_status' => QuoteStatus::SENT->value,
                'changed_by_type' => 'admin',
                'created_at' => $quotedAt,
            ]);
        }

        if ($status === QuoteStatus::ACCEPTED && $acceptedAt) {
            QuoteStatusHistory::create([
                'quote_id' => $quote->id,
                'from_status' => QuoteStatus::SENT->value,
                'to_status' => QuoteStatus::ACCEPTED->value,
                'changed_by_type' => 'user',
                'changed_by_user_id' => $quote->user_id,
                'created_at' => $acceptedAt,
            ]);
        }

        if ($status === QuoteStatus::REJECTED && $rejectedAt) {
            QuoteStatusHistory::create([
                'quote_id' => $quote->id,
                'from_status' => QuoteStatus::SENT->value,
                'to_status' => QuoteStatus::REJECTED->value,
                'changed_by_type' => 'user',
                'changed_by_user_id' => $quote->user_id,
                'notes' => $quote->rejection_reason,
                'created_at' => $rejectedAt,
            ]);
        }

        if ($status === QuoteStatus::EXPIRED) {
            QuoteStatusHistory::create([
                'quote_id' => $quote->id,
                'from_status' => QuoteStatus::SENT->value,
                'to_status' => QuoteStatus::EXPIRED->value,
                'changed_by_type' => 'system',
                'created_at' => $quote->expires_at,
            ]);
        }

        if ($status === QuoteStatus::CANCELLED) {
            QuoteStatusHistory::create([
                'quote_id' => $quote->id,
                'from_status' => QuoteStatus::PENDING->value,
                'to_status' => QuoteStatus::CANCELLED->value,
                'changed_by_type' => 'admin',
                'notes' => 'Cancelled by admin',
                'created_at' => fake()->dateTimeBetween($createdAt, 'now'),
            ]);
        }
    }

    private function createOrder(User $customer, $products, OrderStatus $status, PaymentStatus $paymentStatus, int $itemCount, bool $withDelivery = false, int $daysAgo = null): Order
    {
        $createdAt = $daysAgo ? now()->subDays($daysAgo) : fake()->dateTimeBetween('-30 days', 'now');
        $county = fake()->randomElement(['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret']);

        $order = Order::create([
            'user_id' => $customer->id,
            'reference' => Order::generateReference(),
            'status' => $status->value,
            'payment_status' => $paymentStatus->value,
            'currency' => 'KES',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'shipping_cents' => fake()->numberBetween(50000, 200000),
            'tax_cents' => 0,
            'total_cents' => 0,
            'shipping_address' => [
                'full_name' => $customer->name,
                'phone_number' => $customer->phone_number ?? fake()->phoneNumber(),
                'address' => fake()->streetAddress(),
                'county' => $county,
                'area' => fake()->streetName(),
            ],
            'billing_address' => [
                'full_name' => $customer->name,
                'phone_number' => $customer->phone_number ?? fake()->phoneNumber(),
                'address' => fake()->streetAddress(),
                'county' => $county,
                'area' => fake()->streetName(),
            ],
            'shipping_snapshot' => [
                'method' => 'Standard Delivery',
                'zone' => $county,
                'estimated_days' => fake()->numberBetween(2, 5),
            ],
            'preferred_county' => $county,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Add items
        $selectedProducts = $products->random(min($itemCount, $products->count()));
        $subtotal = 0;

        foreach ($selectedProducts as $product) {
            $quantity = fake()->numberBetween(1, 5);
            $unitPrice = ($product->sale_price ?? $product->price) * 100;
            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_cents' => (int) $unitPrice,
                'unit_tax_cents' => 0,
                'discount_cents' => 0,
                'total_cents' => (int) $lineTotal,
                'product_snapshot' => [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image_path' => $product->image_path,
                    'brand' => $product->brand?->name,
                ],
            ]);
        }

        $total = $subtotal + $order->shipping_cents;
        $order->update([
            'subtotal_cents' => (int) $subtotal,
            'total_cents' => (int) $total,
        ]);

        // Create payment if paid
        if ($paymentStatus === PaymentStatus::PAID) {
            Payment::create([
                'order_id' => $order->id,
                'gateway' => fake()->randomElement(['mpesa', 'stripe', 'bank_transfer']),
                'transaction_id' => strtoupper(fake()->bothify('TXN-########')),
                'amount_cents' => $order->total_cents,
                'currency' => 'KES',
                'status' => PaymentStatus::PAID,
                'paid_at' => fake()->dateTimeBetween($createdAt, 'now'),
                'meta' => [],
            ]);
        }

        // Create delivery order if needed
        if ($withDelivery) {
            $this->createDeliveryOrder($order, $status);
        }

        return $order;
    }

    private function convertQuoteToOrder(Quote $quote): Order
    {
        $order = Order::create([
            'user_id' => $quote->user_id,
            'quote_id' => $quote->id,
            'reference' => Order::generateReference(),
            'status' => OrderStatus::CONFIRMED->value,
            'payment_status' => PaymentStatus::PAID->value,
            'currency' => $quote->currency,
            'subtotal_cents' => $quote->subtotal_cents,
            'discount_cents' => $quote->discount_cents,
            'shipping_cents' => $quote->shipping_cents,
            'tax_cents' => $quote->tax_cents,
            'total_cents' => $quote->total_cents,
            'shipping_address' => [
                'full_name' => $quote->customerName(),
                'phone_number' => $quote->customerPhone(),
                'address' => fake()->streetAddress(),
                'county' => $quote->preferred_county,
                'area' => $quote->preferred_area,
            ],
            'billing_address' => [
                'full_name' => $quote->customerName(),
                'phone_number' => $quote->customerPhone(),
                'address' => fake()->streetAddress(),
                'county' => $quote->preferred_county,
                'area' => $quote->preferred_area,
            ],
            'shipping_snapshot' => [
                'method' => 'Standard Delivery',
                'zone' => $quote->preferred_county,
                'estimated_days' => fake()->numberBetween(2, 5),
            ],
            'preferred_county' => $quote->preferred_county,
            'preferred_area' => $quote->preferred_area,
            'created_at' => $quote->accepted_at,
            'updated_at' => $quote->accepted_at,
        ]);

        // Copy items from quote to order
        foreach ($quote->items as $quoteItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $quoteItem->product_id,
                'product_variant_id' => $quoteItem->product_variant_id,
                'quantity' => $quoteItem->quantity,
                'unit_price_cents' => $quoteItem->quoted_price_cents ?? $quoteItem->original_price_cents,
                'unit_tax_cents' => 0,
                'discount_cents' => $quoteItem->discount_cents,
                'total_cents' => $quoteItem->total_cents,
                'product_snapshot' => $quoteItem->product_snapshot,
            ]);
        }

        // Create payment
        Payment::create([
            'order_id' => $order->id,
            'gateway' => fake()->randomElement(['mpesa', 'stripe', 'bank_transfer']),
            'transaction_id' => strtoupper(fake()->bothify('TXN-########')),
            'amount_cents' => $order->total_cents,
            'currency' => 'KES',
            'status' => PaymentStatus::PAID,
            'paid_at' => fake()->dateTimeBetween($quote->accepted_at, 'now'),
            'meta' => [],
        ]);

        return $order;
    }

    private function createDeliveryOrder(Order $order, OrderStatus $orderStatus): void
    {
        $method = ShippingMethod::where('type', 'flat')->where('status', 'active')->inRandomOrder()->first();
        $zone = ShippingZone::where('status', 'active')->inRandomOrder()->first();
        $rate = ShippingRate::where('shipping_method_id', $method?->id)
            ->where('shipping_zone_id', $zone?->id)
            ->where('status', 'active')
            ->first();

        $deliveryStatus = match ($orderStatus) {
            OrderStatus::CONFIRMED => DeliveryOrderStatus::PENDING,
            OrderStatus::PROCESSING => fake()->randomElement([DeliveryOrderStatus::PENDING, DeliveryOrderStatus::PICKED_UP]),
            OrderStatus::SHIPPED => fake()->randomElement([DeliveryOrderStatus::IN_TRANSIT, DeliveryOrderStatus::OUT_FOR_DELIVERY]),
            OrderStatus::DELIVERED => DeliveryOrderStatus::DELIVERED,
            default => DeliveryOrderStatus::PENDING,
        };

        DeliveryOrder::create([
            'order_id' => $order->id,
            'logistics_provider_id' => 1,
            'shipping_method_id' => $method?->id,
            'shipping_zone_id' => $zone?->id,
            'shipping_rate_id' => $rate?->id,
            'package_weight_kg' => fake()->randomFloat(2, 0.5, 30),
            'shipping_cost' => $order->shipping_cents / 100,
            'cost_breakdown' => [
                'model' => 'flat',
                'zone' => $zone?->name ?? 'Unknown',
                'total' => $order->shipping_cents / 100,
            ],
            'is_return' => false,
            'status' => $deliveryStatus->value,
            'estimated_delivery_at' => now()->addDays(fake()->numberBetween(2, 5)),
            'delivered_at' => $deliveryStatus === DeliveryOrderStatus::DELIVERED ? fake()->dateTimeBetween($order->created_at, 'now') : null,
            'created_at' => $order->created_at,
            'updated_at' => $order->created_at,
        ]);
    }

    private function printSummary(): void
    {
        $this->command->info('');
        $this->command->info('✅ Sales flow seeding complete!');
        $this->command->info('');
        
        $this->command->info('📊 Summary:');
        $this->command->info('   Quotations: ' . Quote::count());
        $this->command->info('     - Pending: ' . Quote::where('status', QuoteStatus::PENDING)->count());
        $this->command->info('     - Sent: ' . Quote::where('status', QuoteStatus::SENT)->count());
        $this->command->info('     - Accepted: ' . Quote::where('status', QuoteStatus::ACCEPTED)->count());
        $this->command->info('     - Rejected: ' . Quote::where('status', QuoteStatus::REJECTED)->count());
        $this->command->info('     - Expired: ' . Quote::where('status', QuoteStatus::EXPIRED)->count());
        $this->command->info('');
        $this->command->info('   Orders: ' . Order::count());
        $this->command->info('     - From quotes: ' . Order::whereNotNull('quote_id')->count());
        $this->command->info('     - Direct: ' . Order::whereNull('quote_id')->count());
        $this->command->info('     - Paid: ' . Order::where('payment_status', PaymentStatus::PAID)->count());
        $this->command->info('');
        $this->command->info('   Delivery Orders: ' . DeliveryOrder::count());
    }
}
