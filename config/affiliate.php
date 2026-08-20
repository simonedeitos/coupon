<?php

declare(strict_types=1);

return [
    'default_network' => 'TradeDoubler',
    'tradedoubler' => [
        'api_base' => getenv('TRADEDOUBLER_API_BASE') ?: 'https://api.tradedoubler.com/1.0',
        'api_key' => getenv('TRADEDOUBLER_API_KEY') ?: '',
        'publisher_id' => getenv('TRADEDOUBLER_PUBLISHER_ID') ?: '',
        'import_statuses' => ['NEW', 'UPDATED', 'DUPLICATE', 'ERROR'],
        'dedupe_keys' => ['external_id', 'hash'],
    ],
];
