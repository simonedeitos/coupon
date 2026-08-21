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
        $ip = $this->anonymizeIp($_SERVER['REMOTE_ADDR'] ?? null);
        $sessionId = session_id() !== '' ? session_id() : null;
        $device = str_contains(strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 'mobile') ? 'mobile' : 'desktop';
        $referer = substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255);
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        // Scrivi il click nel DB (fonte di verità)
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare(
                    'INSERT INTO clicks (offer_id, store_id, session_id, referer, ip_address, user_agent, device_type, created_at)
                     VALUES (?, ?, ?, ?, INET6_ATON(?), ?, ?, NOW())'
                );
                $stmt->execute([
                    (int) $offer['id'],
                    (int) ($offer['store_id'] ?? 0) ?: null,
                    $sessionId,
                    $referer,
                    $ip,
                    $userAgent,
                    $device,
                ]);

                // Incrementa contatori aggregati
                $this->db->prepare('UPDATE offers SET click_count = click_count + 1 WHERE id = ?')
                    ->execute([(int) $offer['id']]);
                if (! empty($offer['store_id'])) {
                    $this->db->prepare('UPDATE stores SET click_count = click_count + 1 WHERE id = ?')
                        ->execute([(int) $offer['store_id']]);
                }

                // Aggiorna tabella giornaliera
                $this->db->prepare(
                    'INSERT INTO click_analytics_daily (date, offer_id, store_id, click_count)
                     VALUES (CURDATE(), ?, ?, 1)
                     ON DUPLICATE KEY UPDATE click_count = click_count + 1'
                )->execute([
                    (int) $offer['id'],
                    (int) ($offer['store_id'] ?? 0) ?: null,
                ]);
            } catch (\Throwable $e) {
                error_log('AnalyticsService::logClick DB failed: ' . $e->getMessage());
            }
        }

        // Mantieni anche il log su file come backup
        $date = date('Y-m-d');
        $record = [
            'offer_id' => $offer['id'],
            'offer_title' => $offer['title'],
            'store_name' => $storeName,
            'referer' => $referer,
            'device' => $device,
            'ip' => $ip,
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

    public function logPageView(string $path): void
    {
        if ($this->db === null) {
            return;
        }
        try {
            $ip = $this->anonymizeIp($_SERVER['REMOTE_ADDR'] ?? null);
            $sessionId = session_id() !== '' ? session_id() : null;
            $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $referer = substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255);

            $this->db->prepare(
                'INSERT INTO page_views (path, referer, session_id, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, INET6_ATON(?), ?, NOW())'
            )->execute([$path, $referer ?: null, $sessionId, $ip, $ua ?: null]);
        } catch (\Throwable $e) {
            error_log('AnalyticsService::logPageView failed: ' . $e->getMessage());
        }
    }

    private function anonymizeIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed === false) {
                return null;
            }
            $bytes = unpack('C*', $packed);
            for ($i = 9; $i <= 16; $i++) {
                if (isset($bytes[$i])) {
                    $bytes[$i] = 0;
                }
            }
            return inet_ntop(pack('C*', ...array_values($bytes))) ?: null;
        }
        return null;
    }

    public function clickSeries(int $days = 30): array
    {
        // Prima prova dal DB se disponibile
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare(
                    'SELECT DATE(created_at) AS date, COUNT(*) AS clicks
                     FROM clicks
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                     GROUP BY DATE(created_at)
                     ORDER BY date ASC'
                );
                $stmt->execute([$days]);
                $dbRows = array_column($stmt->fetchAll(), 'clicks', 'date');
                $series = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime('-' . $i . ' days'));
                    $series[] = ['date' => $date, 'clicks' => (int) ($dbRows[$date] ?? 0)];
                }
                return $series;
            } catch (\Throwable $e) {
                error_log('AnalyticsService::clickSeries DB failed: ' . $e->getMessage());
            }
        }

        // Fallback su file
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
