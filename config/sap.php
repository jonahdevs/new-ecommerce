<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAP Middleware API base URL
    |--------------------------------------------------------------------------
    | The base URL of the SAP middleware API (no trailing slash).
    | Example: http://localhost:85
    |
    | The invoice creation endpoint is: {base_url}/api/invoice/create
    */
    'base_url' => env('SAP_BASE_URL', 'http://localhost:85'),

    /*
    |--------------------------------------------------------------------------
    | Webhook security
    |--------------------------------------------------------------------------
    | HMAC-SHA256 secret used to verify inbound webhook payloads from SAP.
    */
    'api_key' => env('SAP_API_KEY'),

    'webhook_secret' => env('SAP_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | KRA Business PIN
    |--------------------------------------------------------------------------
    | Your company's KRA PIN displayed on tax receipts.
    */
    'business_pin' => env('KRA_BUSINESS_PIN'),

    /*
    |--------------------------------------------------------------------------
    | TLS Certificate Verification
    |--------------------------------------------------------------------------
    | Set to false only for local development against self-signed certificates.
    | Must be true (or a path to a CA bundle) in staging and production.
    */
    'verify_ssl' => env('SAP_VERIFY_SSL', true),
];
