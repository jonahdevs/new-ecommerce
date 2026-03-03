<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\Admin\ProductForm;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Services\Product\ProductAttributeService;
use App\Services\Product\ProductVariationService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class BaseProductComponent extends Component
{
    use WithFileUploads;

    public ProductForm $form;
    public string $activeTab = 'general';
    public bool $addNewCategory = false;
    public bool $addNewBrand = false;
    public bool $showTypeChangeModal = false;
    public string $pendingProductType = '';

    // -----------------------------------------------
    // Two-phase save state
    // -----------------------------------------------

    public array $collectedAttributes = [];
    public array $collectedVariants = [];
    public array $collectedVariantsToDelete = [];
    public int $stateCollected = 0;

    // -----------------------------------------------
    // Entry point — validate then collect state
    // -----------------------------------------------

    public function save(): void
    {
        try {
            $this->form->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields.');
            throw $e;
        }

        // Reset collection counters
        $this->collectedAttributes = [];
        $this->collectedVariants = [];
        $this->collectedVariantsToDelete = [];
        $this->stateCollected = 0;

        // Ask children to push their state up
        $this->dispatch('push-state-to-parent');
    }

    // -----------------------------------------------
    // Collect state from children
    // -----------------------------------------------

    #[On('attributes-state-ready')]
    public function onAttributesReady(array $attributes): void
    {
        $this->collectedAttributes = $attributes;
        $this->stateCollected++;
        $this->attemptSave();
    }

    #[On('variants-state-ready')]
    public function onVariantsReady(array $variants, array $toDelete): void
    {
        $this->collectedVariants = $variants;
        $this->collectedVariantsToDelete = $toDelete;
        $this->stateCollected++;
        $this->attemptSave();
    }

    // -----------------------------------------------
    // Attempt save once both children have responded
    // -----------------------------------------------

    private function attemptSave(): void
    {
        if ($this->stateCollected < 2) return;

        $this->executeSave();
    }

    // -----------------------------------------------
    // Execute save — implemented by Create/Edit
    // -----------------------------------------------

    abstract protected function executeSave(): void;

    // -----------------------------------------------
    // Shared save logic using services
    // -----------------------------------------------

    protected function persistProduct(Product $product): void
    {
        app(ProductAttributeService::class)->save(
            $product,
            $this->collectedAttributes
        );

        app(ProductVariationService::class)->save(
            $product,
            $this->collectedVariants,
            $this->collectedVariantsToDelete
        );
    }

    // -----------------------------------------------
    // Product Type Switching
    // -----------------------------------------------

    public function updatedFormType(string $value): void
    {
        $productId = $this->form->getProductId();

        if (
            $value === 'simple' && $productId &&
            app(ProductVariationService::class)->hasActiveVariants($productId)
        ) {
            $this->pendingProductType = $value;
            $this->form->type = 'variable';
            $this->showTypeChangeModal = true;
            return;
        }

        if ($value === 'variable' && $productId) {
            app(ProductVariationService::class)->reactivateAll($productId);
            $this->dispatch('reactivate-all-variants');
            $this->dispatch('notify', variant: 'success', message: 'Variations restored.');
        }
    }

    public function confirmTypeChange(): void
    {
        $productId = $this->form->getProductId();

        if ($productId) {
            app(ProductVariationService::class)->deactivateAll($productId);
        }

        $this->form->type = $this->pendingProductType;
        $this->pendingProductType = '';
        $this->showTypeChangeModal = false;
        $this->dispatch('deactivate-all-variants');
        $this->dispatch('notify', variant: 'warning', message: 'Switched to Simple. All variations deactivated.');
    }

    public function cancelTypeChange(): void
    {
        $this->form->type = 'variable';
        $this->pendingProductType = '';
        $this->showTypeChangeModal = false;
    }

    // -----------------------------------------------
    // Computed Properties
    // -----------------------------------------------

    #[Computed]
    public function products()
    {
        return Product::active()->orderBy('name')->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::active()->ordered()->get();
    }

    #[Computed]
    public function categories()
    {
        $categories = Category::active()
            ->ordered()
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        return $this->flattenCategories($categories);
    }

    #[Computed]
    public function allCategories()
    {
        return Category::active()->orderBy('name')->get();
    }

    #[Computed]
    public function selectedTags()
    {
        return $this->form->getSelectedTags();
    }

    #[Computed]
    public function mostUsedTags()
    {
        return Tag::withCount('products')
            ->orderByDesc('products_count')
            ->limit(20)
            ->get();
    }

    // -----------------------------------------------
    // Tab Error Indicators
    // -----------------------------------------------

    public function hasGeneralErrors(): bool
    {
        return $this->getErrorBag()->hasAny(['form.price', 'form.sale_price']);
    }

    public function hasInventoryErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.sku',
            'form.manage_stock',
            'form.stock_quantity',
            'form.allow_backorder',
            'form.low_stock_threshold',
            'form.stock_status',
            'form.sold_individually',
        ]);
    }

    public function hasShippingErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.weight',
            'form.length',
            'form.width',
            'form.height',
        ]);
    }

    public function hasLinkedProductsErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.selectedUpsells',
            'form.selectedCrossSells',
        ]);
    }

    public function hasAttributesErrors(): bool
    {
        return false;
    }
    public function hasVariationsErrors(): bool
    {
        return false;
    }
    public function hasAdvancedErrors(): bool
    {
        return false;
    }

    // -----------------------------------------------
    // Tag Methods
    // -----------------------------------------------

    public function addTags(): void
    {
        $this->form->addTags();
    }

    public function removeTag(int $tagId): void
    {
        $this->form->removeTag($tagId);
    }

    // -----------------------------------------------
    // Brand Creation
    // -----------------------------------------------

    public function createBrand(): void
    {
        $this->form->createBrand();
        $this->addNewBrand = false;
        unset($this->brands);
    }

    public function cancelBrandCreation(): void
    {
        $this->form->resetBrandForm();
        $this->addNewBrand = false;
    }

    // -----------------------------------------------
    // Category Creation
    // -----------------------------------------------

    public function createCategory(): void
    {
        $this->form->createCategory();
        $this->addNewCategory = false;
        unset($this->categories);
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    protected function flattenCategories($categories, $depth = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id'    => $category->id,
                'name'  => $category->name,
                'depth' => $depth,
            ];

            if ($category->children->isNotEmpty()) {
                $result = array_merge(
                    $result,
                    $this->flattenCategories($category->children, $depth + 1)
                );
            }
        }

        return $result;
    }
}
