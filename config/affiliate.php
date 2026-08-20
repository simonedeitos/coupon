<?php

declare(strict_types=1);

return [
    'default_network' => 'TradeDoubler',
    'tradedoubler' => [
        'api_base' => getenv('TRADEDOUBLER_API_BASE') ?: 'https://api.tradedoubler.com/1.0',
        'import_statuses' => ['NEW', 'UPDATED', 'DUPLICATE', 'ERROR'],
        'dedupe_keys' => ['external_id', 'hash'],
        'default_page_size' => 100,

        // Token a livello di account (scope PUBLISHER, valgono per tutti i siti collegati all'account)
        'publisher_tokens' => [
            'VOUCHERS' => getenv('TRADEDOUBLER_TOKEN_VOUCHERS_PUBLISHER') ?: '',
            'UTS' => getenv('TRADEDOUBLER_TOKEN_UTS_PUBLISHER') ?: '',
            'CONVERSIONS' => getenv('TRADEDOUBLER_TOKEN_CONVERSIONS_PUBLISHER') ?: '',
        ],

        // Siti configurati con i relativi websiteId e token specifici per sito
        'sites' => [
            'couponami' => [
                'label' => 'Couponami',
                'website_id' => getenv('TRADEDOUBLER_WEBSITE_ID_COUPONAMI') ?: '3495613',
                'tokens' => [
                    'VOUCHERS' => getenv('TRADEDOUBLER_TOKEN_VOUCHERS_COUPONAMI') ?: '',
                    'PRODUCTS' => getenv('TRADEDOUBLER_TOKEN_PRODUCTS_COUPONAMI') ?: '',
                ],
            ],
            'simonedeitos' => [
                'label' => 'Simone Dei Tos',
                'website_id' => getenv('TRADEDOUBLER_WEBSITE_ID_SIMONEDEITOS') ?: '3495566',
                'tokens' => [
                    'VOUCHERS' => getenv('TRADEDOUBLER_TOKEN_VOUCHERS_SIMONEDEITOS') ?: '',
                    'PRODUCTS' => getenv('TRADEDOUBLER_TOKEN_PRODUCTS_SIMONEDEITOS') ?: '',
                ],
            ],
        ],

        // Sito predefinito usato quando non specificato esplicitamente
        'default_site' => 'couponami',
    ],
];