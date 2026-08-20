<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

final class DashboardController
{
    public function index(): array
    {
        $dashboard = app('analyticsRepository')->dashboard();
        $imports = config('app.seed.affiliate_imports', []);
        $programs = config('app.seed.affiliate_programs', []);
        $meta = app('seo')->meta(['title' => 'Dashboard admin', 'path' => '/admin/dashboard']);
        return response_view('admin/dashboard', compact('dashboard', 'imports', 'programs', 'meta'), 'admin');
    }

    public function affiliate(string $tab = 'index'): array
    {
        $imports = config('app.seed.affiliate_imports', []);
        $programs = config('app.seed.affiliate_programs', []);
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
        return ['type' => 'html', 'content' => $csv, 'status' => 200, 'headers' => ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="couponami-analytics.csv"']];
    }

    public function verification(): array
    {
        $items = config('app.seed.verification', []);
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
        $users = app('auth')->all();
        $meta = app('seo')->meta(['title' => 'Utenti admin', 'path' => '/admin/users']);
        return response_view('admin/users/index', compact('users', 'meta'), 'admin');
    }
}
