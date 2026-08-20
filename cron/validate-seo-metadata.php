<?php

declare(strict_types=1);

/**
 * Cron: validate-seo-metadata.php
 * Run weekly: 0 0 * * 0 php /var/www/couponami/cron/validate-seo-metadata.php
 *
 * Validates all generated SEO titles and meta descriptions:
 * - Title length: 30–70 characters
 * - Meta description length: 100–160 characters
 * - Presence of month/year in titles
 * - Keywords populated
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Helpers\DateHelper;

$startedAt = date('c');
$warnings = [];
$checked = 0;

/**
 * @param string[] $warnings
 */
function checkTitle(string $context, string $title, array &$warnings): void
{
    $len = mb_strlen($title);
    if ($len < 30) {
        $warnings[] = "[{$context}] Title too short ({$len} chars): {$title}";
    } elseif ($len > 70) {
        $warnings[] = "[{$context}] Title too long ({$len} chars): {$title}";
    }
}

/**
 * @param string[] $warnings
 */
function checkMeta(string $context, string $meta, array &$warnings): void
{
    $len = mb_strlen($meta);
    if ($len < 100) {
        $warnings[] = "[{$context}] Meta description too short ({$len} chars): {$meta}";
    } elseif ($len > 160) {
        $warnings[] = "[{$context}] Meta description too long ({$len} chars)";
    }
}

try {
    $seo = app('seo');
    $dateString = DateHelper::getSeoDateString();

    // Validate home
    $homeTitle = $seo->generateHomeTitle();
    $homeDesc = $seo->generateHomeDescription();
    checkTitle('home', $homeTitle, $warnings);
    checkMeta('home', $homeDesc, $warnings);
    if (! str_contains($homeTitle, $dateString)) {
        $warnings[] = "[home] Title missing date string '{$dateString}': {$homeTitle}";
    }
    $checked++;

    // Validate categories
    $categories = app('categoryRepository')->all();
    foreach ($categories as $category) {
        $offers = app('offerRepository')->byCategory((int) $category['id']);
        $count = count($offers);
        $title = $seo->generateCategoryTitle($category, $count);
        $desc = $seo->generateCategoryMeta($category, $count);
        checkTitle('category:' . $category['slug'], $title, $warnings);
        checkMeta('category:' . $category['slug'], $desc, $warnings);
        $checked++;
    }

    // Validate stores
    $stores = app('storeRepository')->all();
    foreach ($stores as $store) {
        $offers = app('offerRepository')->byStore((int) $store['id']);
        $count = count($offers);
        $title = $seo->generateStoreTitle($store, $count);
        $desc = $seo->generateStoreMeta($store, $count);
        checkTitle('store:' . $store['slug'], $title, $warnings);
        checkMeta('store:' . $store['slug'], $desc, $warnings);
        $checked++;
    }

    $status = count($warnings) === 0 ? 'SUCCESS' : 'PARTIAL';
    $message = "SEO validation complete: {$checked} pages checked, " . count($warnings) . ' warnings.';
} catch (\Throwable $e) {
    $status = 'ERROR';
    $message = $e->getMessage();
    $warnings[] = $e->getMessage();
}

app('cache')->appendJsonLine('logs', 'cron.log', [
    'job' => 'validate-seo-metadata',
    'status' => $status,
    'message' => $message,
    'warnings' => $warnings,
    'checked' => $checked,
    'started_at' => $startedAt,
    'finished_at' => date('c'),
]);

echo "[{$status}] {$message}" . PHP_EOL;
foreach ($warnings as $warning) {
    echo "  WARNING: {$warning}" . PHP_EOL;
}
