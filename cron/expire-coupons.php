<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$count = 0;
foreach (app('offerRepository')->all() as $offer) {
    if (($offer['status'] ?? '') !== 'EXPIRED' && strtotime($offer['expires_at']) < strtotime('today')) {
        app('offerRepository')->updateStatus((int) $offer['id'], 'EXPIRED');
        $count++;
    }
}
app('cache')->appendJsonLine('logs', 'cron.log', ['job' => 'expire-coupons', 'expired' => $count, 'created_at' => date('c')]);
echo "Expired: {$count}" . PHP_EOL;
