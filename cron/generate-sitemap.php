<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$controller = new App\Controllers\PageController();
$response = $controller->sitemap();
app('cache')->writeFile('sitemaps', 'sitemap.xml', $response['content']);
app('cache')->appendJsonLine('logs', 'cron.log', ['job' => 'generate-sitemap', 'created_at' => date('c')]);
echo base_path('storage/sitemaps/sitemap.xml') . PHP_EOL;
