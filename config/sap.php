<?php

return [
    'base_url' => env('SAP_BASE_URL', 'http://localhost:85'),
    'api_key' => env('SAP_API_KEY'),
    'webhook_secret' => env('SAP_WEBHOOK_SECRET'),
    'business_pin' => env('KRA_BUSINESS_PIN'),
    'verify_ssl' => env('SAP_VERIFY_SSL', true),
    'recovery_delay_minutes' => env('SAP_RECOVERY_DELAY_MINUTES', 30),
];
