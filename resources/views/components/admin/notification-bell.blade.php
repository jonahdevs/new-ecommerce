<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->latest()->limit(15)->get();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        unset($this->unreadCount, $this->notifications);
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->find($id)?->markAsRead();
        unset($this->unreadCount, $this->notifications);
    }

    #[On('echo-private:admin.notifications,QuoteRequestSubmitted')]
    public function onNewQuote(): void
    {
        unset($this->unreadCount, $this->notifications);
    }

    #[On('echo-private:admin.notifications,OrderPlaced')]
    public function onNewOrder(): void
    {
        unset($this->unreadCount, $this->notifications);
    }
}; ?>

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="relative">

    {{-- Bell button with unread badge --}}
    <flux:tooltip content="Notifications" position="bottom">
        <button type="button" x-on:click="open = !open"
                class="relative inline-flex size-9 items-center justify-center rounded-md text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
            <flux:icon.bell variant="outline" class="size-5" />
            @if ($this->unreadCount > 0)
                <span class="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-brand-500 text-[9px] font-bold text-white">
                    {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                </span>
            @endif
        </button>
    </flux:tooltip>

    {{-- Dropdown panel --}}
    <div x-show="open" x-cloak x-on:click.outside="open = false"
         x-transition:enter="transition duration-[120ms] ease-out"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute right-0 top-[calc(100%+8px)] z-50 w-80 overflow-hidden rounded-md border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-700">
            <span class="text-[12px] font-bold tracking-[0.08em] text-ink uppercase dark:text-white">Notifications</span>
            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllRead"
                        class="text-[11.5px] font-medium text-brand-500 hover:text-brand-600 cursor-pointer">
                    Mark all read
                </button>
            @endif
        </div>

        {{-- List --}}
        <div class="max-h-96 overflow-y-auto scrollbar-thin">
            @forelse ($this->notifications as $notification)
                @php $data = $notification->data; $isUnread = $notification->read_at === null; @endphp
                @php
                    $isOrder = ($data['type'] ?? '') === 'new_order';
                    $fallbackUrl = $isOrder ? route('admin.orders.index') : route('admin.quotes.index');
                    $title = $isOrder
                        ? 'New order — ' . ($data['order_number'] ?? '')
                        : 'New quote — ' . ($data['quote_number'] ?? '');
                    $subtitle = $isOrder
                        ? ($data['customer_name'] ?? 'Guest') . ' · ' . (isset($data['total']) ? money($data['total']) : '')
                        : ($data['contact_name'] ?? '') . (isset($data['contact_company']) && $data['contact_company'] ? ' · ' . $data['contact_company'] : '');
                @endphp
                <a href="{{ $data['url'] ?? $fallbackUrl }}" wire:navigate
                   wire:click="markRead('{{ $notification->id }}')"
                   x-on:click="open = false"
                   class="group flex items-start gap-3 border-b border-zinc-100 px-4 py-3.5 last:border-0 dark:border-zinc-800">
                    <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        @if ($isOrder)
                            <flux:icon.shopping-bag variant="micro" class="size-4 text-zinc-400 transition group-hover:text-brand-500" />
                        @else
                            <flux:icon.document-text variant="micro" class="size-4 text-zinc-400 transition group-hover:text-brand-500" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-{{ $isUnread ? 'semibold' : 'medium' }} text-ink transition group-hover:text-brand-500 dark:text-white leading-snug">
                            {{ $title }}
                        </div>
                        <div class="mt-0.5 text-[11.5px] text-ink-3 truncate">
                            {{ $subtitle }}
                        </div>
                        <div class="mt-0.5 text-[11px] text-ink-4">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </div>
                    @if ($isUnread)
                        <span class="mt-2 size-1.5 shrink-0 rounded-full bg-brand-500"></span>
                    @endif
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <flux:icon.bell variant="outline" class="mx-auto size-7 text-zinc-300" />
                    <p class="mt-2 text-[12.5px] text-zinc-400">No notifications yet</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
