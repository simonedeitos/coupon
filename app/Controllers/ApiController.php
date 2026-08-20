<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;

final class ApiController
{
    public function health(): array
    {
        return Response::json(['status' => 'ok', 'app' => config('app.name'), 'time' => date('c')]);
    }

    public function offers(): array
    {
        return Response::json(['data' => app('offerRepository')->all(['status' => 'ACTIVE'])]);
    }

    public function search(): array
    {
        $query = trim((string) request_input('q', ''));
        return Response::json(['query' => $query, 'offers' => app('offerRepository')->all(['search' => $query])]);
    }

    public function dealOfTheDay(): array
    {
        $offers = app('offerRepository')->topToday(8);
        return Response::json(['data' => $offers]);
    }
}
