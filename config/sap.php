<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAP Service Layer connection
    |--------------------------------------------------------------------------
    | The base URL must include the port and API version path, e.g.
    | https://sap-server.example.com:50000/b1s/v1
    */
    'service_layer_url' => env('SAP_SERVICE_LAYER_URL'),
    'company_db'        => env('SAP_COMPANY_DB'),
    'username'          => env('SAP_USERNAME'),
    'password'          => env('SAP_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Session management
    |--------------------------------------------------------------------------
    | SAP sessions expire after this many minutes of inactivity.
    | Set this slightly lower than your actual SAP session timeout
    | so we renew proactively rather than hitting 401s in production.
    */
    'session_timeout' => env('SAP_SESSION_TIMEOUT', 28),

    /*
    |--------------------------------------------------------------------------
    | Webhook security
    |--------------------------------------------------------------------------
    | HMAC-SHA256 secret used to verify inbound webhook payloads from SAP.
    | Generate a strong random string and share it with your SAP team.
    */
    'webhook_secret' => env('SAP_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Document defaults
    |--------------------------------------------------------------------------
    */
    'default_tax_code'  => env('SAP_DEFAULT_TAX_CODE', 'VAT16'),
    'warehouse_code'    => env('SAP_WAREHOUSE_CODE', '01'),

    /*
    |--------------------------------------------------------------------------
    | Guest Business Partner
    |--------------------------------------------------------------------------
    | SAP requires a CardCode on every document. Guest orders (no user_id)
    | are posted against this catch-all Business Partner code.
    */
    'guest_bp_code' => env('SAP_GUEST_BP_CODE', 'C_GUEST'),

];
