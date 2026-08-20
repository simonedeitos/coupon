<?php

declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware
{
    public function handle(array $route): ?array
    {
        if (! app('auth')->check()) {
            flash('error', 'Accedi per continuare.');
            return redirect('/admin');
        }
        return null;
    }
}
