<?php

declare(strict_types=1);

namespace App\Controllers;

final class OfferController
{
    public function index(): array
    {
        $filters = ['type' => request_input('tipo', ''), 'sort' => request_input('ordine', '')];
        $offers = app('offerRepository')->all($filters);
        $meta = app('seo')->meta(['title' => 'Tutti i coupon', 'description' => 'Lista completa coupon, codici sconto e offerte attive.', 'path' => '/coupon', 'breadcrumbs' => [['label' => 'Coupon', 'url' => '/coupon']]]);
        return response_view('frontend/offers/index', compact('offers', 'meta'));
    }

    public function show(string $slug): array
    {
        $offer = app('offerRepository')->findBySlug($slug);
        if (! $offer) {
            return response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Coupon non trovato', 'path' => request_path()])], 'app', 404);
        }
        $store = app('storeRepository')->findById((int) $offer['store_id']);
        $breadcrumbs = [['label' => 'Coupon', 'url' => '/coupon'], ['label' => $offer['title'], 'url' => '/coupon/' . $offer['slug']]];
        $meta = app('seo')->meta(['title' => $offer['title'], 'description' => $offer['description'], 'path' => '/coupon/' . $offer['slug'], 'type' => 'article', 'breadcrumbs' => $breadcrumbs]);
        return response_view('frontend/offers/show', compact('offer', 'store', 'meta', 'breadcrumbs'));
    }

    public function go(int $id): array
    {
        $offer = app('offerRepository')->findById($id);
        if (! $offer) {
            return response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Offerta non trovata', 'path' => request_path()])], 'app', 404);
        }
        $store = app('storeRepository')->findById((int) $offer['store_id']);
        app('analytics')->logClick($offer, $store['name'] ?? 'Store');
        return ['type' => 'redirect', 'content' => '', 'status' => 302, 'headers' => ['Location' => $offer['affiliate_url'], 'Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex, nofollow']];
    }
}
