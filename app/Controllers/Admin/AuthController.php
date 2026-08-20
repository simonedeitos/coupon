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

        // DEBUG TEMPORANEO: logga se la connessione DB è disponibile e se l'utente viene trovato
        $db = app('config')['database'] ?? [];
        error_log('AuthController::login - DB configured: ' . (empty($db['database']) ? 'NO' : 'YES, db=' . $db['database']));

        if (! app('auth')->attempt($username, $password)) {
            app('auth')->hitRateLimit($key);
            flash('error', 'Credenziali non valide.');
            set_old_input(['username' => $username]);
            return redirect('/admin');
        }
        app('auth')->clearRateLimit($key);
        clear_old_input();
        app('cache')->appendJsonLine('logs', 'audit.log', ['action' => 'login', 'actor' => $username, 'created_at' => date('c')]);
        flash('success', 'Accesso effettuato con successo.');
        return redirect('/admin/dashboard');
    }

    public function logout(): array
    {
        $user = app('auth')->user();
        app('cache')->appendJsonLine('logs', 'audit.log', ['action' => 'logout', 'actor' => $user['username'] ?? 'guest', 'created_at' => date('c')]);
        app('auth')->logout();
        flash('success', 'Sessione terminata.');
        return redirect('/admin');
    }
}