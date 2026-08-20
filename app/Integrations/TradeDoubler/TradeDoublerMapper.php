<?php

declare(strict_types=1);

namespace App\Integrations\TradeDoubler;

use App\Helpers\Str;

final class TradeDoublerMapper
{
    public function map(array $payload): array
    {
        return [
            'external_id' => $payload['external_id'],
            'slug' => Str::slug($payload['name']),
            'title' => $payload['name'],
            'description' => $payload['description'],
            'code' => $payload['voucher'],
            'affiliate_url' => $payload['url'],
            'status' => 'NEW',
            'hash' => sha1(($payload['external_id'] ?? '') . '|' . ($payload['voucher'] ?? '')),
        ];
    }
}
