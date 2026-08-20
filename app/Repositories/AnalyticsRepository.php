<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\AnalyticsService;
use App\Services\CacheService;

final class AnalyticsRepository
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly OfferRepository $offers,
        private readonly StoreRepository $stores,
        private readonly CacheService $cache
    ) {
    }

    public function dashboard(): array
    {
        $series = $this->analytics->clickSeries();
        $offers = $this->offers->all();
        $stores = $this->stores->all();

        usort($offers, static fn (array $a, array $b): int => $b['clicks'] <=> $a['clicks']);
        usort($stores, static fn (array $a, array $b): int => ($b['click_count'] ?? 0) <=> ($a['click_count'] ?? 0));

        $clicks30d = array_sum(array_column($series, 'clicks'));
        $totalOffers = count($offers);

        // Leggi audit recente dal DB se disponibile, altrimenti file flat
        $db = app('db');
        $auditEntries = [];
        if ($db !== null) {
            try {
                $stmt = $db->prepare(
                    'SELECT al.action, al.entity_type, al.entity_id, al.created_at, u.username AS actor
                     FROM audit_logs al
                     LEFT JOIN users u ON u.id = al.user_id
                     ORDER BY al.created_at DESC
                     LIMIT 20'
                );
                $stmt->execute();
                $auditEntries = $stmt->fetchAll();
            } catch (\Throwable) {
                $auditEntries = $this->cache->readJsonLines('logs', 'audit.log', 20);
            }
        } else {
            $auditEntries = $this->cache->readJsonLines('logs', 'audit.log', 20);
        }

        return [
            'kpis' => [
                'offers' => $totalOffers,
                'stores' => count($stores),
                'clicks_30d' => $clicks30d,
                'conversion_rate' => $totalOffers > 0 ? round(($clicks30d / max($totalOffers, 1)) * 100, 1) . '%' : '0%',
            ],
            'series' => $series,
            'top_offers' => array_slice($offers, 0, 5),
            'top_stores' => array_slice($stores, 0, 5),
            'audit' => $auditEntries,
        ];
    }
}