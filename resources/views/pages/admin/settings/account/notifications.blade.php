<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Notifications')] class extends Component {
    public bool $notify_new_order = true;
    public bool $notify_failed_payment = true;
    public bool $notify_new_user = false;
    public bool $notify_new_review = false;
    public bool $notify_low_stock = true;
    public bool $notify_out_of_stock = true;
    public bool $notify_new_quote = true;
    public bool $notify_quote_accepted = true;
    public bool $notify_quote_rejected = false;

    public function mount(): void
    {
        $prefs = auth()->user()->notification_preferences ?? [];

        $this->notify_new_order = $prefs['notify_new_order'] ?? true;
        $this->notify_failed_payment = $prefs['notify_failed_payment'] ?? true;
        $this->notify_new_user = $prefs['notify_new_user'] ?? false;
        $this->notify_new_review = $prefs['notify_new_review'] ?? false;
        $this->notify_low_stock = $prefs['notify_low_stock'] ?? true;
        $this->notify_out_of_stock = $prefs['notify_out_of_stock'] ?? true;
        $this->notify_new_quote = $prefs['notify_new_quote'] ?? true;
        $this->notify_quote_accepted = $prefs['notify_quote_accepted'] ?? true;
        $this->notify_quote_rejected = $prefs['notify_quote_rejected'] ?? false;
    }

    public function rules(): array
    {
        return [
            'notify_new_order' => ['boolean'],
            'notify_failed_payment' => ['boolean'],
            'notify_new_user' => ['boolean'],
            'notify_new_review' => ['boolean'],
            'notify_low_stock' => ['boolean'],
            'notify_out_of_stock' => ['boolean'],
            'notify_new_quote' => ['boolean'],
            'notify_quote_accepted' => ['boolean'],
            'notify_quote_rejected' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        auth()->user()->update([
            'notification_preferences' => [
                'notify_new_order' => $this->notify_new_order,
                'notify_failed_payment' => $this->notify_failed_payment,
                'notify_new_user' => $this->notify_new_user,
                'notify_new_review' => $this->notify_new_review,
                'notify_low_stock' => $this->notify_low_stock,
                'notify_out_of_stock' => $this->notify_out_of_stock,
                'notify_new_quote' => $this->notify_new_quote,
                'notify_quote_accepted' => $this->notify_quote_accepted,
                'notify_quote_rejected' => $this->notify_quote_rejected,
            ],
        ]);

        $this->dispatch('notify', variant: 'success', title: __('Preferences saved'), message: __('Your notification preferences have been updated.'));
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('My Notifications')" :subheading="__('Choose which alerts you personally receive and through which channels')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">

                <div class="border-b border-zinc-200 dark:border-zinc-600 px-5 py-3 flex items-center gap-2">
                    <flux:icon.bell class="size-4 text-zinc-500" />
                    <flux:heading>{{ __('Notification Preferences') }}</flux:heading>
                </div>

                {{-- Channel headers --}}
                <div class="flex items-center justify-end gap-5 px-5 py-2.5 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/40">
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-500 w-9 text-center">Email</span>
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-400 w-9 text-center">SMS</span>
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-400 w-9 text-center">Push</span>
                </div>

                {{-- Orders & Payments --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.shopping-bag class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Orders & Payments') }}</span>
                </div>

                <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100 mb-0.5">{{ __('New order placed') }}</div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ __('Notify when a customer places an order') }}</div>
                    </div>
                    <div class="flex items-center gap-5 shrink-0">
                        <label class="relative inline-block w-9 h-5 cursor-pointer">
                            <input type="checkbox" class="peer sr-only" wire:model.live="notify_new_order">
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                    </div>
                </div>

                @foreach([
                    ['model' => 'notify_failed_payment',  'title' => __('Payment failed'),          'description' => __('Alert when a payment attempt fails at checkout')],
                ] as $row)
                <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100 mb-0.5">{{ $row['title'] }}</div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $row['description'] }}</div>
                    </div>
                    <div class="flex items-center gap-5 shrink-0">
                        <label class="relative inline-block w-9 h-5 cursor-pointer">
                            <input type="checkbox" class="peer sr-only" wire:model.live="{{ $row['model'] }}">
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                    </div>
                </div>
                @endforeach

                {{-- Customers & Reviews --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.users class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Customers & Reviews') }}</span>
                </div>

                @foreach([
                    ['model' => 'notify_new_user',   'title' => __('New customer registered'), 'description' => __('Notify when a new customer account is created')],
                    ['model' => 'notify_new_review',  'title' => __('New review submitted'),    'description' => __('Notify when a customer review is pending moderation')],
                ] as $row)
                <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100 mb-0.5">{{ $row['title'] }}</div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $row['description'] }}</div>
                    </div>
                    <div class="flex items-center gap-5 shrink-0">
                        <label class="relative inline-block w-9 h-5 cursor-pointer">
                            <input type="checkbox" class="peer sr-only" wire:model.live="{{ $row['model'] }}">
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                    </div>
                </div>
                @endforeach

                {{-- Inventory --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.archive-box class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Inventory') }}</span>
                </div>

                @foreach([
                    ['model' => 'notify_low_stock',    'title' => __('Low stock alert'),    'description' => __('Send an email alert when a product hits the low stock threshold')],
                    ['model' => 'notify_out_of_stock', 'title' => __('Out of stock alert'), 'description' => __('Send an email alert when a product reaches zero stock')],
                ] as $row)
                <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100 mb-0.5">{{ $row['title'] }}</div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $row['description'] }}</div>
                    </div>
                    <div class="flex items-center gap-5 shrink-0">
                        <label class="relative inline-block w-9 h-5 cursor-pointer">
                            <input type="checkbox" class="peer sr-only" wire:model.live="{{ $row['model'] }}">
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                    </div>
                </div>
                @endforeach

                {{-- Quotations --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.document-text class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Quotations') }}</span>
                </div>

                @foreach([
                    ['model' => 'notify_new_quote',       'title' => __('New quote request'), 'description' => __('Notify when a customer requests a quotation')],
                    ['model' => 'notify_quote_accepted',  'title' => __('Quote accepted'),    'description' => __('Notify when a customer accepts a quotation')],
                    ['model' => 'notify_quote_rejected',  'title' => __('Quote rejected'),    'description' => __('Notify when a customer rejects a quotation')],
                ] as $row)
                <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100 mb-0.5">{{ $row['title'] }}</div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $row['description'] }}</div>
                    </div>
                    <div class="flex items-center gap-5 shrink-0">
                        <label class="relative inline-block w-9 h-5 cursor-pointer">
                            <input type="checkbox" class="peer sr-only" wire:model.live="{{ $row['model'] }}">
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
                            <input type="checkbox" class="peer sr-only" disabled>
                            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                        </label>
                    </div>
                </div>
                @endforeach

            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
