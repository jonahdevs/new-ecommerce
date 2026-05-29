<?php

use App\Services\Mpesa\MpesaPaymentService;

it('normalizes Kenyan phone numbers to the 2547…/2541… MSISDN format', function (string $input, string $expected) {
    expect(MpesaPaymentService::normalizePhone($input))->toBe($expected);
})->with([
    'leading zero' => ['0712345678', '254712345678'],
    'plus prefix' => ['+254712345678', '254712345678'],
    'already normalized' => ['254712345678', '254712345678'],
    'nine digits' => ['712345678', '254712345678'],
    'airtel 01' => ['0110123456', '254110123456'],
    'spaces' => ['0712 345 678', '254712345678'],
]);

it('validates Kenyan mobile numbers', function () {
    expect(MpesaPaymentService::isValidKenyanMobile('0712345678'))->toBeTrue()
        ->and(MpesaPaymentService::isValidKenyanMobile('0110123456'))->toBeTrue()
        ->and(MpesaPaymentService::isValidKenyanMobile('+254712345678'))->toBeTrue()
        ->and(MpesaPaymentService::isValidKenyanMobile('0812345678'))->toBeFalse()
        ->and(MpesaPaymentService::isValidKenyanMobile('12345'))->toBeFalse();
});
