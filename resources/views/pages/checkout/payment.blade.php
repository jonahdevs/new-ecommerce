<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component {
    public ?string $paymentUrl = null;
    public ?string $orderReference = null;

    public function mount()
    {
        // Get payment URL from session (set by CheckoutService)
        $this->paymentUrl = session('pesawise_payment_url');
        $this->orderReference = session('pesawise_payment_reference');

        if (!$this->paymentUrl || !$this->orderReference) {
            return redirect()->route('checkout.summary')->with('error', 'Payment session expired. Please try again.');
        }
    }
};
?>

<div class="min-h-[80svh]">
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-4 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>Checkout</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Payment</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <iframe src="{{ $paymentUrl }}" class="w-full border-0 h-screen" allow="payment"
        sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-top-navigation">
    </iframe>
</div>
