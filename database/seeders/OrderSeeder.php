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
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class OrderSeeder extends Seeder
{
    // ==================================================
    // Demo customer (specific scenario orders)
    // ==================================================
    private User $customer;

    private User $admin;

    private Address $address;

    // ==================================================
    // Historical customers (bulk analytics data)
    // ==================================================
    private array $historicalCustomers = [];

    // ==================================================
    // Shared infrastructure
    // ==================================================
    private DeliveryZone $zone;

    private ShippingMethod $standard;

    private ShippingMethod $express;

    private ShippingCarrier $carrier;

    private Warehouse $warehouse;

    private array $products = [];

    /** Payment channel distribution weights */
    private const CHANNELS = [
        ['state' => 'paystackMobileMoney', 'method' => 'mpesa',        'weight' => 45],
        ['state' => 'paystack',            'method' => 'card',          'weight' => 30],
        ['state' => 'paystackAirtel',      'method' => 'airtel',        'weight' => 15],
        ['state' => null,                  'method' => 'bank_transfer', 'weight' => 10],
    ];

    public function run(): void
    {
        $this->loadInfrastructure();
        $this->loadProducts();

        // 1. Bulk historical orders (180 days) — powers the dashboard analytics charts.
        $this->createHistoricalCustomers();
        $this->seedHistoricalOrders();

        // 2. Specific scenario orders — showcases every status in the admin UI with
        //    detailed SAP logs, KRA receipts, and activity history.
        $this->customer = User::where('email', 'customer@sheffieldafrica.com')->firstOrFail();
        $this->admin = User::where('email', 'admin@sheffieldafrica.com')->firstOrFail();
        $this->address = Address::where('user_id', $this->customer->id)
            ->orderByDesc('is_default')
            ->firstOrFail();

        $this->seedPending();
        $this->seedProcessing();
        $this->seedOutForDelivery();
        $this->seedCompleted();
        $this->seedCancelled();
    }

    // ==================================================
    // HISTORICAL BULK DATA
    // ==================================================

    private function createHistoricalCustomers(): void
    {
        // 12 customers registered at spread intervals so the customer sparkline has data.
        $offsets = [175, 160, 148, 132, 115, 101, 88, 74, 62, 49, 33, 18];

        foreach ($offsets as $daysAgo) {
            $registeredAt = now()->subDays($daysAgo)->addHours(rand(8, 18));

            $user = User::factory()->create([
                'created_at' => $registeredAt,
                'updated_at' => $registeredAt,
            ]);

            $address = Address::create([
                'user_id' => $user->id,
                'label' => 'Home',
                'name' => $user->name,
                'line1' => fake()->streetAddress(),
                'delivery_zone_id' => $this->zone->id,
                'county' => fake()->randomElement(['Nairobi', 'Kiambu', 'Mombasa', 'Nakuru', 'Kisumu', 'Machakos']),
                'is_default' => true,
            ]);

            $this->historicalCustomers[] = ['user' => $user, 'address' => $address];
        }
    }

    private function seedHistoricalOrders(): void
    {
        // Walk back 180 days. Weekdays get 1–3 orders, weekends 0–1.
        for ($daysAgo = 180; $daysAgo >= 1; $daysAgo--) {
            $date = now()->subDays($daysAgo)->startOfDay();
            $count = $date->isWeekend() ? rand(0, 1) : rand(1, 3);

            for ($i = 0; $i < $count; $i++) {
                $placedAt = $date->copy()->addHours(rand(7, 20))->addMinutes(rand(0, 59));
                $this->seedHistoricalOrder($placedAt, $daysAgo);
            }
        }
    }

    private function seedHistoricalOrder(CarbonInterface $placedAt, int $daysAgo): void
    {
        $channel = $this->pickChannel();
        $customer = $this->historicalCustomers[array_rand($this->historicalCustomers)];

        $status = match (true) {
            $daysAgo > 14 => $this->weightedStatus(['completed' => 75, 'cancelled' => 15, 'processing' => 10]),
            $daysAgo > 7 => $this->weightedStatus(['completed' => 50, 'processing' => 30, 'out_for_delivery' => 20]),
            default => $this->weightedStatus(['processing' => 50, 'out_for_delivery' => 25, 'pending' => 15, 'completed' => 10]),
        };

        $subtotal = $this->randomPrice();
        $vat = (int) round($subtotal * 0.16);
        $delivery = rand(0, 1) ? 35000 : 0;
        $confirmedAt = $placedAt->copy()->addMinutes(rand(5, 60));
        $shippingMethod = rand(0, 3) === 0 ? $this->express : $this->standard;

        $attrs = [
            'user_id' => $customer['user']->id,
            'address_id' => $customer['address']->id,
            'delivery_zone_id' => $this->zone->id,
            'delivery_zone_name' => $this->zone->name,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_name' => $customer['user']->name,
            'shipping_line1' => $customer['address']->line1,
            'shipping_city' => 'Nairobi',
            'shipping_state' => 'Nairobi',
            'shipping_country' => 'KE',
            'order_number' => Order::generateNumber(),
            'status' => OrderStatus::from($status),
            'payment_method' => $channel['method'],
            'subtotal_cents' => $subtotal,
            'vat_cents' => $vat,
            'delivery_cents' => $delivery,
            'installation_cents' => 0,
            'total_cents' => $subtotal + $vat + $delivery,
            'currency' => 'KES',
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ];

        if ($status !== 'pending') {
            $attrs['confirmed_at'] = $confirmedAt;
        }

        if (in_array($status, ['out_for_delivery', 'completed'])) {
            $attrs['shipped_at'] = $confirmedAt->copy()->addHours(rand(4, 24));
        }

        if ($status === 'completed') {
            $deliveredAt = $confirmedAt->copy()->addDays(rand(1, 3));
            $attrs['delivered_at'] = $deliveredAt;
            $attrs['updated_at'] = $deliveredAt;
        }

        if ($status === 'cancelled') {
            $attrs['cancelled_at'] = $confirmedAt->copy()->addHours(rand(1, 12));
        }

        if (in_array($status, ['processing', 'out_for_delivery', 'completed'])) {
            [$sapStatus, $docEntry, $docNumber, $cuNumber] = $this->sapState($status, $daysAgo);
            $attrs['sap_sync_status'] = $sapStatus;
            $attrs['sap_synced_at'] = $confirmedAt->copy()->addMinutes(rand(2, 15));

            if ($docEntry) {
                $attrs['sap_doc_entry'] = $docEntry;
                $attrs['sap_doc_number'] = $docNumber;
            }

            if ($cuNumber) {
                $attrs['cu_number'] = $cuNumber;
            }
        }

        $order = Order::create($attrs);

        $this->attachItems($order, rand(1, 4));

        if ($status !== 'pending') {
            $this->createPayment($order, $channel, $confirmedAt);
        }

        if (in_array($status, ['out_for_delivery', 'completed'])) {
            Shipment::create([
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethod->id,
                'carrier_id' => $this->carrier->id,
                'tracking_number' => 'SHF-TRK-'.strtoupper(fake()->bothify('????####')),
                'status' => $status === 'completed' ? ShipmentStatus::DELIVERED : ShipmentStatus::OUT_FOR_DELIVERY,
                'booked_at' => $attrs['shipped_at'],
                'picked_up_at' => $attrs['shipped_at'],
                'delivered_at' => $status === 'completed' ? ($attrs['delivered_at'] ?? null) : null,
                'estimated_delivery_at' => $confirmedAt->copy()->addDays(2),
            ]);
        }
    }

    // ==================================================
    // SPECIFIC SCENARIO ORDERS (demo customer)
    // ==================================================

    private function seedPending(): void
    {
        $this->makeOrder([
            'status' => OrderStatus::PENDING,
            'payment_method' => 'mpesa',
            'notes' => 'Please call before delivery.',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ], 2);

        $this->makeOrder([
            'status' => OrderStatus::PENDING,
            'payment_method' => 'net_30',
            'staff_notes' => 'Customer requested invoice via email before payment.',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ], 1);
    }

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

        Payment::factory()->successful()->paystackMobileMoney()->create([
            'order_id' => $order->id,
            'amount_cents' => $order->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE10092026A',
            'paystack_reference' => 'SHF-2026-00001-MPESAABC',
            'paid_at' => now()->subDays(2),
            'account_reference' => $order->order_number,
        ]);

        $this->logStatusChange($order, OrderStatus::PENDING, OrderStatus::PROCESSING,
            'Payment received via M-Pesa. Receipt: MPE10092026A', now()->subDays(2)->addHours(1));

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

        Payment::factory()->successful()->paystackMobileMoney()->create([
            'order_id' => $order2->id,
            'amount_cents' => $order2->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE20082026B',
            'paystack_reference' => 'SHF-2026-00002-MPESADEF',
            'paid_at' => now()->subDays(4),
            'account_reference' => $order2->order_number,
        ]);

        $this->logStatusChange($order2, OrderStatus::PENDING, OrderStatus::PROCESSING,
            'Payment confirmed via M-Pesa. Receipt: MPE20082026B', now()->subDays(4)->addHours(1));

        foreach ([1, 2, 3] as $attempt) {
            SapSyncLog::create([
                'order_id' => $order2->id,
                'operation' => 'create_invoice',
                'status' => 'failed',
                'endpoint' => '/api/sap/invoice/create',
                'http_method' => 'POST',
                'request_payload' => ['order_number' => $order2->order_number, 'attempt' => $attempt],
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

        $this->logStatusChange($order3, OrderStatus::PENDING, OrderStatus::PROCESSING,
            'Bank transfer received and verified. Ref: KCB-REF-2026-4523', now()->subDays(1)->subHours(3));
    }

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

        Payment::factory()->successful()->paystackMobileMoney()->create([
            'order_id' => $order->id,
            'amount_cents' => $order->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE30072026C',
            'paystack_reference' => 'SHF-2026-00003-MPESAGHI',
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

        $this->logStatusChange($order, OrderStatus::PENDING, OrderStatus::PROCESSING,
            'Payment confirmed via M-Pesa. Receipt: MPE30072026C', now()->subDays(1)->subHours(6));
        $this->logStatusChange($order, OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY,
            'Shipment booked — driver James assigned. ETA today.', now()->subHours(4));

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

        Payment::factory()->successful()->paystackMobileMoney()->create([
            'order_id' => $order2->id,
            'amount_cents' => $order2->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE40062026D',
            'paystack_reference' => 'SHF-2026-00004-MPESAJKL',
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

        $this->logStatusChange($order2, OrderStatus::PENDING, OrderStatus::PROCESSING,
            'Payment confirmed via M-Pesa. Receipt: MPE40062026D', now()->subHours(5));
        $this->logStatusChange($order2, OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY,
            'Express shipment dispatched — driver en route.', now()->subHours(2));

        app(OrderDocumentService::class)->generateDispatchDocuments($order2);
    }

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
            'cu_number' => 'KRA-CU-'.fake()->numerify('######'),
            'sap_synced_at' => now()->subDays(3)->addHours(1),
            'created_at' => now()->subDays(5)->subHours(1),
            'updated_at' => now()->subDays(3),
        ], 2);

        Payment::factory()->successful()->paystackMobileMoney()->create([
            'order_id' => $order->id,
            'amount_cents' => $order->total_cents,
            'phone' => '254712345678',
            'mpesa_receipt' => 'MPE50052026E',
            'paystack_reference' => 'SHF-2026-00005-MPESAMNO',
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

        $this->logStatusChange($order, OrderStatus::PENDING, OrderStatus::PROCESSING,
            'Payment received via M-Pesa. Receipt: MPE50052026E', now()->subDays(5));
        $this->logStatusChange($order, OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY,
            'Items packed and handed to driver.', now()->subDays(4)->subHour());
        $this->logStatusChange($order, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::COMPLETED,
            'Delivered and signed for by Anita Wanjiru.', now()->subDays(3));

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
            'response_payload' => ['status' => 'validated', 'cu_number' => $order->cu_number, 'validated_at' => $order->sap_synced_at],
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
            'cu_number' => 'KRA-CU-'.fake()->numerify('######'),
            'sap_synced_at' => now()->subDays(10)->addHours(2),
            'created_at' => now()->subDays(11)->subHours(2),
            'updated_at' => now()->subDays(10),
        ], 4);

        Payment::factory()->successful()->paystack()->create([
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

        $this->logStatusChange($order2, OrderStatus::PENDING, OrderStatus::PROCESSING,
            'Card payment verified. VIP order — expedited handling.', now()->subDays(11));
        $this->logStatusChange($order2, OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY,
            'Express shipment booked — priority dispatch.', now()->subDays(10)->subHours(4));
        $this->logStatusChange($order2, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::COMPLETED,
            'Delivered on time. Customer confirmed receipt.', now()->subDays(10));

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
            'response_payload' => ['status' => 'validated', 'cu_number' => $order2->cu_number, 'validated_at' => $order2->sap_synced_at],
            'http_status_code' => 200,
            'duration_ms' => 761,
        ]);

        app(OrderDocumentService::class)->generateDispatchDocuments($order2);
        app(KraReceiptService::class)->generate($order2);
    }

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

        $this->logStatusChange($order, OrderStatus::PENDING, OrderStatus::CANCELLED,
            'Customer called in to cancel — changed mind after placing. Refund issued via M-Pesa reversal.',
            now()->subDays(2)->addHours(3));
    }

    // ==================================================
    // SHARED HELPERS
    // ==================================================

    private function loadInfrastructure(): void
    {
        $this->zone = DeliveryZone::where('name', 'Nairobi & Surroundings')->firstOrFail();
        $this->standard = ShippingMethod::where('slug', 'standard-delivery')->firstOrFail();
        $this->express = ShippingMethod::where('slug', 'express-delivery')->firstOrFail();
        $this->carrier = ShippingCarrier::where('slug', 'sheffield')->firstOrFail();
        $this->warehouse = Warehouse::where('slug', 'nairobi-hq')->firstOrFail();
    }

    private function loadProducts(): void
    {
        $rows = DB::table('products')
            ->select('id', 'name', 'sku', 'model_number', 'price', 'sale_price')
            ->get();

        $this->products = $rows->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'model_number' => $p->model_number,
            'price' => $p->sale_price ?? $p->price ?? 5000000,
        ])->toArray();
    }

    private function makeOrder(array $attributes, int $itemCount = 2): Order
    {
        $subtotal = fake()->numberBetween(5, 50) * 100000;
        $vat = (int) round($subtotal * 0.16);
        $delivery = 35000;

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
            $qty = fake()->numberBetween(1, 3);
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

    private function createPayment(Order $order, array $channel, CarbonInterface $paidAt): void
    {
        $base = [
            'order_id' => $order->id,
            'amount_cents' => $order->total_cents,
            'paid_at' => $paidAt,
            'account_reference' => $order->order_number,
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ];

        match ($channel['state']) {
            'paystackMobileMoney' => Payment::factory()->successful()->paystackMobileMoney()->create(array_merge($base, [
                'phone' => '2547'.fake()->numerify('########'),
                'mpesa_receipt' => strtoupper(fake()->bothify('???#####??')),
                'paystack_reference' => 'SHF-'.now()->year.'-'.fake()->numerify('#####').'-'.strtoupper(fake()->lexify('????????')),
            ])),
            'paystack' => Payment::factory()->successful()->paystack()->create(array_merge($base, [
                'paystack_reference' => 'SHF-'.now()->year.'-'.fake()->numerify('#####').'-'.strtoupper(fake()->lexify('????????')),
            ])),
            'paystackAirtel' => Payment::factory()->successful()->paystackAirtel()->create(array_merge($base, [
                'phone' => '2547'.fake()->numerify('########'),
                'paystack_reference' => 'SHF-'.now()->year.'-'.fake()->numerify('#####').'-'.strtoupper(fake()->lexify('????????')),
            ])),
            default => Payment::factory()->successful()->create(array_merge($base, [
                'provider' => 'bank_transfer',
                'phone' => null,
                'merchant_request_id' => null,
                'checkout_request_id' => null,
            ])),
        };
    }

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

    private function pickChannel(): array
    {
        $roll = rand(1, 100);
        $cumulative = 0;

        foreach (self::CHANNELS as $channel) {
            $cumulative += $channel['weight'];
            if ($roll <= $cumulative) {
                return $channel;
            }
        }

        return self::CHANNELS[0];
    }

    private function weightedStatus(array $weights): string
    {
        $total = array_sum($weights);
        $roll = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $status => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $status;
            }
        }

        return array_key_first($weights);
    }

    private function randomPrice(): int
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 40 => rand(5, 20) * 100000,
            $roll <= 75 => rand(20, 80) * 100000,
            $roll <= 90 => rand(80, 150) * 100000,
            default => rand(150, 300) * 100000,
        };
    }

    /** @return array{SapSyncStatus, ?string, ?string, ?string} */
    private function sapState(string $status, int $daysAgo): array
    {
        if ($status === 'completed' && $daysAgo > 3) {
            return [
                SapSyncStatus::COMPLETED,
                'DOC-'.fake()->numerify('####'),
                'INV-'.fake()->numerify('####'),
                'KRA-CU-'.fake()->numerify('######'),
            ];
        }

        if (in_array($status, ['completed', 'out_for_delivery'])) {
            return [SapSyncStatus::AWAITING_CU, 'DOC-'.fake()->numerify('####'), 'INV-'.fake()->numerify('####'), null];
        }

        $roll = rand(1, 100);

        if ($roll <= 60) {
            return [SapSyncStatus::AWAITING_CU, 'DOC-'.fake()->numerify('####'), 'INV-'.fake()->numerify('####'), null];
        }

        if ($roll <= 80) {
            return [SapSyncStatus::PENDING, null, null, null];
        }

        if ($roll <= 90) {
            return [SapSyncStatus::SYNCING, null, null, null];
        }

        return [SapSyncStatus::FAILED, null, null, null];
    }
}
