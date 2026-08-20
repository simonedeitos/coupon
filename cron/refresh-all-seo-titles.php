<?php

declare(strict_types=1);

/**
 * Cron: refresh-all-seo-titles.php
 * Run on the 1st of each month: 0 0 1 * * php /var/www/couponami/cron/refresh-all-seo-titles.php
 *
 * Full monthly refresh of all SEO titles when the month changes.
 * Clears any stale month-specific SEO cache and regenerates everything.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Helpers\DateHelper;

$startedAt = date('c');
$count = 0;

try {
    $seo = app('seo');
    $cache = app('cache');

    $currentDate = DateHelper::getSeoDateString();

    // Refresh home
    $homeData = [
        'title' => $seo->generateHomeTitle(),
        'description' => $seo->generateHomeDescription(),
        'date' => $currentDate,
        'generated_at' => date('c'),
    ];
    $cache->write('seo', 'home.json', json_encode($homeData, JSON_UNESCAPED_UNICODE));
    $count++;

    // Refresh all categories
    $categories = app('categoryRepository')->all();
    foreach ($categories as $category) {
        $offers = app('offerRepository')->byCategory((int) $category['id']);
        $offerCount = count($offers);
        $data = [
            'title' => $seo->generateCategoryTitle($category, $offerCount),
            'description' => $seo->generateCategoryMeta($category, $offerCount),
            'keywords' => $seo->generateCategoryKeywords($category),
            'month_year' => $currentDate,
            'generated_at' => date('c'),
        ];
        $cache->write('seo', 'category_' . $category['slug'] . '.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        $count++;
    }

    // Refresh all stores
    $stores = app('storeRepository')->all();
    foreach ($stores as $store) {
        $offers = app('offerRepository')->byStore((int) $store['id']);
        $offerCount = count($offers);
        $data = [
            'title' => $seo->generateStoreTitle($store, $offerCount),
            'description' => $seo->generateStoreMeta($store, $offerCount),
            'keywords' => $seo->generateStoreKeywords($store),
            'month_year' => $currentDate,
            'generated_at' => date('c'),
        ];
        $cache->write('seo', 'store_' . $store['slug'] . '.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        $count++;
    }

    $status = 'SUCCESS';
    $message = "Monthly SEO title refresh complete: {$count} pages updated for {$currentDate}";
} catch (\Throwable $e) {
    $status = 'ERROR';
    $message = $e->getMessage();
}

app('cache')->appendJsonLine('logs', 'cron.log', [
    'job' => 'refresh-all-seo-titles',
    'status' => $status,
    'message' => $message,
    'count' => $count,
    'started_at' => $startedAt,
    'finished_at' => date('c'),
]);

echo "[{$status}] {$message}" . PHP_EOL;
