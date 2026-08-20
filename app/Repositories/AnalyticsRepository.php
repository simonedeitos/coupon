<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\AnalyticsService;
use App\Services\CacheService;

final class AnalyticsRepository
{
    public function __construct(private readonly AnalyticsService $analytics, private readonly OfferRepository $offers, private readonly StoreRepository $stores, private readonly CacheService $cache)
    {
    }

    public function dashboard(): array
    {
        $series = $this->analytics->clickSeries();
        $offers = $this->offers->all();
        $stores = $this->stores->all();
        usort($offers, static fn (array $a, array $b): int => $b['clicks'] <=> $a['clicks']);
        usort($stores, static fn (array $a, array $b): int => $b['offers_count'] <=> $a['offers_count']);
        return [
            'kpis' => ['offers' => count($offers), 'stores' => count($stores), 'clicks_30d' => array_sum(array_column($series, 'clicks')), 'conversion_rate' => '4.7%'],
            'series' => $series,
            'top_offers' => array_slice($offers, 0, 5),
            'top_stores' => array_slice($stores, 0, 5),
            'audit' => $this->cache->readJsonLines('logs', 'audit.log', 20),
        ];
    }
}
