<?php

declare(strict_types=1);

use App\Controllers\ApiController;

return [
    ['method' => 'GET', 'pattern' => '/api/health', 'handler' => [ApiController::class, 'health']],
    ['method' => 'GET', 'pattern' => '/api/offers', 'handler' => [ApiController::class, 'offers']],
    ['method' => 'GET', 'pattern' => '/api/search', 'handler' => [ApiController::class, 'search']],
];
