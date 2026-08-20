<?php

declare(strict_types=1);

namespace App\Services;

final class AuthService
{
    public function __construct(private readonly array $users, private readonly CacheService $cache)
    {
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->findByUsername($username);
        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return false;
        }
        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
            'email' => $user['email'],
        ];
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
        if (! $user) {
            return false;
        }
        return $user['role'] === 'SUPER_ADMIN' || $user['role'] === $role;
    }

    public function isRateLimited(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $attempts = $this->attempts();
        $recent = array_values(array_filter($attempts[$key] ?? [], static fn (int $timestamp): bool => $timestamp >= (time() - $windowSeconds)));
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
        return array_map(static fn (array $user): array => [
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
            'email' => $user['email'],
        ], $this->users);
    }

    private function findByUsername(string $username): ?array
    {
        foreach ($this->users as $user) {
            if ($user['username'] === $username) {
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
}
