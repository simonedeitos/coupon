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
        $meta = app('seo')->meta(['title' => 'Ricerca coupon', 'description' => 'Ricerca per negozi, categorie e offerte Couponami.', 'path' => '/cerca', 'breadcrumbs' => [['label' => 'Ricerca', 'url' => '/cerca']]]);
        return response_view('frontend/search/index', compact('query', 'offers', 'stores', 'categories', 'meta'));
    }
}
