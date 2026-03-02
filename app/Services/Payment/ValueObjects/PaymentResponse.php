<?php

namespace App\Services\Payment\ValueObjects;

/**
 * PaymentResponse
 *
 * Returned by every gateway after initiate().
 * Tells the checkout UI exactly what to do next.
 *
 * Types:
 *   redirect   → redirect customer to $url (Pesawise, Pesapal, PayPal)
 *   iframe     → show $url in an iframe/modal (Pesawise iframe mode)
 *   stk_push   → M-Pesa push sent, show waiting screen
 *   inline     → Stripe Elements, show card form with $clientSecret
 */
class PaymentResponse
{
    private function __construct(
        public readonly string  $type,
        public readonly bool    $success,
        public readonly ?string $url              = null,
        public readonly ?string $checkoutRequestId = null,
        public readonly ?string $clientSecret     = null,
        public readonly ?string $message          = null,
    ) {}

    //  Named constructors 

    public static function redirect(string $url): self
    {
        return new self(type: 'redirect', success: true, url: $url);
    }

    public static function iframe(string $url): self
    {
        return new self(type: 'iframe', success: true, url: $url);
    }

    public static function stkPush(string $checkoutRequestId): self
    {
        return new self(
            type: 'stk_push',
            success: true,
            checkoutRequestId: $checkoutRequestId,
        );
    }

    public static function inline(string $clientSecret): self
    {
        return new self(type: 'inline', success: true, clientSecret: $clientSecret);
    }

    public static function failed(string $message): self
    {
        return new self(type: 'failed', success: false, message: $message);
    }

    //  Helpers 

    public function isRedirect(): bool
    {
        return $this->type === 'redirect';
    }
    public function isIframe(): bool
    {
        return $this->type === 'iframe';
    }
    public function isStkPush(): bool
    {
        return $this->type === 'stk_push';
    }
    public function isInline(): bool
    {
        return $this->type === 'inline';
    }
    public function isFailed(): bool
    {
        return ! $this->success;
    }
}
