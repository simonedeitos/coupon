<?php

declare(strict_types=1);

namespace App\Models;

final class Offer
{
    public function __construct(private array $attributes = [])
    {
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
}
