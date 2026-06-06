<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\SapSyncStatus;
use App\Enums\ShipmentStatus;
use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SapSyncLog;
use App\Models\Shipment;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OrderDocumentService;
use App\Services\Sap\KraReceiptService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class OrderSeeder extends Seeder
{
    private User $customer;

    private User $admin;

    private Address $address;

    private DeliveryZone $zone;

    private ShippingMethod $standard;

    private ShippingMethod $express;

    private ShippingCarrier $carrier;

    private Warehouse $warehouse;

    private array $products = [];

    public function run(): void
    {
        $this->customer = User::where('email', 'customer@sheffield.test')->firstOrFail();
        $this->admin = User::where('email', 'admin@sheffield.test')->firstOrFail();
        $this->address = Address::where('user_id', $this->customer->id)
            ->orderByDesc('is_default')
            ->firstOrFail();
        $this->zone = DeliveryZone::where('name', 'Nairobi & Surroundings')->firstOrFail();
        $this->standard = ShippingMethod::where('slug', 'standard-delivery')->firstOrFail();
        $this->express = ShippingMethod::where('slug', 'express-delivery')->firstOrFail();
        $this->carrier = ShippingCarrier::where('slug', 'sheffield')->firstOrFail();
        $this->warehouse = Warehouse::where('slug', 'nairobi-hq')->firstOrFail();

        $this->loadProducts();

        // Spread created_at across the last 30 days for realistic index/filter views.
        $this->seedPending();
        $this->seedProcessing();
        $this->seedOutForDelivery();
        $this->seedCompleted();
        $this->seedCancelled();
    }

    // --------------------------------------------------
    // PENDING — customer placed order, no payment yet
    // --------------------------------------------------

    private function seedPending(): void
    {
        // Standard pending — just placed
        $this->makeOrder([
            'status' => OrderStatus::PENDING,
            'payment_method' => 'mpesa',
            'notes' => 'Please call before delivery.',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ], 2);

        // Pending — placed a few days ago, still unpaid
        $this->makeOrder([
            'status' => OrderStatus::PENDING,
            'payment_method' => 'net_30',
            'staff_notes' => 'Customer requested invoice via email before payment.',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ], 1);
    }

    // --------------------------------------------------
    // PROCESSING — paid, SAP in various sync states
    // --------------------------------------------------

    private function seedProcessing(): void
    {
        // Paid via M-Pesa, SAP awaiting CU number
        $order = $this->makeOrder([
            'status' => OrderStatus::PROCESSING,
            'payment_method' => 'mpesa',
            'confirmed_at' => now()->subDays(2),
            'sap_sync_status' => SapSyncStatus::AWAITING_CU,
            'sap_doc_entry' => 'DOC-2026-0042',
            'sap_doc_number' => 'INV-2026-0042',
            'sap_synced_at' => now()->subDays(2)->addHours(1),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2)->addHours(1),
        ], 3);

        Payment::factory()->successful()->create([
            'order_id' => $order->id,
            'amount_cents' => $order->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE10092026A',
            'paid_at' => now()->subDays(2),
            'account_reference' => $order->order_number,
        ]);

        $this->logStatusChange(
            $order,
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            'Payment received via M-Pesa. Receipt: MPE10092026A',
            now()->subDays(2)->addHours(1),
        );

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'create_invoice',
            'status' => 'success',
            'endpoint' => '/api/sap/invoice/create',
            'http_method' => 'POST',
            'request_payload' => ['order_number' => $order->order_number, 'total_cents' => $order->total_cents],
            'response_payload' => ['doc_entry' => 'DOC-2026-0042', 'doc_number' => 'INV-2026-0042', 'status' => 'created'],
            'http_status_code' => 201,
            'sap_document_number' => 'INV-2026-0042',
            'duration_ms' => 1543,
        ]);

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'check_kra_status',
            'status' => 'pending',
            'endpoint' => '/api/kra/cu-status',
            'http_method' => 'GET',
            'request_payload' => ['doc_number' => 'INV-2026-0042'],
            'response_payload' => ['status' => 'pending', 'message' => 'Awaiting CU assignment from KRA eTIMS'],
            'http_status_code' => 200,
            'duration_ms' => 892,
        ]);

        // Paid via M-Pesa, SAP sync failed
        $order2 = $this->makeOrder([
            'status' => OrderStatus::PROCESSING,
            'payment_method' => 'mpesa',
            'staff_notes' => 'SAP sync failed — check ERP logs. Retried manually once.',
            'confirmed_at' => now()->subDays(4),
            'sap_sync_status' => SapSyncStatus::FAILED,
            'sap_sync_attempts' => 3,
            'sap_sync_error' => 'Connection timeout after 30s — SAP ERP unreachable',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4)->addHours(2),
        ], 2);

        Payment::factory()->successful()->create([
            'order_id' => $order2->id,
            'amount_cents' => $order2->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE20082026B',
            'paid_at' => now()->subDays(4),
            'account_reference' => $order2->order_number,
        ]);

        $this->logStatusChange(
            $order2,
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            'Payment confirmed via M-Pesa. Receipt: MPE20082026B',
            now()->subDays(4)->addHours(1),
        );

        foreach ([1, 2, 3] as $attempt) {
            SapSyncLog::create([
                'order_id' => $order2->id,
                'operation' => 'create_invoice',
                'status' => 'failed',
                'endpoint' => '/api/sap/invoice/create',
                'http_method' => 'POST',
                'request_payload' => ['order_number' => $order2->order_number, 'attempt' => $attempt],
                'response_payload' => null,
                'http_status_code' => 503,
                'error_message' => 'Connection timeout after 30s — SAP ERP unreachable',
                'duration_ms' => 30012,
            ]);
        }

        // Bank transfer — pending SAP sync
        $order3 = $this->makeOrder([
            'status' => OrderStatus::PROCESSING,
            'payment_method' => 'bank_transfer',
            'notes' => 'Bank ref: KCB-REF-2026-4523',
            'confirmed_at' => now()->subDays(1)->subHours(3),
            'sap_sync_status' => SapSyncStatus::PENDING,
            'created_at' => now()->subDays(1)->subHours(5),
            'updated_at' => now()->subDays(1)->subHours(3),
        ], 1);

        Payment::factory()->successful()->create([
            'order_id' => $order3->id,
            'amount_cents' => $order3->total_cents,
            'phone' => null,
            'provider' => 'bank_transfer',
            'paid_at' => now()->subDays(1)->subHours(4),
            'account_reference' => $order3->order_number,
        ]);

        $this->logStatusChange(
            $order3,
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            'Bank transfer received and verified. Ref: KCB-REF-2026-4523',
            now()->subDays(1)->subHours(3),
        );
    }

    // --------------------------------------------------
    // OUT FOR DELIVERY — shipments booked and in motion
    // --------------------------------------------------

    private function seedOutForDelivery(): void
    {
        // In transit — shipment picked up by driver
        $order = $this->makeOrder([
            'status' => OrderStatus::OUT_FOR_DELIVERY,
            'payment_method' => 'mpesa',
            'shipping_method_id' => $this->standard->id,
            'confirmed_at' => now()->subDays(1)->subHours(6),
            'shipped_at' => now()->subHours(4),
            'sap_sync_status' => SapSyncStatus::AWAITING_CU,
            'sap_doc_entry' => 'DOC-2026-0039',
            'sap_doc_number' => 'INV-2026-0039',
            'sap_synced_at' => now()->subDays(1)->subHours(5),
            'created_at' => now()->subDays(1)->subHours(8),
            'updated_at' => now()->subHours(4),
        ], 2);

        Payment::factory()->successful()->create([
            'order_id' => $order->id,
            'amount_cents' => $order->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE30072026C',
            'paid_at' => now()->subDays(1)->subHours(7),
            'account_reference' => $order->order_number,
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $this->standard->id,
            'carrier_id' => $this->carrier->id,
            'tracking_number' => 'SHF-TRK-'.strtoupper(fake()->bothify('????####')),
            'status' => ShipmentStatus::IN_TRANSIT,
            'estimated_delivery_at' => today(),
            'booked_at' => now()->subHours(5),
            'picked_up_at' => now()->subHours(4),
            'notes' => 'Driver: James — +254 700 111 222',
        ]);

        $this->logStatusChange(
            $order,
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            'Payment confirmed via M-Pesa. Receipt: MPE30072026C',
            now()->subDays(1)->subHours(6),
        );

        $this->logStatusChange(
            $order,
            OrderStatus::PROCESSING,
            OrderStatus::OUT_FOR_DELIVERY,
            'Shipment booked — driver James assigned. ETA today.',
            now()->subHours(4),
        );

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'create_invoice',
            'status' => 'success',
            'endpoint' => '/api/sap/invoice/create',
            'http_method' => 'POST',
            'request_payload' => ['order_number' => $order->order_number, 'total_cents' => $order->total_cents],
            'response_payload' => ['doc_entry' => 'DOC-2026-0039', 'doc_number' => 'INV-2026-0039', 'status' => 'created'],
            'http_status_code' => 201,
            'sap_document_number' => 'INV-2026-0039',
            'duration_ms' => 1218,
        ]);

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'check_kra_status',
            'status' => 'pending',
            'endpoint' => '/api/kra/cu-status',
            'http_method' => 'GET',
            'request_payload' => ['doc_number' => 'INV-2026-0039'],
            'response_payload' => ['status' => 'pending', 'message' => 'Awaiting CU assignment from KRA eTIMS'],
            'http_status_code' => 200,
            'duration_ms' => 741,
        ]);

        app(OrderDocumentService::class)->generateDispatchDocuments($order);

        // Express — driver en route
        $order2 = $this->makeOrder([
            'status' => OrderStatus::OUT_FOR_DELIVERY,
            'payment_method' => 'mpesa',
            'shipping_method_id' => $this->express->id,
            'notes' => 'Leave at gate if no one answers.',
            'confirmed_at' => now()->subHours(5),
            'shipped_at' => now()->subHours(2),
            'sap_sync_status' => SapSyncStatus::SYNCING,
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(2),
        ], 3);

        Payment::factory()->successful()->create([
            'order_id' => $order2->id,
            'amount_cents' => $order2->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE40062026D',
            'paid_at' => now()->subHours(5),
            'account_reference' => $order2->order_number,
        ]);

        Shipment::create([
            'order_id' => $order2->id,
            'shipping_method_id' => $this->express->id,
            'carrier_id' => $this->carrier->id,
            'tracking_number' => 'SHF-TRK-'.strtoupper(fake()->bothify('????####')),
            'status' => ShipmentStatus::OUT_FOR_DELIVERY,
            'estimated_delivery_at' => today(),
            'booked_at' => now()->subHours(3),
            'picked_up_at' => now()->subHours(2),
        ]);

        $this->logStatusChange(
            $order2,
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            'Payment confirmed via M-Pesa. Receipt: MPE40062026D',
            now()->subHours(5),
        );

        $this->logStatusChange(
            $order2,
            OrderStatus::PROCESSING,
            OrderStatus::OUT_FOR_DELIVERY,
            'Express shipment dispatched — driver en route.',
            now()->subHours(2),
        );

        SapSyncLog::create([
            'order_id' => $order2->id,
            'operation' => 'create_invoice',
            'status' => 'syncing',
            'endpoint' => '/api/sap/invoice/create',
            'http_method' => 'POST',
            'request_payload' => ['order_number' => $order2->order_number, 'total_cents' => $order2->total_cents],
            'response_payload' => null,
            'http_status_code' => null,
            'duration_ms' => null,
        ]);

        app(OrderDocumentService::class)->generateDispatchDocuments($order2);
    }

    // --------------------------------------------------
    // COMPLETED — delivered, KRA validated
    // --------------------------------------------------

    private function seedCompleted(): void
    {
        // Completed 3 days ago — full KRA lifecycle done
        $order = $this->makeOrder([
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'mpesa',
            'shipping_method_id' => $this->standard->id,
            'confirmed_at' => now()->subDays(5),
            'shipped_at' => now()->subDays(4),
            'delivered_at' => now()->subDays(3),
            'sap_sync_status' => SapSyncStatus::COMPLETED,
            'sap_doc_entry' => 'DOC-2026-0031',
            'sap_doc_number' => 'INV-2026-0031',
            'sap_synced_at' => now()->subDays(5)->addHours(2),
            'kra_cu_number' => 'KRA-CU-'.fake()->numerify('######'),
            'kra_validated_at' => now()->subDays(3)->addHours(1),
            'created_at' => now()->subDays(5)->subHours(1),
            'updated_at' => now()->subDays(3),
        ], 2);

        Payment::factory()->successful()->create([
            'order_id' => $order->id,
            'amount_cents' => $order->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE50052026E',
            'paid_at' => now()->subDays(5),
            'account_reference' => $order->order_number,
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $this->standard->id,
            'carrier_id' => $this->carrier->id,
            'tracking_number' => 'SHF-TRK-'.strtoupper(fake()->bothify('????####')),
            'status' => ShipmentStatus::DELIVERED,
            'estimated_delivery_at' => now()->subDays(4),
            'booked_at' => now()->subDays(4)->subHours(2),
            'picked_up_at' => now()->subDays(4)->subHour(),
            'delivered_at' => now()->subDays(3),
            'notes' => 'Delivered and signed by Anita.',
        ]);

        $this->logStatusChange(
            $order,
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            'Payment received via M-Pesa. Receipt: MPE50052026E',
            now()->subDays(5),
        );

        $this->logStatusChange(
            $order,
            OrderStatus::PROCESSING,
            OrderStatus::OUT_FOR_DELIVERY,
            'Items packed and handed to driver.',
            now()->subDays(4)->subHour(),
        );

        $this->logStatusChange(
            $order,
            OrderStatus::OUT_FOR_DELIVERY,
            OrderStatus::COMPLETED,
            'Delivered and signed for by Anita Wanjiru.',
            now()->subDays(3),
        );

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'create_invoice',
            'status' => 'success',
            'endpoint' => '/api/sap/invoice/create',
            'http_method' => 'POST',
            'request_payload' => ['order_number' => $order->order_number, 'total_cents' => $order->total_cents],
            'response_payload' => ['doc_entry' => 'DOC-2026-0031', 'doc_number' => 'INV-2026-0031', 'status' => 'created'],
            'http_status_code' => 201,
            'sap_document_number' => 'INV-2026-0031',
            'duration_ms' => 1102,
        ]);

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'check_kra_status',
            'status' => 'success',
            'endpoint' => '/api/kra/cu-status',
            'http_method' => 'GET',
            'request_payload' => ['doc_number' => 'INV-2026-0031'],
            'response_payload' => ['status' => 'validated', 'cu_number' => $order->kra_cu_number, 'validated_at' => $order->kra_validated_at],
            'http_status_code' => 200,
            'duration_ms' => 834,
        ]);

        app(OrderDocumentService::class)->generateDispatchDocuments($order);
        app(KraReceiptService::class)->generate($order);

        // Completed 10 days ago — older order for revenue stats
        $order2 = $this->makeOrder([
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'card',
            'shipping_method_id' => $this->express->id,
            'staff_notes' => 'VIP customer — expedited handling.',
            'confirmed_at' => now()->subDays(11),
            'shipped_at' => now()->subDays(10)->subHours(3),
            'delivered_at' => now()->subDays(10),
            'sap_sync_status' => SapSyncStatus::COMPLETED,
            'sap_doc_entry' => 'DOC-2026-0018',
            'sap_doc_number' => 'INV-2026-0018',
            'sap_synced_at' => now()->subDays(11)->addHours(1),
            'kra_cu_number' => 'KRA-CU-'.fake()->numerify('######'),
            'kra_validated_at' => now()->subDays(10)->addHours(2),
            'created_at' => now()->subDays(11)->subHours(2),
            'updated_at' => now()->subDays(10),
        ], 4);

        Payment::factory()->successful()->stripe()->create([
            'order_id' => $order2->id,
            'amount_cents' => $order2->total_cents,
            'paid_at' => now()->subDays(11),
            'account_reference' => $order2->order_number,
        ]);

        Shipment::create([
            'order_id' => $order2->id,
            'shipping_method_id' => $this->express->id,
            'carrier_id' => $this->carrier->id,
            'tracking_number' => 'SHF-TRK-'.strtoupper(fake()->bothify('????####')),
            'status' => ShipmentStatus::DELIVERED,
            'estimated_delivery_at' => now()->subDays(10),
            'booked_at' => now()->subDays(10)->subHours(5),
            'picked_up_at' => now()->subDays(10)->subHours(4),
            'delivered_at' => now()->subDays(10),
        ]);

        $this->logStatusChange(
            $order2,
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            'Card payment verified. VIP order — expedited handling.',
            now()->subDays(11),
        );

        $this->logStatusChange(
            $order2,
            OrderStatus::PROCESSING,
            OrderStatus::OUT_FOR_DELIVERY,
            'Express shipment booked — priority dispatch.',
            now()->subDays(10)->subHours(4),
        );

        $this->logStatusChange(
            $order2,
            OrderStatus::OUT_FOR_DELIVERY,
            OrderStatus::COMPLETED,
            'Delivered on time. Customer confirmed receipt.',
            now()->subDays(10),
        );

        SapSyncLog::create([
            'order_id' => $order2->id,
            'operation' => 'create_invoice',
            'status' => 'success',
            'endpoint' => '/api/sap/invoice/create',
            'http_method' => 'POST',
            'request_payload' => ['order_number' => $order2->order_number, 'total_cents' => $order2->total_cents],
            'response_payload' => ['doc_entry' => 'DOC-2026-0018', 'doc_number' => 'INV-2026-0018', 'status' => 'created'],
            'http_status_code' => 201,
            'sap_document_number' => 'INV-2026-0018',
            'duration_ms' => 943,
        ]);

        SapSyncLog::create([
            'order_id' => $order2->id,
            'operation' => 'check_kra_status',
            'status' => 'success',
            'endpoint' => '/api/kra/cu-status',
            'http_method' => 'GET',
            'request_payload' => ['doc_number' => 'INV-2026-0018'],
            'response_payload' => ['status' => 'validated', 'cu_number' => $order2->kra_cu_number, 'validated_at' => $order2->kra_validated_at],
            'http_status_code' => 200,
            'duration_ms' => 761,
        ]);

        app(OrderDocumentService::class)->generateDispatchDocuments($order2);
        app(KraReceiptService::class)->generate($order2);
    }

    // --------------------------------------------------
    // CANCELLED — with timestamp and note
    // --------------------------------------------------

    private function seedCancelled(): void
    {
        $order = $this->makeOrder([
            'status' => OrderStatus::CANCELLED,
            'payment_method' => 'mpesa',
            'notes' => 'Customer cancelled — changed mind.',
            'staff_notes' => 'Refund processed via M-Pesa reversal. Ref: REV-2026-007.',
            'cancelled_at' => now()->subDays(2)->addHours(3),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2)->addHours(3),
        ], 1);

        $this->logStatusChange(
            $order,
            OrderStatus::PENDING,
            OrderStatus::CANCELLED,
            'Customer called in to cancel — changed mind after placing. Refund issued via M-Pesa reversal.',
            now()->subDays(2)->addHours(3),
        );
    }

    // --------------------------------------------------
    // HELPERS
    // --------------------------------------------------

    private function makeOrder(array $attributes, int $itemCount = 2): Order
    {
        $subtotal = fake()->numberBetween(5, 50) * 100000; // KES 5k–50k
        $vat = (int) round($subtotal * 0.16);
        $delivery = 35000; // KES 350

        $order = Order::create(array_merge([
            'user_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'delivery_zone_id' => $this->zone->id,
            'delivery_zone_name' => $this->zone->name,
            'shipping_method_id' => $this->standard->id,
            'shipping_name' => $this->address->name,
            'shipping_phone' => $this->address->phone,
            'shipping_line1' => $this->address->line1,
            'shipping_city' => 'Nairobi',
            'shipping_state' => 'Nairobi',
            'shipping_country' => 'KE',
            'order_number' => Order::generateNumber(),
            'subtotal_cents' => $subtotal,
            'vat_cents' => $vat,
            'delivery_cents' => $delivery,
            'installation_cents' => 0,
            'total_cents' => $subtotal + $vat + $delivery,
            'currency' => 'KES',
        ], $attributes));

        $this->attachItems($order, $itemCount);

        return $order;
    }

    private function attachItems(Order $order, int $count): void
    {
        if (empty($this->products)) {
            return;
        }

        $selected = collect($this->products)->random(min($count, count($this->products)));

        foreach ($selected as $product) {
            $qty = fake()->numberBetween(1, 2);
            $price = $product['price'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product['id'],
                'product_snapshot' => [
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'model_number' => $product['model_number'] ?? null,
                ],
                'unit_price_cents' => $price,
                'quantity' => $qty,
                'line_total_cents' => $price * $qty,
                'tax_rate' => 16.00,
                'tax_cents' => (int) round($price * $qty * 0.16),
            ]);
        }
    }

    /**
     * Log a status transition as a single activity entry attributed to the admin.
     * Backdates created_at so the log reflects the real sequence of events.
     */
    private function logStatusChange(
        Order $order,
        OrderStatus $from,
        OrderStatus $to,
        ?string $note,
        CarbonInterface $at,
    ): void {
        $attributes = ['status' => $to->value];
        if ($note) {
            $attributes['note'] = $note;
        }

        /** @var ActivityContract $activity */
        $activity = activity('order')
            ->causedBy($this->admin)
            ->performedOn($order)
            ->withProperties([
                'attributes' => $attributes,
                'old' => ['status' => $from->value],
            ])
            ->event('updated')
            ->log('Status updated');

        $activity->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
    }

    private function loadProducts(): void
    {
        $rows = \DB::table('products')
            ->select('id', 'name', 'sku', 'model_number', 'price', 'sale_price')
            ->inRandomOrder()
            ->limit(12)
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $this->products = $rows->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'model_number' => $p->model_number,
            'price' => $p->sale_price ?? $p->price ?? 5000000,
        ])->toArray();
    }
}
