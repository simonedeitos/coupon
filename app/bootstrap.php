<?php

declare(strict_types=1);

use App\Repositories\AnalyticsRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\OfferRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\StoreRepository;
use App\Services\AnalyticsService;
use App\Services\AuthService;
use App\Services\CacheService;
use App\Services\CsrfService;
use App\Services\SchemaService;
use App\Services\SeoService;
use App\Services\SitemapService;
use App\Services\ViewService;
use App\Database\Connection;

const BASE_PATH = __DIR__ . '/..';

date_default_timezone_set('Europe/Rome');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/Helpers/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (! isset($GLOBALS['couponami'])) {
    $config = [
        'app' => require BASE_PATH . '/config/app.php',
        'database' => require BASE_PATH . '/config/database.php',
        'affiliate' => require BASE_PATH . '/config/affiliate.php',
        'seo' => require BASE_PATH . '/config/seo.php',
    ];

    $cache = new CacheService(BASE_PATH . '/storage');
    $csrf = new CsrfService();
    $db = Connection::get($config['database']);
    $auth = new AuthService($config['app']['admin_users'], $cache, $db);
    $analytics = new AnalyticsService($cache);
    $seo = new SeoService($config['seo'], $config['app']);
    $schema = new SchemaService($config['app'], $config['seo']);
    $categories = new CategoryRepository($cache, $config['app']['seed']['categories'], $db);
    $stores = new StoreRepository($cache, $config['app']['seed']['stores'], $db);
    $offers = new OfferRepository($cache, [], $db);
    $settings = new SettingsRepository($cache);
    $sitemap = new SitemapService($config['app']);
    $view = new ViewService(BASE_PATH . '/views');

    $GLOBALS['couponami'] = [
        'config' => $config,
        'cache' => $cache,
        'csrf' => $csrf,
        'auth' => $auth,
        'analytics' => $analytics,
        'seo' => $seo,
        'schema' => $schema,
        'sitemap' => $sitemap,
        'view' => $view,
        'categoryRepository' => $categories,
        'storeRepository' => $stores,
        'offerRepository' => $offers,
        'settingsRepository' => $settings,
        'analyticsRepository' => new AnalyticsRepository($analytics, $offers, $stores, $cache),
    ];
}
