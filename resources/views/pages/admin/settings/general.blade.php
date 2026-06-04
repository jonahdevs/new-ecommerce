<?php

use App\Settings\NotificationSettings;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('General settings — Admin')] class extends Component
{
    #[Url]
    public string $section = 'profile';

    /** @var array<string, array<string, bool>> */
    public array $notifications = [];

    /** @var array<string, array<string, bool>> */
    protected static array $notificationDefaults = [
        'new_order'      => ['email' => true,  'inapp' => true,  'whatsapp' => false],
        'new_review'     => ['email' => false, 'inapp' => true,  'whatsapp' => false],
        'low_stock'      => ['email' => true,  'inapp' => true,  'whatsapp' => false],
        'new_quote'      => ['email' => true,  'inapp' => true,  'whatsapp' => false],
        'quote_decision' => ['email' => true,  'inapp' => true,  'whatsapp' => false],
    ];

    public function mount(): void
    {
        $prefs = Auth::user()->staff_preferences ?? [];

        $this->notifications = array_replace_recursive(
            static::$notificationDefaults,
            $prefs['notifications'] ?? [],
        );
    }

    #[Computed]
    public function whatsappEnabled(): bool
    {
        return app(NotificationSettings::class)->whatsapp_channel_enabled;
    }

    public function saveNotifications(): void
    {
        $prefs = Auth::user()->staff_preferences ?? [];
        $prefs['notifications'] = $this->notifications;
        Auth::user()->update(['staff_preferences' => $prefs]);

        Flux::toast(heading: 'Saved', text: 'Notification preferences updated.', variant: 'success');
    }
}; ?>

<x-admin.settings-shell tab="general" :section="$section">

    {{-- Profile (personal account) --}}
    @if ($section === 'profile')
        <livewire:pages::account.settings.profile :embedded="true" />
    @endif

    {{-- Security (personal account) --}}
    @if ($section === 'security')
        <livewire:pages::account.settings.security :embedded="true" />
    @endif

    {{-- Appearance (personal account) --}}
    @if ($section === 'appearance')
        <livewire:pages::account.settings.appearance :embedded="true" />
    @endif

    {{-- My notifications (personal staff alerts) --}}
    @if ($section === 'notifications')
        <form wire:submit="saveNotifications">
            <flux:card class="p-0">

                {{-- Title --}}
                <div class="flex items-center gap-2 border-b border-zinc-200 px-5 py-3 dark:border-zinc-600">
                    <flux:icon.bell class="size-4 text-zinc-500" />
                    <flux:heading>My notifications</flux:heading>
                </div>

                <p class="border-b border-zinc-200 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-600 dark:text-zinc-400">
                    Choose which alerts you personally receive. These override nothing — the global master switches in
                    <a href="{{ route('admin.settings.app', ['section' => 'notifications']) }}" wire:navigate
                        class="text-brand-500 hover:underline">App → Notifications</a> take priority.
                </p>

                {{-- Channel headers --}}
                <div class="flex items-center justify-end border-b border-zinc-200 bg-zinc-50 px-5 py-2.5 dark:border-zinc-600 dark:bg-zinc-800/40">
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-500">Email</span>
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-500">In-app</span>
                    <span @class([
                        'w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest',
                        'text-zinc-500' => $this->whatsappEnabled,
                        'text-zinc-300 dark:text-zinc-600' => ! $this->whatsappEnabled,
                    ])>WhatsApp</span>
                </div>

                @php
                    $groups = [
                        [
                            'label' => 'Orders & Payments',
                            'icon'  => 'shopping-bag',
                            'rows'  => [
                                ['key' => 'new_order', 'label' => 'New order placed', 'desc' => 'Alert when a customer places an order.'],
                            ],
                        ],
                        [
                            'label' => 'Customers & Reviews',
                            'icon'  => 'users',
                            'rows'  => [
                                ['key' => 'new_review', 'label' => 'New review submitted', 'desc' => 'Alert when a customer review is pending moderation.'],
                            ],
                        ],
                        [
                            'label' => 'Inventory',
                            'icon'  => 'archive-box',
                            'rows'  => [
                                ['key' => 'low_stock', 'label' => 'Low stock alert', 'desc' => 'Alert when a product hits the low stock threshold.'],
                            ],
                        ],
                        [
                            'label' => 'Quotations',
                            'icon'  => 'document-text',
                            'rows'  => [
                                ['key' => 'new_quote',      'label' => 'New quote request',         'desc' => 'Alert when a customer submits a quote request.'],
                                ['key' => 'quote_decision', 'label' => 'Quote accepted / declined', 'desc' => 'Alert when a customer responds to a prepared quotation.'],
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
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5
                            @if (! $loop->last || ! $loop->parent->last) border-b border-zinc-200 dark:border-zinc-700 @endif">
                            <div class="flex-1">
                                <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">{{ $row['label'] }}</div>
                                <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $row['desc'] }}</div>
                            </div>
                            <div class="flex shrink-0 items-center">
                                <div class="flex w-16 justify-center">
                                    <flux:switch wire:model="notifications.{{ $row['key'] }}.email" />
                                </div>
                                <div class="flex w-16 justify-center">
                                    <flux:switch wire:model="notifications.{{ $row['key'] }}.inapp" />
                                </div>
                                <div @class(['flex w-16 justify-center', 'opacity-40' => ! $this->whatsappEnabled])>
                                    <flux:switch wire:model="notifications.{{ $row['key'] }}.whatsapp" :disabled="! $this->whatsappEnabled" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach

            </flux:card>

            <div class="mt-6 flex justify-end">
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    @endif

</x-admin.settings-shell>
