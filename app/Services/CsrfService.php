<?php

declare(strict_types=1);

namespace App\Services;

final class CsrfService
{
    public function token(): string
    {
        if (! isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public function validate(?string $token): bool
    {
        return is_string($token) && hash_equals($this->token(), $token);
    }
}
