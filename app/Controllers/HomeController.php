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
        $meta = app('seo')->meta(['title' => 'Couponami — Home', 'description' => 'Home dinamica con categorie, negozi, coupon in evidenza e ultime offerte.', 'path' => '/']);
        return response_view('frontend/home', compact('categories', 'stores', 'offers', 'latest', 'meta'));
    }
}
