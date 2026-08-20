<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Integrations\TradeDoubler\TradeDoublerClient;
use App\Integrations\TradeDoubler\TradeDoublerImporter;
use App\Integrations\TradeDoubler\TradeDoublerMapper;

$importer = new TradeDoublerImporter(new TradeDoublerClient(config('affiliate.tradedoubler')), new TradeDoublerMapper(), app('offerRepository'), app('cache'));
$result = $importer->import();
app('cache')->appendJsonLine('logs', 'cron.log', ['job' => 'sync-affiliate', 'result' => $result, 'created_at' => date('c')]);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
