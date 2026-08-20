<?php

declare(strict_types=1);

namespace App\Integrations\TradeDoubler;

use App\Repositories\OfferRepository;
use App\Services\CacheService;

final class TradeDoublerImporter
{
    public function __construct(private readonly TradeDoublerClient $client, private readonly TradeDoublerMapper $mapper, private readonly OfferRepository $offers, private readonly CacheService $cache)
    {
    }

    public function import(): array
    {
        $existing = $this->offers->all();
        $hashes = array_column($existing, 'hash');
        $result = ['NEW' => 0, 'UPDATED' => 0, 'DUPLICATE' => 0, 'ERROR' => 0];
        foreach ($this->client->fetchOffers() as $item) {
            $mapped = $this->mapper->map($item);
            if (in_array($mapped['hash'], $hashes, true)) {
                $result['DUPLICATE']++;
                continue;
            }
            $this->cache->appendJsonLine('logs', 'imports.log', ['source' => 'TradeDoubler', 'status' => 'NEW', 'external_id' => $mapped['external_id'], 'created_at' => date('c')]);
            $result['NEW']++;
        }
        return $result;
    }
}
