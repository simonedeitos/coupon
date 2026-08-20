<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Str;
use App\Services\CacheService;
use PDO;

final class StoreRepository
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
                    'SELECT s.id, s.slug, s.name, s.description, s.website_url AS website,
                            s.is_featured,
                            COUNT(o.id) AS offers_count
                     FROM stores s
                     LEFT JOIN offers o ON o.store_id = s.id AND o.status = \'ACTIVE\'
                     WHERE s.is_active = 1
                     GROUP BY s.id
                     ORDER BY s.is_featured DESC, offers_count DESC, s.name ASC',
                )->fetchAll();

                return array_map(static fn (array $r): array => [
                    'id' => (int) $r['id'],
                    'slug' => $r['slug'],
                    'name' => $r['name'],
                    'initial' => strtoupper(mb_substr($r['name'], 0, 1)),
                    'description' => $r['description'] ?? '',
                    'website' => $r['website'] ?? '',
                    'offers_count' => (int) $r['offers_count'],
                    'featured' => (bool) $r['is_featured'],
                ], $rows);
            } catch (\Throwable $e) {
                error_log('StoreRepository::all failed: ' . $e->getMessage());
            }
        }

        return array_values($this->cache->collection('stores', $this->seed));
    }

    public function count(): int
    {
        if ($this->db !== null) {
            return (int) $this->db->query("SELECT COUNT(*) FROM stores WHERE is_active = 1")->fetchColumn();
        }
        return count($this->all());
    }

    public function featured(int $limit = 5): array
    {
        if ($this->db !== null) {
            $stmt = $this->db->prepare(
                'SELECT s.id, s.slug, s.name, s.description, s.website_url AS website,
                        COUNT(o.id) AS offers_count
                 FROM stores s
                 LEFT JOIN offers o ON o.store_id = s.id AND o.status = \'ACTIVE\'
                 WHERE s.is_active = 1 AND s.is_featured = 1
                 GROUP BY s.id
                 ORDER BY offers_count DESC, s.name ASC
                 LIMIT ?',
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            return array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'slug' => $r['slug'],
                'name' => $r['name'],
                'initial' => strtoupper(mb_substr($r['name'], 0, 1)),
                'description' => $r['description'] ?? '',
                'website' => $r['website'] ?? '',
                'offers_count' => (int) $r['offers_count'],
                'featured' => true,
            ], $rows);
        }
        return array_slice(array_values(array_filter($this->all(), static fn (array $store): bool => (bool) ($store['featured'] ?? false))), 0, $limit);
    }

    public function findBySlug(string $slug): ?array
    {
        if ($this->db !== null) {
            $stmt = $this->db->prepare(
                'SELECT s.id, s.slug, s.name, s.description, s.website_url AS website,
                        COUNT(o.id) AS offers_count
                 FROM stores s
                 LEFT JOIN offers o ON o.store_id = s.id AND o.status = \'ACTIVE\'
                 WHERE s.slug = ? AND s.is_active = 1
                 GROUP BY s.id',
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
                'initial' => strtoupper(mb_substr($row['name'], 0, 1)),
                'description' => $row['description'] ?? '',
                'website' => $row['website'] ?? '',
                'offers_count' => (int) $row['offers_count'],
                'featured' => false,
            ];
        }
        foreach ($this->all() as $store) {
            if ($store['slug'] === $slug) {
                return $store;
            }
        }
        return null;
    }

    public function findById(int $id): ?array
    {
        if ($this->db !== null) {
            $stmt = $this->db->prepare(
                'SELECT id, slug, name, description, website_url AS website FROM stores WHERE id = ? AND is_active = 1',
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row === false) {
                return null;
            }
            return [
                'id' => (int) $row['id'],
                'slug' => $row['slug'],
                'name' => $row['name'],
                'initial' => strtoupper(mb_substr($row['name'], 0, 1)),
                'description' => $row['description'] ?? '',
                'website' => $row['website'] ?? '',
                'offers_count' => 0,
                'featured' => false,
            ];
        }
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
