<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController
{
    public function index(): array
    {
        $categories = app('categoryRepository')->featured();
        $stores = app('storeRepository')->featured();
        $offers = app('offerRepository')->featured();
        $latest = app('offerRepository')->latest();
        $seo = app('seo');
        $jsonLd = app('schema')->generateWebSiteSchema();
        $meta = $seo->meta([
            'title' => $seo->generateHomeTitle(),
            'description' => $seo->generateHomeDescription(),
            'keywords' => 'coupon, codici sconto, offerte, risparmio, ' . \App\Helpers\DateHelper::getSeoDateString(),
            'path' => '/',
            'jsonLd' => $jsonLd,
        ]);
        return response_view('frontend/home', compact('categories', 'stores', 'offers', 'latest', 'meta'));
    }
}
