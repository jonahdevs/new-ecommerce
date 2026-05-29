<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Mpesa\MpesaPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MpesaCallbackController extends Controller
{
    public function __construct(private MpesaPaymentService $payments) {}

    /**
     * Receive the asynchronous STK Push result from Safaricom (production path).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->payments->applyCallback($request->all());

        // Acknowledge receipt so Safaricom stops retrying.
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
