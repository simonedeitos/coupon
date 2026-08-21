<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

final class AuthController
{
    public function loginForm(): array
    {
        if (app('auth')->check()) {
            return redirect('/admin/dashboard');
        }
        $meta = app('seo')->meta(['title' => 'Login admin', 'path' => '/admin']);
        return response_view('admin/login', compact('meta'), 'admin');
    }

    public function login(): array
    {
        $username = trim((string) request_input('username', ''));
        $password = (string) request_input('password', '');
        $key = ($_SERVER['REMOTE_ADDR'] ?? 'cli') . ':' . strtolower($username);

        if (! app('auth')->attempt($username, $password)) {
            app('auth')->hitRateLimit($key);
            flash('error', 'Credenziali non valide.');
            set_old_input(['username' => $username]);
            return redirect('/admin');
        }
        app('auth')->clearRateLimit($key);
        clear_old_input();
        $this->writeAudit('login', 'users', app('auth')->user()['id'] ?? null, ['username' => $username], app('auth')->user()['id'] ?? null);
        app('cache')->appendJsonLine('logs', 'audit.log', ['action' => 'login', 'actor' => $username, 'created_at' => date('c')]);
        flash('success', 'Accesso effettuato con successo.');
        return redirect('/admin/dashboard');
    }

    public function logout(): array
    {
        $user = app('auth')->user();
        $this->writeAudit('logout', 'users', $user['id'] ?? null, ['username' => $user['username'] ?? 'guest'], $user['id'] ?? null);
        app('cache')->appendJsonLine('logs', 'audit.log', ['action' => 'logout', 'actor' => $user['username'] ?? 'guest', 'created_at' => date('c')]);
        app('auth')->logout();
        flash('success', 'Sessione terminata.');
        return redirect('/admin');
    }

    private function writeAudit(string $action, string $entityType, ?int $entityId, array $payload = [], ?int $actorId = null): void
    {
        $db = app('db');
        if ($db === null) {
            return;
        }
        $actorId = $actorId ?? (app('auth')->user()['id'] ?? $entityId);
        try {
            $db->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, payload, created_at)
                 VALUES (?, ?, ?, ?, INET6_ATON(?), ?, NOW())'
            )->execute([
                $actorId !== null ? (int) $actorId : null,
                $action,
                $entityType,
                $entityId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            error_log('AuthController::writeAudit failed: ' . $e->getMessage());
        }
    }
}