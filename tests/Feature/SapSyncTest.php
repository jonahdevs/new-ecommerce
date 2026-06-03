<?php

use App\Enums\StockStatus;
use App\Jobs\ProcessSapProductSync;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Settings\IntegrationSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Queue::fake();

    app(IntegrationSettings::class)->fill([
        'sap_enabled' => true,
        'sap_sync_price' => true,
        'sap_sync_quantity' => true,
        'sap_webhook_secret' => 'test-secret-123',
    ])->save();
});

function sapHeaders(): array
{
    return ['X-SAP-Secret' => 'test-secret-123'];
}

// ── Controller ────────────────────────────────────────────────────────────────

it('rejects requests with a missing or wrong secret', function () {
    postJson('/api/products/sync', [])->assertUnauthorized();
    postJson('/api/products/sync', [], ['X-SAP-Secret' => 'wrong'])->assertUnauthorized();
});

it('returns 503 when SAP sync is disabled', function () {
    app(IntegrationSettings::class)->fill(['sap_enabled' => false])->save();

    postJson('/api/products/sync', [
        'products' => [['sku' => 'X', 'price' => 100, 'stock_quantity' => 1]],
    ], sapHeaders())->assertServiceUnavailable();

    Queue::assertNothingPushed();
});

it('validates the request body', function () {
    postJson('/api/products/sync', [], sapHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['products']);

    postJson('/api/products/sync', ['products' => [['sku' => 'X']]], sapHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['products.0.price', 'products.0.stock_quantity']);

    Queue::assertNothingPushed();
});

it('accepts a valid batch and returns 202 with the queued job', function () {
    $payload = [
        'products' => [
            ['sku' => 'PROD-001', 'price' => 1500.0, 'stock_quantity' => 50],
            ['sku' => 'PROD-002', 'price' => 2500.0, 'stock_quantity' => 30],
        ],
    ];

    postJson('/api/products/sync', $payload, sapHeaders())
        ->assertAccepted()
        ->assertJson(['success' => true, 'total' => 2]);

    Queue::assertPushed(ProcessSapProductSync::class, function ($job) {
        return count($job->products) === 2
            && $job->products[0]['sku'] === 'PROD-001';
    });
});

// ── Job ───────────────────────────────────────────────────────────────────────

it('syncs price and quantity to a product by sku', function () {
    $product = Product::factory()->create(['sku' => 'PROD-001', 'sale_price' => 1000, 'stock_quantity' => 5]);

    (new ProcessSapProductSync([
        ['sku' => 'PROD-001', 'price' => 2000, 'stock_quantity' => 50],
    ]))->handle(app(IntegrationSettings::class));

    $product->refresh();
    expect($product->sale_price)->toBe(2000)
        ->and($product->stock_quantity)->toBe(50)
        ->and($product->stock_status)->toBe(StockStatus::IN_STOCK)
        ->and($product->sap_last_synced_at)->not->toBeNull();
});

it('sets stock_status to out_of_stock when quantity is zero', function () {
    $product = Product::factory()->create(['sku' => 'PROD-002', 'stock_quantity' => 10]);

    (new ProcessSapProductSync([
        ['sku' => 'PROD-002', 'price' => 500, 'stock_quantity' => 0],
    ]))->handle(app(IntegrationSettings::class));

    expect($product->refresh()->stock_status)->toBe(StockStatus::OUT_OF_STOCK);
});

it('syncs price and quantity to a product variant by sku', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'VAR-001', 'price' => 100, 'stock_quantity' => 3]);

    (new ProcessSapProductSync([
        ['sku' => 'VAR-001', 'price' => 999, 'stock_quantity' => 20],
    ]))->handle(app(IntegrationSettings::class));

    $variant->refresh();
    expect($variant->price)->toBe(999)
        ->and($variant->stock_quantity)->toBe(20)
        ->and($variant->stock_status)->toBe(StockStatus::IN_STOCK)
        ->and($variant->sap_last_synced_at)->not->toBeNull();
});

it('does not update price when sap_sync_price is disabled', function () {
    app(IntegrationSettings::class)->fill(['sap_sync_price' => false])->save();
    $product = Product::factory()->create(['sku' => 'PROD-003', 'sale_price' => 500]);

    (new ProcessSapProductSync([
        ['sku' => 'PROD-003', 'price' => 999, 'stock_quantity' => 10],
    ]))->handle(app(IntegrationSettings::class));

    expect($product->refresh()->sale_price)->toBe(500);
});

it('does not update quantity when sap_sync_quantity is disabled', function () {
    app(IntegrationSettings::class)->fill(['sap_sync_quantity' => false])->save();
    $product = Product::factory()->create(['sku' => 'PROD-004', 'stock_quantity' => 5]);

    (new ProcessSapProductSync([
        ['sku' => 'PROD-004', 'price' => 100, 'stock_quantity' => 999],
    ]))->handle(app(IntegrationSettings::class));

    expect($product->refresh()->stock_quantity)->toBe(5);
});

it('uses only two queries to look up any size batch', function () {
    Product::factory()->count(5)->create();
    $skus = Product::pluck('sku')->toArray();
    $items = array_map(fn ($sku) => ['sku' => $sku, 'price' => 100, 'stock_quantity' => 10], $skus);

    // 2 SELECTs (whereIn products + whereIn variants) + 5 UPDATEs — never N×2 SELECTs
    $queryCount = 0;
    DB::listen(fn ($q) => $queryCount++);

    (new ProcessSapProductSync($items))->handle(app(IntegrationSettings::class));

    expect($queryCount)->toBeLessThanOrEqual(count($skus) + 2);
});

it('attributes synced changes to the SAP source in the activity log', function () {
    $product = Product::factory()->create(['sku' => 'PROD-008', 'sale_price' => 1000, 'stock_quantity' => 5]);

    (new ProcessSapProductSync([
        ['sku' => 'PROD-008', 'price' => 2000, 'stock_quantity' => 50],
    ]))->handle(app(IntegrationSettings::class));

    $activity = $product->activitiesAsSubject()->where('event', 'updated')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->getProperty('source'))->toBe('SAP sync');
});

it('logs a warning for unknown skus without failing the job', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('SAP sync: SKU not found.', ['sku' => 'DOES-NOT-EXIST']);

    (new ProcessSapProductSync([
        ['sku' => 'DOES-NOT-EXIST', 'price' => 100, 'stock_quantity' => 10],
    ]))->handle(app(IntegrationSettings::class));
});
