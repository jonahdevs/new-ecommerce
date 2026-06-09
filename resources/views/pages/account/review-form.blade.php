<?php

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Settings\ReviewSettings;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Write a Review')] class extends Component
{
    public Product $product;

    public int $rating = 5;

    public string $title = '';

    public string $body = '';

    public function mount(Product $product): void
    {
        \Artesaos\SEOTools\Facades\SEOMeta::setRobots('noindex,follow');

        // Ensure the user actually purchased this product.
        $purchased = auth()->user()
            ->orders()
            ->where('status', OrderStatus::COMPLETED->value)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        abort_unless($purchased, 403, 'You must purchase this product before reviewing it.');

        $this->product = $product;

        // Pre-fill if an existing review exists.
        $existing = Review::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $this->rating = $existing->rating;
            $this->title  = $existing->title ?? '';
            $this->body   = $existing->body;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['nullable', 'string', 'max:120'],
            'body'   => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $settings = app(ReviewSettings::class);
        $auto     = $settings->auto_approve;
        $status   = $auto ? ReviewStatus::APPROVED : ReviewStatus::PENDING;
        $user     = auth()->user();

        $existing = Review::where('user_id', $user->id)
            ->where('product_id', $this->product->id)
            ->first();

        if ($existing) {
            $existing->update([
                'rating' => $this->rating,
                'title'  => $this->title ?: null,
                'body'   => $this->body,
                'status' => $status,
            ]);
        } else {
            Review::create([
                'product_id'        => $this->product->id,
                'user_id'           => $user->id,
                'author_name'       => $user->name,
                'rating'            => $this->rating,
                'title'             => $this->title ?: null,
                'body'              => $this->body,
                'status'            => $status,
                'verified_purchase' => true,
            ]);
        }

        Flux::toast(
            heading: $existing ? 'Review updated' : 'Thank you!',
            text: $auto ? 'Your review is now live.' : 'Your review has been submitted for moderation.',
            variant: 'success',
        );

        $this->redirectRoute('account.reviews', navigate: true);
    }
}; ?>

<div class="page-fade space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('account.reviews')" wire:navigate>Pending Reviews</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Write a Review</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    @php
        $cover = $product->images->where('is_cover', true)->first() ?? $product->images->first();
    @endphp

    {{-- Product summary card --}}
    <div class="flex items-center gap-4 rounded-md border border-zinc-200 bg-white p-4">
        @if ($cover)
            <img src="{{ Storage::url($cover->path) }}"
                 alt="{{ $product->name }}"
                 class="size-16 rounded-md border border-zinc-100 object-contain p-1 shrink-0">
        @else
            <div class="flex size-16 shrink-0 items-center justify-center rounded-md border border-zinc-100 bg-surface-sunken">
                <flux:icon.photo class="size-6 text-zinc-300" />
            </div>
        @endif
        <div class="min-w-0">
            <p class="text-xs text-ink-3">You're reviewing</p>
            <p class="text-sm font-semibold text-ink line-clamp-2">{{ $product->name }}</p>
        </div>
    </div>

    {{-- Review form --}}
    <div class="rounded-md border border-zinc-200 bg-white p-6">
        <flux:heading size="lg">{{ $this->body ? 'Update your review' : 'Write a review' }}</flux:heading>
        <flux:text class="mt-1">Share what you love, or how it could be improved.</flux:text>

        <form wire:submit="submit" class="mt-6 space-y-5">
            <flux:field>
                <flux:label>Rating</flux:label>
                <flux:select wire:model="rating">
                    @foreach ([5 => 'Excellent', 4 => 'Good', 3 => 'Average', 2 => 'Poor', 1 => 'Terrible'] as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $value }} stars — {{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="rating" />
            </flux:field>

            <flux:field>
                <flux:label>Title <flux:badge size="sm" color="zinc" class="ml-1">Optional</flux:badge></flux:label>
                <flux:input wire:model="title" placeholder="Sum it up in a few words" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Your review</flux:label>
                <flux:textarea wire:model="body" rows="5"
                               placeholder="How has this product performed for you? What would you tell someone considering buying it?" />
                <flux:error name="body" />
            </flux:field>

            <div class="flex items-center gap-4 pt-1">
                <flux:button type="submit" variant="primary">
                    {{ $this->body ? 'Update review' : 'Submit review' }}
                </flux:button>
                <flux:button :href="route('account.reviews')" variant="ghost" wire:navigate>Cancel</flux:button>
            </div>

            <p class="text-[12px] text-ink-3">Reviews are published once approved by our team.</p>
        </form>
    </div>

</div>
