<?php

use App\Models\Order;
use App\Models\Payment;
use Livewire\Attributes\{Computed, Layout, Locked};
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public string $orderReference = '';

    #[Locked]
    public ?int $orderId = null;

    public function mount(string $order): void
    {
        // Resolve order by reference
        $orderModel = Order::where('reference', $order)
            ->with(['payment', 'items.product', 'deliveryOrder.shippingMethod', 'user'])
            ->firstOrFail();

        \Log::info('Order Model: ' . $orderModel);

        // Only the owner can access this page
        abort_if($orderModel->user_id !== auth()->id(), 403);

        // If already paid — go straight to confirmation
        if ($orderModel->payment?->status === 'paid') {
            $this->redirectRoute('customer.orders.confirmation', $orderModel, navigate: true);
            return;
        }

        // If no payment record or no client secret — something went wrong
        if (!$orderModel->payment?->payment_url) {
            session()->flash('error', 'Payment session expired. Please try again.');
            $this->redirectRoute('checkout.summary', navigate: true);
            return;
        }

        $this->orderReference = $orderModel->reference;
        $this->orderId = $orderModel->id;
    }

    //  Computed

    #[Computed]
    public function order(): Order
    {
        return Order::with(['payment', 'items.product', 'deliveryOrder.shippingMethod'])->findOrFail($this->orderId);
    }

    #[Computed]
    public function clientSecret(): string
    {
        // payment_url stores the Stripe client_secret (set by StripeGateway)
        return $this->order->payment?->payment_url ?? '';
    }

    #[Computed]
    public function publicKey(): string
    {
        return app(\App\Settings\PaymentSettings::class)->stripe_public_key ?: config('services.stripe.publishable_key');
    }

    #[Computed]
    public function returnUrl(): string
    {
        return route('customer.orders.confirmation', $this->order);
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>
                Checkout
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Card Payment</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            {{-- ── Left: Payment form (3 cols) ── --}}
            <div class="lg:col-span-3">
                <flux:card class="p-0">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <flux:icon.credit-card class="size-4 text-zinc-400" />
                        <flux:heading level="3" class="font-medium!">Card Details</flux:heading>
                    </div>

                    <div class="p-5" x-data="stripePayment">

                        {{-- Error alert --}}
                        <div x-show="errorMessage" x-transition
                            class="mb-4 flex items-start gap-2 bg-red-50 border border-red-200 rounded-md px-3 py-2.5 text-sm text-red-700">
                            <flux:icon.exclamation-circle class="size-4 shrink-0 mt-0.5" />
                            <span x-text="errorMessage"></span>
                        </div>

                        {{-- Card holder name --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                                Cardholder Name
                            </label>
                            <input x-model="cardholderName" type="text" placeholder="Name on card"
                                autocomplete="cc-name"
                                class="w-full border border-zinc-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-zinc-800 focus:border-zinc-800 transition-colors" />
                        </div>

                        {{-- Card number --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                                Card Number
                            </label>
                            <div id="stripe-card-number"
                                class="w-full border border-zinc-300 rounded-md px-3 py-2.5 text-sm focus-within:ring-1 focus-within:ring-zinc-800 focus-within:border-zinc-800 transition-colors bg-white">
                            </div>
                        </div>

                        {{-- Expiry + CVC --}}
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                                    Expiry Date
                                </label>
                                <div id="stripe-card-expiry"
                                    class="w-full border border-zinc-300 rounded-md px-3 py-2.5 text-sm focus-within:ring-1 focus-within:ring-zinc-800 focus-within:border-zinc-800 transition-colors bg-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                                    CVC
                                </label>
                                <div id="stripe-card-cvc"
                                    class="w-full border border-zinc-300 rounded-md px-3 py-2.5 text-sm focus-within:ring-1 focus-within:ring-zinc-800 focus-within:border-zinc-800 transition-colors bg-white">
                                </div>
                            </div>
                        </div>

                        {{-- Pay button --}}
                        <button x-on:click="submitPayment" x-bind:disabled="loading || !ready"
                            class="w-full flex items-center justify-center gap-2 bg-zinc-900 hover:bg-zinc-700 disabled:bg-zinc-300 disabled:cursor-not-allowed text-white font-semibold py-3 px-4 rounded-md transition-colors text-sm">
                            <span x-show="!loading">
                                Pay {{ format_currency($this->order->total) }}
                            </span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Processing...
                            </span>
                        </button>

                        {{-- Security note --}}
                        <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                            <flux:icon.lock-closed class="size-3" />
                            <span>Payments secured by Stripe. We never store your card details.</span>
                        </div>

                        {{-- Accepted cards --}}
                        <div class="mt-3 flex items-center justify-center gap-2">
                            @foreach (['Visa', 'MC', 'Amex'] as $card)
                                <span class="px-2 py-0.5 bg-zinc-100 rounded text-xs text-zinc-500 font-medium">
                                    {{ $card }}
                                </span>
                            @endforeach
                        </div>

                    </div>
                </flux:card>
            </div>

            {{-- ── Right: Order summary (2 cols) ── --}}
            <div class="lg:col-span-2">

                {{-- Order reference --}}
                <flux:card class="p-0">
                    <div class="px-4 py-3 border-b">
                        <flux:heading level="3" class="font-medium!">Order Summary</flux:heading>
                    </div>

                    {{-- Items --}}
                    <div class="divide-y max-h-52 overflow-y-auto">
                        @foreach ($this->order->items as $item)
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden shrink-0">
                                    @if ($item->product?->image_path)
                                        <img src="{{ asset($item->product->image_path) }}" alt="{{ $item->name }}"
                                            class="w-full h-full object-cover" />
                                    @else
                                        <flux:icon.photo class="w-full h-full p-1.5 text-zinc-300" />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium truncate">{{ $item->name }}</p>
                                    <p class="text-xs text-zinc-400">× {{ $item->quantity }}</p>
                                </div>
                                <span class="text-xs font-semibold shrink-0">
                                    {{ format_currency($item->total_cents / 100) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Totals --}}
                    <div class="px-4 py-3 border-t space-y-1.5">
                        <div class="flex justify-between text-xs text-zinc-500">
                            <span>Subtotal</span>
                            <span>{{ format_currency($this->order->subtotal) }}</span>
                        </div>

                        @if ($this->order->discount > 0)
                            <div class="flex justify-between text-xs text-green-600">
                                <span>Discount</span>
                                <span>− {{ format_currency($this->order->discount) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-xs text-zinc-500">
                            <span>Shipping
                                @if ($this->order->deliveryOrder?->shippingMethod)
                                    <span class="text-zinc-400">
                                        · {{ $this->order->deliveryOrder->shippingMethod->name }}
                                    </span>
                                @endif
                            </span>
                            <span>
                                {{ $this->order->shipping == 0 ? 'Free' : format_currency($this->order->shipping) }}
                            </span>
                        </div>

                        <div class="flex justify-between font-semibold text-sm border-t pt-2 mt-1">
                            <span>Total</span>
                            <span>{{ format_currency($this->order->total) }}</span>
                        </div>
                    </div>
                </flux:card>

                {{-- Delivery info --}}
                @if ($this->order->deliveryOrder)
                    <div class="bg-white border rounded-lg px-4 py-3">
                        <div class="flex items-center gap-1.5 mb-2">
                            <flux:icon.truck class="size-4 text-zinc-400" />
                            <flux:text class="text-xs font-medium">Delivering to</flux:text>
                        </div>
                        <flux:text class="text-xs text-zinc-500">
                            {{ $this->order->shipping_address['full_name'] ?? '' }}<br />
                            {{ implode(
                                ', ',
                                array_filter([$this->order->shipping_address['area'] ?? null, $this->order->shipping_address['county'] ?? null]),
                            ) }}
                        </flux:text>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>




@script
    <script>
        Alpine.data('stripePayment', () => ({
            stripe: null,
            elements: null,
            cardNumber: null,
            cardExpiry: null,
            cardCvc: null,
            cardholderName: '',
            loading: false,
            ready: false,
            errorMessage: '',

            init() {
                const publicKey = @js($this->publicKey);
                const clientSecret = @js($this->clientSecret);
                const returnUrl = @js($this->returnUrl);

                if (!publicKey || !clientSecret) {
                    this.errorMessage = 'Payment configuration error. Please contact support.';
                    return;
                }

                this.stripe = Stripe(publicKey);
                this.elements = this.stripe.elements();

                const style = {
                    base: {
                        fontSize: '14px',
                        color: '#18181b',
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        fontSmoothing: 'antialiased',
                        '::placeholder': {
                            color: '#a1a1aa'
                        },
                        iconColor: '#71717a',
                    },
                    invalid: {
                        color: '#ef4444',
                        iconColor: '#ef4444',
                    },
                };

                this.cardNumber = this.elements.create('cardNumber', {
                    style,
                    showIcon: true
                });
                this.cardExpiry = this.elements.create('cardExpiry', {
                    style
                });
                this.cardCvc = this.elements.create('cardCvc', {
                    style
                });

                this.cardNumber.mount('#stripe-card-number');
                this.cardExpiry.mount('#stripe-card-expiry');
                this.cardCvc.mount('#stripe-card-cvc');

                this.cardNumber.on('ready', () => {
                    this.ready = true;
                });

                [this.cardNumber, this.cardExpiry, this.cardCvc].forEach(el => {
                    el.on('change', (e) => {
                        this.errorMessage = e.error ? e.error.message : '';
                    });
                });

                this._clientSecret = clientSecret;
                this._returnUrl = returnUrl;
            },

            async submitPayment() {
                if (this.loading) return;

                this.loading = true;
                this.errorMessage = '';

                const {
                    paymentIntent,
                    error
                } = await this.stripe.confirmCardPayment(
                    this._clientSecret, {
                        payment_method: {
                            card: this.cardNumber,
                            billing_details: {
                                name: this.cardholderName || undefined,
                            },
                        },
                        return_url: this._returnUrl,
                    }
                );

                if (error) {
                    this.errorMessage = error.message;
                    this.loading = false;
                    return;
                }

                if (paymentIntent && paymentIntent.status === 'succeeded') {
                    window.location.href = this._returnUrl;
                    return;
                }

                this.loading = false;
            },
        }));
    </script>
@endscript
