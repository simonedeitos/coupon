<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Str;
use App\Services\CacheService;
use PDO;

final class OfferRepository
{
    public function __construct(
        private readonly CacheService $cache,
        private readonly ?PDO $db = null,
    ) {
    }

    private static function mapRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'slug' => $r['slug'],
            'store_id' => (int) $r['store_id'],
            'category_id' => (int) ($r['category_id'] ?? 0),
            'type' => $r['offer_type'] ?? $r['type'] ?? 'OFFERTA',
            'title' => $r['title'],
            'description' => $r['description'] ?? '',
            'discount' => $r['badge'] ?? $r['discount'] ?? '',
            'discount_type' => $r['discount_type'] ?? null,
            'discount_value' => isset($r['discount_value']) && $r['discount_value'] !== null ? (float) $r['discount_value'] : null,
            'code' => $r['coupon_code'] ?? $r['code'] ?? '',
            'affiliate_url' => $r['affiliate_url'] ?? '',
            'status' => $r['status'],
            'featured' => (bool) ($r['is_featured'] ?? $r['featured'] ?? false),
            'badge' => $r['badge'] ?? $r['discount'] ?? '',
            'expires_at' => $r['expires_at'] ?? '',
            'clicks' => (int) ($r['click_count'] ?? $r['clicks'] ?? 0),
            'click_count' => (int) ($r['click_count'] ?? $r['clicks'] ?? 0),
            'priority' => (int) ($r['priority'] ?? 0),
            'external_id' => $r['external_id'] ?? '',
            'hash' => $r['dedupe_hash'] ?? $r['hash'] ?? '',
        ];
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function all(array $filters = []): array
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $sql = 'SELECT id, slug, store_id, category_id, offer_type, title, description, badge, coupon_code, affiliate_url, status, is_featured, expires_at, priority, external_id, dedupe_hash, click_count, discount_type, discount_value
                    FROM offers
                    WHERE 1 = 1';
            $params = [];

            if (! empty($filters['type'])) {
                $sql .= ' AND offer_type = ?';
                $params[] = strtoupper((string) $filters['type']);
            }

            if (! empty($filters['status'])) {
                $sql .= ' AND status = ?';
                $params[] = strtoupper((string) $filters['status']);
            }

            if (! empty($filters['search'])) {
                $sql .= ' AND (title LIKE ? ESCAPE \'\\\\\' OR description LIKE ? ESCAPE \'\\\\\')';
                $term = '%' . self::escapeLike((string) $filters['search']) . '%';
                $params[] = $term;
                $params[] = $term;
            }

            $sql .= ($filters['sort'] ?? '') === 'recenti'
                ? ' ORDER BY created_at DESC, priority DESC'
                : ' ORDER BY priority DESC, created_at DESC';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return array_map([self::class, 'mapRow'], $stmt->fetchAll());
        } catch (\Throwable $e) {
            error_log('OfferRepository::all failed: ' . $e->getMessage());
            return [];
        }
    }

    public function count(): int
    {
        if ($this->db === null) {
            return 0;
        }
        try {
            return (int) $this->db->query(
                "SELECT COUNT(*) FROM offers WHERE status = 'ACTIVE' AND (expires_at IS NULL OR expires_at > NOW())",
            )->fetchColumn();
        } catch (\Throwable $e) {
            error_log('OfferRepository::count failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function featured(int $limit = 3): array
    {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, COALESCE(rc.recent_clicks, 0) AS recent_clicks
                 FROM offers o
                 LEFT JOIN (
                    SELECT offer_id, COUNT(*) AS recent_clicks
                    FROM clicks
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY offer_id
                 ) rc ON rc.offer_id = o.id
                 WHERE o.status = 'ACTIVE' AND o.is_featured = 1
                   AND (o.expires_at IS NULL OR o.expires_at > NOW())
                 ORDER BY recent_clicks DESC, o.click_count DESC, o.priority DESC, o.created_at DESC
                 LIMIT ?",
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            if (! empty($rows)) {
                return array_map([self::class, 'mapRow'], $rows);
            }
            $stmt = $this->db->prepare(
                "SELECT o.*, COALESCE(rc.recent_clicks, 0) AS recent_clicks
                 FROM offers o
                 LEFT JOIN (
                    SELECT offer_id, COUNT(*) AS recent_clicks
                    FROM clicks
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY offer_id
                 ) rc ON rc.offer_id = o.id
                 WHERE o.status = 'ACTIVE'
                   AND (o.expires_at IS NULL OR o.expires_at > NOW())
                 ORDER BY recent_clicks DESC, o.click_count DESC, o.priority DESC, o.created_at DESC
                 LIMIT ?",
            );
            $stmt->execute([$limit]);
            return array_map([self::class, 'mapRow'], $stmt->fetchAll());
        } catch (\Throwable $e) {
            error_log('OfferRepository::featured failed: ' . $e->getMessage());
            return [];
        }
    }

    public function topClickedToday(int $limit = 10): array
    {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, COALESCE(tc.today_clicks, 0) AS today_clicks
                 FROM offers o
                 LEFT JOIN (
                    SELECT offer_id, COUNT(*) AS today_clicks
                    FROM clicks
                    WHERE DATE(created_at) = CURDATE()
                    GROUP BY offer_id
                 ) tc ON tc.offer_id = o.id
                 WHERE o.status = 'ACTIVE'
                   AND (o.expires_at IS NULL OR o.expires_at > NOW())
                 ORDER BY today_clicks DESC, o.click_count DESC, o.priority DESC, o.created_at DESC
                 LIMIT ?",
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            if (! empty($rows)) {
                return array_map([self::class, 'mapRow'], $rows);
            }
            $stmt = $this->db->prepare(
                "SELECT * FROM offers
                 WHERE status = 'ACTIVE' AND is_featured = 1
                   AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY priority DESC, created_at DESC
                 LIMIT ?",
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            if (! empty($rows)) {
                return array_map([self::class, 'mapRow'], $rows);
            }
            $stmt = $this->db->prepare(
                "SELECT * FROM offers
                 WHERE status = 'ACTIVE'
                   AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY click_count DESC, priority DESC, created_at DESC
                 LIMIT ?",
            );
            $stmt->execute([$limit]);
            return array_map([self::class, 'mapRow'], $stmt->fetchAll());
        } catch (\Throwable $e) {
            error_log('OfferRepository::topClickedToday failed: ' . $e->getMessage());
            return [];
        }
    }

    public function topToday(int $limit = 10): array
    {
        return $this->topClickedToday($limit);
    }

    public function latest(int $limit = 4): array
    {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM offers
                 WHERE status = 'ACTIVE'
                   AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY created_at DESC
                 LIMIT ?",
            );
            $stmt->execute([$limit]);
            return array_map([self::class, 'mapRow'], $stmt->fetchAll());
        } catch (\Throwable $e) {
            error_log('OfferRepository::latest failed: ' . $e->getMessage());
            return [];
        }
    }

    public function byCategory(int $categoryId): array
    {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM offers WHERE category_id = ? AND status = 'ACTIVE'
                   AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY priority DESC",
            );
            $stmt->execute([$categoryId]);
            return array_map([self::class, 'mapRow'], $stmt->fetchAll());
        } catch (\Throwable $e) {
            error_log('OfferRepository::byCategory failed: ' . $e->getMessage());
            return [];
        }
    }

    public function byStore(int $storeId): array
    {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM offers WHERE store_id = ? AND status = 'ACTIVE'
                   AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY priority DESC",
            );
            $stmt->execute([$storeId]);
            return array_map([self::class, 'mapRow'], $stmt->fetchAll());
        } catch (\Throwable $e) {
            error_log('OfferRepository::byStore failed: ' . $e->getMessage());
            return [];
        }
    }

    public function findBySlug(string $slug): ?array
    {
        if ($this->db === null) {
            return null;
        }
        try {
            $stmt = $this->db->prepare('SELECT * FROM offers WHERE slug = ?');
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row !== false ? self::mapRow($row) : null;
        } catch (\Throwable $e) {
            error_log('OfferRepository::findBySlug failed: ' . $e->getMessage());
            return null;
        }
    }

    public function findById(int $id): ?array
    {
        if ($this->db === null) {
            return null;
        }
        try {
            $stmt = $this->db->prepare('SELECT * FROM offers WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row !== false ? self::mapRow($row) : null;
        } catch (\Throwable $e) {
            error_log('OfferRepository::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    public function save(array $payload): array
    {
        if ($this->db === null) {
            return $payload;
        }

        $payload['slug'] = Str::slug($payload['title']);
        $featured = ! empty($payload['featured']) ? 1 : 0;
        $priority = (int) ($payload['priority'] ?? 50);
        $status = strtoupper((string) ($payload['status'] ?? 'DRAFT'));

        try {
            if (! empty($payload['id'])) {
                $stmt = $this->db->prepare(
                    'UPDATE offers SET store_id = ?, category_id = ?, offer_type = ?, title = ?, slug = ?, description = ?,
                        badge = ?, discount_type = ?, discount_value = ?, coupon_code = ?, affiliate_url = ?, status = ?, is_featured = ?, expires_at = ?, priority = ?
                     WHERE id = ?'
                );
                $discountType = strtoupper((string) ($payload['discount_type'] ?? 'NONE'));
                $discountValue = is_numeric($payload['discount_value'] ?? null) ? (float) $payload['discount_value'] : null;
                if ($discountType === 'NONE') {
                    $discountValue = null;
                }
                $stmt->execute([
                    (int) ($payload['store_id'] ?? 0),
                    (int) ($payload['category_id'] ?? 0),
                    strtoupper((string) ($payload['type'] ?? 'OFFERTA')),
                    $payload['title'],
                    $payload['slug'],
                    $payload['description'] ?? null,
                    $payload['discount'] ?? $payload['badge'] ?? null,
                    in_array($discountType, ['PERCENT', 'AMOUNT', 'NONE'], true) ? $discountType : 'NONE',
                    $discountValue,
                    $payload['code'] ?? null,
                    $payload['affiliate_url'] ?? null,
                    $status,
                    $featured,
                    $payload['expires_at'] ?? null,
                    $priority,
                    (int) $payload['id'],
                ]);
                return $payload;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO offers (store_id, category_id, offer_type, title, slug, description, badge, discount_type, discount_value, coupon_code, affiliate_url, status, is_featured, expires_at, priority)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $discountType = strtoupper((string) ($payload['discount_type'] ?? 'NONE'));
            $discountValue = is_numeric($payload['discount_value'] ?? null) ? (float) $payload['discount_value'] : null;
            if ($discountType === 'NONE') {
                $discountValue = null;
            }
            $stmt->execute([
                (int) ($payload['store_id'] ?? 0),
                (int) ($payload['category_id'] ?? 0),
                strtoupper((string) ($payload['type'] ?? 'OFFERTA')),
                $payload['title'],
                $payload['slug'],
                $payload['description'] ?? null,
                $payload['discount'] ?? $payload['badge'] ?? null,
                in_array($discountType, ['PERCENT', 'AMOUNT', 'NONE'], true) ? $discountType : 'NONE',
                $discountValue,
                $payload['code'] ?? null,
                $payload['affiliate_url'] ?? null,
                $status,
                $featured,
                $payload['expires_at'] ?? null,
                $priority,
            ]);
            $payload['id'] = (int) $this->db->lastInsertId();
            return $payload;
        } catch (\Throwable $e) {
            error_log('OfferRepository::save failed: ' . $e->getMessage());
            return $payload;
        }
    }

    public function delete(int $id): void
    {
        if ($this->db === null) {
            return;
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM offers WHERE id = ?');
            $stmt->execute([$id]);
        } catch (\Throwable $e) {
            error_log('OfferRepository::delete failed: ' . $e->getMessage());
        }
    }

    public function updateStatus(int $id, string $status): void
    {
        if ($this->db === null) {
            return;
        }
        try {
            $stmt = $this->db->prepare('UPDATE offers SET status = ? WHERE id = ?');
            $stmt->execute([strtoupper($status), $id]);
        } catch (\Throwable $e) {
            error_log('OfferRepository::updateStatus failed: ' . $e->getMessage());
        }
    }
}