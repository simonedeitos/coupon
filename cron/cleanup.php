<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$anonymized = app('analytics')->anonymizeOldClicks();
app('cache')->appendJsonLine('logs', 'cron.log', ['job' => 'cleanup', 'anonymized' => $anonymized, 'created_at' => date('c')]);
echo "Anonymized: {$anonymized}" . PHP_EOL;
