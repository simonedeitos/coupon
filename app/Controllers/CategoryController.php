<?php

declare(strict_types=1);

namespace App\Controllers;

final class CategoryController
{
    public function index(): array
    {
        $categories = app('categoryRepository')->all();
        $seo = app('seo');
        $meta = $seo->meta([
            'title' => $seo->generateCategoryListTitle(),
            'description' => 'Sfoglia tutte le categorie Couponami e trova i migliori coupon per ogni settore a ' . \App\Helpers\DateHelper::getSeoDateString() . '.',
            'keywords' => 'categorie coupon, codici sconto per categoria, offerte ' . \App\Helpers\DateHelper::getSeoDateString(),
            'path' => '/categorie',
            'breadcrumbs' => [['label' => 'Categorie', 'url' => '/categorie']],
        ]);
        return response_view('frontend/categories/index', compact('categories', 'meta'));
    }

    public function show(string $slug): array
    {
        $category = app('categoryRepository')->findBySlug($slug);
        if (! $category) {
            return response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Categoria non trovata', 'path' => request_path()])], 'app', 404);
        }
        $offers = app('offerRepository')->byCategory((int) $category['id']);
        $count = count($offers);
        $seo = app('seo');
        $breadcrumbs = [['label' => 'Categorie', 'url' => '/categorie'], ['label' => $category['name'], 'url' => '/categoria/' . $category['slug']]];
        $jsonLd = app('schema')->generateCategoryBreadcrumb($category);
        $meta = $seo->meta([
            'title' => $seo->generateCategoryTitle($category, $count),
            'description' => $seo->generateCategoryMeta($category, $count),
            'keywords' => $seo->generateCategoryKeywords($category),
            'path' => '/categoria/' . $category['slug'],
            'breadcrumbs' => $breadcrumbs,
            'jsonLd' => $jsonLd,
        ]);
        return response_view('frontend/categories/show', compact('category', 'offers', 'meta', 'breadcrumbs'));
    }
}
