<?php

namespace Database\Seeders;

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'customer@sheffieldafrica.com')->firstOrFail();
        $products = Product::inRandomOrder()->take(10)->get();

        if ($products->isEmpty()) {
            return;
        }

        // ── Quote 1: AWAITING_APPROVAL — ready for customer to accept or decline ──
        $q1 = Quote::factory()->create([
            'user_id' => $user->id,
            'status' => QuoteStatus::AWAITING_APPROVAL,
            'total_cents' => 64200000,
            'subtotal_cents' => 56000000,
            'vat_cents' => 8200000,
            'vat_rate' => 16,
            'expires_at' => now()->addDays(7),
            'terms' => "Payment is due within 30 days of invoice.\nDelivery within Nairobi is included. Outside Nairobi attracts additional charges.",
        ]);
        // booted() auto-records: null → draft
        $q1->recordStatusChange(QuoteStatus::DRAFT, QuoteStatus::AWAITING_APPROVAL);

        // ── Quote 2: SENT — priced but awaiting internal review before customer sees it ──
        $q2 = Quote::factory()->create([
            'user_id' => $user->id,
            'status' => QuoteStatus::SENT,
            'total_cents' => 16445000,
            'subtotal_cents' => 14175000,
            'vat_cents' => 2270000,
            'vat_rate' => 16,
            'expires_at' => now()->addDays(14),
        ]);
        // booted() auto-records: null → draft
        $q2->recordStatusChange(QuoteStatus::DRAFT, QuoteStatus::SENT);

        // ── Quote 3: DECLINED — customer declined after reviewing ──
        $q3 = Quote::factory()->create([
            'user_id' => $user->id,
            'status' => QuoteStatus::DECLINED,
            'total_cents' => 38900000,
            'subtotal_cents' => 33500000,
            'vat_cents' => 5400000,
            'vat_rate' => 16,
            'expires_at' => now()->subDays(5),
        ]);
        // booted() auto-records: null → draft
        $q3->recordStatusChange(QuoteStatus::DRAFT, QuoteStatus::AWAITING_APPROVAL);
        $q3->recordStatusChange(QuoteStatus::AWAITING_APPROVAL, QuoteStatus::DECLINED);

        // ── Quote 4: DRAFT — fresh request, not yet priced ──
        $q4 = Quote::factory()->create([
            'user_id' => $user->id,
            'status' => QuoteStatus::DRAFT,
            'total_cents' => 0,
        ]);
        // booted() auto-records: null → draft (nothing else to add)

        $quotes = collect([$q1, $q2, $q3, $q4]);

        $quotes->each(function (Quote $quote) use ($products) {
            $items = $products->random(fake()->numberBetween(1, 4));

            foreach ($items as $product) {
                $qty = fake()->numberBetween(1, 2);
                $price = $quote->isPriced()
                    ? ($product->sale_price ?? $product->price ?? 3000000)
                    : 0;

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'product_snapshot' => [
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'model_number' => $product->model_number,
                    ],
                    'unit_price_cents' => $price,
                    'quantity' => $qty,
                    'line_total_cents' => $price * $qty,
                ]);
            }
        });
    }
}
