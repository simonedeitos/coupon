<?php

declare(strict_types=1);

namespace App\Controllers;

final class SearchController
{
    public function index(): array
    {
        $query = trim((string) request_input('q', ''));
        $offers = $query !== '' ? app('offerRepository')->all(['search' => $query]) : [];
        $stores = array_values(array_filter(app('storeRepository')->all(), static fn (array $store): bool => $query !== '' && str_contains(mb_strtolower($store['name'] . ' ' . $store['description']), mb_strtolower($query))));
        $categories = array_values(array_filter(app('categoryRepository')->all(), static fn (array $category): bool => $query !== '' && str_contains(mb_strtolower($category['name'] . ' ' . $category['description']), mb_strtolower($query))));
        $seo = app('seo');
        $results = count($offers) + count($stores) + count($categories);
        $meta = $seo->meta([
            'title' => $seo->generateSearchTitle($query, $results),
            'description' => $seo->generateSearchMeta($query, $results),
            'keywords' => $query !== '' ? "coupon {$query}, codici sconto {$query}, offerte " . \App\Helpers\DateHelper::getSeoDateString() : '',
            'path' => '/cerca',
            'breadcrumbs' => [['label' => 'Ricerca', 'url' => '/cerca']],
        ]);
        return response_view('frontend/search/index', compact('query', 'offers', 'stores', 'categories', 'meta'));
    }
}
