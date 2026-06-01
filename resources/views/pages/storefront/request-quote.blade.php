<?php

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Request a quote — Sheffield')] class extends Component
{
    /** @var array<string, int> */
    public array $items = [];

    public string $itemSearch = '';

    public bool $showItemModal = false;

    public int $itemsPerPage = 18;

    public string $notes = '';

    public string $contact_name = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $contact_company = '';

    public function mount(): void
    {
        abort_unless(app(\App\Settings\QuotationSettings::class)->quotes_enabled, 404);

        SEOMeta::setRobots('noindex,follow');

        $this->items = StorefrontSession::cart();

        if ($user = auth()->user()) {
            $this->contact_name = $user->name;
            $this->contact_email = $user->email;
            $this->contact_phone = (string) $user->addresses()->orderByDesc('is_default')->value('phone');
        }
    }

    /**
     * @return Collection<int, array{slug: string, qty: int, product: Product, unit_price_cents: int, line_total_cents: int}>
     */
    #[Computed]
    public function lines(): Collection
    {
        if ($this->items === []) {
            return collect();
        }

        $products = Product::query()
            ->with(['brand', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('slug', array_keys($this->items))
            ->where('visibility', 'visible')
            ->get()
            ->keyBy('slug');

        return collect($this->items)
            ->map(function ($qty, $slug) use ($products) {
                if (! $products->has($slug)) {
                    return null;
                }

                $product = $products[$slug];
                $unit = $product->sale_price ?? $product->price ?? 0;

                return [
                    'slug' => $slug,
                    'qty' => (int) $qty,
                    'product' => $product,
                    'unit_price_cents' => (int) $unit,
                    'line_total_cents' => (int) $unit * (int) $qty,
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
            ->with(['brand', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible')
            ->whereNotIn('slug', array_keys($this->items));

        if (strlen(trim($this->itemSearch)) >= 2) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->itemSearch}%")
                    ->orWhere('sku', 'like', "%{$this->itemSearch}%")
                    ->orWhere('model_number', 'like', "%{$this->itemSearch}%")
                    ->orWhereHas('brand', fn ($q2) => $q2->where('name', 'like', "%{$this->itemSearch}%"));
            });
        }

        return $query->orderBy('sort_order')->orderByDesc('id')->paginate($this->itemsPerPage, ['*'], 'page', 1);
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
        $exists = Product::where('slug', $slug)->where('visibility', 'visible')->exists();

        if (! $exists) {
            return;
        }

        $this->items[$slug] = ($this->items[$slug] ?? 0) + 1;
        unset($this->lines, $this->searchResults);
    }

    public function removeItem(string $slug): void
    {
        unset($this->items[$slug]);
        unset($this->lines, $this->searchResults);
    }

    public function incrementItem(string $slug): void
    {
        if (isset($this->items[$slug])) {
            $this->items[$slug]++;
            unset($this->lines);
        }
    }

    public function decrementItem(string $slug): void
    {
        if (isset($this->items[$slug]) && $this->items[$slug] > 1) {
            $this->items[$slug]--;
            unset($this->lines);
        }
    }

    public function submit(): void
    {
        $this->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_email' => ['required', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_company' => ['nullable', 'string', 'max:150'],
        ]);

        $lines = $this->lines;
        $isGuest = auth()->guest();
        $title = $this->generateTitle();

        DB::transaction(function () use ($lines, $title) {
            $quote = Quote::create([
                'user_id' => auth()->id(),
                'contact_name' => $this->contact_name,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone ?: null,
                'contact_company' => $this->contact_company ?: null,
                'quote_number' => Quote::generateNumber(),
                'title' => $title,
                'status' => QuoteStatus::SENT,
                'total_cents' => (int) $lines->sum('line_total_cents'),
                'notes' => $this->notes ?: null,
                'expires_at' => now()->addDays(app(\App\Settings\QuotationSettings::class)->default_validity_days),
            ]);

            foreach ($lines as $line) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'product_sku' => $line['product']->sku,
                    'unit_price_cents' => $line['unit_price_cents'],
                    'quantity' => $line['qty'],
                    'line_total_cents' => $line['line_total_cents'],
                ]);
            }
        });

        Flux::toast(
            heading: 'Quote request sent',
            text: 'Our team will review your request and respond shortly.',
            variant: 'success',
        );

        if ($isGuest) {
            $this->redirectRoute('home', navigate: true);
        } else {
            $this->redirectRoute('account.quotes.index', navigate: true);
        }
    }

    private function generateTitle(): string
    {
        $who = trim($this->contact_company) ?: trim($this->contact_name);

        return $who !== '' ? "Quote request — {$who}" : 'Quote request';
    }

}; ?>

@php
    $totalCents = $this->lines->sum('line_total_cents');
@endphp

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
                Building a kitchen, fitting out a site, or tendering a project? Tell us what you need and our team
                will prepare a formal quotation. Add products on the right, or just describe your requirements.
            </p>
        </div>

        <form wire:submit="submit" class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- ── Left: your details ── --}}
            <div class="flex-1 min-w-0">
                <section>
                    @auth
                        <p class="mb-5 text-[12.5px] text-ink-4">Contact details are pre-filled from your account.</p>
                    @endauth
                    @guest
                        <p class="mb-5 text-[12.5px] text-ink-3">
                            Already have an account?
                            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-500 hover:text-brand-600">Log in</a>
                            to track this quote in your account.
                        </p>
                    @endguest

                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>Full name <span class="ms-0.5 text-red-500">*</span></flux:label>
                                <flux:input wire:model="contact_name" placeholder="Anita Wanjiru" />
                                <flux:error name="contact_name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Email <span class="ms-0.5 text-red-500">*</span></flux:label>
                                <flux:input wire:model="contact_email" type="email" placeholder="you@company.co.ke" />
                                <flux:error name="contact_email" />
                            </flux:field>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>Phone</flux:label>
                                <flux:input wire:model="contact_phone" type="tel" placeholder="+254 712 345 678" />
                                <flux:error name="contact_phone" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Company</flux:label>
                                <flux:input wire:model="contact_company" placeholder="Company / organisation" />
                                <flux:error name="contact_company" />
                            </flux:field>
                        </div>
                        <flux:field>
                            <flux:label>Notes &amp; requirements</flux:label>
                            <flux:textarea wire:model="notes" rows="5"
                                           placeholder="Timelines, site details, power/water specs, anything else we should know…" />
                            <flux:error name="notes" />
                        </flux:field>
                    </div>
                </section>
            </div>

            {{-- ── Right: items panel ── --}}
            <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-96">
                <div class="rounded-md border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                        <h2 class="text-[11px] font-bold tracking-[0.14em] text-ink uppercase">
                            Items <span class="ml-0.5 text-ink-4">({{ $this->lines->count() }})</span>
                        </h2>
                        <flux:button type="button" variant="customer-outline" size="customer" icon="plus" wire:click="openItemModal">
                            Add item
                        </flux:button>
                    </div>

                    <div class="p-6">
                    {{-- Added items --}}
                    @if ($this->lines->isEmpty())
                        <div class="rounded-md border border-dashed border-zinc-300 p-6 text-center">
                            <flux:icon.cube variant="outline" class="mx-auto size-7 text-ink-4" />
                            <p class="mt-2 text-[12.5px] text-ink-3">No items yet. Add products, or just describe what you need in the notes.</p>
                            <flux:button type="button" variant="customer-outline" size="customer" icon="plus" wire:click="openItemModal" class="mt-3">
                                Add item
                            </flux:button>
                        </div>
                    @else
                        <div class="divide-y divide-zinc-100">
                            @foreach ($this->lines as $line)
                                <div wire:key="item-{{ $line['slug'] }}" class="flex gap-3 py-3.5">
                                    <div class="size-12 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1">
                                        @if ($line['product']->cover_url)
                                            <img src="{{ $line['product']->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-[12.5px] font-semibold leading-snug text-ink">{{ $line['product']->name }}</div>
                                        <div class="mt-1 flex items-center justify-between gap-2">
                                            <div class="inline-flex items-center rounded border border-zinc-200">
                                                <button type="button" wire:click="decrementItem('{{ $line['slug'] }}')"
                                                        class="flex size-7 cursor-pointer items-center justify-center text-ink-3 transition hover:bg-surface-sunken hover:text-ink">
                                                    <span class="text-sm leading-none">−</span>
                                                </button>
                                                <span class="min-w-7 text-center text-[12.5px] font-semibold tabular-nums">{{ $line['qty'] }}</span>
                                                <button type="button" wire:click="incrementItem('{{ $line['slug'] }}')"
                                                        class="flex size-7 cursor-pointer items-center justify-center text-ink-3 transition hover:bg-surface-sunken hover:text-ink">
                                                    <span class="text-sm leading-none">+</span>
                                                </button>
                                            </div>
                                            <span class="text-[12.5px] font-semibold text-ink tabular-nums whitespace-nowrap">
                                                {!! $line['unit_price_cents'] > 0 ? money($line['line_total_cents']) : 'POA' !!}
                                            </span>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="removeItem('{{ $line['slug'] }}')"
                                            class="shrink-0 cursor-pointer self-start text-ink-4 transition hover:text-brand-500" title="Remove">
                                        <flux:icon.x-mark variant="micro" class="size-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="my-4 h-px bg-zinc-100"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-[13px] font-bold tracking-wide uppercase">Indicative total</span>
                        <span class="text-xl font-bold text-brand-500 tabular-nums">{!! $totalCents > 0 ? money($totalCents) : '—' !!}</span>
                    </div>

                    <p class="mt-3 text-[11.5px] leading-relaxed text-ink-4">
                        Prices are indicative and exclude VAT, delivery and installation. Your formal quotation will
                        confirm final pricing and lead times.
                    </p>

                    <flux:button type="submit" variant="customer-primary" size="customer-lg" icon:trailing="arrow-right" class="mt-5! w-full!">
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
    <flux:modal wire:model.self="showItemModal" class="md:w-[760px]">
        <flux:heading>Add items to your quote</flux:heading>
        <flux:subheading>Search the catalog and add as many products as you need.</flux:subheading>

        <div class="mt-5">
            <div class="relative">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-4">
                    <flux:icon.magnifying-glass variant="micro" class="size-4" />
                </span>
                <input wire:model.live.debounce.250ms="itemSearch"
                       type="search" autocomplete="off" spellcheck="false" autofocus
                       placeholder="Search products by name, brand or SKU…"
                       class="h-11 w-full rounded-md border border-zinc-200 bg-white pl-10 pr-4 text-[13.5px] text-ink placeholder:text-ink-4 transition-[border-color,box-shadow] duration-100 focus:border-brand-500 focus:ring-0 focus:outline-none focus:shadow-[0_0_0_3px_hsl(354_68%_45%/0.12)]" />
            </div>

            <div class="mt-4 mb-1 text-[10.5px] font-bold tracking-[0.1em] text-ink-4 uppercase">
                {{ strlen(trim($itemSearch)) >= 2 ? 'Results' : 'Browse the catalog' }}
            </div>

            <div class="max-h-96 overflow-y-auto">
                @if ($this->searchResults->isEmpty())
                    <div class="py-12 text-center">
                        @if (strlen(trim($itemSearch)) >= 2)
                            <p class="text-[13.5px] font-medium text-ink-2">No matches for "{{ $itemSearch }}"</p>
                            <p class="mt-1 text-[12px] text-ink-4">Try a brand, category or SKU. Already-added items are hidden.</p>
                        @else
                            <flux:icon.cube variant="outline" class="mx-auto size-7 text-ink-4" />
                            <p class="mt-2 text-[13px] text-ink-3">No more products to add.</p>
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        @foreach ($this->searchResults as $product)
                            @php $price = $product->sale_price ?? $product->price; @endphp
                            <div wire:key="res-{{ $product->slug }}"
                                 class="group flex flex-col overflow-hidden rounded-md border border-zinc-200 bg-white transition hover:shadow-md">
                                <div class="relative aspect-square overflow-hidden bg-surface-sunken p-2">
                                    @if ($product->cover_url)
                                        <img src="{{ $product->cover_url }}" alt="{{ $product->name }}" class="size-full object-contain" loading="lazy" />
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
                                <div class="flex flex-1 flex-col border-t border-zinc-100 px-3 py-2.5">
                                    @if ($product->brand)
                                        <div class="truncate text-[9.5px] font-bold tracking-[0.08em] text-brand-blue-600 uppercase">{{ $product->brand->name }}</div>
                                    @endif
                                    <div class="mt-0.5 line-clamp-2 min-h-8 text-[12px] font-medium leading-snug text-ink">{{ $product->name }}</div>
                                    <div class="mt-1.5 text-[12.5px] font-bold text-ink tabular-nums whitespace-nowrap">
                                        {!! $price ? money($price) : 'POA' !!}
                                    </div>
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
                <span class="text-[12.5px] text-ink-3">{{ $this->lines->count() }} item{{ $this->lines->count() === 1 ? '' : 's' }} in quote</span>
                <flux:button type="button" variant="customer-primary" size="customer" x-on:click="$flux.modals().close()">Done</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
