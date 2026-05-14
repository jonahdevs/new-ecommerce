<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\Gateways\MpesaGateway;
use App\Services\Payment\Gateways\StripeGateway;
use App\Settings\StripeSettings;
use App\Settings\TaxSettings;
use Livewire\Attributes\{Computed, Layout, Locked, On};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.checkout')] class extends Component {
    #[Locked]
    public ?int $orderId = null;

    public string $paymentMethod = 'card'; // 'card' | 'mpesa'
    public string $mpesaPhone = '';
    public bool $isProcessing = false;

    public function mount(string $order): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        $orderModel = Order::where('reference', $order)
            ->with(['payment', 'items.product', 'user'])
            ->firstOrFail();

        abort_if($orderModel->user_id !== auth()->id(), 403);

        if ($orderModel->payment?->status === PaymentStatus::PAID->value) {
            $this->redirectRoute('customer.orders.confirmation', ['order' => $orderModel->reference], navigate: true);
            return;
        }

        $storedMethod = $orderModel->payment?->meta['payment_method'] ?? 'card';
        $this->paymentMethod = in_array($storedMethod, ['card', 'mpesa']) ? $storedMethod : 'card';

        // Always ensure a Stripe PaymentIntent exists on this page regardless
        // of the default payment method. If the order was placed with mpesa,
        // payment_url will be empty and the card option would fail without this.
        if (empty($orderModel->payment?->payment_url)) {
            app(StripeGateway::class)->initiate($orderModel, $orderModel->payment);
            $orderModel->refresh();
        }

        $this->orderId = $orderModel->id;
    }

    // =====================================================
    // Real-time Updates — Payment Confirmation
    // =====================================================

    #[On('echo-private:order.{orderId},.order.updated')]
    public function handleOrderUpdate(array $data): void
    {
        // Check if payment was confirmed
        if ($data['payment_status'] === PaymentStatus::PAID->value) {
            // Redirect to confirmation page
            $this->dispatch('notify', title: 'Payment Confirmed!', variant: 'success', message: 'Your payment has been received. Redirecting...');

            // Small delay to show the notification before redirect
            $this->js("setTimeout(() => { window.location.href = '{$this->returnUrl}'; }, 1500);");
        }
    }

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
        $settings = app(StripeSettings::class);
        return $settings->public_key ?: config('services.stripe.publishable_key', '');
    }

    #[Computed]
    public function returnUrl(): string
    {
        return route('customer.orders.confirmation', ['order' => $this->order]);
    }

    #[Computed]
    public function taxSettings(): TaxSettings
    {
        return app(TaxSettings::class);
    }

    public function initiateMpesa(): void
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

            $payment->update([
                'meta' => array_merge($payment->meta ?? [], [
                    'mpesa_phone' => $this->mpesaPhone,
                ]),
            ]);

            $response = app(MpesaGateway::class)->initiateWithPhone($order, $payment, $this->mpesaPhone);

            if ($response->isFailed()) {
                $this->dispatch('notify', variant: 'danger', message: $response->message ?? 'Failed to send M-Pesa request. Please try again.');
                $this->isProcessing = false;
                return;
            }

            $this->dispatch('stk-push-initiated', checkoutRequestId: $response->checkoutRequestId);
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
            logger()->error('M-Pesa initiation failed on pay page', ['error' => $e->getMessage()]);
            $this->isProcessing = false;
        }
    }

    public function resetProcessing(): void
    {
        $this->isProcessing = false;
    }
};
?>

@push('head-scripts')
    <script src="https://js.stripe.com/v3/"></script>
@endpush

<div>
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>
                Checkout
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Payment</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Payment</x-slot:heading>

    {{-- Hide the default order summary button since we have payment-specific buttons --}}
    <x-slot name="orderSummaryCta">
        <div class="px-4 py-2">
            <div class="text-center text-sm text-zinc-500">
                Choose a payment method to complete your order
            </div>
            <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                <flux:icon.shield-check class="size-3" />
                <span class="uppercase tracking-widest">SSL Encrypted & Secure</span>
            </div>
        </div>
    </x-slot>

    {{-- ── Payment options ── --}}
    <div class="space-y-3">

        {{-- ── Card option ── --}}

        <flux:card wire:ignore x-data="stripePayment" class="p-0 overflow-hidden">
            {{-- Radio header --}}
            <label class="flex items-center gap-3 px-4 py-3.5 cursor-pointer bg-white"
                @click="$wire.set('paymentMethod', 'card')">
                <input type="radio" :checked="$wire.paymentMethod === 'card'" class="accent-zinc-800" />
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

            {{-- Card fields — always in DOM (x-show not @if), Stripe stays mounted --}}
            <div x-show="$wire.paymentMethod === 'card'" x-cloak class="px-5 pb-5 border-t bg-white">

                {{-- Error alert --}}
                <div x-show="errorMessage" x-transition
                    class="mt-4 flex items-start gap-2 bg-red-50 border border-red-200 rounded-md px-3 py-2.5 text-sm text-red-700">
                    <flux:icon.exclamation-circle class="size-4 shrink-0 mt-0.5" />
                    <span x-text="errorMessage"></span>
                </div>

                {{-- Cardholder name --}}
                <div class="mt-4 mb-4">

                    <flux:input label="Cardholder Name" x-model="cardholderName" type="text"
                        placeholder="Name on card" autocomplete="cc-name" />
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
                <button @click="submitPayment()" :disabled="loading || !ready"
                    class="w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-container disabled:bg-primary-hover/50 disabled:cursor-not-allowed text-on-primary font-semibold py-3 px-4 rounded-md transition-colors text-sm">
                    <span x-show="!loading">
                        Pay {{ format_currency($this->order->total) }}
                    </span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Processing...
                    </span>
                </button>

                <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400 font-medium">
                    <flux:icon.lock-closed class="size-3" />
                    <span>Payments secured by Stripe. We never store your card details.</span>
                </div>
            </div>
        </flux:card>

        {{-- ── M-Pesa option ── --}}
        <flux:card class="p-0 overflow-hidden">
            {{-- Radio header --}}
            <label class="flex items-center gap-3 px-4 py-3.5 cursor-pointer"
                wire:click="$set('paymentMethod', 'mpesa')">
                <input type="radio" wire:model.live="paymentMethod" value="mpesa" class="accent-zinc-800" />
                <flux:icon.device-phone-mobile class="size-4 text-zinc-500" />
                <span class="font-medium text-sm">M-Pesa</span>
                <span class="ml-auto text-xs text-zinc-400">Safaricom</span>
            </label>

            <div x-show="$wire.paymentMethod === 'mpesa'" x-cloak class="px-5 pb-5 border-t">
                <div class="mt-4 mb-5">
                    <flux:text class="text-sm text-zinc-500 mb-4">
                        Enter the M-Pesa number you want to pay with. You will receive a
                        prompt on your phone to enter your PIN.
                    </flux:text>

                    <flux:input wire:model="mpesaPhone" type="tel" placeholder="e.g. 0712 345 678"
                        label="M-Pesa Phone Number" class="w-full" />
                    @error('mpesaPhone')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <flux:button wire:click="initiateMpesa" wire:loading.attr="disabled" wire:target="initiateMpesa"
                    :disabled="$isProcessing" variant="customer-primary" size="customer-lg"
                    class="w-full cursor-pointer">
                    <flux:icon.device-phone-mobile class="w-3.5 h-3.5" />
                    <span wire:loading.remove wire:target="initiateMpesa">
                        Pay {{ format_currency($this->order->total) }}
                    </span>
                    <span wire:loading wire:target="initiateMpesa" class="flex items-center gap-2">
                        <flux:icon.arrow-path class="size-3.5 animate-spin" />
                        Sending request...
                    </span>
                </flux:button>

                <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400 font-medium">
                    <flux:icon.lock-closed class="size-3" />
                    <span>Secure payment via Safaricom M-Pesa</span>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- ── M-Pesa STK waiting modal ── --}}
    <flux:modal name="stk-waiting" class="max-w-sm">
        <div x-data="stkWaiting" x-init="init()">

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
                        :style="'width: ' + (timeLeft / 60 * 100) + '%'"></div>
                </div>
                <flux:text class="text-xs text-zinc-400">Waiting for confirmation...</flux:text>
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
                    <flux:button x-on:click="retry()" variant="customer-primary" size="customer-lg"
                        class="w-full cursor-pointer">
                        <flux:icon.arrow-path class="size-3.5" />
                        Retry M-Pesa
                    </flux:button>

                    <flux:button
                        x-on:click="$flux.modal('stk-waiting').close(); $wire.set('paymentMethod', 'card'); $wire.resetProcessing()"
                        variant="customer-outline" size="customer-lg" class="w-full cursor-pointer">
                        <flux:icon.credit-card class="size-3.5" />
                        Pay with Card instead
                    </flux:button>

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
            _clientSecret: '',
            _returnUrl: '',
            _redirecting: false,

            init() {
                // Stripe mounts once at page load regardless of default payment method.
                // wire:ignore on the wrapper ensures Livewire never destroys this component.
                this.$nextTick(() => this.initStripe());
            },

            initStripe() {
                if (this.stripe) return; // Guard against double-mount

                const publicKey = @js($this->publicKey);
                const clientSecret = @js($this->clientSecret);
                const returnUrl = @js($this->returnUrl);

                if (!publicKey || !clientSecret) {
                    this.errorMessage = 'Payment configuration error. Please contact support.';
                    return;
                }

                this._clientSecret = clientSecret;
                this._returnUrl = returnUrl;

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

                // Mark ready only after all three elements have mounted
                let readyCount = 0;
                [this.cardNumber, this.cardExpiry, this.cardCvc].forEach(el => {
                    el.on('ready', () => {
                        if (++readyCount === 3) this.ready = true;
                    });
                    el.on('change', (e) => {
                        this.errorMessage = e.error?.message ?? '';
                    });
                });
            },

            async submitPayment() {
                if (this.loading || !this.ready) return;

                this.loading = true;
                this.errorMessage = '';

                try {
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
                        return;
                    }

                    if (paymentIntent) {
                        switch (paymentIntent.status) {
                            case 'succeeded':
                                this._redirecting = true;
                                window.location.href = this._returnUrl;
                                return;
                            case 'requires_action':
                                this.errorMessage = 'Authentication was not completed. Please try again.';
                                break;
                            case 'requires_payment_method':
                                this.errorMessage = 'Your card was declined. Please try a different card.';
                                break;
                            default:
                                this.errorMessage = 'Something went wrong. Please try again.';
                        }
                    }
                } catch (e) {
                    this.errorMessage = 'An unexpected error occurred. Please try again.';
                } finally {
                    if (!this._redirecting) this.loading = false;
                }
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
                if (this.interval) clearInterval(this.interval);
                this.timeLeft = 60;
                this.interval = setInterval(() => {
                    this.timeLeft--;
                    if (this.timeLeft <= 0) {
                        clearInterval(this.interval);
                        this.timedOut = true;
                        $wire.resetProcessing();
                    }
                }, 1000);
            },

            retry() {
                this.timedOut = false;
                $flux.modal('stk-waiting').close();
                $wire.resetProcessing();
            },
        }));
    </script>
@endscript
