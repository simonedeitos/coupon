<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Str;
use App\Services\CacheService;
use PDO;

final class CategoryRepository
{
    public function __construct(
        private readonly CacheService $cache,
        private readonly array $seed,
        private readonly ?PDO $db = null,
    ) {
    }

    public function all(): array
    {
        if ($this->db !== null) {
            try {
                $rows = $this->db->query(
                    'SELECT c.id, c.slug, c.name, c.icon, c.description,
                            COUNT(o.id) AS offer_count
                     FROM categories c
                     LEFT JOIN offers o ON o.category_id = c.id AND o.status = \'ACTIVE\'
                     WHERE c.is_active = 1
                     GROUP BY c.id
                     ORDER BY c.sort_order DESC, c.name ASC',
                )->fetchAll();

                return array_map(static fn (array $r): array => [
                    'id' => (int) $r['id'],
                    'slug' => $r['slug'],
                    'name' => $r['name'],
                    'icon' => $r['icon'] ?? '🏷️',
                    'description' => $r['description'] ?? '',
                    'offer_count' => (int) $r['offer_count'],
                ], $rows);
            } catch (\Throwable) {
            }
        }

        return array_values($this->cache->collection('categories', $this->seed));
    }

    public function count(): int
    {
        if ($this->db !== null) {
            return (int) $this->db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
        }
        return count($this->all());
    }

    public function featured(int $limit = 6): array
    {
        if ($this->db !== null) {
            $stmt = $this->db->prepare(
                'SELECT c.id, c.slug, c.name, c.icon, c.description,
                        COUNT(o.id) AS offer_count
                 FROM categories c
                 LEFT JOIN offers o ON o.category_id = c.id AND o.status = \'ACTIVE\'
                 WHERE c.is_active = 1
                 GROUP BY c.id
                 ORDER BY c.sort_order DESC, c.name ASC
                 LIMIT ?',
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            return array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'slug' => $r['slug'],
                'name' => $r['name'],
                'icon' => $r['icon'] ?? '🏷️',
                'description' => $r['description'] ?? '',
                'offer_count' => (int) $r['offer_count'],
            ], $rows);
        }
        return array_slice($this->all(), 0, $limit);
    }

    public function findBySlug(string $slug): ?array
    {
        if ($this->db !== null) {
            $stmt = $this->db->prepare(
                'SELECT c.id, c.slug, c.name, c.icon, c.description,
                        COUNT(o.id) AS offer_count
                 FROM categories c
                 LEFT JOIN offers o ON o.category_id = c.id AND o.status = \'ACTIVE\'
                 WHERE c.slug = ? AND c.is_active = 1
                 GROUP BY c.id',
            );
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            if ($row === false) {
                return null;
            }
            return [
                'id' => (int) $row['id'],
                'slug' => $row['slug'],
                'name' => $row['name'],
                'icon' => $row['icon'] ?? '🏷️',
                'description' => $row['description'] ?? '',
                'offer_count' => (int) $row['offer_count'],
            ];
        }
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
