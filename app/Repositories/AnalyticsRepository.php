<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\AnalyticsService;
use App\Services\CacheService;
use DateTimeImmutable;
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

    public function dashboard(array $filters = []): array
    {
        $range = $this->resolveRange($filters);
        $series = [];
        $topOffers = [];
        $topStores = [];
        $recentClicks = [];
        $totalClicks = 0;
        $totalPageViews = 0;

        if ($this->db !== null) {
            try {
                $seriesStmt = $this->db->prepare(
                    "SELECT d.day_date AS date,
                            COALESCE(c.clicks, 0) AS clicks,
                            COALESCE(v.page_views, 0) AS page_views
                     FROM (
                        SELECT DATE(:start) + INTERVAL seq.day DAY AS day_date
                        FROM (
                            SELECT 0 AS day UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
                            SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
                            SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL
                            SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL
                            SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL
                            SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL
                            SELECT 30 UNION ALL SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34 UNION ALL
                            SELECT 35 UNION ALL SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39 UNION ALL
                            SELECT 40 UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44 UNION ALL
                            SELECT 45 UNION ALL SELECT 46 UNION ALL SELECT 47 UNION ALL SELECT 48 UNION ALL SELECT 49 UNION ALL
                            SELECT 50 UNION ALL SELECT 51 UNION ALL SELECT 52 UNION ALL SELECT 53 UNION ALL SELECT 54 UNION ALL
                            SELECT 55 UNION ALL SELECT 56 UNION ALL SELECT 57 UNION ALL SELECT 58 UNION ALL SELECT 59
                        ) seq
                        WHERE DATE(:start) + INTERVAL seq.day DAY <= DATE(:end)
                     ) d
                     LEFT JOIN (
                        SELECT DATE(created_at) AS day_date, COUNT(*) AS clicks
                        FROM clicks
                        WHERE created_at >= :start AND created_at <= :end
                        GROUP BY DATE(created_at)
                     ) c ON c.day_date = d.day_date
                     LEFT JOIN (
                        SELECT DATE(created_at) AS day_date, COUNT(*) AS page_views
                        FROM page_views
                        WHERE created_at >= :start AND created_at <= :end
                        GROUP BY DATE(created_at)
                     ) v ON v.day_date = d.day_date
                     ORDER BY d.day_date ASC"
                );
                $seriesStmt->execute([
                    'start' => $range['start'] . ' 00:00:00',
                    'end' => $range['end'] . ' 23:59:59',
                ]);
                $series = $seriesStmt->fetchAll();

                $totalStmt = $this->db->prepare(
                    'SELECT COUNT(*) FROM clicks WHERE created_at >= ? AND created_at <= ?'
                );
                $totalStmt->execute([$range['start'] . ' 00:00:00', $range['end'] . ' 23:59:59']);
                $totalClicks = (int) $totalStmt->fetchColumn();

                try {
                    $pvStmt = $this->db->prepare(
                        'SELECT COUNT(*) FROM page_views WHERE created_at >= ? AND created_at <= ?'
                    );
                    $pvStmt->execute([$range['start'] . ' 00:00:00', $range['end'] . ' 23:59:59']);
                    $totalPageViews = (int) $pvStmt->fetchColumn();
                } catch (\Throwable) {
                    $totalPageViews = 0;
                }

                $topOffersStmt = $this->db->prepare(
                    "SELECT o.id, o.title, o.status, s.name AS store_name, COUNT(c.id) AS clicks
                     FROM clicks c
                     INNER JOIN offers o ON o.id = c.offer_id
                     LEFT JOIN stores s ON s.id = o.store_id
                     WHERE c.created_at >= ? AND c.created_at <= ?
                     GROUP BY o.id
                     ORDER BY clicks DESC, o.click_count DESC
                     LIMIT 20"
                );
                $topOffersStmt->execute([$range['start'] . ' 00:00:00', $range['end'] . ' 23:59:59']);
                $topOffers = $topOffersStmt->fetchAll();

                $topStoresStmt = $this->db->prepare(
                    "SELECT s.id, s.name, s.website_url AS website, COUNT(c.id) AS clicks,
                            COUNT(DISTINCT o.id) AS offers_count
                     FROM stores s
                     LEFT JOIN offers o ON o.store_id = s.id AND o.status = 'ACTIVE'
                     LEFT JOIN clicks c ON c.store_id = s.id AND c.created_at >= ? AND c.created_at <= ?
                     WHERE s.is_active = 1
                     GROUP BY s.id
                     ORDER BY clicks DESC, s.click_count DESC
                     LIMIT 20"
                );
                $topStoresStmt->execute([$range['start'] . ' 00:00:00', $range['end'] . ' 23:59:59']);
                $topStores = $topStoresStmt->fetchAll();

                $clickDetailsStmt = $this->db->prepare(
                    "SELECT c.created_at, c.referer, c.session_id, o.title AS offer_title, s.name AS store_name
                     FROM clicks c
                     INNER JOIN offers o ON o.id = c.offer_id
                     LEFT JOIN stores s ON s.id = c.store_id
                     WHERE c.created_at >= ? AND c.created_at <= ?
                     ORDER BY c.created_at DESC
                     LIMIT 100"
                );
                $clickDetailsStmt->execute([$range['start'] . ' 00:00:00', $range['end'] . ' 23:59:59']);
                $recentClicks = $clickDetailsStmt->fetchAll();
            } catch (\Throwable $e) {
                error_log('AnalyticsRepository::dashboard DB failed: ' . $e->getMessage());
            }
        }

        if ($series === []) {
            $series = $this->analytics->clickSeries((int) $range['days']);
        }
        if ($topOffers === []) {
            $allOffers = $this->offers->all();
            usort($allOffers, static fn (array $a, array $b): int => $b['click_count'] <=> $a['click_count']);
            $topOffers = array_slice($allOffers, 0, 20);
        }
        if ($topStores === []) {
            $allStores = $this->stores->all();
            usort($allStores, static fn (array $a, array $b): int => ($b['click_count'] ?? 0) <=> ($a['click_count'] ?? 0));
            $topStores = array_slice($allStores, 0, 20);
        }

        $clicksInRange = array_sum(array_map(static fn (array $row): int => (int) ($row['clicks'] ?? 0), $series));
        $pageViewsInRange = array_sum(array_map(static fn (array $row): int => (int) ($row['page_views'] ?? 0), $series));

        return [
            'filters' => $range,
            'kpis' => [
                'offers' => $this->offers->count(),
                'stores' => $this->stores->count(),
                'clicks_30d' => $clicksInRange,
                'total_clicks' => $totalClicks,
                'total_page_views' => $totalPageViews > 0 ? $totalPageViews : $pageViewsInRange,
                'conversion_rate' => ($pageViewsInRange > 0 ? round(($clicksInRange / $pageViewsInRange) * 100, 2) : 0) . '%',
            ],
            'series' => $series,
            'top_offers' => $topOffers,
            'top_stores' => $topStores,
            'recent_clicks' => $recentClicks,
            'audit' => $this->cache->readJsonLines('logs', 'audit.log', 20),
        ];
    }

    private function resolveRange(array $filters): array
    {
        $preset = strtolower((string) ($filters['preset'] ?? '30d'));
        $today = new DateTimeImmutable('today');
        $start = $today;
        $end = $today;

        if ($preset === 'today') {
            $start = $today;
        } elseif ($preset === 'yesterday') {
            $start = $today->modify('-1 day');
            $end = $start;
        } elseif ($preset === '7d') {
            $start = $today->modify('-6 day');
        } elseif ($preset === '30d') {
            $start = $today->modify('-29 day');
        } elseif ($preset === 'custom') {
            $startInput = (string) ($filters['start_date'] ?? '');
            $endInput = (string) ($filters['end_date'] ?? '');
            $startCustom = DateTimeImmutable::createFromFormat('Y-m-d', $startInput) ?: $today->modify('-29 day');
            $endCustom = DateTimeImmutable::createFromFormat('Y-m-d', $endInput) ?: $today;
            $start = $startCustom <= $endCustom ? $startCustom : $endCustom;
            $end = $endCustom >= $startCustom ? $endCustom : $startCustom;
            if ($end->diff($start)->days > 59) {
                $end = $start->modify('+59 day');
            }
        } else {
            $preset = '30d';
            $start = $today->modify('-29 day');
        }

        $days = (int) max(1, $end->diff($start)->days + 1);
        return [
            'preset' => $preset,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'days' => $days,
        ];
    }
}
