<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ManagementController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;

return [
    ['method' => 'GET', 'pattern' => '/admin', 'handler' => [AuthController::class, 'loginForm']],
    ['method' => 'POST', 'pattern' => '/admin/login', 'handler' => [AuthController::class, 'login'], 'name' => 'admin.login.submit', 'middleware' => [CsrfMiddleware::class, RateLimitMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/logout', 'handler' => [AuthController::class, 'logout'], 'middleware' => [CsrfMiddleware::class, AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/dashboard', 'handler' => [DashboardController::class, 'index'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/offers', 'handler' => [ManagementController::class, 'index'], 'defaults' => ['section' => 'offers'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/offers/create', 'handler' => [ManagementController::class, 'form'], 'defaults' => ['section' => 'offers'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/offers/{id}/edit', 'handler' => [ManagementController::class, 'form'], 'defaults' => ['section' => 'offers'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/offers/save', 'handler' => [ManagementController::class, 'save'], 'defaults' => ['section' => 'offers'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/offers/{id}/delete', 'handler' => [ManagementController::class, 'delete'], 'defaults' => ['section' => 'offers'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/offers/{id}/status', 'handler' => [ManagementController::class, 'status'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/stores', 'handler' => [ManagementController::class, 'index'], 'defaults' => ['section' => 'stores'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/stores/create', 'handler' => [ManagementController::class, 'form'], 'defaults' => ['section' => 'stores'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/stores/{id}/edit', 'handler' => [ManagementController::class, 'form'], 'defaults' => ['section' => 'stores'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/stores/save', 'handler' => [ManagementController::class, 'save'], 'defaults' => ['section' => 'stores'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/stores/{id}/delete', 'handler' => [ManagementController::class, 'delete'], 'defaults' => ['section' => 'stores'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/categories', 'handler' => [ManagementController::class, 'index'], 'defaults' => ['section' => 'categories'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/categories/create', 'handler' => [ManagementController::class, 'form'], 'defaults' => ['section' => 'categories'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/categories/{id}/edit', 'handler' => [ManagementController::class, 'form'], 'defaults' => ['section' => 'categories'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/categories/save', 'handler' => [ManagementController::class, 'save'], 'defaults' => ['section' => 'categories'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/categories/{id}/delete', 'handler' => [ManagementController::class, 'delete'], 'defaults' => ['section' => 'categories'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/affiliate', 'handler' => [DashboardController::class, 'affiliate'], 'defaults' => ['tab' => 'index'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/affiliate/imports', 'handler' => [DashboardController::class, 'affiliate'], 'defaults' => ['tab' => 'imports'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/affiliate/programs', 'handler' => [DashboardController::class, 'affiliate'], 'defaults' => ['tab' => 'programs'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/analytics', 'handler' => [DashboardController::class, 'analytics'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/analytics/export', 'handler' => [DashboardController::class, 'analyticsExport'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/verification', 'handler' => [DashboardController::class, 'verification'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/audit', 'handler' => [DashboardController::class, 'audit'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/settings', 'handler' => [ManagementController::class, 'index'], 'defaults' => ['section' => 'settings'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/settings/save', 'handler' => [ManagementController::class, 'save'], 'defaults' => ['section' => 'settings'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/users', 'handler' => [DashboardController::class, 'users'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'GET', 'pattern' => '/admin/feature-flags', 'handler' => [ManagementController::class, 'index'], 'defaults' => ['section' => 'feature-flags'], 'middleware' => [AuthMiddleware::class]],
    ['method' => 'POST', 'pattern' => '/admin/feature-flags/save', 'handler' => [ManagementController::class, 'save'], 'defaults' => ['section' => 'feature-flags'], 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]],
];
