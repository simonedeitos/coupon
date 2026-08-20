<?php

declare(strict_types=1);

namespace App\Services;

final class SeoService
{
    public function __construct(private readonly array $seoConfig, private readonly array $appConfig)
    {
    }

    public function meta(array $data = []): array
    {
        $title = $data['title'] ?? $this->seoConfig['default_title'];
        $description = $data['description'] ?? $this->seoConfig['default_description'];
        $canonical = rtrim($this->appConfig['base_url'], '/') . ($data['path'] ?? '/');
        $type = $data['type'] ?? $this->seoConfig['default_type'];
        $breadcrumbs = $data['breadcrumbs'] ?? [];
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => $type === 'article' ? 'Article' : 'WebSite',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'publisher' => ['@type' => 'Organization', 'name' => $this->seoConfig['site_name']],
        ];
        if ($breadcrumbs) {
            $jsonLd['breadcrumb'] = array_map(static fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['label'],
                'item' => rtrim((string) config('app.base_url'), '/') . $item['url'],
            ], $breadcrumbs, array_keys($breadcrumbs));
        }
        return compact('title', 'description', 'canonical', 'type', 'jsonLd', 'breadcrumbs');
    }
}
