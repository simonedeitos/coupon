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
        } catch (\Throwable $e) {
            error_log('CategoryRepository::all failed: ' . $e->getMessage());
            return [];
        }
    }

    public function count(): int
    {
        if ($this->db === null) {
            return 0;
        }
        try {
            return (int) $this->db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
        } catch (\Throwable $e) {
            error_log('CategoryRepository::count failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function featured(int $limit = 6): array
    {
        if ($this->db === null) {
            return [];
        }
        try {
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
        } catch (\Throwable $e) {
            error_log('CategoryRepository::featured failed: ' . $e->getMessage());
            return [];
        }
    }

    public function findBySlug(string $slug): ?array
    {
        if ($this->db === null) {
            return null;
        }
        try {
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
        } catch (\Throwable $e) {
            error_log('CategoryRepository::findBySlug failed: ' . $e->getMessage());
            return null;
        }
    }

    public function save(array $payload): array
    {
        if ($this->db === null) {
            return $payload;
        }

        $payload['slug'] = Str::slug($payload['name']);

        try {
            if (! empty($payload['id'])) {
                $stmt = $this->db->prepare(
                    'UPDATE categories SET name = ?, slug = ?, icon = ?, description = ?, is_active = ? WHERE id = ?'
                );
                $stmt->execute([
                    $payload['name'],
                    $payload['slug'],
                    $payload['icon'] ?? null,
                    $payload['description'] ?? null,
                    ! empty($payload['is_active']) ? 1 : 1,
                    (int) $payload['id'],
                ]);
                return $payload;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO categories (name, slug, icon, description, is_active) VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $payload['name'],
                $payload['slug'],
                $payload['icon'] ?? null,
                $payload['description'] ?? null,
            ]);
            $payload['id'] = (int) $this->db->lastInsertId();
            return $payload;
        } catch (\Throwable $e) {
            error_log('CategoryRepository::save failed: ' . $e->getMessage());
            return $payload;
        }
    }

    public function delete(int $id): void
    {
        if ($this->db === null) {
            return;
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
        } catch (\Throwable $e) {
            error_log('CategoryRepository::delete failed: ' . $e->getMessage());
        }
    }
}