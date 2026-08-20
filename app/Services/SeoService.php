<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\DateHelper;

final class SeoService
{
    public function __construct(private readonly array $seoConfig, private readonly array $appConfig)
    {
    }

    public function meta(array $data = []): array
    {
        $title = $data['title'] ?? $this->seoConfig['default_title'];
        $description = $data['description'] ?? $this->seoConfig['default_description'];
        $keywords = $data['keywords'] ?? '';
        $canonical = rtrim($this->appConfig['base_url'], '/') . ($data['path'] ?? '/');
        $type = $data['type'] ?? $this->seoConfig['default_type'];
        $breadcrumbs = $data['breadcrumbs'] ?? [];
        $jsonLd = $data['jsonLd'] ?? $this->buildDefaultJsonLd($title, $description, $canonical, $type, $breadcrumbs);
        return compact('title', 'description', 'keywords', 'canonical', 'type', 'jsonLd', 'breadcrumbs');
    }

    public function generateHomeTitle(): string
    {
        $date = DateHelper::getSeoDateString();
        return "Couponami — Codici sconto e coupon {$date}";
    }

    public function generateHomeDescription(): string
    {
        $date = DateHelper::getSeoDateString();
        return "Trova i migliori codici sconto e coupon verificati di {$date}. Risparmia su moda, tecnologia, viaggi e tanto altro con Couponami.";
    }

    public function generateCategoryTitle(array $category, int $count = 0): string
    {
        $date = DateHelper::getSeoDateString();
        $name = $category['name'] ?? '';
        $suffix = $count > 0 ? " — {$count} offerte" : '';
        return "Codici sconto {$name} {$date}{$suffix}";
    }

    public function generateCategoryMeta(array $category, int $count = 0): string
    {
        $date = DateHelper::getSeoDateString();
        $name = $category['name'] ?? '';
        $countPart = $count > 0 ? "{$count} " : '';
        return "Scopri i migliori coupon e codici sconto {$name} di {$date}. {$countPart}offerte verificate e aggiornate ogni giorno su Couponami.";
    }

    public function generateCategoryKeywords(array $category): string
    {
        $date = DateHelper::getSeoDateString();
        $name = $category['name'] ?? '';
        return "coupon {$name}, codici sconto {$name}, offerte {$name}, {$name} {$date}";
    }

    public function generateStoreTitle(array $store, int $count = 0): string
    {
        $date = DateHelper::getSeoDateString();
        $name = $store['name'] ?? '';
        $suffix = $count > 0 ? " — {$count} codici sconto verificati" : '';
        return "Coupon {$name} {$date}{$suffix}";
    }

    public function generateStoreMeta(array $store, int $count = 0): string
    {
        $date = DateHelper::getSeoDateString();
        $name = $store['name'] ?? '';
        $countPart = $count > 0 ? "{$count} " : '';
        return "Tutti i coupon e codici sconto {$name} aggiornati a {$date}. {$countPart}offerte verificate e pronte all'uso. Risparmia con Couponami.";
    }

    public function generateStoreKeywords(array $store): string
    {
        $date = DateHelper::getSeoDateString();
        $name = $store['name'] ?? '';
        return "coupon {$name}, codici sconto {$name}, offerte {$name}, {$name} {$date}";
    }

    public function generateStoreListTitle(): string
    {
        $date = DateHelper::getSeoDateString();
        return "Negozi con coupon {$date}";
    }

    public function generateOfferTitle(array $offer): string
    {
        $date = DateHelper::getSeoDateString();
        $title = $offer['title'] ?? '';
        $code = ! empty($offer['coupon_code']) ? " — Codice: {$offer['coupon_code']}" : '';
        return "{$title} {$date}{$code}";
    }

    public function generateOfferMeta(array $offer): string
    {
        $date = DateHelper::getSeoDateString();
        $title = $offer['title'] ?? '';
        $description = $offer['description'] ?? '';
        if ($description !== '') {
            return mb_substr($description, 0, 140) . " Valido a {$date} su Couponami.";
        }
        return "Approfitta dell'offerta: {$title}. Valida a {$date} e verificata da Couponami.";
    }

    public function generateOfferListTitle(): string
    {
        $date = DateHelper::getSeoDateString();
        return "Coupon e offerte {$date}";
    }

    public function generateSearchTitle(string $query, int $results): string
    {
        $date = DateHelper::getSeoDateString();
        $q = $query !== '' ? "'{$query}' " : '';
        return "Coupon {$q}{$date} — {$results} risultati";
    }

    public function generateSearchMeta(string $query, int $results): string
    {
        $date = DateHelper::getSeoDateString();
        $q = $query !== '' ? " per '{$query}'" : '';
        return "Trovati {$results} coupon{$q} aggiornati a {$date}. Codici sconto verificati e pronti all'uso su Couponami.";
    }

    public function generateCategoryListTitle(): string
    {
        $date = DateHelper::getSeoDateString();
        return "Categorie di coupon {$date}";
    }

    private function buildDefaultJsonLd(string $title, string $description, string $canonical, string $type, array $breadcrumbs): array
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => $type === 'article' ? 'Article' : 'WebSite',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'publisher' => ['@type' => 'Organization', 'name' => $this->seoConfig['site_name']],
        ];
        if ($breadcrumbs) {
            $jsonLd['breadcrumb'] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(static fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => rtrim((string) config('app.base_url'), '/') . $item['url'],
                ], $breadcrumbs, array_keys($breadcrumbs)),
            ];
        }
        return $jsonLd;
    }
}
