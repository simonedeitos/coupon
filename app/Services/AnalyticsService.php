<?php

declare(strict_types=1);

namespace App\Services;

final class AnalyticsService
{
    public function __construct(private readonly CacheService $cache)
    {
    }

    public function logClick(array $offer, string $storeName): void
    {
        $date = date('Y-m-d');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $anonymizedIp = preg_replace('/\d+$/', '0', $ip) ?: '0.0.0.0';
        $record = [
            'offer_id' => $offer['id'],
            'offer_title' => $offer['title'],
            'store_name' => $storeName,
            'referer' => substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 190),
            'device' => str_contains(strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 'mobile') ? 'mobile' : 'desktop',
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
        $entries = $this->cache->readJsonLines('logs', 'clicks.log', 1000);
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
