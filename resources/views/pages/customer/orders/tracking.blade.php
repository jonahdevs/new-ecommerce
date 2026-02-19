<?php

use Livewire\Component;
use App\Models\Order;
use Livewire\Attributes\{Layout, Computed, Title};

new #[Title('Order Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;
};
?>

<div>
    <flux:card class="rounded-md p-0">
        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.orders.show', $order)" wire:navigate></flux:button>

            <flux:heading size="lg">Package History</flux:heading>
        </div>

        <div class="p-5 px-8">
            <x-my-timeline-item title="Order placed" first icon="o-map-pin" />

            <x-my-timeline-item title="Payment confirmed" icon="o-credit-card" />

            <x-my-timeline-item title="Shipped" icon="o-paper-airplane" />

            <x-my-timeline-item title="Delivered" pending last icon="o-gift" />
        </div>
    </flux:card>
</div>
