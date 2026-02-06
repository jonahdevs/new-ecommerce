<?php
use App\Models\Product;
use App\Livewire\Forms\Admin\ProductForm;
use Livewire\Component;

new class extends Component {
    public ProductForm $form;

    public function save()
    {
        $product = $this->form->store();
        session()->flash('status', 'Product master created. Now configure details.');

        return redirect()->route('admin.products.edit', $product);
    }
}; ?>

<div class="max-w-4xl mx-auto p-6">
    <flux:header>
        <flux:heading size="xl">Create New Product</flux:heading>
        <flux:subheading>Step 1: Define basic identity and pricing</flux:subheading>
    </flux:header>

    <flux:separator class="my-6" />

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-6">
                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:input label="Product Name" wire:model="form.name" placeholder="e.g. Wireless Headphones" />
                    <flux:textarea label="Short Description" wire:model="form.short_description" rows="2" />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:select label="Product Type" wire:model="form.type">
                            <option value="simple">Simple Product</option>
                            <option value="variable">Variable Product</option>
                        </flux:select>
                        <flux:input label="SKU (Stock Keeping Unit)" wire:model="form.sku" />
                    </div>
                </section>

                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">Pricing</flux:heading>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input type="number" step="0.01" label="Regular Price" wire:model="form.price"
                            icon="banknotes" />
                        <flux:input type="number" step="0.01" label="Sale Price" wire:model="form.sale_price"
                            icon="tag" />
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">Status & Flags</flux:heading>
                    <flux:switch label="Active" wire:model="form.is_active" />
                    <flux:switch label="Featured" wire:model="form.is_featured" />
                    <flux:switch label="Mark as New" wire:model="form.is_new" />
                </section>

                <flux:button type="submit" variant="primary" class="w-full">Create Product & Continue</flux:button>
                <flux:button href="{{ route('admin.products') }}" variant="ghost" class="w-full">Cancel</flux:button>
            </div>
        </div>
    </form>
</div>
