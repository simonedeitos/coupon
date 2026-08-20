<?php

declare(strict_types=1);

namespace App\Middleware;

final class RateLimitMiddleware
{
    public function handle(array $route): ?array
    {
        if (($route['name'] ?? '') !== 'admin.login.submit') {
            return null;
        }
        $key = ($_SERVER['REMOTE_ADDR'] ?? 'cli') . ':' . strtolower((string) ($_POST['username'] ?? 'guest'));
        if (app('auth')->isRateLimited($key)) {
            flash('error', 'Hai superato il numero massimo di tentativi. Riprova più tardi.');
            return redirect('/admin');
        }
        return null;
    }
}
