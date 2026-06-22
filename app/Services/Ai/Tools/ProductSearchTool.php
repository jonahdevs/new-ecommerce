<?php

namespace App\Services\Ai\Tools;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Lets the assistant search the live catalogue. Mirrors the storefront search
 * query (visible + published only) so the bot can only surface real, buyable
 * products — never invented ones.
 */
class ProductSearchTool implements Tool
{
    private const LIMIT = 5;

    public function name(): string
    {
        return 'search_products';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Search the Sheffield catalogue for real commercial equipment by keyword '
                    .'(product name, brand, category, SKU or model number). Always call this before recommending '
                    .'a product or answering "what do you have" / "do you sell" questions.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search keywords, e.g. "combi oven", "Rational", "blast chiller", "undercounter fridge".',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    public function handle(array $arguments): string
    {
        $query = trim((string) ($arguments['query'] ?? ''));

        if (Str::length($query) < 2) {
            return $this->encode([], 'Query too short — ask the customer for a more specific keyword.');
        }

        $products = Product::query()
            ->with(['brand', 'primaryCategory'])
            ->visibleInSearch()
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('model_number', 'like', "%{$query}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('primaryCategory', fn ($c) => $c->where('name', 'like', "%{$query}%"));
            })
            ->take(self::LIMIT)
            ->get();

        if ($products->isEmpty()) {
            return $this->encode([], 'No matching products. Suggest browsing the catalogue or requesting a quote.');
        }

        $rows = $products->map(function (Product $product) {
            $price = $product->sale_price ?? $product->price;
            $buyOnline = ! $product->requires_quotation && $price > 0;

            return [
                'name' => $product->name,
                'brand' => $product->brand?->name,
                'category' => $product->primaryCategory?->name,
                'summary' => Str::of(strip_tags((string) $product->short_description))->squish()->limit(160)->value(),
                'price' => $buyOnline ? money($price) : null,
                'purchase' => $buyOnline ? 'Can be bought online at checkout or via a quote' : 'Quote only — request a quote for pricing',
                'url' => route('product.show', $product),
            ];
        })->all();

        return $this->encode($rows, 'Recommend the 1-3 most relevant products. ALWAYS include each product\'s url as a link. '
            .'Only mention products listed here. If a product can be bought online, say so and share its price; otherwise invite a quote.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    private function encode(array $products, string $note): string
    {
        return (string) json_encode(
            ['products' => $products, 'note' => $note],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
