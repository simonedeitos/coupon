<?php

declare(strict_types=1);

namespace App\Controllers;

final class StoreController
{
    public function index(): array
    {
        $stores = app('storeRepository')->all();
        $seo = app('seo');
        $meta = $seo->meta([
            'title' => $seo->generateStoreListTitle(),
            'description' => 'Consulta tutti i negozi partner con coupon verificati a ' . \App\Helpers\DateHelper::getSeoDateString() . '.',
            'keywords' => 'negozi con coupon, codici sconto negozi, offerte ' . \App\Helpers\DateHelper::getSeoDateString(),
            'path' => '/negozi',
            'breadcrumbs' => [['label' => 'Negozi', 'url' => '/negozi']],
        ]);
        return response_view('frontend/stores/index', compact('stores', 'meta'));
    }

    public function show(string $slug): array
    {
        $store = app('storeRepository')->findBySlug($slug);
        if (! $store) {
            return response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Negozio non trovato', 'path' => request_path()])], 'app', 404);
        }
        $offers = app('offerRepository')->byStore((int) $store['id']);
        $count = count($offers);
        $seo = app('seo');
        $breadcrumbs = [['label' => 'Negozi', 'url' => '/negozi'], ['label' => $store['name'], 'url' => '/negozio/' . $store['slug']]];
        $jsonLd = app('schema')->generateStoreBreadcrumb($store);
        $meta = $seo->meta([
            'title' => $seo->generateStoreTitle($store, $count),
            'description' => $seo->generateStoreMeta($store, $count),
            'keywords' => $seo->generateStoreKeywords($store),
            'path' => '/negozio/' . $store['slug'],
            'breadcrumbs' => $breadcrumbs,
            'jsonLd' => $jsonLd,
        ]);
        return response_view('frontend/stores/show', compact('store', 'offers', 'meta', 'breadcrumbs'));
    }
}
