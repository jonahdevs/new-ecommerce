<?php

namespace App\Livewire\Concerns;

use App\Models\Attribute as ProductAttribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\TaxClass;
use Livewire\Attributes\Computed;

trait ManagesProductForm
{
    // ── Linked-product search state ────────────────────────────────────────────

    public string $upsellQuery = '';

    public string $crossSellQuery = '';

    public string $accessoryQuery = '';

    // ── Variant image uploads ──────────────────────────────────────────────────

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $variantImages = [];

    // ── Computed data ──────────────────────────────────────────────────────────

    #[Computed]
    public function taxClasses()
    {
        return TaxClass::orderBy('name')->get();
    }

    /**
     * All active attributes with their values (for value checkboxes).
     */
    #[Computed]
    public function availableAttributes()
    {
        return ProductAttribute::with('values')->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Active attributes not yet added to this product (for the "Add existing" dropdown).
     */
    #[Computed]
    public function selectableAttributes()
    {
        $selectedIds = collect($this->form->product_attributes)
            ->where('is_new', false)
            ->pluck('attribute_id')
            ->filter()
            ->all();

        return ProductAttribute::where('is_active', true)
            ->whereNotIn('id', $selectedIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Returns the value options (id + label) for a given attribute ID.
     */
    public function getProductAttributeValues(int $attributeId): array
    {
        return $this->availableAttributes
            ->firstWhere('id', $attributeId)
            ?->values
            ->where('is_active', true)
            ->map(fn ($v) => ['id' => $v->id, 'label' => $v->label])
            ->values()
            ->toArray() ?? [];
    }

    #[Computed]
    public function unpricedVariantsCount(): int
    {
        return collect($this->form->variations)
            ->filter(fn ($v) => $v['price'] === '' || $v['price'] === null)
            ->count();
    }

    #[Computed]
    public function upsellResults(): array
    {
        return $this->searchLinkedProducts($this->upsellQuery);
    }

    #[Computed]
    public function crossSellResults(): array
    {
        return $this->searchLinkedProducts($this->crossSellQuery);
    }

    #[Computed]
    public function accessoryResults(): array
    {
        return $this->searchLinkedProducts($this->accessoryQuery);
    }

    private function searchLinkedProducts(string $query): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $excluded = array_merge(
            [$this->product?->id ?? 0],
            array_column($this->form->upsell_products, 'id'),
            array_column($this->form->cross_sell_products, 'id'),
            array_column($this->form->accessory_products, 'id'),
        );

        return Product::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%");
        })
            ->whereNotIn('id', array_filter($excluded))
            ->limit(8)
            ->get(['id', 'name', 'sku'])
            ->toArray();
    }

    // ── Linked product actions ─────────────────────────────────────────────────

    public function addLinkedProduct(int $productId, string $type): void
    {
        $product = Product::find($productId, ['id', 'name', 'sku']);
        if (! $product) {
            return;
        }

        $data = ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku];
        $list = $this->resolveLinkedList($type);

        if (! collect($this->form->{$list})->contains('id', $productId)) {
            $this->form->{$list}[] = $data;
        }

        match ($type) {
            'upsell' => $this->upsellQuery = '',
            'cross_sell' => $this->crossSellQuery = '',
            'accessory' => $this->accessoryQuery = '',
        };
    }

    public function removeLinkedProduct(int $index, string $type): void
    {
        $list = $this->resolveLinkedList($type);
        array_splice($this->form->{$list}, $index, 1);
        $this->form->{$list} = array_values($this->form->{$list});
    }

    private function resolveLinkedList(string $type): string
    {
        return match ($type) {
            'upsell' => 'upsell_products',
            'cross_sell' => 'cross_sell_products',
            'accessory' => 'accessory_products',
        };
    }

    // ── Attribute actions ──────────────────────────────────────────────────────

    public function addNewAttribute(): void
    {
        $this->form->product_attributes[] = [
            'attribute_id'           => null,
            'name'                   => '',
            'values'                 => '',
            'is_variation_attribute' => false,
            'is_visible'             => true,
            'is_new'                 => true,
        ];
    }

    public function addExistingAttribute(int $attributeId): void
    {
        $attr = $this->availableAttributes->firstWhere('id', $attributeId);

        if (! $attr) {
            return;
        }

        $alreadyAdded = collect($this->form->product_attributes)
            ->where('is_new', false)
            ->pluck('attribute_id')
            ->contains($attributeId);

        if ($alreadyAdded) {
            return;
        }

        $this->form->product_attributes[] = [
            'attribute_id'           => $attr->id,
            'name'                   => $attr->name,
            'values'                 => [],
            'is_variation_attribute' => false,
            'is_visible'             => true,
            'is_new'                 => false,
        ];
    }

    public function removeSelectedAttribute(int $index): void
    {
        array_splice($this->form->product_attributes, $index, 1);
        $this->form->product_attributes = array_values($this->form->product_attributes);
    }

    // ── Variation actions ──────────────────────────────────────────────────────

    public function addVariant(): void
    {
        $this->form->variations[] = $this->blankVariation();
    }

    public function removeVariant(int $index): void
    {
        array_splice($this->form->variations, $index, 1);
        $this->form->variations = array_values($this->form->variations);

        unset($this->variantImages[$index]);
        $this->variantImages = array_values($this->variantImages);
    }

    public function removeVariantImage(int $index): void
    {
        unset($this->variantImages[$index]);
        if (isset($this->form->variations[$index])) {
            $this->form->variations[$index]['image_path'] = null;
        }
    }

    public function toggleAllVariantsActive(): void
    {
        $allActive = collect($this->form->variations)->every(fn ($v) => $v['is_active']);
        $this->form->variations = array_map(function ($v) use ($allActive) {
            $v['is_active'] = ! $allActive;

            return $v;
        }, $this->form->variations);
    }

    public function toggleAllVariantsManageStock(): void
    {
        $allManage = collect($this->form->variations)->every(fn ($v) => $v['manage_stock']);
        $this->form->variations = array_map(function ($v) use ($allManage) {
            $v['manage_stock'] = ! $allManage;

            return $v;
        }, $this->form->variations);
    }

    public function setAllVariantsStockStatus(string $status): void
    {
        $this->form->variations = array_map(function ($v) use ($status) {
            $v['stock_status'] = $status;

            return $v;
        }, $this->form->variations);
    }

    public function clearAllVariants(): void
    {
        $this->form->variations = [];
        $this->variantImages = [];
    }

    public function regenerateVariations(): void
    {
        $this->generateVariations();
    }

    public function generateVariations(): void
    {
        // Only use existing (non-new) attributes flagged for variations with selected values
        $variationAttrs = array_values(array_filter(
            $this->form->product_attributes,
            fn ($a) => ! ($a['is_new'] ?? false)
                && ! empty($a['is_variation_attribute'])
                && ! empty($a['values'])
        ));

        if (empty($variationAttrs)) {
            $this->dispatch('notify', title: 'No Variation Attributes', variant: 'warning',
                message: 'Mark at least one attribute as "Used for variations" and select its values first.');

            return;
        }

        $allValueIds = array_merge(...array_column($variationAttrs, 'values'));
        $valueLabels = AttributeValue::whereIn('id', $allValueIds)->pluck('label', 'id')->toArray();

        $valueSets = array_column($variationAttrs, 'values');
        $combinations = $this->cartesianProduct($valueSets);

        $added = 0;
        foreach ($combinations as $combo) {
            $sorted = $combo;
            sort($sorted);
            $exists = collect($this->form->variations)->contains(function ($v) use ($sorted) {
                $attrs = $v['attributes'] ?? [];
                sort($attrs);

                return $attrs === $sorted;
            });

            if (! $exists) {
                $name = implode(' / ', array_map(fn ($id) => $valueLabels[$id] ?? "Value {$id}", $combo));
                $this->form->variations[] = $this->blankVariation($name, $combo);
                $added++;
            }
        }

        $this->dispatch(
            'notify',
            title: $added > 0 ? 'Variations Generated' : 'No New Variations',
            variant: $added > 0 ? 'success' : 'info',
            message: $added > 0 ? "{$added} variation(s) added." : 'All combinations already exist.',
        );
    }

    /**
     * Stores pending variant image uploads and writes their paths into form->variations.
     * Must be called before form->store() / form->update().
     */
    protected function processVariantImages(): void
    {
        foreach ($this->variantImages as $index => $image) {
            if ($image && is_object($image) && isset($this->form->variations[$index])) {
                $this->form->variations[$index]['image_path'] = $image->store('products/variants', 'public');
            }
        }
    }

    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) {
            return [[]];
        }
        $result = [[]];
        foreach ($sets as $set) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($set as $value) {
                    $newResult[] = [...$existing, $value];
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    private function blankVariation(string $name = '', array $attributes = []): array
    {
        return [
            'id'                     => null,
            'name'                   => $name,
            'sku'                    => '',
            'price'                  => '',
            'sale_price'             => '',
            'cost_price'             => '',
            'manage_stock'           => false,
            'stock_quantity'         => 0,
            'stock_status'           => 'in_stock',
            'allow_backorders'       => false,
            'backorder_message'      => '',
            'max_backorder_quantity' => '',
            'expected_restock_date'  => '',
            'low_stock_threshold'    => '',
            'weight'                 => '',
            'height'                 => '',
            'width'                  => '',
            'length'                 => '',
            'image_path'             => null,
            'is_default'             => false,
            'is_active'              => true,
            'description'            => '',
            'attributes'             => $attributes,
        ];
    }
}
