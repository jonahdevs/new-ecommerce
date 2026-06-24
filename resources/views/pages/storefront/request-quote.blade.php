<?php

use App\Enums\QuoteStatus;
use App\Events\QuoteRequestSubmitted;
use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Notifications\Quotes\NewQuoteRequested;
use App\Notifications\Quotes\QuoteRequestReceived;
use App\Rules\Recaptcha;
use App\Services\DeliveryResolver;
use App\Settings\QuotationSettings;
use App\Support\StaffRecipients;
use App\Support\StorefrontSession;
use App\Support\TaxCalculator;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Request a quote')] class extends Component {
    /** @var array<string, int> */
    public array $items = [];

    public string $itemSearch = '';

    public bool $showItemModal = false;

    public int $itemsPerPage = 18;

    public string $notes = '';

    public bool $needs_delivery = false;

    public string $delivery_address = '';

    // ==================================================
    // ADDRESS (AUTHENTICATED USERS, DELIVERY ONLY)
    // ==================================================
    public ?int $selectedAddressId = null;

    public bool $showAddressModal = false;

    public string $addressModalMode = 'select';

    public string $label = 'Home';

    public string $name = '';

    public string $phone = '';

    public string $alternative_phone = '';

    public string $line1 = '';

    public string $delivery_instructions = '';

    public bool $is_default = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $contact_name = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $contact_company = '';

    public string $recaptchaToken = '';

    public function mount(): void
    {
        abort_unless(app(QuotationSettings::class)->quotes_enabled, 404);

        SEOMeta::setRobots('noindex,follow');

        $this->items = StorefrontSession::cart();

        // Allow deep-linking products into the request: a single product (e.g. from
        // the product page) or a comma-separated list (e.g. from the wishlist).
        $slugs = collect(explode(',', (string) request()->query('product').','.(string) request()->query('products')))
            ->map(fn ($slug) => trim($slug))
            ->filter()
            ->unique();

        if ($slugs->isNotEmpty()) {
            $valid = Product::whereIn('slug', $slugs->all())->published()->visibleInCatalog()->pluck('slug');
            foreach ($slugs as $slug) {
                if ($valid->contains($slug) && !isset($this->items[$slug])) {
                    $this->items[$slug] = 1;
                }
            }
        }

        if ($user = auth()->user()) {
            $default = $user->addresses()->orderByDesc('is_default')->first();
            $this->contact_name = $user->name;
            $this->contact_email = $user->email;
            $this->contact_phone = (string) ($default?->phone ?? '');
            $this->selectedAddressId = $default?->id;
        }
    }

    /**
     * @return Collection<int, array{key: string, slug: string, qty: int, product: Product, variant: ?ProductVariant, label: ?string}>
     */
    #[Computed]
    public function lines(): Collection
    {
        if ($this->items === []) {
            return collect();
        }

        $keys = collect($this->items)->keys()->map(fn($key) => StorefrontSession::splitKey($key));

        $products = Product::query()
            ->with(['brand:id,name', 'media'])
            ->whereIn('slug', $keys->pluck('slug')->unique()->all())
            ->published()
            ->visibleInCatalog()
            ->get()
            ->keyBy('slug');

        $variants = ProductVariant::query()
            ->with('attributeValues')
            ->whereIn('id', $keys->pluck('variantId')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        return collect($this->items)
            ->map(function ($qty, $key) use ($products, $variants) {
                ['slug' => $slug, 'variantId' => $variantId] = StorefrontSession::splitKey($key);

                $product = $products->get($slug);
                if (!$product) {
                    return null;
                }

                $variant = $variantId ? $variants->get($variantId) : null;
                if ($variantId && !$variant) {
                    return null;
                }

                $label = $variant ? $variant->attributeValues->map(fn($v) => $v->label ?: $v->value)->filter()->implode(' / ') : null;

                return [
                    'key' => $key,
                    'slug' => $slug,
                    'qty' => (int) $qty,
                    'product' => $product,
                    'variant' => $variant,
                    'label' => $label ?: null,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function searchResults(): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['brand:id,name', 'media'])
            ->published()
            ->visibleInCatalog()
            ->whereNotIn('slug', array_keys($this->items));

        if (strlen(trim($this->itemSearch)) >= 2) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->itemSearch}%")
                    ->orWhere('sku', 'like', "%{$this->itemSearch}%")
                    ->orWhere('model_number', 'like', "%{$this->itemSearch}%")
                    ->orWhereHas('brand', fn($q2) => $q2->where('name', 'like', "%{$this->itemSearch}%"));
            });
        }

        return $query
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($this->itemsPerPage, ['*'], 'page', 1);
    }

    public function openItemModal(): void
    {
        $this->itemSearch = '';
        $this->itemsPerPage = 18;
        unset($this->searchResults);
        $this->showItemModal = true;
    }

    public function updatedItemSearch(): void
    {
        $this->itemsPerPage = 18;
        unset($this->searchResults);
    }

    public function loadMoreItems(): void
    {
        $this->itemsPerPage += 12;
        unset($this->searchResults);
    }

    public function addItem(string $slug): void
    {
        $exists = Product::where('slug', $slug)->published()->visibleInCatalog()->exists();

        if (!$exists) {
            return;
        }

        $this->items[$slug] = ($this->items[$slug] ?? 0) + 1;
        unset($this->lines, $this->searchResults);
    }

    public function removeItem(string $key): void
    {
        unset($this->items[$key]);
        unset($this->lines, $this->searchResults);
    }

    public function incrementItem(string $key): void
    {
        if (isset($this->items[$key])) {
            $this->items[$key]++;
            unset($this->lines);
        }
    }

    public function decrementItem(string $key): void
    {
        if (isset($this->items[$key]) && $this->items[$key] > 1) {
            $this->items[$key]--;
            unset($this->lines);
        }
    }

    #[Computed]
    public function addresses()
    {
        if (auth()->guest()) {
            return collect();
        }

        return auth()->user()->addresses()->orderByDesc('is_default')->orderBy('created_at')->get();
    }

    #[Computed]
    public function selectedAddress(): ?Address
    {
        if (!$this->selectedAddressId) {
            return null;
        }

        return $this->addresses->firstWhere('id', $this->selectedAddressId);
    }

    #[Computed]
    public function pinnedZone(): ?DeliveryZone
    {
        return app(DeliveryResolver::class)->resolveZone($this->latitude, $this->longitude);
    }

    public function addressRules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternative_phone' => ['nullable', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function openAddressModal(string $mode = 'select'): void
    {
        $this->resetValidation();

        if ($mode === 'create' || $this->addresses->isEmpty()) {
            $this->prepareAddressForm();
            $this->addressModalMode = 'create';
        } else {
            $this->addressModalMode = 'select';
        }

        $this->showAddressModal = true;
    }

    public function startAddressCreate(): void
    {
        $this->resetValidation();
        $this->prepareAddressForm();
        $this->addressModalMode = 'create';
    }

    private function prepareAddressForm(): void
    {
        $this->reset(['label', 'name', 'phone', 'alternative_phone', 'line1', 'delivery_instructions', 'is_default', 'latitude', 'longitude']);
        $this->label = 'Home';
    }

    public function selectAddress(int $id): void
    {
        if ($this->addresses->contains('id', $id)) {
            $this->selectedAddressId = $id;
            unset($this->selectedAddress);
        }

        $this->showAddressModal = false;
    }

    public function saveAddress(): void
    {
        $data = $this->validate($this->addressRules());

        if ($data['is_default']) {
            auth()
                ->user()
                ->addresses()
                ->update(['is_default' => false]);
        }

        if (auth()->user()->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $data['delivery_zone_id'] = app(DeliveryResolver::class)->resolveZone($data['latitude'] ?? null, $data['longitude'] ?? null)?->id;

        $address = auth()->user()->addresses()->create($data);

        $this->selectedAddressId = $address->id;
        $this->showAddressModal = false;
        unset($this->addresses, $this->selectedAddress);

        Flux::toast(heading: 'Address added', text: 'Your delivery address has been saved.', variant: 'success');
    }

    public function submit(): void
    {
        $this->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'needs_delivery' => ['boolean'],
            'delivery_address' => [auth()->guest() && $this->needs_delivery ? 'required' : 'nullable', 'string', 'max:500'],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_email' => [auth()->guest() ? 'required' : 'nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_company' => ['nullable', 'string', 'max:150'],
            'recaptchaToken' => [new Recaptcha('quote')],
        ]);

        if ($this->lines->isEmpty()) {
            Flux::toast(heading: 'No items added', text: 'Add at least one product to your quote request.', variant: 'warning');

            return;
        }

        if ($this->needs_delivery && auth()->check() && !$this->selectedAddress) {
            $this->addError('selectedAddressId', 'Please select a delivery address.');

            return;
        }

        $lines = $this->lines;

        $quote = DB::transaction(function () use ($lines) {
            $quotationSettings = app(QuotationSettings::class);

            $tax = app(TaxCalculator::class);

            $quote = Quote::create([
                'user_id' => auth()->id(),
                'contact_name' => $this->contact_name,
                'contact_email' => auth()->check() ? auth()->user()->email : $this->contact_email,
                'contact_phone' => $this->contact_phone ?: null,
                'contact_company' => $this->contact_company ?: null,
                'quote_number' => Quote::generateNumber(),
                'status' => QuoteStatus::DRAFT,
                'total_cents' => 0,
                'vat_rate' => $tax->enabled() ? $tax->defaultRate() : 0,
                'tax_inclusive' => $tax->enabled() && $tax->pricesIncludeTax(),
                'notes' => $this->notes ?: null,
                'terms' => $quotationSettings->quote_terms ?: null,
                'delivery_required' => $this->needs_delivery,
                'delivery_address' => $this->needs_delivery ? (auth()->check() ? $this->selectedAddress?->oneLiner() : ($this->delivery_address ?: null)) : null,
                'expires_at' => now()->addDays($quotationSettings->default_validity_days),
            ]);

            foreach ($lines as $line) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $line['product']->id,
                    'product_snapshot' => [
                        'name' => $line['product']->name . ($line['label'] ? ' — ' . $line['label'] : ''),
                        'sku' => $line['variant']?->sku ?? $line['product']->sku,
                        'model_number' => $line['product']->model_number,
                        'slug' => $line['product']->slug,
                        'cover_url' => $line['product']->cover_url,
                    ],
                    'unit_price_cents' => 0,
                    'quantity' => $line['qty'],
                    'line_total_cents' => 0,
                ]);
            }

            return $quote;
        });

        // Confirm receipt to the customer (auth user or guest email)
        $quote->notifyContact(new QuoteRequestReceived($quote));

        // Notify staff + real-time bell
        Notification::send(StaffRecipients::for('quotes.manage'), new NewQuoteRequested($quote));
        QuoteRequestSubmitted::dispatch($quote);

        // Clear only the cart-specific inputs; keep the prefilled contact and
        // address details so a follow-up request doesn't force re-entry.
        $this->reset(['items', 'notes', 'needs_delivery', 'delivery_address']);
        unset($this->lines);

        Flux::toast(heading: 'Quote request submitted', text: 'We\'ve received your request and will get back to you shortly.', variant: 'success');
    }
}; ?>


<div class="page-fade">
    <div class="shell pt-4 pb-20">

        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Request a quote</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Page header --}}
        <div class="max-w-2xl">
            <h1 class="text-3xl font-semibold tracking-tight">Request a quote</h1>
            <p class="mt-2 text-ink-3">
                Fitting out a commercial kitchen, cold room, laundry or healthcare facility? Tell us what you need and
                our team will prepare a formal quotation. Add products on the right, or just describe your requirements.
            </p>
        </div>

        <x-recaptcha-livewire />
        <form x-data @submit.prevent="__rcSubmit('quote', $wire)" class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- ================================================== --}}
            {{-- LEFT: DETAILS --}}
            {{-- ================================================== --}}
            <div class="flex-1 min-w-0">
                <section>
                    @guest
                        <p class="mb-5 text-[12.5px] text-ink-3">
                            Already have an account?
                            <a href="{{ route('login') }}" wire:navigate
                                class="font-semibold text-brand-500 hover:text-brand-600">Log in</a>
                            to track this quote in your account.
                        </p>
                    @endguest

                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>Full name <span class="ms-0.5 text-red-500">*</span></flux:label>
                                <flux:input wire:model.blur="contact_name" placeholder="Your name" />
                                <flux:error name="contact_name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Email <span class="ms-0.5 text-red-500">*</span></flux:label>
                                @auth
                                    <flux:input type="email" :value="auth()->user()->email" disabled />
                                @else
                                    <flux:input wire:model.blur="contact_email" type="email"
                                        placeholder="you@company.co.ke" />
                                    <flux:error name="contact_email" />
                                @endauth
                            </flux:field>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>Phone</flux:label>
                                <x-phone-input wire:model="contact_phone" />
                                <flux:error name="contact_phone" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Company / organisation</flux:label>
                                <flux:input wire:model.blur="contact_company" placeholder="Optional" />
                                <flux:error name="contact_company" />
                            </flux:field>
                        </div>
                        {{-- Delivery --}}
                        <div class="space-y-2">
                            <div class="text-[11px] font-bold tracking-widest text-ink-3 uppercase">Delivery</div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button type="button" wire:click="$set('needs_delivery', false)"
                                    class="flex items-start gap-3 rounded-md border p-4 text-left transition {{ !$needs_delivery ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                                    <span
                                        class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border-2 {{ !$needs_delivery ? 'border-brand-500' : 'border-zinc-300' }}">
                                        @if (!$needs_delivery)
                                            <span class="size-2 rounded-full bg-brand-500"></span>
                                        @endif
                                    </span>
                                    <div>
                                        <div class="text-[13.5px] font-semibold text-ink">Collect from Sheffield</div>
                                        <div class="mt-0.5 text-[12px] text-ink-3">Pick up from our Nairobi showroom —
                                            free.</div>
                                    </div>
                                </button>

                                <button type="button" wire:click="$set('needs_delivery', true)"
                                    class="flex items-start gap-3 rounded-md border p-4 text-left transition {{ $needs_delivery ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                                    <span
                                        class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border-2 {{ $needs_delivery ? 'border-brand-500' : 'border-zinc-300' }}">
                                        @if ($needs_delivery)
                                            <span class="size-2 rounded-full bg-brand-500"></span>
                                        @endif
                                    </span>
                                    <div>
                                        <div class="text-[13.5px] font-semibold text-ink">Deliver to my location</div>
                                        <div class="mt-0.5 text-[12px] text-ink-3">We'll include delivery in your quote.
                                        </div>
                                    </div>
                                </button>
                            </div>

                            @if ($needs_delivery)
                                <div class="rounded-md border border-zinc-200 bg-white">
                                    <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3.5">
                                        <span
                                            class="text-[11px] font-bold tracking-widest text-ink-3 uppercase">Delivery
                                            address</span>
                                        @auth
                                            @if ($this->addresses->isNotEmpty())
                                                <flux:button type="button" variant="customer-outline" size="customer"
                                                    icon="pencil-square" wire:click="openAddressModal('select')">Select
                                                </flux:button>
                                            @else
                                                <flux:button type="button" variant="customer-outline" size="customer"
                                                    icon="plus" wire:click="openAddressModal('create')">Add</flux:button>
                                            @endif
                                        @endauth
                                    </div>
                                    <div class="p-5">
                                        @auth
                                            @if ($this->selectedAddress)
                                                @php $addr = $this->selectedAddress; @endphp
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-[10.5px] font-bold tracking-widest text-ink-3 uppercase">{{ $addr->label }}</span>
                                                    @if ($addr->is_default)
                                                        <span
                                                            class="rounded-full bg-brand-500/10 px-2 py-0.5 text-[9.5px] font-bold tracking-wide text-brand-500 uppercase">Default</span>
                                                    @endif
                                                </div>
                                                <div class="mt-1 text-[14px] font-semibold text-ink">{{ $addr->fullName() }}
                                                </div>
                                                <div class="mt-1 text-[13px] text-ink-2">{{ $addr->oneLiner() }}</div>
                                            @elseif ($this->addresses->isNotEmpty())
                                                <p class="text-[13px] text-ink-3">Select a delivery address to continue.</p>
                                            @else
                                                <div
                                                    class="rounded-md border border-dashed border-zinc-300 p-5 text-center">
                                                    <flux:icon.map-pin variant="outline"
                                                        class="mx-auto size-6 text-ink-4" />
                                                    <p class="mt-2 text-[12.5px] text-ink-3">No saved addresses yet.</p>
                                                    <flux:button type="button" variant="customer-primary" size="customer"
                                                        icon="plus" wire:click="openAddressModal('create')"
                                                        class="mt-3">Add an address</flux:button>
                                                </div>
                                            @endif
                                            @error('selectedAddressId')
                                                <p class="mt-2 text-[12.5px] text-red-500">{{ $message }}</p>
                                            @enderror
                                        @endauth
                                        @guest
                                            <flux:field>
                                                <flux:label>Address / location <span class="ms-0.5 text-red-500">*</span>
                                                </flux:label>
                                                <flux:textarea wire:model="delivery_address" rows="2"
                                                    placeholder="e.g. Westlands, Nairobi — or a full street address" />
                                                <flux:error name="delivery_address" />
                                            </flux:field>
                                        @endguest
                                    </div>
                                </div>
                            @endif
                        </div>

                        <flux:field>
                            <flux:label>Notes &amp; requirements</flux:label>
                            <flux:textarea wire:model.blur="notes" rows="5"
                                placeholder="Timelines, site details, power/water specs, anything else we should know…" />
                            <flux:error name="notes" />
                        </flux:field>
                    </div>
                </section>
            </div>

            {{-- ================================================== --}}
            {{-- RIGHT: ITEMS PANEL --}}
            {{-- ================================================== --}}
            <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-96">
                <div class="rounded-md border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3">
                        <h2 class="text-[11px] font-bold tracking-[0.14em] text-ink uppercase">
                            Items <span class="ml-0.5 text-ink-4">({{ $this->lines->count() }})</span>
                        </h2>
                        <flux:button type="button" variant="customer-outline" size="customer" icon="plus"
                            wire:click="openItemModal">
                            Add item
                        </flux:button>
                    </div>

                    <div class="p-6">
                        {{-- Added items --}}
                        @if ($this->lines->isEmpty())
                            <div class="rounded-md border border-dashed border-zinc-300 p-6 text-center">
                                <flux:icon.document-text variant="outline" class="mx-auto size-7 text-ink-4" />
                                <p class="mt-2 text-[12.5px] text-ink-3">No items yet. Add products, or just describe
                                    what you need in the notes.</p>
                            </div>
                        @else
                            <div class="divide-y divide-zinc-100">
                                @foreach ($this->lines as $line)
                                    <div wire:key="item-{{ $line['key'] }}" class="flex gap-3 py-3.5">
                                        <div class="relative size-12 shrink-0 overflow-hidden bg-surface-sunken">
                                            @if ($line['product']->cover_url)
                                                @if ($placeholder = $line['product']->cover_placeholder)
                                                    <img src="{{ $placeholder }}" alt="" aria-hidden="true"
                                                        class="absolute inset-0 size-full scale-110 object-contain blur-xl" />
                                                @endif
                                                <img src="{{ $line['product']->cover_url }}" alt=""
                                                    loading="lazy"
                                                    x-data="{ loaded: false }" x-init="loaded = $el.complete" x-on:load="loaded = true"
                                                    x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                                    class="relative size-full object-contain transition duration-500" />
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-[12.5px] font-semibold leading-snug text-ink">
                                                {{ $line['product']->name }}</div>
                                            @if ($line['label'])
                                                <div class="truncate text-[11px] text-ink-3">{{ $line['label'] }}
                                                </div>
                                            @endif
                                            <div class="mt-1 flex items-center justify-between gap-2">
                                                <div class="inline-flex items-center rounded border border-zinc-200">
                                                    <button type="button"
                                                        wire:click="decrementItem('{{ $line['key'] }}')"
                                                        class="flex size-7 cursor-pointer items-center justify-center text-ink-3 transition hover:bg-surface-sunken hover:text-ink">
                                                        <span class="text-sm leading-none">−</span>
                                                    </button>
                                                    <span
                                                        class="min-w-7 text-center text-[12.5px] font-semibold tabular-nums">{{ $line['qty'] }}</span>
                                                    <button type="button"
                                                        wire:click="incrementItem('{{ $line['key'] }}')"
                                                        class="flex size-7 cursor-pointer items-center justify-center text-ink-3 transition hover:bg-surface-sunken hover:text-ink">
                                                        <span class="text-sm leading-none">+</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" wire:click="removeItem('{{ $line['key'] }}')"
                                            class="shrink-0 cursor-pointer self-start text-ink-4 transition hover:text-brand-500"
                                            title="Remove">
                                            <flux:icon.x-mark variant="micro" class="size-4" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="my-4 h-px bg-zinc-100"></div>

                        <flux:button type="submit" variant="customer-primary" size="customer-lg"
                            class="mt-5! w-full!" :disabled="$this->lines->isEmpty()">
                            Submit request
                        </flux:button>

                        <div class="mt-4 flex flex-col gap-2 text-[12px] text-ink-3">
                            <span class="flex items-center gap-2">
                                <flux:icon.clock variant="micro" class="size-3.5 text-brand-500" />
                                Typical response within 1 business day
                            </span>
                            <span class="flex items-center gap-2">
                                <flux:icon.document-text variant="micro" class="size-3.5 text-brand-500" />
                                No obligation — review before you commit
                            </span>
                        </div>
                    </div>
                </div>
            </aside>
        </form>
    </div>

    {{-- Add-item modal (search only) --}}
    <flux:modal wire:model.self="showItemModal" class="md:w-180 lg:w-215 md:max-w-none">
        <flux:heading class="uppercase">Add items to your quote</flux:heading>
        <flux:subheading>Search the catalog and add as many products as you need.</flux:subheading>

        <div class="@container mt-5">
            <flux:input wire:model.live.debounce.250ms="itemSearch" type="search" autocomplete="off" size="sm"
                spellcheck="false" autofocus icon="magnifying-glass" clearable
                placeholder="Search products by name, brand or SKU…" />

            <div class="mt-4 mb-1 text-[10.5px] font-bold tracking-widest text-ink-4 uppercase">
                {{ strlen(trim($itemSearch)) >= 2 ? 'Results' : 'Browse the catalog' }}
            </div>

            <div class="max-h-96 overflow-y-auto scrollbar-thin">
                @if ($this->searchResults->isEmpty())
                    <div class="py-12 text-center">
                        @if (strlen(trim($itemSearch)) >= 2)
                            <p class="text-[13.5px] font-medium text-ink-2">No matches for "{{ $itemSearch }}"</p>
                            <p class="mt-1 text-[12px] text-ink-4">Try a brand, category or SKU. Already-added items
                                are hidden.</p>
                        @else
                            <flux:icon.cube variant="outline" class="mx-auto size-7 text-ink-4" />
                            <p class="mt-2 text-[13px] text-ink-3">No more products to add.</p>
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 @xs:grid-cols-2 @lg:grid-cols-3 @2xl:grid-cols-4">
                        @foreach ($this->searchResults as $product)
                            <div wire:key="res-{{ $product->slug }}"
                                class="group flex flex-col overflow-hidden rounded-md border border-zinc-200 bg-white transition hover:shadow-md">
                                <div class="relative aspect-square overflow-hidden bg-surface-sunken">
                                    @if ($product->cover_url)
                                        @if ($placeholder = $product->cover_placeholder)
                                            <img src="{{ $placeholder }}" alt="" aria-hidden="true"
                                                class="absolute inset-0 size-full scale-110 object-contain blur-xl" />
                                        @endif
                                        <img src="{{ $product->cover_url }}" alt="{{ $product->name }}"
                                            loading="lazy"
                                            x-data="{ loaded: false }" x-init="loaded = $el.complete" x-on:load="loaded = true"
                                            x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                            class="relative size-full object-contain transition duration-500" />
                                    @else
                                        <div class="flex size-full items-center justify-center text-ink-4">
                                            <flux:icon.photo class="size-8" />
                                        </div>
                                    @endif
                                    <flux:tooltip content="Add to quote">
                                        <button type="button" wire:click="addItem('{{ $product->slug }}')"
                                            aria-label="Add {{ $product->name }} to quote"
                                            class="absolute right-2 bottom-2 inline-flex size-8 cursor-pointer items-center justify-center rounded-full bg-brand-500 text-white shadow-md transition hover:bg-brand-600">
                                            <flux:icon.plus variant="micro" class="size-4" />
                                        </button>
                                    </flux:tooltip>
                                </div>
                                <div class="flex flex-1 flex-col px-3 py-2.5">
                                    @if ($product->brand)
                                        <div
                                            class="truncate text-[9.5px] font-bold tracking-[0.08em] text-brand-blue-600 uppercase">
                                            {{ $product->brand->name }}</div>
                                    @endif
                                    <div
                                        class="mt-0.5 line-clamp-2 min-h-8 text-[12px] font-medium leading-snug text-ink">
                                        {{ $product->name }}</div>
                                    @if ($product->sku)
                                        <div class="mt-1.5 font-mono text-[10.5px] text-ink-4">{{ $product->sku }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($this->searchResults->hasMorePages())
                        <div wire:intersect="loadMoreItems" class="flex justify-center py-4">
                            <flux:icon.loading class="size-5 text-ink-4" />
                        </div>
                    @endif
                @endif
            </div>

            <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-4">
                <span class="text-[12.5px] text-ink-3">{{ $this->lines->count() }}
                    item{{ $this->lines->count() === 1 ? '' : 's' }} in quote</span>
                <flux:button type="button" variant="customer-primary" size="customer"
                    x-on:click="$flux.modals().close()">Done</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Address modal (authenticated users) --}}
    @auth
        <div x-data="addressMap()"
            x-effect="($wire.showAddressModal && $wire.addressModalMode === 'create') ? open() : close()">
            <flux:modal wire:model.self="showAddressModal" class="md:w-[640px] md:max-w-none" :dismissible="false">
                @if ($addressModalMode === 'select')
                    <flux:heading class="uppercase tracking-wide">Choose a delivery address</flux:heading>
                    <flux:subheading>Select where you'd like this order delivered.</flux:subheading>

                    <div class="mt-5 space-y-3">
                        @foreach ($this->addresses as $address)
                            <button type="button" wire:key="modal-addr-{{ $address->id }}"
                                wire:click="selectAddress({{ $address->id }})"
                                class="block w-full rounded-md border p-4 text-left transition {{ $this->selectedAddressId === $address->id ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-[10.5px] font-bold tracking-widest text-ink-3 uppercase">{{ $address->label }}</span>
                                    @if ($address->is_default)
                                        <span
                                            class="rounded-full bg-brand-500/10 px-2 py-0.5 text-[9.5px] font-bold tracking-wide text-brand-500 uppercase">Default</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-[13.5px] font-semibold text-ink">{{ $address->fullName() }}</div>
                                <div class="mt-1 text-[12.5px] leading-relaxed text-ink-2">{{ $address->oneLiner() }}
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel
                        </flux:button>
                        <flux:button type="button" variant="customer-outline" size="customer" icon="plus"
                            wire:click="startAddressCreate">Add new address</flux:button>
                    </div>
                @else
                    <flux:heading class="uppercase tracking-wide">New address</flux:heading>
                    <flux:subheading>
                        <span x-show="step === 1">Pin where you'd like this order delivered.</span>
                        <span x-show="step === 2" x-cloak>Now fill in the delivery address details.</span>
                    </flux:subheading>

                    <form wire:submit="saveAddress" class="mt-6">
                        <div x-show="step === 1" class="space-y-3">
                            @include('partials.storefront.address-map-pin')
                            <div class="flex justify-end gap-3 pt-2">
                                @if ($this->addresses->isNotEmpty())
                                    <flux:button type="button" icon="arrow-left"
                                        wire:click="$set('addressModalMode', 'select')">Back</flux:button>
                                @else
                                    <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">
                                        Cancel</flux:button>
                                @endif
                                <flux:button type="button" variant="customer-primary" size="customer"
                                    icon:trailing="arrow-right" x-on:click="showDetails()">Next</flux:button>
                            </div>
                        </div>

                        <div x-show="step === 2" x-cloak class="space-y-4">
                            @include('partials.storefront.address-fields')
                            <div class="flex justify-between gap-3 pt-2">
                                <flux:button type="button" icon="arrow-left"
                                    x-on:click="showLocation()">Back</flux:button>
                                <flux:button type="submit" variant="customer-primary" size="customer">Add address
                                </flux:button>
                            </div>
                        </div>
                    </form>
                @endif
            </flux:modal>
        </div>
    @endauth
</div>
