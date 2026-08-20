<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$count = 0;
 $offers = app('offerRepository')->all();
foreach ($offers as &$offer) {
    $expiresAt = ! empty($offer['expires_at']) ? strtotime((string) $offer['expires_at']) : false;
    if (($offer['status'] ?? '') !== 'EXPIRED' && $expiresAt !== false && $expiresAt < strtotime('today')) {
        $offer['status'] = 'EXPIRED';
        $count++;
    }
}
unset($offer);
if ($count > 0) {
    app('offerRepository')->replaceAll($offers);
}
app('cache')->appendJsonLine('logs', 'cron.log', ['job' => 'expire-coupons', 'expired' => $count, 'created_at' => date('c')]);
echo "Expired: {$count}" . PHP_EOL;
