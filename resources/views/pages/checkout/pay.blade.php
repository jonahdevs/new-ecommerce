<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\Gateways\MpesaGateway;
use App\Settings\PaymentSettings;
use Livewire\Attributes\{Computed, Layout, Locked};
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public ?int $orderId = null;

    public string $paymentMethod = 'card'; // 'card' | 'mpesa'
    public string $mpesaPhone = '';
    public bool $isProcessing = false;

    public function mount(string $order): void
    {
        $orderModel = Order::where('reference', $order)
            ->with(['payment', 'items.product', 'user'])
            ->firstOrFail();

        // Only the owner can access this page
        abort_if($orderModel->user_id !== auth()->id(), 403);

        // If already paid — go straight to confirmation
        if ($orderModel->payment?->status === PaymentStatus::PAID->value) {
            $this->redirectRoute('customer.orders.confirmation', ['order' => $orderModel->reference], navigate: true);
            return;
        }

        // Set default payment method from what customer selected at checkout
        $storedMethod = $orderModel->payment?->meta['payment_method'] ?? 'card';
        $this->paymentMethod = in_array($storedMethod, ['card', 'mpesa']) ? $storedMethod : 'card';

        $this->orderId = $orderModel->id;
    }

    //  Computed

    #[Computed]
    public function order(): Order
    {
        return Order::with(['payment', 'items.product'])->findOrFail($this->orderId);
    }

    #[Computed]
    public function clientSecret(): string
    {
        return $this->order->payment?->payment_url ?? '';
    }

    #[Computed]
    public function publicKey(): string
    {
        return app(PaymentSettings::class)->stripe_public_key ?: config('services.stripe.publishable_key');
    }

    #[Computed]
    public function returnUrl(): string
    {
        return route('customer.orders.confirmation', ['order' => $this->order]);
    }

    //  M-Pesa STK Push

    public function inititateMpesa(): void
    {
        $this->validate(
            [
                'mpesaPhone' => ['required', 'string', 'regex:/^(07|01|2547|2541)\d{8}$/'],
            ],
            [
                'mpesaPhone.required' => 'Please enter your M-Pesa phone number.',
                'mpesaPhone.regex' => 'Please enter a valid Kenyan phone number e.g. 0712345678.',
            ],
        );

        $this->isProcessing = true;

        try {
            $order = $this->order;
            $payment = $order->payment;

            // Store the phone used for this payment
            $payment->update([
                'meta' => array_merge($payment->meta ?? [], [
                    'mpesa_phone' => $this->mpesaPhone,
                ]),
            ]);

            // Fire STK push with customer-entered phone
            $response = app(MpesaGateway::class)->initiateWithPhone($order, $payment, $this->mpesaPhone);

            if ($response->isFailed()) {
                $this->dispatch('notify', variant: 'danger', message: $response->message ?? 'Failed to send M-Pesa request. Please try again.');
                $this->isProcessing = false;
                return;
            }

            // Show STK waiting modal
            $this->dispatch('stk-push-initiated', checkoutRequestId: $response->checkoutRequestId);
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
            logger()->error('M-Pesa initiation failed on pay page', ['error' => $e->getMessage()]);
            $this->isProcessing = false;
        }
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
            <flux:breadcrumbs.item>Payment</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            {{-- ── Left: Payment options (3 cols) ── --}}
            <div class="lg:col-span-3 space-y-3">

                {{-- Card option --}}
                <flux:card @class([
                    'p-0 overflow-hidden transition-colors',
                    'border-zinc-800' => $paymentMethod === 'card',
                ])>
                    {{-- Radio header --}}
                    <label class="flex items-center gap-3 px-4 py-3.5 cursor-pointer"
                        wire:click="$set('paymentMethod', 'card')">
                        <input type="radio" wire:model.live="paymentMethod" value="card" class="accent-zinc-800" />
                        <flux:icon.credit-card class="size-4 text-zinc-500" />
                        <span class="font-medium text-sm">Card Payment</span>
                        <div class="ml-auto flex items-center gap-1.5">
                            @foreach (['Visa', 'MC', 'Amex'] as $card)
                                <span class="px-1.5 py-0.5 bg-zinc-100 rounded text-xs text-zinc-500 font-medium">
                                    {{ $card }}
                                </span>
                            @endforeach
                        </div>
                    </label>

                    {{-- Card fields — shown when card is selected --}}
                    @if ($paymentMethod === 'card')
                        <div class="px-5 pb-5 border-t" x-data="stripePayment">

                            {{-- Error alert --}}
                            <div x-show="errorMessage" x-transition
                                class="mt-4 flex items-start gap-2 bg-red-50 border border-red-200 rounded-md px-3 py-2.5 text-sm text-red-700">
                                <flux:icon.exclamation-circle class="size-4 shrink-0 mt-0.5" />
                                <span x-text="errorMessage"></span>
                            </div>

                            {{-- Cardholder name --}}
                            <div class="mt-4 mb-4">
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

                            <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                                <flux:icon.lock-closed class="size-3" />
                                <span>Payments secured by Stripe. We never store your card details.</span>
                            </div>
                        </div>
                    @endif
                </flux:card>

                {{-- M-Pesa option --}}
                <flux:card @class([
                    'p-0 overflow-hidden transition-colors',
                    'border-zinc-800' => $paymentMethod === 'mpesa',
                ])>
                    {{-- Radio header --}}
                    <label class="flex items-center gap-3 px-4 py-3.5 cursor-pointer"
                        wire:click="$set('paymentMethod', 'mpesa')">
                        <input type="radio" wire:model.live="paymentMethod" value="mpesa" class="accent-zinc-800" />
                        <flux:icon.device-phone-mobile class="size-4 text-zinc-500" />
                        <span class="font-medium text-sm">M-Pesa</span>
                        <span class="ml-auto text-xs text-zinc-400">Safaricom</span>
                    </label>

                    {{-- M-Pesa fields — shown when mpesa is selected --}}
                    @if ($paymentMethod === 'mpesa')
                        <div class="px-5 pb-5 border-t">
                            <div class="mt-4 mb-5">
                                <flux:text class="text-sm text-zinc-500 mb-4">
                                    Enter the M-Pesa number you want to pay with. You will receive a
                                    prompt on your phone to enter your PIN.
                                </flux:text>

                                <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                                    M-Pesa Phone Number
                                </label>
                                <flux:input wire:model="mpesaPhone" type="tel" placeholder="e.g. 0712 345 678"
                                    class="w-full" />
                                @error('mpesaPhone')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <flux:button wire:click="inititateMpesa" wire:loading.attr="disabled"
                                :disabled="$isProcessing" variant="primary" class="w-full cursor-pointer"
                                icon="device-phone-mobile">
                                {{ $isProcessing ? 'Sending request...' : 'Pay ' . format_currency($this->order->total) . ' via M-Pesa' }}
                            </flux:button>

                            <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                                <flux:icon.lock-closed class="size-3" />
                                <span>Secure payment via Safaricom M-Pesa</span>
                            </div>
                        </div>
                    @endif
                </flux:card>
            </div>

            {{-- ── Right: Order summary (2 cols) ── --}}
            <div class="lg:col-span-2">
                <flux:card class="p-0">
                    <div class="px-4 py-3 border-b">
                        <flux:heading level="3" class="font-medium!">Order Summary</flux:heading>
                    </div>

                    {{-- Items --}}
                    <div class="divide-y max-h-52 overflow-y-auto">
                        @foreach ($this->order->items as $item)
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden shrink-0">
                                    @php $img = $item->product_image_url ?? $item->product?->image_url; @endphp
                                    @if ($img)
                                        <img src="{{ asset($img) }}" alt="{{ $item->product_snapshot['name'] ?? '' }}"
                                            class="w-full h-full object-cover" />
                                    @else
                                        <flux:icon.photo class="w-full h-full p-1.5 text-zinc-300" />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium truncate">
                                        {{ $item->product_snapshot['name'] ?? $item->product?->name }}
                                    </p>
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
                                @if ($this->order->shipping_snapshot['method_name'] ?? null)
                                    <span class="text-zinc-400">
                                        · {{ $this->order->shipping_snapshot['method_name'] }}
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
            </div>
        </div>
    </div>

    {{-- M-Pesa STK waiting modal --}}
    <flux:modal name="stk-waiting" class="max-w-sm">
        <div x-data="stkWaiting">

            {{-- Waiting state --}}
            <div x-show="!timedOut" class="text-center p-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <flux:icon.device-phone-mobile class="size-8 text-green-600" />
                </div>

                <flux:heading size="lg" class="mb-2">Check your phone</flux:heading>

                <flux:text class="text-zinc-500 text-sm mb-6">
                    An M-Pesa payment request has been sent to your phone.
                    Enter your PIN to complete payment.
                </flux:text>

                <div class="text-2xl font-mono font-bold text-zinc-800 mb-2" x-text="timeLeft + 's'"></div>
                <div class="w-full bg-zinc-100 rounded-full h-1.5 mb-6">
                    <div class="bg-green-500 h-1.5 rounded-full transition-all duration-1000"
                        x-bind:style="'width: ' + (timeLeft / 60 * 100) + '%'"></div>
                </div>

                <flux:text class="text-xs text-zinc-400">
                    Waiting for confirmation...
                </flux:text>
            </div>

            {{-- Timed out state --}}
            <div x-show="timedOut" class="text-center p-6">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <flux:icon.clock class="size-8 text-amber-500" />
                </div>

                <flux:heading size="lg" class="mb-2">Request Expired</flux:heading>

                <flux:text class="text-zinc-500 text-sm mb-6">
                    The M-Pesa request timed out. You can retry or switch to card payment.
                </flux:text>

                <div class="flex flex-col gap-2">
                    {{-- Retry M-Pesa --}}
                    <flux:button x-on:click="retry()" variant="primary" class="w-full cursor-pointer">
                        <flux:icon.arrow-path class="size-4 me-2" />
                        Retry M-Pesa
                    </flux:button>

                    {{-- Switch to card --}}
                    <flux:button x-on:click="$flux.modal('stk-waiting').close(); $wire.set('paymentMethod', 'card')"
                        variant="ghost" class="w-full cursor-pointer">
                        <flux:icon.credit-card class="size-4 me-2" />
                        Pay with Card instead
                    </flux:button>

                    {{-- Cancel --}}
                    <flux:link href="{{ route('customer.orders.index') }}" class="text-xs text-zinc-400 mt-1">
                        Cancel and view orders
                    </flux:link>
                </div>
            </div>

        </div>
    </flux:modal>
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

                console.log(returnUrl);

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
                        iconColor: '#ef4444'
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
                                name: this.cardholderName || undefined
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

        Alpine.data('stkWaiting', () => ({
            timeLeft: 60,
            checkoutRequestId: null,
            interval: null,
            timedOut: false,

            init() {
                Livewire.on('stk-push-initiated', ({
                    checkoutRequestId
                }) => {
                    this.checkoutRequestId = checkoutRequestId;
                    this.timedOut = false;
                    $flux.modal('stk-waiting').show();
                    this.startCountdown();
                });
            },

            startCountdown() {
                this.timeLeft = 60;
                clearInterval(this.interval);
                this.interval = setInterval(() => {
                    this.timeLeft--;
                    if (this.timeLeft <= 0) {
                        clearInterval(this.interval);
                        this.timedOut = true;
                    }
                }, 1000);
            },
            retry() {
                this.timedOut = false;
                $flux.modal('stk-waiting').close();
            },
        }));
    </script>
@endscript
