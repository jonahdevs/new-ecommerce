<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pesawise' => [
        'api_url' => env('PESAWISE_API_URL', 'https://api.pesawise.xyz/api'),
        'env' => env('PESAWISE_ENV'),
        'api_key' => env('PESAWISE_API_KEY'),
        'api_secret' => env('PESAWISE_API_SECRET'),
        'balance_id_kes' => env('PESAWISE_BALANCE_ID_KES', 1102801),
        'balance_id_usd' => env('PESAWISE_BALANCE_ID_USD', 1102802),
        'callback_base_url' => env('PESAWISE_CALLBACK_BASE_URL'),
    ],

    'paypal' => [
        'env' => env('PAYPAL_ENV', 'sandbox'),
        'client_id' => env('PAYPAL_MODE') === 'live'
            ? env('PAYPAL_LIVE_CLIENT_ID')
            : env('PAYPAL_SANDBOX_CLIENT_ID'),
        'secret' => env('PAYPAL_MODE') === 'live'
            ? env('PAYPAL_LIVE_SECRET')
            : env('PAYPAL_SANDBOX_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'mpesa' => [
        'env' => env('MPESA_ENV', 'sandbox'), // sandbox or production
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        'shortcode' => env('MPESA_SHORTCODE', '174379'), // 174379 is sandbox default
        'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL') . '/checkout/mpesa/callback'),
    ],
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET')
    ],
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URL'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URL'),
    ],
];
