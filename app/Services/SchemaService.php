<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\DateHelper;

final class SchemaService
{
    public function __construct(private readonly array $appConfig, private readonly array $seoConfig)
    {
    }

    /**
     * Generate a BreadcrumbList schema.org JSON-LD array.
     *
     * @param array $items Each item: ['label' => string, 'url' => string]
     */
    public function generateBreadcrumb(array $items): array
    {
        $baseUrl = rtrim($this->appConfig['base_url'], '/');
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $baseUrl . $item['url'],
                ],
                $items,
                array_keys($items)
            ),
        ];
    }

    /**
     * Generate category breadcrumb schema.
     */
    public function generateCategoryBreadcrumb(array $category): array
    {
        return $this->generateBreadcrumb([
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Categorie', 'url' => '/categorie'],
            ['label' => ($category['name'] ?? '') . ' ' . DateHelper::getSeoDateString(), 'url' => '/categoria/' . ($category['slug'] ?? '')],
        ]);
    }

    /**
     * Generate store breadcrumb schema.
     */
    public function generateStoreBreadcrumb(array $store): array
    {
        return $this->generateBreadcrumb([
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Negozi', 'url' => '/negozi'],
            ['label' => $store['name'] ?? '', 'url' => '/negozio/' . ($store['slug'] ?? '')],
        ]);
    }

    /**
     * Generate an Offer schema.org JSON-LD array for a coupon.
     */
    public function generateOfferSchema(array $offer, array $store = []): array
    {
        $baseUrl = rtrim($this->appConfig['base_url'], '/');
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Offer',
            'name' => $offer['title'] ?? '',
            'description' => $offer['description'] ?? '',
            'url' => $baseUrl . '/coupon/' . ($offer['slug'] ?? ''),
            'availability' => 'https://schema.org/InStock',
            'priceCurrency' => 'EUR',
        ];

        if (! empty($offer['coupon_code'])) {
            $schema['priceSpecification'] = [
                '@type' => 'PriceSpecification',
                'eligibleTransactionVolume' => [
                    '@type' => 'PriceSpecification',
                    'priceCurrency' => 'EUR',
                ],
            ];
            $schema['serialNumber'] = $offer['coupon_code'];
        }

        if (! empty($offer['expires_at'])) {
            try {
                $schema['validThrough'] = (new \DateTimeImmutable($offer['expires_at']))->format('Y-m-d');
            } catch (\Exception) {
                // Skip invalid date
            }
        }

        if (! empty($store['name'])) {
            $schema['seller'] = [
                '@type' => 'Organization',
                'name' => $store['name'],
                'url' => $store['website_url'] ?? '',
            ];
        }

        return $schema;
    }

    /**
     * Generate Organization schema.org JSON-LD for the site.
     */
    public function generateOrganizationSchema(): array
    {
        $baseUrl = rtrim($this->appConfig['base_url'], '/');
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->seoConfig['site_name'] ?? 'Couponami',
            'url' => $baseUrl,
            'logo' => $baseUrl . ($this->seoConfig['default_image'] ?? '/assets/images/social-cover.png'),
            'sameAs' => [],
        ];
    }

    /**
     * Generate WebSite schema with SearchAction.
     */
    public function generateWebSiteSchema(): array
    {
        $baseUrl = rtrim($this->appConfig['base_url'], '/');
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->seoConfig['site_name'] ?? 'Couponami',
            'url' => $baseUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $baseUrl . '/cerca?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
