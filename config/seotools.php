<?php

/**
 * @see https://github.com/artesaos/seotools
 */
return [
    'inertia' => env('SEO_TOOLS_INERTIA', false),

    'meta' => [
        'defaults' => [
            'title' => 'Sheffield — Commercial Kitchen Equipment for East Africa',
            'titleBefore' => false,
            'description' => 'Sheffield supplies commercial kitchen equipment to restaurants, hotels and catering operations across Kenya, Uganda, Tanzania and Rwanda. Authorised distributor for Rational, Hobart, True, Electrolux Professional and more.',
            'separator' => ' · ',
            'keywords' => [
                'commercial kitchen equipment',
                'restaurant equipment Kenya',
                'hotel kitchen Nairobi',
                'combi ovens East Africa',
                'commercial refrigeration',
                'Rational distributor',
                'Hobart distributor',
            ],
            'canonical' => 'current', // emits <link rel="canonical"> on every page
            'robots' => 'index,follow',
        ],

        'webmaster_tags' => [
            'google' => env('SEO_GOOGLE_VERIFICATION'),
            'bing' => null,
            'alexa' => null,
            'pinterest' => null,
            'yandex' => null,
            'norton' => null,
        ],

        'add_notranslate_class' => false,
    ],

    'opengraph' => [
        'defaults' => [
            'title' => 'Sheffield — Commercial Kitchen Equipment for East Africa',
            'description' => 'Authorised distributor for restaurant, hotel and catering equipment. Showrooms in Nairobi, Mombasa, Kampala and Kigali.',
            'url' => false, // set per-request in partials/head.blade.php so it's always the current absolute URL
            'type' => 'website',
            'site_name' => 'Sheffield',
            'locale' => 'en_KE',
            'images' => [], // default OG image is added per-request as an absolute URL (see partials/head.blade.php)
        ],
    ],

    'twitter' => [
        'defaults' => [
            'card' => 'summary_large_image',
            // 'site' => '@sheffieldea', // set once a Twitter/X handle is registered
        ],
    ],

    'json-ld' => [
        'defaults' => [
            'title' => 'Sheffield — Commercial Kitchen Equipment for East Africa',
            'description' => 'Authorised distributor for restaurant, hotel and catering equipment.',
            'url' => false, // set per-request in partials/head.blade.php
            'type' => 'WebPage',
            'images' => [],
        ],
    ],
];
