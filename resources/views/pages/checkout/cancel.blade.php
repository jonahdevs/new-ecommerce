<?php

use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.checkout')] class extends Component {
    public ?Order $order = null;
    public ?string $reason = null;

    public function mount(?string $reference = null)
    {
        // Try to find the most recent pending/failed order for this user
        if ($reference) {
            $this->order = Order::where('reference', $reference)
                ->where('user_id', auth()->id())
                ->first();
        }

        if (!$this->order) {
            $this->order = Order::where('user_id', auth()->id())
                ->whereIn('payment_status', ['pending', 'failed'])
                ->latest()
                ->first();
        }

        // Get cancellation reason from query string if provided
        $this->reason = request()->query('reason');
    }

    public function retryPayment()
    {
        if (!$this->order) {
            return $this->redirectRoute('cart', navigate: true);
        }

        // Redirect to pay page to retry
        return $this->redirectRoute('checkout.pay', $this->order, navigate: true);
    }
};
?>

<div>
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Payment Cancelled</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Payment Cancelled</x-slot:heading>

    <flux:card class="max-w-lg mx-auto">
        <div class="text-center py-8">
            {{-- Icon --}}
            <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-amber-100 flex items-center justify-center">
                <flux:icon.exclamation-triangle class="size-8 text-amber-600" />
            </div>

            {{-- Message --}}
            <flux:heading size="xl" class="mb-2">Payment was cancelled</flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if ($reason)
                    {{ $reason }}
                @else
                    Your payment was not completed. Don't worry — no charges were made to your account.
                @endif
            </flux:text>

            {{-- Order details if available --}}
            @if ($order)
                <div class="bg-zinc-50 rounded-lg p-4 mb-6 text-left">
                    <div class="flex justify-between items-center mb-2">
                        <flux:text class="text-sm text-zinc-500">Order Reference</flux:text>
                        <flux:text class="font-medium">{{ $order->reference }}</flux:text>
                    </div>
                    <div class="flex justify-between items-center">
                        <flux:text class="text-sm text-zinc-500">Total Amount</flux:text>
                        <flux:text class="font-medium">{{ format_currency($order->total) }}</flux:text>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                @if ($order && $order->payment_status->value !== 'paid')
                    <flux:button variant="customer-primary" size="customer-lg" wire:click="retryPayment"
                        class="w-full sm:w-auto cursor-pointer">
                        <flux:icon.arrow-path class="size-3.5" />
                        Try Again
                    </flux:button>
                @endif

                <flux:button variant="customer-outline" size="customer-lg" :href="route('cart')" wire:navigate
                    class="w-full sm:w-auto cursor-pointer">
                    <flux:icon.shopping-cart class="size-3.5" />
                    Return to Cart
                </flux:button>

                <flux:button variant="customer-outline" size="customer-lg" :href="route('customer.orders.index')"
                    wire:navigate class="w-full sm:w-auto cursor-pointer">
                    <flux:icon.clipboard-document-list class="size-3.5" />
                    View Orders
                </flux:button>
            </div>

            {{-- Help text --}}
            <flux:text class="text-xs text-zinc-400 mt-6">
                If you continue to experience issues, please contact our support team.
            </flux:text>
        </div>
    </flux:card>
</div>
