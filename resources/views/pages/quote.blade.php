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
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-3 px-4">
                <flux:skeleton animate="shimmer" class="w-4 h-4" />
                <flux:skeleton animate="shimmer" class="w-14 h-4" />
                <flux:skeleton animate="shimmer" class="w-3 h-4" />
                <flux:skeleton animate="shimmer" class="w-20 h-4" />
            </div>
        </div>
        <div class="container mx-auto px-4 py-6 min-h-[80svh]">
            <flux:skeleton animate="shimmer" class="w-40 h-8 mb-6" />
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 lg:col-span-7">
                    <flux:skeleton animate="shimmer" class="w-full h-96 rounded-lg" />
                </div>
                <div class="col-span-12 lg:col-span-5 space-y-3">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="bg-white rounded-lg border p-3 flex gap-3">
                            <flux:skeleton animate="shimmer" class="w-14 h-14 rounded-md shrink-0" />
                            <div class="flex-1 space-y-2">
                                <flux:skeleton animate="shimmer" class="w-3/4 h-4" />
                                <flux:skeleton animate="shimmer" class="w-1/2 h-3" />
                                <flux:skeleton animate="shimmer" class="w-24 h-6" />
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Quote</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-6 min-h-[80svh]">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6 gap-4">
            <flux:heading level="1" class="font-bold! text-xl! sm:text-2xl! lg:text-3xl!">Request Quote
            </flux:heading>
            @if (!$this->isEmpty)
                <flux:button variant="filled" wire:click="clearBasket" size="sm" class="cursor-pointer">
                    Clear all
                </flux:button>
            @endif
        </div>

        @if ($this->isEmpty)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <flux:icon.document-text class="w-16 h-16 sm:w-20 sm:h-20 text-zinc-300 stroke-1 mb-4" />
                <flux:heading level="2"
                    class="text-lg! sm:text-xl! font-semibold! text-zinc-800 dark:text-zinc-100 mb-2">
                    Your quote basket is empty
                </flux:heading>
                <flux:text class="text-zinc-500 mb-8 max-w-md text-xs! sm:text-sm!">
                    Browse our products and click "Add to Quote" on any item that requires custom pricing.
                </flux:text>
                <flux:button href="{{ route('shop.index') }}" wire:navigate variant="primary">
                    Browse Products
                </flux:button>
            </div>
        @else
            <div class="grid grid-cols-12 gap-6 items-start">

                {{-- ── LEFT: FORM (col-span-7) ── --}}
                <div class="col-span-12 lg:col-span-7">
                    <flux:card class="space-y-6">

                        {{-- Contact details --}}
                        <div class="space-y-4 pt-4  ">
                            @if ($this->isGuest)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Full name</flux:label>
                                        <flux:input wire:model="guestName" placeholder="John Kamau" />
                                        <flux:error name="guestName" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Phone number</flux:label>
                                        <flux:input wire:model="guestPhone" type="tel"
                                            placeholder="+254 7XX XXX XXX" />
                                        <flux:error name="guestPhone" />
                                    </flux:field>
                                </div>

                                <flux:field>
                                    <flux:label>Email address</flux:label>
                                    <flux:input wire:model="guestEmail" type="email"
                                        placeholder="john@business.co.ke" />
                                    <flux:error name="guestEmail" />
                                </flux:field>


                                {{-- Delivery location --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <flux:select label="County" wire:model.live="selectedCounty"
                                        placeholder="Select county">
                                        @foreach ($this->counties as $county)
                                            <option value="{{ $county->id }}">{{ $county->name }}</option>
                                        @endforeach
                                    </flux:select>


                                    @if ($this->areas->isNotEmpty())
                                        <flux:select label="Area" wire:model="selectedArea" placeholder="Select area">
                                            @foreach ($this->areas as $area)
                                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error name="selectedArea" />
                                    @else
                                        <flux:select label="Area" disabled placeholder="Select county first" />
                                    @endif

                                </div>
                            @else
                                <div
                                    class="flex items-center gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <flux:icon.user-circle class="size-5 text-zinc-400 shrink-0" />
                                    <div>
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ Auth::user()->name }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            Quote will be sent to {{ Auth::user()->email }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Notes --}}
                        <flux:field>
                            <flux:label>
                                Additional notes
                                <span class="text-zinc-400 font-normal text-xs ml-1">(optional)</span>
                            </flux:label>
                            <flux:textarea wire:model="customerNotes" rows="4"
                                placeholder="Installation requirements, voltage specifications, site access details, number of covers, kitchen layout constraints..." />
                            <flux:error name="customerNotes" />
                        </flux:field>

                        {{-- Info note --}}
                        <div
                            class="flex gap-3 px-4 py-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-100 dark:border-blue-900">
                            <flux:icon.information-circle class="size-5 text-secondary shrink-0 mt-0.5" />
                            <p class="text-sm text-blue-800 dark:text-blue-200 leading-relaxed">
                                Our team will review your request and contact you within 1 business day with a formal
                                quote.
                            </p>
                        </div>

                        {{-- Submit --}}
                        <flux:button wire:click="submit" variant="primary" class="w-full uppercase cursor-pointer"
                            wire:loading.attr="disabled" wire:target="submit" :disabled="$submitting">
                            <span wire:loading.remove wire:target="submit">Submit Quote Request</span>
                            <span wire:loading wire:target="submit">Submitting...</span>
                        </flux:button>

                    </flux:card>
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
                    <div
                        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
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

                            <div wire:key="qi-{{ $item['product_id'] }}-{{ $item['variant_id'] }}"
                                class="flex items-start gap-3 p-4
                                    {{ !$loop->last ? 'border-b border-zinc-100 dark:border-zinc-800' : '' }}">

                                {{-- Image --}}
                                <div
                                    class="w-14 h-14 rounded-md border border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 overflow-hidden flex-shrink-0">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                            class="w-full h-full object-contain" loading="lazy" />
                                    @else
                                        <flux:icon.photo class="w-full h-full p-2 text-zinc-300 stroke-1" />
                                    @endif
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('products.show', $product) }}" wire:navigate
                                        class="text-sm font-medium text-zinc-800 dark:text-zinc-100
                                            hover:text-secondary hover:underline block truncate">
                                        {{ $product->name }}
                                    </a>

                                    @if ($variantAttrs->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach ($variantAttrs as $attrName => $attrValue)
                                                <span
                                                    class="text-[11px] bg-zinc-100 dark:bg-zinc-800
                                                    border border-zinc-200 dark:border-zinc-700
                                                    rounded px-1.5 py-0.5 text-zinc-600 dark:text-zinc-400">
                                                    {{ $attrName }}: {{ $attrValue }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @elseif ($sku)
                                        <p class="text-xs text-zinc-400 mt-1">SKU: {{ $sku }}</p>
                                    @endif

                                    {{-- Quantity stepper + remove --}}
                                    <div class="flex items-center gap-3 mt-2.5">
                                        <div
                                            class="flex items-center border border-zinc-200 dark:border-zinc-700 rounded-md overflow-hidden">
                                            <button type="button"
                                                wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['variant_id'] ?? 'null' }}, {{ $item['quantity'] - 1 }})"
                                                class="w-7 h-7 flex items-center justify-center text-zinc-500
                                                    hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors
                                                    cursor-pointer text-base leading-none">
                                                −
                                            </button>
                                            <span
                                                class="w-8 h-7 flex items-center justify-center text-xs
                                                font-medium text-zinc-800 dark:text-zinc-100
                                                border-l border-r border-zinc-200 dark:border-zinc-700">
                                                {{ $item['quantity'] }}
                                            </span>
                                            <button type="button"
                                                wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['variant_id'] ?? 'null' }}, {{ $item['quantity'] + 1 }})"
                                                class="w-7 h-7 flex items-center justify-center text-zinc-500
                                                    hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors
                                                    cursor-pointer text-base leading-none">
                                                +
                                            </button>
                                        </div>

                                        <button type="button"
                                            wire:click="removeItem({{ $item['product_id'] }}, {{ $item['variant_id'] ?? 'null' }})"
                                            class="ml-auto text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">
                                            <flux:icon.trash class="size-4" variant="outline" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
