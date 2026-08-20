<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AuthService
{
    public function __construct(
        private readonly array $users,
        private readonly CacheService $cache,
        private readonly ?PDO $pdo = null
    ) {
    }

    /**
     * Tentativo di login con username e password
     * Legge dal database se disponibile, altrimenti da config
     */
    public function attempt(string $username, string $password): bool
    {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }

        // Verifica password con Argon2id o bcrypt
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Salva in session
        $_SESSION['admin_user'] = [
            'id' => $user['id'] ?? null,
            'username' => $user['username'],
            'display_name' => $user['display_name'] ?? $user['display_name'] ?? 'Admin',
            'role' => $user['role'] ?? 'admin',
            'email' => $user['email'] ?? '',
        ];

        return true;
    }

    /**
     * Logout - Rimuove sessione admin
     */
    public function logout(): void
    {
        unset($_SESSION['admin_user']);
    }

    /**
     * Verifica se utente è autenticato
     */
    public function check(): bool
    {
        return isset($_SESSION['admin_user']);
    }

    /**
     * Recupera utente corrente autenticato
     */
    public function user(): ?array
    {
        return $_SESSION['admin_user'] ?? null;
    }

    /**
     * Verifica se utente ha un ruolo specifico
     */
    public function hasRole(string $role): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        return $user['role'] === 'super_admin' || $user['role'] === $role;
    }

    /**
     * Verifica se è rate limitato
     */
    public function isRateLimited(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $attempts = $this->attempts();
        $recent = array_values(array_filter(
            $attempts[$key] ?? [],
            static fn (int $timestamp): bool => $timestamp >= (time() - $windowSeconds)
        ));
        $attempts[$key] = $recent;
        $this->cache->putCollection('login_attempts', $attempts);
        return count($recent) >= $maxAttempts;
    }

    /**
     * Registra tentativo fallito di login (rate limiting)
     */
    public function hitRateLimit(string $key): void
    {
        $attempts = $this->attempts();
        $attempts[$key] = [...($attempts[$key] ?? []), time()];
        $this->cache->putCollection('login_attempts', $attempts);
    }

    /**
     * Pulisce rate limiting dopo login riuscito
     */
    public function clearRateLimit(string $key): void
    {
        $attempts = $this->attempts();
        unset($attempts[$key]);
        $this->cache->putCollection('login_attempts', $attempts);
    }

    /**
     * Ottieni tutti gli utenti
     */
    public function all(): array
    {
        // Se c'è il database, leggi da lì
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT id, username, display_name, role, email 
                     FROM users 
                     WHERE is_active = 1 
                     ORDER BY username ASC"
                );
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                error_log('AuthService::all() database error: ' . $e->getMessage());
            }
        }

        // Fallback a config
        return array_map(static fn (array $user): array => [
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
            'email' => $user['email'] ?? '',
        ], $this->users);
    }

    /**
     * Trova utente per username
     * Legge dal database se disponibile, altrimenti da config
     */
    private function findByUsername(string $username): ?array
    {
        $username = trim((string)$username);

        // Priorità: Database > Config
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT id, username, email, password_hash, display_name, role, is_active 
                     FROM users 
                     WHERE LOWER(username) = LOWER(?)
                       AND is_active = 1
                     LIMIT 1"
                );
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    return $user;
                }
            } catch (\Exception $e) {
                error_log('AuthService::findByUsername() database error: ' . $e->getMessage());
                // Fallback a config se c'è errore DB
            }
        }

        // Fallback: Leggi da config/app.php
        foreach ($this->users as $user) {
            if (strtolower($user['username']) === strtolower($username)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Ottieni storico tentativi di login (rate limiting)
     */
    private function attempts(): array
    {
        $attempts = $this->cache->collection('login_attempts', []);
        return is_array($attempts) ? $attempts : [];
    }
}
