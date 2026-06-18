<?php

use App\Settings\NotificationSettings;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::settings')] #[Title('Notifications')] class extends Component
{
    /** @var array<string, array<string, bool>> */
    public array $prefs = [];

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'orders' => [
                'confirmation' => true,
                'updates'      => true,
            ],
            'quotes' => [
                'received' => true,
                'updates'  => true,
            ],
            'marketing' => false,
            'account'   => true,
            'inapp' => [
                'orders' => [
                    'confirmation' => true,
                    'updates'      => true,
                ],
                'quotes' => [
                    'received' => true,
                    'updates'  => true,
                ],
                'marketing' => false,
                'account'   => true,
            ],
            'whatsapp'  => [
                'orders' => [
                    'confirmation' => true,
                    'updates'      => true,
                ],
                'quotes' => [
                    'received' => true,
                    'updates'  => true,
                ],
                'marketing' => false,
                'account'   => true,
            ],
        ];
    }

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');

        $stored = Auth::user()->notification_preferences ?? [];
        $this->prefs = array_replace_recursive(static::defaults(), $stored);
    }

    #[Computed]
    public function inappEnabled(): bool
    {
        return app(NotificationSettings::class)->inapp_channel_enabled;
    }

    #[Computed]
    public function whatsappEnabled(): bool
    {
        return app(NotificationSettings::class)->whatsapp_channel_enabled;
    }

    public function save(): void
    {
        Auth::user()->update(['notification_preferences' => $this->prefs]);

        Flux::toast(variant: 'success', text: __('Notification preferences saved.'));
    }
}; ?>

@push('breadcrumbs')
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Notifications</flux:breadcrumbs.item>
    </flux:breadcrumbs>
@endpush

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::account.settings.layout>

        <form wire:submit="save">

            <flux:card class="p-0">

                {{-- Card title --}}
                <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3">
                    <flux:icon.bell variant="outline" class="size-4 text-zinc-600" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Notification Preferences</flux:heading>
                </div>

                {{-- Channel headers --}}
                <div class="flex items-center justify-end border-b border-zinc-200 bg-zinc-50 px-5 py-2.5 dark:border-zinc-600 dark:bg-zinc-800/40">
                    <span class="w-14 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-500 sm:w-16">Email</span>
                    <span @class([
                        'w-14 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest sm:w-16',
                        'text-zinc-500' => $this->inappEnabled,
                        'text-zinc-300 dark:text-zinc-600' => ! $this->inappEnabled,
                    ])>In-app</span>
                    <span @class([
                        'w-14 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest sm:w-16',
                        'text-zinc-500' => $this->whatsappEnabled,
                        'text-zinc-300 dark:text-zinc-600' => ! $this->whatsappEnabled,
                    ])>WhatsApp</span>
                </div>

                @php
                    $groups = [
                        [
                            'label' => 'Orders & Shipping',
                            'icon'  => 'shopping-bag',
                            'rows'  => [
                                ['key' => 'orders.confirmation', 'label' => 'Order Confirmation', 'desc' => 'Sent when your order is placed and payment is received.'],
                                ['key' => 'orders.updates',      'label' => 'Order Updates',      'desc' => 'Covers when your order is shipped, delivered or cancelled.'],
                            ],
                        ],
                        [
                            'label' => 'Quotations',
                            'icon'  => 'document-text',
                            'rows'  => [
                                ['key' => 'quotes.received', 'label' => 'Quote Received',      'desc' => 'Acknowledgement when your quote request is received by our team.'],
                                ['key' => 'quotes.updates',  'label' => 'Quote Updates',       'desc' => 'Sent when your quotation is priced, ready for review, or about to expire.'],
                            ],
                        ],
                        [
                            'label' => 'Marketing & Account',
                            'icon'  => 'megaphone',
                            'rows'  => [
                                ['key' => 'marketing', 'label' => 'Marketing Emails',  'desc' => 'Product news, catalogs, special offers and promotions.'],
                                ['key' => 'account',   'label' => 'Account & Security','desc' => 'Password changes, 2FA updates and security alerts.'],
                            ],
                        ],
                    ];
                @endphp

                @foreach ($groups as $group)
                    <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50/60 px-5 py-3 dark:border-zinc-600 dark:bg-zinc-800/20">
                        <flux:icon :icon="$group['icon']" class="size-3.5 shrink-0 text-brand-500" />
                        <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">{{ $group['label'] }}</span>
                    </div>
                    @foreach ($group['rows'] as $row)
                        @php
                            // Convert dot-notation key to wire:model path, e.g. orders.confirmation → prefs.orders.confirmation
                            $modelPath = 'prefs.' . $row['key'];
                        @endphp
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5
                            @if (! $loop->last || ! $loop->parent->last) border-b border-zinc-200 dark:border-zinc-700 @endif">
                            <div class="flex-1">
                                <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">{{ $row['label'] }}</div>
                                <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $row['desc'] }}</div>
                            </div>
                            <div class="flex shrink-0 items-center">
                                <div class="flex w-14 justify-center sm:w-16">
                                    <flux:switch wire:model="{{ $modelPath }}" />
                                </div>
                                <div @class([
                                    'flex w-14 justify-center sm:w-16',
                                    'opacity-40' => ! $this->inappEnabled,
                                ])>
                                    <flux:switch
                                        wire:model="prefs.inapp.{{ $row['key'] }}"
                                        :disabled="! $this->inappEnabled" />
                                </div>
                                <div @class([
                                    'flex w-14 justify-center sm:w-16',
                                    'opacity-40' => ! $this->whatsappEnabled,
                                ])>
                                    <flux:switch
                                        wire:model="prefs.whatsapp.{{ $row['key'] }}"
                                        :disabled="! $this->whatsappEnabled" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach

            </flux:card>

            <div class="mt-6 flex justify-end">
                <flux:button variant="primary" type="submit">Save preferences</flux:button>
            </div>

        </form>

    </x-pages::account.settings.layout>
</section>
