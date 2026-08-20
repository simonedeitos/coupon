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
        $db = app('db');
        $page = max(1, (int) request_input('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        $items = [];
        $total = 0;

        if ($db !== null) {
            try {
                $total = (int) $db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
                $stmt = $db->prepare(
                    'SELECT al.*, u.username AS actor
                     FROM audit_logs al
                     LEFT JOIN users u ON u.id = al.user_id
                     ORDER BY al.created_at DESC
                     LIMIT ? OFFSET ?'
                );
                $stmt->execute([$perPage, $offset]);
                $items = $stmt->fetchAll();
            } catch (\Throwable) {
                // Fallback su file flat
                $items = app('cache')->readJsonLines('logs', 'audit.log', 100);
            }
        } else {
            $items = app('cache')->readJsonLines('logs', 'audit.log', 100);
        }

        $meta = app('seo')->meta(['title' => 'Audit log', 'path' => '/admin/audit']);
        $pages = $perPage > 0 && $total > 0 ? (int) ceil($total / $perPage) : 1;
        return response_view('admin/audit/index', compact('items', 'meta', 'page', 'pages', 'total'), 'admin');
    }

    public function users(): array
    {
        $users = app('auth')->all();
        $meta = app('seo')->meta(['title' => 'Utenti admin', 'path' => '/admin/users']);
        return response_view('admin/users/index', compact('users', 'meta'), 'admin');
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