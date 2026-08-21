<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AuthService
{
    private ?array $userColumns = null;

    public function __construct(
        private readonly array $users,
        private readonly CacheService $cache,
        private readonly ?PDO $pdo = null
    ) {
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->findByUsername($username);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['admin_user'] = [
            'id' => $user['id'] ?? null,
            'username' => $user['username'],
            'display_name' => $user['display_name'] ?? 'Admin',
            'role' => strtoupper((string) ($user['role'] ?? 'ADMIN')),
            'email' => $user['email'] ?? '',
        ];

        if ($this->pdo !== null && ! empty($user['id'])) {
            try {
                $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int) $user['id']]);
            } catch (\Throwable) {
            }
        }

        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['admin_user']);
    }

    public function check(): bool
    {
        return isset($_SESSION['admin_user']);
    }

    public function user(): ?array
    {
        return $_SESSION['admin_user'] ?? null;
    }

    public function hasRole(string $role): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $userRole = strtoupper((string) ($user['role'] ?? ''));
        $role = strtoupper($role);
        return $userRole === 'SUPER_ADMIN' || $userRole === $role;
    }

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

    public function hitRateLimit(string $key): void
    {
        $attempts = $this->attempts();
        $attempts[$key] = [...($attempts[$key] ?? []), time()];
        $this->cache->putCollection('login_attempts', $attempts);
    }

    public function clearRateLimit(string $key): void
    {
        $attempts = $this->attempts();
        unset($attempts[$key]);
        $this->cache->putCollection('login_attempts', $attempts);
    }

    public function all(): array
    {
        if ($this->pdo) {
            $queries = [];
            $columns = $this->userColumns();
            $select = $this->userSelectColumns($columns);
            if ($columns === []) {
                $queries = [
                    "SELECT {$select} FROM users WHERE status = 'ACTIVE' ORDER BY username ASC",
                    "SELECT {$select} FROM users WHERE is_active = 1 ORDER BY username ASC",
                ];
            } else {
                if (! empty($columns['status']) && ! empty($columns['is_active'])) {
                    $queries[] = "SELECT {$select} FROM users WHERE status = 'ACTIVE' OR is_active = 1 ORDER BY username ASC";
                } elseif (! empty($columns['status'])) {
                    $queries[] = "SELECT {$select} FROM users WHERE status = 'ACTIVE' ORDER BY username ASC";
                } elseif (! empty($columns['is_active'])) {
                    $queries[] = "SELECT {$select} FROM users WHERE is_active = 1 ORDER BY username ASC";
                }
            }

            $hadDatabaseError = false;
            $hadSuccessfulQuery = false;
            foreach ($queries as $sql) {
                try {
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute();
                    $hadSuccessfulQuery = true;
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($users !== []) {
                        return $users;
                    }
                } catch (\Throwable) {
                    $hadDatabaseError = true;
                }
            }

            if ($hadSuccessfulQuery) {
                return [];
            }

            if ($hadDatabaseError || $queries === []) {
                error_log('AuthService::all() database error: unable to query users with supported schemas');
            }
        }

        return array_map(static fn (array $user): array => [
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
            'email' => $user['email'] ?? '',
            'is_active' => 1,
            'last_login_at' => null,
        ], $this->users);
    }

    public function create(array $data): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        $username = trim((string) ($data['username'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $displayName = trim((string) ($data['display_name'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = strtoupper((string) ($data['role'] ?? 'EDITOR'));
        $isActive = ! empty($data['is_active']) ? 1 : 0;
        $allowedRoles = ['SUPER_ADMIN', 'ADMIN', 'EDITOR', 'ANALYTICS'];

        if ($username === '' || $email === '' || $password === '') {
            return false;
        }
        if (! in_array($role, $allowedRoles, true)) {
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        if (! is_string($passwordHash) || $passwordHash === '') {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, email, display_name, role, password_hash, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            return $stmt->execute([$username, $email, $displayName, $role, $passwordHash, $isActive]);
        } catch (\Throwable $e) {
            error_log('AuthService::create failed: ' . $e->getMessage());
            return false;
        }
    }

    private function findByUsername(string $username): ?array
    {
        $username = trim((string) $username);

        if ($this->pdo) {
            $queries = [];
            $columns = $this->userColumns();
            if ($columns === []) {
                $queries = [
                    "SELECT id, username, email, password_hash, display_name, role FROM users WHERE LOWER(username) = LOWER(?) AND status = 'ACTIVE' LIMIT 1",
                    'SELECT id, username, email, password_hash, display_name, role FROM users WHERE LOWER(username) = LOWER(?) AND is_active = 1 LIMIT 1',
                ];
            } else {
                if (! empty($columns['status']) && ! empty($columns['is_active'])) {
                    $queries[] = "SELECT id, username, email, password_hash, display_name, role FROM users WHERE LOWER(username) = LOWER(?) AND (status = 'ACTIVE' OR is_active = 1) LIMIT 1";
                } elseif (! empty($columns['status'])) {
                    $queries[] = "SELECT id, username, email, password_hash, display_name, role FROM users WHERE LOWER(username) = LOWER(?) AND status = 'ACTIVE' LIMIT 1";
                } elseif (! empty($columns['is_active'])) {
                    $queries[] = 'SELECT id, username, email, password_hash, display_name, role FROM users WHERE LOWER(username) = LOWER(?) AND is_active = 1 LIMIT 1';
                }
            }

            $hadDatabaseError = false;
            $hadSuccessfulQuery = false;
            foreach ($queries as $sql) {
                try {
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$username]);
                    $hadSuccessfulQuery = true;
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        return $user;
                    }
                } catch (\Throwable) {
                    $hadDatabaseError = true;
                }
            }

            if (! $hadSuccessfulQuery && ($hadDatabaseError || $queries === [])) {
                error_log('AuthService::findByUsername() database error: unable to query users with supported schemas');
            }

            if ($hadSuccessfulQuery) {
                return null;
            }
        }

        foreach ($this->users as $user) {
            if (strtolower($user['username']) === strtolower($username)) {
                return $user;
            }
        }

        return null;
    }

    private function attempts(): array
    {
        $attempts = $this->cache->collection('login_attempts', []);
        return is_array($attempts) ? $attempts : [];
    }

    private function userColumns(): array
    {
        if ($this->userColumns !== null) {
            return $this->userColumns;
        }

        if ($this->pdo === null) {
            $this->userColumns = [];
            return $this->userColumns;
        }

        try {
            $stmt = $this->pdo->query('SELECT * FROM users LIMIT 0');
            $columns = [];
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                $name = strtolower((string) ($meta['name'] ?? ''));
                if ($name !== '') {
                    $columns[$name] = true;
                }
            }
            $this->userColumns = $columns;
        } catch (\Throwable) {
            $this->userColumns = [];
        }

        return $this->userColumns;
    }

    private function userSelectColumns(array $columns): string
    {
        $activeExpr = ! empty($columns['is_active']) ? 'is_active' : '1';
        $lastLoginExpr = ! empty($columns['last_login_at']) ? 'last_login_at' : 'NULL';
        return "id, username, display_name, role, email, {$activeExpr} AS is_active, {$lastLoginExpr} AS last_login_at";
    }
}