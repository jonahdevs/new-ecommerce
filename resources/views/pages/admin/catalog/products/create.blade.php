<?php
use Livewire\Attributes\Title;
use App\Livewire\Admin\BaseProductComponent;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

new #[Title('Create Product')] class extends BaseProductComponent {
    protected function executeSave(): void
    {
        $this->authorize('create', Product::class);

        try {
            DB::transaction(function () {
                $product = $this->form->store();
                $this->persistProduct($product);
            });

            $this->dispatch('notify', variant: 'success', message: 'Product created successfully!');
            $this->dispatch('product-saved');
            $this->redirectRoute('admin.products.index', navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Product create failed', ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to create product.');
        }
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.products.index')" wire:navigate>Products
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Create New Product</flux:heading>

        <flux:button variant="primary" type="submit" form="product-form" class="cursor-pointer min-w-32"
            wire:loading.attr="disabled" wire:target="save">
            Create
        </flux:button>
    </div>

    @include('pages.admin.catalog.products.partials._form')
</div>
