<?php

declare(strict_types=1);

use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\OfferController;
use App\Controllers\PageController;
use App\Controllers\SearchController;
use App\Controllers\StoreController;

return [
    ['method' => 'GET', 'pattern' => '/', 'handler' => [HomeController::class, 'index'], 'name' => 'home'],
    ['method' => 'GET', 'pattern' => '/categorie', 'handler' => [CategoryController::class, 'index']],
    ['method' => 'GET', 'pattern' => '/categoria/{slug}', 'handler' => [CategoryController::class, 'show']],
    ['method' => 'GET', 'pattern' => '/negozi', 'handler' => [StoreController::class, 'index']],
    ['method' => 'GET', 'pattern' => '/negozio/{slug}', 'handler' => [StoreController::class, 'show']],
    ['method' => 'GET', 'pattern' => '/coupon', 'handler' => [OfferController::class, 'index']],
    ['method' => 'GET', 'pattern' => '/coupon/{slug}', 'handler' => [OfferController::class, 'show']],
    ['method' => 'GET', 'pattern' => '/go/{id}', 'handler' => [OfferController::class, 'go']],
    ['method' => 'GET', 'pattern' => '/cerca', 'handler' => [SearchController::class, 'index']],
    ['method' => 'POST', 'pattern' => '/newsletter', 'handler' => [PageController::class, 'newsletter'], 'middleware' => [App\Middleware\CsrfMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/sitemap.xml', 'handler' => [PageController::class, 'sitemap']],
    ['method' => 'GET', 'pattern' => '/come-funziona', 'handler' => [PageController::class, 'show'], 'defaults' => ['slug' => 'come-funziona']],
    ['method' => 'GET', 'pattern' => '/chi-siamo', 'handler' => [PageController::class, 'show'], 'defaults' => ['slug' => 'chi-siamo']],
    ['method' => 'GET', 'pattern' => '/privacy', 'handler' => [PageController::class, 'show'], 'defaults' => ['slug' => 'privacy']],
    ['method' => 'GET', 'pattern' => '/cookie', 'handler' => [PageController::class, 'show'], 'defaults' => ['slug' => 'cookie']],
];
