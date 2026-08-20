<?php

declare(strict_types=1);

namespace App\Middleware;

final class RoleMiddleware
{
    public function handle(array $route): ?array
    {
        $role = $route['role'] ?? null;
        if (! $role) {
            return null;
        }
        if (! app('auth')->hasRole($role)) {
            flash('error', 'Permessi insufficienti per questa sezione.');
            return redirect('/admin/dashboard');
        }
        return null;
    }
}
