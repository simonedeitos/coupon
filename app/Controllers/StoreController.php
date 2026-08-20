<?php

declare(strict_types=1);

namespace App\Controllers;

final class StoreController
{
    public function index(): array
    {
        $stores = app('storeRepository')->all();
        $meta = app('seo')->meta(['title' => 'Negozi partner', 'description' => 'Consulta tutti i negozi partner e le loro offerte.', 'path' => '/negozi', 'breadcrumbs' => [['label' => 'Negozi', 'url' => '/negozi']]]);
        return response_view('frontend/stores/index', compact('stores', 'meta'));
    }

    public function show(string $slug): array
    {
        $store = app('storeRepository')->findBySlug($slug);
        if (! $store) {
            return response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Negozio non trovato', 'path' => request_path()])], 'app', 404);
        }
        $offers = app('offerRepository')->byStore((int) $store['id']);
        $breadcrumbs = [['label' => 'Negozi', 'url' => '/negozi'], ['label' => $store['name'], 'url' => '/negozio/' . $store['slug']]];
        $meta = app('seo')->meta(['title' => $store['name'] . ' — Coupon e offerte', 'description' => $store['description'], 'path' => '/negozio/' . $store['slug'], 'breadcrumbs' => $breadcrumbs]);
        return response_view('frontend/stores/show', compact('store', 'offers', 'meta', 'breadcrumbs'));
    }
}
