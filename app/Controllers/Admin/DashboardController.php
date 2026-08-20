<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

final class DashboardController
{
    public function index(): array
    {
        $dashboard = app('analyticsRepository')->dashboard();
        $imports = [];
        $programs = [];
        $meta = app('seo')->meta(['title' => 'Dashboard admin', 'path' => '/admin/dashboard']);
        return response_view('admin/dashboard', compact('dashboard', 'imports', 'programs', 'meta'), 'admin');
    }

    public function affiliate(string $tab = 'index'): array
    {
        $imports = [];
        $programs = [];
        $meta = app('seo')->meta(['title' => 'TradeDoubler', 'path' => '/admin/affiliate']);
        return response_view('admin/affiliate/' . $tab, compact('imports', 'programs', 'meta'), 'admin');
    }

    public function analytics(): array
    {
        $dashboard = app('analyticsRepository')->dashboard();
        $meta = app('seo')->meta(['title' => 'Analytics', 'path' => '/admin/analytics']);
        return response_view('admin/analytics/index', compact('dashboard', 'meta'), 'admin');
    }

    public function analyticsExport(): array
    {
        $csv = app('analytics')->exportCsv(app('analytics')->clickSeries());
        return ['type' => 'raw', 'content' => $csv, 'status' => 200, 'headers' => ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="couponami-analytics.csv"']];
    }

    public function verification(): array
    {
        $items = [];
        $meta = app('seo')->meta(['title' => 'Verifica offerte', 'path' => '/admin/verification']);
        return response_view('admin/verification/index', compact('items', 'meta'), 'admin');
    }

    public function audit(): array
    {
        $items = app('cache')->readJsonLines('logs', 'audit.log', 100);
        $meta = app('seo')->meta(['title' => 'Audit log', 'path' => '/admin/audit']);
        return response_view('admin/audit/index', compact('items', 'meta'), 'admin');
    }

    public function users(): array
    {
        $items = app('auth')->all();
        $meta = app('seo')->meta(['title' => 'Utenti admin', 'path' => '/admin/users']);
        return response_view('admin/users/index', compact('items', 'meta'), 'admin');
    }

    public function seo(): array
    {
        $seoService = app('seo');
        $categories = app('categoryRepository')->all();
        $stores = app('storeRepository')->all();

        $allOffers = app('offerRepository')->all();
        $countByCategory = [];
        $countByStore = [];
        foreach ($allOffers as $offer) {
            if (! empty($offer['category_id'])) {
                $countByCategory[(int) $offer['category_id']] = ($countByCategory[(int) $offer['category_id']] ?? 0) + 1;
            }
            if (! empty($offer['store_id'])) {
                $countByStore[(int) $offer['store_id']] = ($countByStore[(int) $offer['store_id']] ?? 0) + 1;
            }
        }

        $pages = [];

        $homeTitle = $seoService->generateHomeTitle();
        $homeDesc = $seoService->generateHomeDescription();
        $pages[] = [
            'type' => 'Home',
            'name' => 'Couponami',
            'title' => $homeTitle,
            'description' => $homeDesc,
        ];

        foreach ($categories as $category) {
            $pages[] = [
                'type' => 'Categoria',
                'name' => $category['name'],
                'title' => $category['name'] . ' - Couponami',
                'description' => $category['description'] ?? '',
                'offers' => $countByCategory[(int) $category['id']] ?? 0,
            ];
        }

        foreach ($stores as $store) {
            $pages[] = [
                'type' => 'Negozio',
                'name' => $store['name'],
                'title' => $store['name'] . ' - Couponami',
                'description' => $store['description'] ?? '',
                'offers' => $countByStore[(int) $store['id']] ?? 0,
            ];
        }

        $meta = $seoService->meta(['title' => 'SEO admin', 'path' => '/admin/seo']);
        return response_view('admin/seo/index', compact('pages', 'meta'), 'admin');
    }
}