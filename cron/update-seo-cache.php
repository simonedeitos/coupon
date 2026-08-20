<?php

declare(strict_types=1);

/**
 * Cron: update-seo-cache.php
 * Run daily at midnight: 0 0 * * * php /var/www/couponami/cron/update-seo-cache.php
 *
 * Regenerates cached SEO titles and meta descriptions for the home page
 * and all active categories/stores/offers.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Helpers\DateHelper;

$startedAt = date('c');
$count = 0;
$errors = [];

try {
    $seo = app('seo');
    $cache = app('cache');

    // Cache home SEO data
    $homeData = [
        'title' => $seo->generateHomeTitle(),
        'description' => $seo->generateHomeDescription(),
        'date' => DateHelper::getSeoDateString(),
        'generated_at' => date('c'),
    ];
    $cache->write('seo', 'home.json', json_encode($homeData, JSON_UNESCAPED_UNICODE));
    $count++;

    // Cache category SEO data
    $categories = app('categoryRepository')->all();
    foreach ($categories as $category) {
        $offers = app('offerRepository')->byCategory((int) $category['id']);
        $offerCount = count($offers);
        $data = [
            'title' => $seo->generateCategoryTitle($category, $offerCount),
            'description' => $seo->generateCategoryMeta($category, $offerCount),
            'keywords' => $seo->generateCategoryKeywords($category),
            'generated_at' => date('c'),
        ];
        $cache->write('seo', 'category_' . $category['slug'] . '.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        $count++;
    }

    // Cache store SEO data
    $stores = app('storeRepository')->all();
    foreach ($stores as $store) {
        $offers = app('offerRepository')->byStore((int) $store['id']);
        $offerCount = count($offers);
        $data = [
            'title' => $seo->generateStoreTitle($store, $offerCount),
            'description' => $seo->generateStoreMeta($store, $offerCount),
            'keywords' => $seo->generateStoreKeywords($store),
            'generated_at' => date('c'),
        ];
        $cache->write('seo', 'store_' . $store['slug'] . '.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        $count++;
    }

    $status = 'SUCCESS';
    $message = "SEO cache updated: {$count} entries regenerated for " . DateHelper::getSeoDateString();
} catch (\Throwable $e) {
    $status = 'ERROR';
    $message = $e->getMessage();
    $errors[] = $e->getMessage();
}

app('cache')->appendJsonLine('logs', 'cron.log', [
    'job' => 'update-seo-cache',
    'status' => $status,
    'message' => $message,
    'count' => $count,
    'started_at' => $startedAt,
    'finished_at' => date('c'),
]);

echo "[{$status}] {$message}" . PHP_EOL;
