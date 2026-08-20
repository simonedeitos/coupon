<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Str;
use App\Services\CacheService;

final class CategoryRepository
{
    public function __construct(private readonly CacheService $cache, private readonly array $seed)
    {
    }

    public function all(): array
    {
        return array_values($this->cache->collection('categories', $this->seed));
    }

    public function featured(int $limit = 6): array
    {
        return array_slice($this->all(), 0, $limit);
    }

    public function findBySlug(string $slug): ?array
    {
        foreach ($this->all() as $category) {
            if ($category['slug'] === $slug) {
                return $category;
            }
        }
        return null;
    }

    public function save(array $payload): array
    {
        $categories = $this->all();
        $payload['slug'] = Str::slug($payload['name']);
        $payload['offer_count'] = (int) ($payload['offer_count'] ?? 0);
        if (! empty($payload['id'])) {
            foreach ($categories as &$category) {
                if ((int) $category['id'] === (int) $payload['id']) {
                    $category = [...$category, ...$payload];
                    $this->cache->putCollection('categories', $categories);
                    return $category;
                }
            }
        }
        $payload['id'] = empty($categories) ? 1 : (max(array_column($categories, 'id')) + 1);
        $categories[] = $payload;
        $this->cache->putCollection('categories', $categories);
        return $payload;
    }

    public function delete(int $id): void
    {
        $categories = array_values(array_filter($this->all(), static fn (array $category): bool => (int) $category['id'] !== $id));
        $this->cache->putCollection('categories', $categories);
    }
}
