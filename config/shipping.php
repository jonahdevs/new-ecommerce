<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fallback Shipping Rate
    |--------------------------------------------------------------------------
    |
    | This rate is used when no matching shipping rate is found for a given
    | zone and weight combination. Applies to standard delivery method.
    |
    */
    'fallback_rate' => env('SHIPPING_FALLBACK_RATE', 500.00),

    /*
    |--------------------------------------------------------------------------
    | Default Shipping Method
    |--------------------------------------------------------------------------
    |
    | The default shipping method code to use when user hasn't specified
    | a preference. Should match a code in shipping_methods table.
    |
    */
    'default_method_code' => env('SHIPPING_DEFAULT_METHOD', 'standard'),

    /*
    |--------------------------------------------------------------------------
    | Weight Unit
    |--------------------------------------------------------------------------
    |
    | The unit used for product weights in the database.
    | Options: 'grams', 'kilograms'
    |
    */
    'weight_unit' => 'grams', // Products store weight in grams

    /*
    |--------------------------------------------------------------------------
    | Minimum Order Weight
    |--------------------------------------------------------------------------
    |
    | Minimum weight in KG for shipping calculation. Orders below this
    | will be rounded up to this weight.
    |
    */
    'min_order_weight_kg' => 0.1,

    /*
    |--------------------------------------------------------------------------
    | Default Estimated Delivery Days
    |--------------------------------------------------------------------------
    |
    | Used when shipping rate doesn't have estimated days defined
    |
    */
    'default_estimated_days' => [
        'min' => 2,
        'max' => 5,
    ],
];
