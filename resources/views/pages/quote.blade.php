<?php

use App\Models\County;
use App\Models\Area;
use App\Services\QuoteBasketService;
use App\Services\QuotationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    #[Validate('nullable|integer|exists:counties,id')]
    public ?int $selectedCounty = null;

    #[Validate('nullable|integer|exists:areas,id')]
    public ?int $selectedArea = null;

    #[Validate('nullable|string|max:1000')]
    public string $customerNotes = '';

    #[Validate('required_if:isGuest,true|string|max:100')]
    public string $guestName = '';

    #[Validate('required_if:isGuest,true|string|max:20')]
    public string $guestPhone = '';

    #[Validate('required_if:isGuest,true|email|max:150')]
    public string $guestEmail = '';

    public bool $submitting = false;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        if (Auth::check()) {
            $user = Auth::user();
            $this->guestName = $user->name ?? '';
            $this->guestEmail = $user->email ?? '';
            $this->guestPhone = $user->phone ?? '';
        }
    }

    #[Computed]
    public function isGuest(): bool
    {
        return !Auth::check();
    }

    #[Computed]
    public function basketItems()
    {
        return app(QuoteBasketService::class)->hydratedItems();
    }

    #[Computed]
    public function isEmpty(): bool
    {
        return $this->basketItems->isEmpty();
    }

    #[Computed(persist: true)]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function areas()
    {
        if (!$this->selectedCounty) {
            return collect();
        }
        return Area::where('county_id', $this->selectedCounty)->orderBy('name')->get();
    }

    public function updatedSelectedCounty(): void
    {
        $this->selectedArea = null;
        unset($this->areas);
    }

    public function updateQuantity(int $productId, ?int $variantId, int $quantity): void
    {
        app(QuoteBasketService::class)->updateQuantity($productId, $variantId, $quantity);
        unset($this->basketItems, $this->isEmpty);
    }

    public function removeItem(int $productId, ?int $variantId = null): void
    {
        app(QuoteBasketService::class)->remove($productId, $variantId);
        unset($this->basketItems, $this->isEmpty);
        $this->dispatch('quote-basket-updated');
        $this->dispatch('notify', variant: 'success', message: 'Item removed from quote basket');
    }

    #[\Livewire\Attributes\On('quote-item-added')]
    #[\Livewire\Attributes\On('quote-item-removed')]
    public function refreshBasket(): void
    {
        unset($this->basketItems, $this->isEmpty);
    }

    public function clearBasket(): void
    {
        app(QuoteBasketService::class)->clear();
        unset($this->basketItems, $this->isEmpty);
        $this->dispatch('quote-basket-updated');
    }

    public function submit(QuotationService $quotationService): void
    {
        $this->validate();

        if ($this->isEmpty) {
            $this->dispatch('notify', variant: 'warning', message: 'Your quote basket is empty.');
            return;
        }

        $this->submitting = true;

        try {
            $county = $this->selectedCounty ? County::find($this->selectedCounty)?->name : null;

            $area = $this->selectedArea ? Area::find($this->selectedArea)?->name : null;

            $order = $quotationService->createFromBasket(app(QuoteBasketService::class), [
                'preferred_county' => $county,
                'preferred_area' => $area,
                'customer_notes' => $this->customerNotes ?: null,
                'name' => $this->guestName,
                'email' => $this->guestEmail,
                'phone' => $this->guestPhone,
            ]);

            unset($this->basketItems, $this->isEmpty);
            $this->dispatch('quote-basket-updated');

            $this->redirect(route('checkout.quote-success', $order->reference), navigate: true);
        } catch (\Throwable $th) {
            $this->submitting = false;
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to submit quote request. Please try again.');
        }
    }
};
?>

@placeholder
    <div>
        {{-- Breadcrumb placeholder --}}
        <div class="bg-white border-b border-zinc-200 py-3">
            <div class="container mx-auto px-4">
                <div class="flex items-center gap-3">
                    <flux:skeleton animate="shimmer" class="w-12 h-4" />
                    <flux:skeleton animate="shimmer" class="w-3 h-4" />
                    <flux:skeleton animate="shimmer" class="w-16 h-4" />
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4 py-6 min-h-[80svh]">
            {{-- Header placeholder --}}
            <div class="flex items-center justify-between mb-6 gap-4">
                <flux:skeleton animate="shimmer" class="w-48 h-8" />
                <div class="flex items-center gap-2">
                    <flux:skeleton animate="shimmer" class="w-28 h-10 rounded-md" />
                    <flux:skeleton animate="shimmer" class="w-24 h-10 rounded-md" />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-6 items-start">
                {{-- Left: Form placeholder --}}
                <div class="col-span-12 lg:col-span-7 space-y-6">
                    {{-- Contact fields --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <flux:skeleton animate="shimmer" class="w-24 h-4" />
                            <flux:skeleton animate="shimmer" class="w-full h-10 rounded-md" />
                        </div>
                        <div class="space-y-2">
                            <flux:skeleton animate="shimmer" class="w-28 h-4" />
                            <flux:skeleton animate="shimmer" class="w-full h-10 rounded-md" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <flux:skeleton animate="shimmer" class="w-32 h-4" />
                        <flux:skeleton animate="shimmer" class="w-full h-10 rounded-md" />
                    </div>
                    {{-- Location fields --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <flux:skeleton animate="shimmer" class="w-16 h-4" />
                            <flux:skeleton animate="shimmer" class="w-full h-10 rounded-md" />
                        </div>
                        <div class="space-y-2">
                            <flux:skeleton animate="shimmer" class="w-12 h-4" />
                            <flux:skeleton animate="shimmer" class="w-full h-10 rounded-md" />
                        </div>
                    </div>
                    {{-- Notes field --}}
                    <div class="space-y-2">
                        <flux:skeleton animate="shimmer" class="w-36 h-4" />
                        <flux:skeleton animate="shimmer" class="w-full h-24 rounded-md" />
                    </div>
                    {{-- Info box --}}
                    <flux:skeleton animate="shimmer" class="w-full h-16 rounded-md" />
                    {{-- Submit button --}}
                    <flux:skeleton animate="shimmer" class="w-full h-12 rounded-md" />
                </div>

                {{-- Right: Items placeholder --}}
                <div class="col-span-12 lg:col-span-5 space-y-4">
                    <flux:skeleton animate="shimmer" class="w-32 h-4" />
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="bg-zinc-50 border-b border-zinc-200 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <flux:skeleton animate="shimmer" class="w-20 h-4" />
                                <flux:skeleton animate="shimmer" class="w-24 h-4" />
                            </div>
                        </div>
                        @for ($i = 0; $i < 3; $i++)
                            <div class="px-6 py-5 border-b border-zinc-200 last:border-b-0">
                                <div class="flex items-center gap-4">
                                    <flux:skeleton animate="shimmer" class="w-16 h-16 rounded shrink-0" />
                                    <div class="flex-1 space-y-2">
                                        <flux:skeleton animate="shimmer" class="w-3/4 h-4" />
                                        <flux:skeleton animate="shimmer" class="w-1/2 h-3" />
                                        <flux:skeleton animate="shimmer" class="w-20 h-3" />
                                    </div>
                                    <flux:skeleton animate="shimmer" class="w-24 h-8 rounded" />
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    {{-- Breadcrumb --}}
    <div class="bg-white border-b border-zinc-200 py-3">
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Quote</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-6 min-h-[80svh]">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6 gap-4">
            <flux:heading level="1" class="font-bold! text-xl! sm:text-2xl! lg:text-3xl!">Request Quote
            </flux:heading>
            <div class="flex items-center gap-2">
                <flux:modal.trigger name="quote-product-picker">
                    <flux:button size="customer" variant="customer-primary" icon="plus" class="cursor-pointer">
                        Add Items
                    </flux:button>
                </flux:modal.trigger>
                @if (!$this->isEmpty)
                    <flux:button variant="customer-outline" wire:click="clearBasket" size="customer"
                        class="cursor-pointer">
                        Clear all
                    </flux:button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6 items-start">

            {{-- ── LEFT: FORM (col-span-7) ── --}}
            <div class="col-span-12 lg:col-span-7">
                @php
                    $selectArrow =
                        "appearance-none bg-[url('data:image/svg+xml,%3Csvg_xmlns=%22http://www.w3.org/2000/svg%22_width=%2210%22_height=%226%22%3E%3Cpath_d=%22M0_0l5_6_5-6z%22_fill=%22%23888%22/%3E%3C/svg%3E')] bg-no-repeat bg-[right_12px_center]";
                @endphp
                <div class="space-y-6">

                    {{-- Contact details --}}
                    <div class="space-y-4">
                        @if ($this->isGuest)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-customer.form-field label="Full Name" name="guestName" :required="true">
                                    <input type="text" wire:model="guestName" class="customer-input"
                                        placeholder="John Kamau" />
                                </x-customer.form-field>
                                <x-customer.form-field label="Phone Number" name="guestPhone" :required="true">
                                    <input type="tel" wire:model="guestPhone" class="customer-input"
                                        placeholder="+254 7XX XXX XXX" />
                                </x-customer.form-field>
                            </div>

                            <x-customer.form-field label="Email Address" name="guestEmail" :required="true">
                                <input type="email" wire:model="guestEmail" class="customer-input"
                                    placeholder="john@business.co.ke" />
                            </x-customer.form-field>

                            {{-- Delivery location --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-customer.form-field label="County" name="selectedCounty">
                                    <select wire:model.live="selectedCounty"
                                        class="customer-input {{ $selectArrow }}">
                                        <option value="">Select county...</option>
                                        @foreach ($this->counties as $county)
                                            <option value="{{ $county->id }}">{{ $county->name }}</option>
                                        @endforeach
                                    </select>
                                </x-customer.form-field>

                                <x-customer.form-field label="Area" name="selectedArea">
                                    @if ($this->areas->isNotEmpty())
                                        <select wire:model="selectedArea" class="customer-input {{ $selectArrow }}">
                                            <option value="">Select area...</option>
                                            @foreach ($this->areas as $area)
                                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <select disabled
                                            class="customer-input {{ $selectArrow }} opacity-50 cursor-not-allowed">
                                            <option>Select county first</option>
                                        </select>
                                    @endif
                                </x-customer.form-field>
                            </div>
                        @else
                            {{-- Authenticated: show pre-populated fields (readonly for name/email) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-customer.form-field label="Full Name" name="guestName">
                                    <input type="text" wire:model="guestName"
                                        class="customer-input bg-zinc-50 dark:bg-zinc-800/50" readonly />
                                </x-customer.form-field>
                                <x-customer.form-field label="Phone Number" name="guestPhone">
                                    <input type="tel" wire:model="guestPhone" class="customer-input"
                                        placeholder="+254 7XX XXX XXX" />
                                </x-customer.form-field>
                            </div>

                            <x-customer.form-field label="Email Address" name="guestEmail">
                                <input type="email" wire:model="guestEmail"
                                    class="customer-input bg-zinc-50 dark:bg-zinc-800/50" readonly />
                            </x-customer.form-field>

                            {{-- Delivery location --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-customer.form-field label="County" name="selectedCounty">
                                    <select wire:model.live="selectedCounty"
                                        class="customer-input {{ $selectArrow }}">
                                        <option value="">Select county...</option>
                                        @foreach ($this->counties as $county)
                                            <option value="{{ $county->id }}">{{ $county->name }}</option>
                                        @endforeach
                                    </select>
                                </x-customer.form-field>

                                <x-customer.form-field label="Area" name="selectedArea">
                                    @if ($this->areas->isNotEmpty())
                                        <select wire:model="selectedArea" class="customer-input {{ $selectArrow }}">
                                            <option value="">Select area...</option>
                                            @foreach ($this->areas as $area)
                                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <select disabled
                                            class="customer-input {{ $selectArrow }} opacity-50 cursor-not-allowed">
                                            <option>Select county first</option>
                                        </select>
                                    @endif
                                </x-customer.form-field>
                            </div>
                        @endif
                    </div>

                    {{-- Notes --}}
                    <x-customer.form-field label="Additional Notes" name="customerNotes"
                        hint="Optional — installation requirements, voltage specs, site access details...">
                        <textarea wire:model="customerNotes" rows="4" class="customer-input resize-none"
                            placeholder="Installation requirements, voltage specifications, site access details, number of covers, kitchen layout constraints..."></textarea>
                    </x-customer.form-field>

                    {{-- Info note --}}
                    <div
                        class="flex gap-3 px-4 py-3 bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900">
                        <flux:icon.information-circle class="size-5 text-secondary shrink-0 mt-0.5" />
                        <p class="text-sm text-blue-800 dark:text-blue-200 leading-relaxed">
                            Our team will review your request and contact you within 1 business day with a formal quote.
                        </p>
                    </div>

                    {{-- Submit --}}
                    <flux:button wire:click="submit" variant="customer-primary" size="customer-lg"
                        class="w-full cursor-pointer" wire:loading.attr="disabled" wire:target="submit"
                        :disabled="$submitting || $this->isEmpty">
                        <span wire:loading.remove wire:target="submit">Submit Quote Request</span>
                        <span wire:loading wire:target="submit">Submitting...</span>
                    </flux:button>

                </div>
            </div>

            {{-- ── RIGHT: ITEMS (col-span-5) ── --}}
            <div class="col-span-12 lg:col-span-5 space-y-4 lg:sticky lg:top-44">

                <div class="flex items-center justify-between">
                    <p class="text-xs sm:text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ $this->basketItems->count() }}
                        {{ Str::plural('item', $this->basketItems->count()) }} in your quote
                    </p>
                </div>

                {{-- Items list --}}
                <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                    @if ($this->isEmpty)
                        <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                            <flux:icon.document-text class="w-12 h-12 text-zinc-300 stroke-1 mb-3" />
                            <p class="text-sm font-medium text-zinc-700 mb-1">Your quote basket is empty</p>
                            <p class="text-xs text-zinc-400 mb-4">Search and add products you'd like to request a quote
                                for.</p>
                            <flux:modal.trigger name="quote-product-picker">
                                <flux:button size="customer" variant="customer-primary" icon="plus"
                                    class="cursor-pointer">
                                    Add Items
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                    @else
                        <table class="w-full">
                            <thead class="bg-zinc-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Product
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Quantity
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200">
                                @foreach ($this->basketItems as $item)
                                    @php
                                        $variant = $item['variant'];
                                        $product = $item['product'];
                                        $imageUrl = $variant?->image_path
                                            ? Storage::url($variant->image_path)
                                            : $product->image_url;
                                        $sku = $variant?->sku ?? $product->sku;
                                        $variantAttrs = $variant
                                            ? $variant->attributeValues->mapWithKeys(
                                                fn($av) => [$av->attribute->name => $av->label ?: $av->value],
                                            )
                                            : collect();
                                    @endphp

                                    <tr wire:key="qi-{{ $item['product_id'] }}-{{ $item['variant_id'] }}">

                                        {{-- Product column --}}
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="w-16 h-16 rounded border border-zinc-200 bg-zinc-50 overflow-hidden shrink-0">
                                                    @if ($imageUrl)
                                                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                                            class="w-full h-full object-contain" loading="lazy" />
                                                    @else
                                                        <flux:icon.photo
                                                            class="w-full h-full p-2 text-zinc-300 stroke-1" />
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    @if ($product->brand)
                                                        <p
                                                            class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 mb-0.5">
                                                            {{ $product->brand->name }}
                                                        </p>
                                                    @endif
                                                    <a href="{{ route('products.show', $product) }}" wire:navigate
                                                        class="text-sm font-medium text-zinc-950 hover:underline block leading-snug mb-1">
                                                        {{ $product->name }}
                                                    </a>
                                                    @if ($variantAttrs->isNotEmpty())
                                                        <div class="flex flex-wrap gap-1 mb-1">
                                                            @foreach ($variantAttrs as $attrName => $attrValue)
                                                                <span
                                                                    class="text-[10px] text-zinc-500">{{ $attrName }}:
                                                                    {{ $attrValue }}</span>
                                                            @endforeach
                                                        </div>
                                                    @elseif ($sku)
                                                        <p class="text-[10px] text-zinc-400 mb-1">SKU:
                                                            {{ $sku }}</p>
                                                    @endif
                                                    <button type="button"
                                                        wire:click="removeItem({{ $item['product_id'] }}, {{ $item['variant_id'] ?? 'null' }})"
                                                        class="text-[11px] text-zinc-500 hover:text-red-500 transition-colors cursor-pointer">
                                                        <flux:icon.trash class="size-3 inline mr-0.5" />
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Quantity column --}}
                                        <td class="px-4 py-5 text-center">
                                            <div class="flex items-center justify-center">
                                                <div
                                                    class="flex items-center border border-zinc-200 rounded overflow-hidden">
                                                    <button type="button"
                                                        wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['variant_id'] ?? 'null' }}, {{ $item['quantity'] - 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 transition-colors border-r border-zinc-200 cursor-pointer">
                                                        <flux:icon.minus class="size-3" />
                                                    </button>
                                                    <span
                                                        class="w-10 h-8 flex items-center justify-center text-sm font-medium bg-white">
                                                        {{ $item['quantity'] }}
                                                    </span>
                                                    <button type="button"
                                                        wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['variant_id'] ?? 'null' }}, {{ $item['quantity'] + 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 transition-colors border-l border-zinc-200 cursor-pointer">
                                                        <flux:icon.plus class="size-3" />
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Product Picker Modal --}}
    <flux:modal name="quote-product-picker" class="w-full max-w-4xl p-0!">
        <div
            class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 shrink-0">
            <flux:heading size="base">Add Items to Quote</flux:heading>
        </div>
        <div class="h-[70vh] overflow-hidden flex flex-col">
            <livewire:quote-product-picker x-on:quote-item-added.window="$wire.$refresh()"
                x-on:quote-item-removed.window="$wire.$refresh()" />
        </div>
    </flux:modal>

</div>
