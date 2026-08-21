<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\AnalyticsService;
use App\Services\CacheService;
use PDO;

final class AnalyticsRepository
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly OfferRepository $offers,
        private readonly StoreRepository $stores,
        private readonly CacheService $cache,
        private readonly ?PDO $db = null,
    ) {
    }

    public function dashboard(): array
    {
        $series = $this->analytics->clickSeries();
        $clicks30d = array_sum(array_column($series, 'clicks'));

        $topOffers = [];
        $topStores = [];
        $totalClicks = 0;
        $totalPageViews = 0;
        $auditItems = [];

        if ($this->db !== null) {
            try {
                $stmt = $this->db->query(
                    "SELECT o.id, o.title, o.click_count, s.name AS store_name
                     FROM offers o
                     LEFT JOIN stores s ON s.id = o.store_id
                     WHERE o.status = 'ACTIVE'
                     ORDER BY o.click_count DESC
                     LIMIT 5"
                );
                $topOffers = $stmt->fetchAll();

                $stmt = $this->db->query(
                    "SELECT id, name, click_count FROM stores WHERE is_active = 1
                     ORDER BY click_count DESC LIMIT 5"
                );
                $topStores = $stmt->fetchAll();

                $totalClicks = (int) $this->db->query('SELECT COUNT(*) FROM clicks')->fetchColumn();

                try {
                    $totalPageViews = (int) $this->db->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
                } catch (\Throwable) {
                    $totalPageViews = 0;
                }

                $stmt = $this->db->query(
                    "SELECT al.action, al.entity_type, al.entity_id, al.payload, al.created_at,
                            u.username AS actor
                     FROM audit_logs al
                     LEFT JOIN users u ON u.id = al.user_id
                     ORDER BY al.created_at DESC
                     LIMIT 50"
                );
                foreach ($stmt->fetchAll() as $row) {
                    $payload = [];
                    if ($row['payload'] !== null) {
                        $payload = json_decode($row['payload'], true) ?? [];
                    }
                    $auditItems[] = [
                        'action' => $row['action'],
                        'actor' => $row['actor'] ?? 'sistema',
                        'target' => trim(($row['entity_type'] ?? '') . ':' . ($row['entity_id'] ?? ''), ':'),
                        'created_at' => $row['created_at'],
                        'payload' => $payload,
                    ];
                }
            } catch (\Throwable $e) {
                error_log('AnalyticsRepository::dashboard DB failed: ' . $e->getMessage());
            }
        }

        if (empty($topOffers)) {
            $allOffers = $this->offers->all();
            usort($allOffers, static fn (array $a, array $b): int => $b['click_count'] <=> $a['click_count']);
            $topOffers = array_slice($allOffers, 0, 5);
        }
        if (empty($topStores)) {
            $allStores = $this->stores->all();
            usort($allStores, static fn (array $a, array $b): int => ($b['click_count'] ?? 0) <=> ($a['click_count'] ?? 0));
            $topStores = array_slice($allStores, 0, 5);
        }
        if (empty($auditItems)) {
            $auditItems = $this->cache->readJsonLines('logs', 'audit.log', 20);
        }

        return [
            'kpis' => [
                'offers' => $this->offers->count(),
                'stores' => $this->stores->count(),
                'clicks_30d' => $clicks30d,
                'total_clicks' => $totalClicks,
                'total_page_views' => $totalPageViews,
                'conversion_rate' => $this->offers->count() > 0 ? round(($clicks30d / max($this->offers->count(), 1)) * 100, 1) . '%' : '0%',
            ],
            'series' => $series,
            'top_offers' => $topOffers,
            'top_stores' => $topStores,
            'audit' => $auditItems,
        ];
    }
}
