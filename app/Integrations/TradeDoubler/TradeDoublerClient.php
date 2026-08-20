<?php

declare(strict_types=1);

namespace App\Integrations\TradeDoubler;

final class TradeDoublerClient implements AffiliateNetworkInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function fetchOffers(): array
    {
        return [
            ['external_id' => 'td-2001', 'name' => 'Flash Sale FashionHub', 'description' => '20% extra', 'voucher' => 'FLASH20', 'url' => 'https://example.com/fashionhub/flash'],
            ['external_id' => 'td-2002', 'name' => 'TechWorld Monitor Days', 'description' => 'Up to 35%', 'voucher' => '', 'url' => 'https://example.com/techworld/monitor-days'],
        ];
    }
}
