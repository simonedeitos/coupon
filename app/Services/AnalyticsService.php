<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AnalyticsService
{
    public function __construct(
        private readonly CacheService $cache,
        private readonly ?PDO $db = null,
    ) {
    }

    public function logClick(array $offer, string $storeName): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $anonymizedIp = preg_replace('/\d+$/', '0', $ip) ?: '0.0.0.0';
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $referer = substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255);
        $device = str_contains(strtolower($userAgent), 'mobile') ? 'mobile' : 'desktop';
        $offerId = (int) $offer['id'];
        $storeId = (int) ($offer['store_id'] ?? 0);

        // Scrivi sul DB (tabella clicks) e aggiorna i contatori aggregati
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare(
                    'INSERT INTO clicks (offer_id, store_id, referer, device_type, anonymized_ip, user_agent_hash)
                     VALUES (?, ?, ?, ?, INET6_ATON(?), ?)'
                );
                $stmt->execute([
                    $offerId,
                    $storeId ?: null,
                    $referer ?: null,
                    $device,
                    $anonymizedIp,
                    hash('sha256', $userAgent),
                ]);

                // Incrementa click_count aggregato sull'offerta
                $this->db->prepare('UPDATE offers SET click_count = click_count + 1 WHERE id = ?')
                    ->execute([$offerId]);

                // Incrementa click_count aggregato sullo store
                if ($storeId > 0) {
                    $this->db->prepare('UPDATE stores SET click_count = click_count + 1 WHERE id = ?')
                        ->execute([$storeId]);
                }

                // Aggiorna il riepilogo giornaliero (tabella click_analytics_daily)
                $this->db->prepare(
                    'INSERT INTO click_analytics_daily (date, offer_id, store_id, click_count)
                     VALUES (CURDATE(), ?, ?, 1)
                     ON DUPLICATE KEY UPDATE click_count = click_count + 1'
                )->execute([$offerId, $storeId ?: null]);
            } catch (\Throwable $e) {
                error_log('AnalyticsService::logClick DB failed: ' . $e->getMessage());
            }
        }

        // Mantieni anche il log flat per retrocompatibilità / export CSV
        $date = date('Y-m-d');
        $record = [
            'offer_id' => $offerId,
            'offer_title' => $offer['title'],
            'store_name' => $storeName,
            'referer' => $referer,
            'device' => $device,
            'ip' => $anonymizedIp,
            'created_at' => date('c'),
        ];
        $this->cache->appendJsonLine('logs', 'clicks.log', $record);

        $daily = $this->cache->collection('click_daily', []);
        $daily[$date]['date'] = $date;
        $daily[$date]['clicks'] = ($daily[$date]['clicks'] ?? 0) + 1;
        $daily[$date]['offers'][$offer['title']] = ($daily[$date]['offers'][$offer['title']] ?? 0) + 1;
        $daily[$date]['stores'][$storeName] = ($daily[$date]['stores'][$storeName] ?? 0) + 1;
        $this->cache->putCollection('click_daily', $daily);
    }

    public function clickSeries(int $days = 30): array
    {
        // Preferisci i dati DB se disponibili
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare(
                    "SELECT DATE(created_at) AS date, COUNT(*) AS clicks
                     FROM clicks
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                     GROUP BY DATE(created_at)
                     ORDER BY date ASC"
                );
                $stmt->execute([$days]);
                $dbRows = [];
                foreach ($stmt->fetchAll() as $row) {
                    $dbRows[$row['date']] = (int) $row['clicks'];
                }
                $series = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime('-' . $i . ' days'));
                    $series[] = ['date' => $date, 'clicks' => $dbRows[$date] ?? 0];
                }
                return $series;
            } catch (\Throwable $e) {
                error_log('AnalyticsService::clickSeries DB failed: ' . $e->getMessage());
            }
        }

        // Fallback su cache file
        $daily = $this->cache->collection('click_daily', []);
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $series[] = ['date' => $date, 'clicks' => (int) ($daily[$date]['clicks'] ?? 0)];
        }
        return $series;
    }

    public function exportCsv(array $series): string
    {
        $lines = ["date,clicks"];
        foreach ($series as $item) {
            $lines[] = $item['date'] . ',' . $item['clicks'];
        }
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    public function anonymizeOldClicks(int $days = 90): int
    {
        $entries = $this->cache->readJsonLines('logs', 'clicks.log', null);
        $updated = 0;
        foreach ($entries as &$entry) {
            if (strtotime($entry['created_at'] ?? 'now') < strtotime('-' . $days . ' days')) {
                $entry['ip'] = '0.0.0.0';
                $updated++;
            }
        }
        if ($updated > 0) {
            $content = implode(PHP_EOL, array_map(static fn (array $entry): string => json_encode($entry, JSON_UNESCAPED_UNICODE), $entries)) . PHP_EOL;
            $this->cache->writeFile('logs', 'clicks.log', $content);
        }
        return $updated;
    }
}
