<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Str;
use App\Services\CacheService;

final class StoreRepository
{
    public function __construct(private readonly CacheService $cache, private readonly array $seed)
    {
    }

    public function all(): array
    {
        return array_values($this->cache->collection('stores', $this->seed));
    }

    public function featured(int $limit = 5): array
    {
        return array_slice(array_values(array_filter($this->all(), static fn (array $store): bool => (bool) ($store['featured'] ?? false))), 0, $limit);
    }

    public function findBySlug(string $slug): ?array
    {
        foreach ($this->all() as $store) {
            if ($store['slug'] === $slug) {
                return $store;
            }
        }
        return null;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->all() as $store) {
            if ((int) $store['id'] === $id) {
                return $store;
            }
        }
        return null;
    }

    public function save(array $payload): array
    {
        $stores = $this->all();
        $payload['slug'] = Str::slug($payload['name']);
        $payload['initial'] = strtoupper(substr($payload['name'], 0, 1));
        $payload['featured'] = ! empty($payload['featured']);
        $payload['offers_count'] = (int) ($payload['offers_count'] ?? 0);
        if (! empty($payload['id'])) {
            foreach ($stores as &$store) {
                if ((int) $store['id'] === (int) $payload['id']) {
                    $store = [...$store, ...$payload];
                    $this->cache->putCollection('stores', $stores);
                    return $store;
                }
            }
        }
        $payload['id'] = empty($stores) ? 1 : (max(array_column($stores, 'id')) + 1);
        $stores[] = $payload;
        $this->cache->putCollection('stores', $stores);
        return $payload;
    }

    public function delete(int $id): void
    {
        $stores = array_values(array_filter($this->all(), static fn (array $store): bool => (int) $store['id'] !== $id));
        $this->cache->putCollection('stores', $stores);
    }
}
