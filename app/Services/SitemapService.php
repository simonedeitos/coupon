<?php

declare(strict_types=1);

namespace App\Services;

final class SitemapService
{
    public function __construct(private readonly array $appConfig)
    {
    }

    public function generate(array $urls): string
    {
        $base = rtrim($this->appConfig['base_url'], '/');
        $items = [];
        foreach ($urls as $url) {
            $items[] = sprintf(
                '<url><loc>%s%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%s</priority></url>',
                $base,
                e($url['path']),
                e($url['lastmod'] ?? date('c')),
                e($url['changefreq'] ?? 'daily'),
                e((string) ($url['priority'] ?? '0.7'))
            );
        }
        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . implode('', $items) . '</urlset>';
    }
}
