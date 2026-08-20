<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController
{
    public function index(): array
    {
        $categoryRepo = app('categoryRepository');
        $storeRepo = app('storeRepository');
        $offerRepo = app('offerRepository');
        $categories = $categoryRepo->featured();
        $stores = $storeRepo->featured();
        $offers = $offerRepo->featured();
        $latest = $offerRepo->latest();
        $stats = [
            'total_offers' => $offerRepo->count(),
            'total_stores' => $storeRepo->count(),
            'total_categories' => $categoryRepo->count(),
        ];
        $seo = app('seo');
        $jsonLd = app('schema')->generateWebSiteSchema();
        $meta = $seo->meta([
            'title' => $seo->generateHomeTitle(),
            'description' => $seo->generateHomeDescription(),
            'keywords' => 'coupon, codici sconto, offerte, risparmio, ' . \App\Helpers\DateHelper::getSeoDateString(),
            'path' => '/',
            'jsonLd' => $jsonLd,
        ]);
        return response_view('frontend/home', compact('categories', 'stores', 'offers', 'latest', 'meta', 'stats'));
    }
}
