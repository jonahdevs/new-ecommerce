<?php

use Livewire\Component;
use App\Models\Order;
use Livewire\Attributes\{Layout, Computed, Title};

new #[Title('Order Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;
};
?>

<div>
    <div class="bg-white border rounded-lg">
        <!-- Header -->
        <div class="px-5 py-2 border-b">
            <div class="flex items-center gap-3">
                <a href="{{ route('customer.orders.show', $order) }}" wire:navigate
                    class="text-zinc-500 hover:text-zinc-700">
                    <flux:icon.arrow-left class="w-5 h-5" />
                </a>
                <h1 class="text-lg font-medium text-zinc-900">Package History</h1>
            </div>
        </div>

        <!-- Timeline -->
        <div class="p-5">
            {{-- <livewire:order-timeline :order="$order" :isAdmin="false" /> --}}
        </div>
    </div>
</div>
