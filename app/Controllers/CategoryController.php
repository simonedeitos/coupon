<?php

declare(strict_types=1);

namespace App\Controllers;

final class CategoryController
{
    public function index(): array
    {
        $categories = app('categoryRepository')->all();
        $meta = app('seo')->meta(['title' => 'Categorie coupon', 'description' => 'Sfoglia tutte le categorie Couponami.', 'path' => '/categorie', 'breadcrumbs' => [['label' => 'Categorie', 'url' => '/categorie']]]);
        return response_view('frontend/categories/index', compact('categories', 'meta'));
    }

    public function show(string $slug): array
    {
        $category = app('categoryRepository')->findBySlug($slug);
        if (! $category) {
            return response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Categoria non trovata', 'path' => request_path()])], 'app', 404);
        }
        $offers = app('offerRepository')->byCategory((int) $category['id']);
        $breadcrumbs = [['label' => 'Categorie', 'url' => '/categorie'], ['label' => $category['name'], 'url' => '/categoria/' . $category['slug']]];
        $meta = app('seo')->meta(['title' => $category['name'] . ' — Coupon', 'description' => $category['description'], 'path' => '/categoria/' . $category['slug'], 'breadcrumbs' => $breadcrumbs]);
        return response_view('frontend/categories/show', compact('category', 'offers', 'meta', 'breadcrumbs'));
    }
}
