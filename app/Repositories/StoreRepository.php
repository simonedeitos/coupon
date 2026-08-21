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
        private readonly ?PDO $db = null,
    ) {
    }

    public function all(): array
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $rows = $this->db->query(
                'SELECT s.id, s.slug, s.name, s.description, s.website_url AS website,
                        s.is_featured, s.click_count,
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
                'click_count' => (int) ($r['click_count'] ?? 0),
                'featured' => (bool) $r['is_featured'],
            ], $rows);
        } catch (\Throwable $e) {
            error_log('StoreRepository::all failed: ' . $e->getMessage());
            return [];
        }
    }

    public function count(): int
    {
        if ($this->db === null) {
            return 0;
        }
        try {
            return (int) $this->db->query("SELECT COUNT(*) FROM stores WHERE is_active = 1")->fetchColumn();
        } catch (\Throwable $e) {
            error_log('StoreRepository::count failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function featured(int $limit = 5): array
    {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT s.id, s.slug, s.name, s.description, s.website_url AS website,
                        s.logo_path, s.click_count,
                        COALESCE(so.offers_count, 0) AS offers_count,
                        COALESCE(sc.recent_clicks, 0) AS recent_clicks
                 FROM stores s
                 LEFT JOIN (
                    SELECT store_id, COUNT(*) AS offers_count
                    FROM offers
                    WHERE status = \'ACTIVE\'
                    GROUP BY store_id
                 ) so ON so.store_id = s.id
                 LEFT JOIN (
                    SELECT store_id, COUNT(*) AS recent_clicks
                    FROM clicks
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY store_id
                 ) sc ON sc.store_id = s.id
                 WHERE s.is_active = 1 AND s.is_featured = 1
                 ORDER BY recent_clicks DESC, s.click_count DESC, offers_count DESC, s.name ASC
                 LIMIT ?',
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            if (! empty($rows)) {
                return array_map(static fn (array $r): array => self::mapStoreRow($r, true), $rows);
            }
            $stmt = $this->db->prepare(
                'SELECT s.id, s.slug, s.name, s.description, s.website_url AS website,
                        s.logo_path, s.click_count,
                        COALESCE(so.offers_count, 0) AS offers_count,
                        COALESCE(sc.recent_clicks, 0) AS recent_clicks
                 FROM stores s
                 LEFT JOIN (
                    SELECT store_id, COUNT(*) AS offers_count
                    FROM offers
                    WHERE status = \'ACTIVE\'
                    GROUP BY store_id
                 ) so ON so.store_id = s.id
                 LEFT JOIN (
                    SELECT store_id, COUNT(*) AS recent_clicks
                    FROM clicks
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY store_id
                 ) sc ON sc.store_id = s.id
                 WHERE s.is_active = 1
                 ORDER BY recent_clicks DESC, s.click_count DESC, offers_count DESC, s.name ASC
                 LIMIT ?',
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            return array_map(static fn (array $r): array => self::mapStoreRow($r, false), $rows);
        } catch (\Throwable $e) {
            error_log('StoreRepository::featured failed: ' . $e->getMessage());
            return [];
        }
    }

    private static function mapStoreRow(array $r, bool $featured): array
    {
        return [
            'id' => (int) $r['id'],
            'slug' => $r['slug'],
            'name' => $r['name'],
            'initial' => strtoupper(mb_substr($r['name'], 0, 1)),
            'description' => $r['description'] ?? '',
            'website' => $r['website'] ?? '',
            'logo_path' => $r['logo_path'] ?? '',
            'click_count' => (int) ($r['click_count'] ?? 0),
            'offers_count' => (int) ($r['offers_count'] ?? 0),
            'featured' => $featured,
        ];
    }

    public function findBySlug(string $slug): ?array
    {
        if ($this->db === null) {
            return null;
        }
        try {
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
        } catch (\Throwable $e) {
            error_log('StoreRepository::findBySlug failed: ' . $e->getMessage());
            return null;
        }
    }

    public function findById(int $id): ?array
    {
        if ($this->db === null) {
            return null;
        }
        try {
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
        } catch (\Throwable $e) {
            error_log('StoreRepository::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    public function save(array $payload): array
    {
        if ($this->db === null) {
            return $payload;
        }

        $payload['slug'] = Str::slug($payload['name']);
        $featured = ! empty($payload['featured']) ? 1 : 0;

        try {
            if (! empty($payload['id'])) {
                $stmt = $this->db->prepare(
                    'UPDATE stores SET name = ?, slug = ?, description = ?, website_url = ?, is_featured = ? WHERE id = ?'
                );
                $stmt->execute([
                    $payload['name'],
                    $payload['slug'],
                    $payload['description'] ?? null,
                    $payload['website'] ?? null,
                    $featured,
                    (int) $payload['id'],
                ]);
                return $payload;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO stores (name, slug, description, website_url, is_featured, is_active) VALUES (?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $payload['name'],
                $payload['slug'],
                $payload['description'] ?? null,
                $payload['website'] ?? null,
                $featured,
            ]);
            $payload['id'] = (int) $this->db->lastInsertId();
            return $payload;
        } catch (\Throwable $e) {
            error_log('StoreRepository::save failed: ' . $e->getMessage());
            return $payload;
        }
    }

    public function delete(int $id): void
    {
        if ($this->db === null) {
            return;
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM stores WHERE id = ?');
            $stmt->execute([$id]);
        } catch (\Throwable $e) {
            error_log('StoreRepository::delete failed: ' . $e->getMessage());
        }
    }
}