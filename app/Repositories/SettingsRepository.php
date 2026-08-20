<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\CacheService;

final class SettingsRepository
{
    private array $defaults = [
        'system' => ['site_name' => 'Couponami', 'support_email' => 'support@couponami.local', 'default_currency' => 'EUR', 'sitemap_max_urls' => '50000'],
        'feature_flags' => ['homepage_featured_carousel' => true, 'trade_doubler_sync' => true, 'csv_export' => true, 'verification_panel' => true],
    ];

    public function __construct(private readonly CacheService $cache)
    {
    }

    public function section(string $section): array
    {
        return $this->cache->collection('settings_' . $section, $this->defaults[$section] ?? []);
    }

    public function saveSection(string $section, array $payload): array
    {
        $existing = $this->section($section);
        $sanitized = [];
        foreach ($payload as $key => $value) {
            $sanitized[$key] = is_string($value) ? trim($value) : (bool) $value;
        }
        $merged = [...$existing, ...$sanitized];
        $this->cache->putCollection('settings_' . $section, $merged);
        return $merged;
    }
}
