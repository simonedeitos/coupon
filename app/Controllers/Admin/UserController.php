<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

final class UserController
{
    public function store(): array
    {
        $data = [
            'username' => trim((string) request_input('username', '')),
            'email' => trim((string) request_input('email', '')),
            'display_name' => trim((string) request_input('display_name', '')),
            'password' => (string) request_input('password', ''),
            'role' => strtoupper((string) request_input('role', 'EDITOR')),
            'is_active' => request_input('is_active', '') ? 1 : 0,
        ];

        if ($data['username'] === '' || $data['email'] === '' || $data['password'] === '') {
            flash('error', 'Username, email e password sono obbligatori.');
            return redirect('/admin/users');
        }

        $ok = app('auth')->create($data);
        if ($ok) {
            flash('success', 'Utente creato con successo.');
        } else {
            flash('error', 'Errore nella creazione dell\'utente. Verifica i dati inseriti (username/email potrebbero essere già in uso).');
        }

        return redirect('/admin/users');
    }
}
