<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Str;
use App\Services\CacheService;

final class OfferRepository
{
    public function __construct(private readonly CacheService $cache, private readonly array $seed)
    {
    }

    public function all(array $filters = []): array
    {
        $offers = array_values($this->cache->collection('offers', $this->seed));
        if (! empty($filters['type'])) {
            $offers = array_values(array_filter($offers, static fn (array $offer): bool => $offer['type'] === strtoupper((string) $filters['type'])));
        }
        if (! empty($filters['status'])) {
            $offers = array_values(array_filter($offers, static fn (array $offer): bool => $offer['status'] === strtoupper((string) $filters['status'])));
        }
        if (! empty($filters['search'])) {
            $term = mb_strtolower((string) $filters['search']);
            $offers = array_values(array_filter($offers, static fn (array $offer): bool => str_contains(mb_strtolower($offer['title'] . ' ' . $offer['description']), $term)));
        }
        if (($filters['sort'] ?? '') === 'recenti') {
            usort($offers, static fn (array $a, array $b): int => strcmp($b['expires_at'], $a['expires_at']));
        }
        return $offers;
    }

    public function featured(int $limit = 3): array
    {
        return array_slice(array_values(array_filter($this->all(['status' => 'ACTIVE']), static fn (array $offer): bool => (bool) ($offer['featured'] ?? false))), 0, $limit);
    }

    public function latest(int $limit = 4): array
    {
        $offers = $this->all();
        usort($offers, static fn (array $a, array $b): int => strcmp($b['expires_at'], $a['expires_at']));
        return array_slice($offers, 0, $limit);
    }

    public function byCategory(int $categoryId): array
    {
        return array_values(array_filter($this->all(), static fn (array $offer): bool => (int) $offer['category_id'] === $categoryId));
    }

    public function byStore(int $storeId): array
    {
        return array_values(array_filter($this->all(), static fn (array $offer): bool => (int) $offer['store_id'] === $storeId));
    }

    public function findBySlug(string $slug): ?array
    {
        foreach ($this->all() as $offer) {
            if ($offer['slug'] === $slug) {
                return $offer;
            }
        }
        return null;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->all() as $offer) {
            if ((int) $offer['id'] === $id) {
                return $offer;
            }
        }
        return null;
    }

    public function save(array $payload): array
    {
        $offers = $this->all();
        $payload['slug'] = Str::slug($payload['title']);
        $payload['featured'] = ! empty($payload['featured']);
        $payload['clicks'] = (int) ($payload['clicks'] ?? 0);
        $payload['priority'] = (int) ($payload['priority'] ?? 50);
        $payload['hash'] = sha1(($payload['external_id'] ?? $payload['slug']) . '|' . ($payload['code'] ?? '') . '|' . ($payload['status'] ?? 'ACTIVE'));
        if (! empty($payload['id'])) {
            foreach ($offers as &$offer) {
                if ((int) $offer['id'] === (int) $payload['id']) {
                    $offer = [...$offer, ...$payload];
                    $this->cache->putCollection('offers', $offers);
                    return $offer;
                }
            }
        }
        $payload['id'] = empty($offers) ? 1 : (max(array_column($offers, 'id')) + 1);
        $offers[] = $payload;
        $this->cache->putCollection('offers', $offers);
        return $payload;
    }

    public function delete(int $id): void
    {
        $offers = array_values(array_filter($this->all(), static fn (array $offer): bool => (int) $offer['id'] !== $id));
        $this->cache->putCollection('offers', $offers);
    }

    public function updateStatus(int $id, string $status): void
    {
        $offers = $this->all();
        foreach ($offers as &$offer) {
            if ((int) $offer['id'] === $id) {
                $offer['status'] = strtoupper($status);
            }
        }
        $this->cache->putCollection('offers', $offers);
    }

    public function replaceAll(array $offers): void
    {
        $this->cache->putCollection('offers', $offers);
    }
}
