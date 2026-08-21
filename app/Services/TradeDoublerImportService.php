<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Str;
use PDO;

final class TradeDoublerImportService
{
    public function __construct(
        private readonly TradeDoublerClient $client,
        private readonly ?PDO $db,
    ) {
    }

    public function availableSites(): array
    {
        return $this->client->sites();
    }

    public function defaultSite(): string
    {
        return $this->client->defaultSite();
    }

    public function search(string $siteKey, string $system, array $filters): array
    {
        if ($system === 'PRODUCTS') {
            $result = $this->client->searchProducts($siteKey, $filters);
            $items = $this->attachTrackingUrls($result['products'], $siteKey);
            $items = $this->markAlreadyImported($items);
            return ['items' => $items, 'error' => $result['error']];
        }

        $result = $this->client->searchVouchers($siteKey, $filters);
        $items = $this->attachTrackingUrls($result['vouchers'], $siteKey);
        $items = $this->markAlreadyImported($items);
        return ['items' => $items, 'error' => $result['error']];
    }

    private function attachTrackingUrls(array $items, string $siteKey): array
    {
        $site = $this->client->site($siteKey);
        $websiteId = $site['website_id'] ?? '';

        foreach ($items as &$item) {
            $existingTrackingUrl = (string) self::firstOf($item, ['trackingUrl', 'program.trackingUrl'], '');
            if ($existingTrackingUrl !== '') {
                $item['trackingUrl'] = $existingTrackingUrl;
                continue;
            }

            $programId = (string) self::firstOf($item, ['program.id', 'programId', 'advertiser.id', 'advertiserId'], '');
            if ($programId === '' || $websiteId === '') {
                continue;
            }

            $trackingUrl = 'https://clk.tradedoubler.com/click?p=' . rawurlencode($programId) . '&a=' . rawurlencode((string) $websiteId);

            $deeplink = (string) self::firstOf($item, ['landingUrl', 'deepLink', 'productUrl', 'buyUrl', 'url', 'program.landingUrl'], '');
            if ($deeplink !== '') {
                $trackingUrl .= '&url=' . rawurlencode($deeplink);
            }

            $item['trackingUrl'] = $trackingUrl;
        }
        unset($item);

        return $items;
    }

    private function markAlreadyImported(array $items): array
    {
        if ($this->db === null || $items === []) {
            foreach ($items as &$item) {
                $item['_already_imported'] = false;
            }
            unset($item);
            return $items;
        }

        $externalIds = [];
        foreach ($items as $item) {
            $externalId = (string) self::firstOf($item, ['voucherId', 'productId', 'id'], '');
            if ($externalId !== '') {
                $externalIds[] = $externalId;
            }
        }
        $externalIds = array_values(array_unique($externalIds));

        $existingHashes = [];
        if ($externalIds !== []) {
            try {
                $placeholders = implode(',', array_fill(0, count($externalIds), '?'));
                $stmt = $this->db->prepare(
                    "SELECT external_id, external_hash FROM affiliate_mappings WHERE external_id IN ({$placeholders})"
                );
                $stmt->execute($externalIds);
                foreach ($stmt->fetchAll() as $row) {
                    $existingHashes[$row['external_id'] . '|' . $row['external_hash']] = true;
                }
            } catch (\Throwable $e) {
                error_log('TradeDoublerImportService::markAlreadyImported failed: ' . $e->getMessage());
            }
        }

        foreach ($items as &$item) {
            $externalId = (string) self::firstOf($item, ['voucherId', 'productId', 'id'], '');
            $code = (string) self::firstOf($item, ['code', 'voucherCode', 'couponCode'], '');
            $title = (string) self::firstOf($item, ['title', 'name', 'voucherTitle'], '');
            $hash = sha1($externalId . '|' . $code . '|' . $title);

            $item['_already_imported'] = isset($existingHashes[$externalId . '|' . $hash]);
        }
        unset($item);

        return $items;
    }

    private static function field(array $item, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $value = self::fieldValue($item, (string) $key);
            if ($value !== '' && $value !== null) {
                return $value;
            }
        }
        return $default;
    }

    private static function fieldValue(array $item, string $key)
    {
        if (array_key_exists($key, $item)) {
            return $item[$key];
        }

        if (! str_contains($key, '.')) {
            return null;
        }

        $cursor = $item;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    private static function nestedField(array $item, string $dotKey, $default = null)
    {
        if (array_key_exists($dotKey, $item) && $item[$dotKey] !== '' && $item[$dotKey] !== null) {
            return $item[$dotKey];
        }

        $parts = explode('.', $dotKey);
        $current = $item;
        foreach ($parts as $part) {
            if (! is_array($current) || ! array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return ($current !== null && $current !== '') ? $current : $default;
    }

    private static function firstOf(array $item, array $dotKeys, $default = null)
    {
        foreach ($dotKeys as $key) {
            $key = (string) $key;
            $value = str_contains($key, '.')
                ? self::nestedField($item, $key)
                : self::field($item, [$key]);

            if (is_array($value)) {
                continue;
            }

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    private static function toMysqlDate($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            $raw = (int) $raw;
            $seconds = $raw > 9999999999 ? intdiv($raw, 1000) : $raw;
            return date('Y-m-d H:i:s', $seconds);
        }
        $ts = strtotime((string) $raw);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }

    private static function toDecimal($raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (float) $raw;
        }
        $normalized = str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', (string) $raw) ?? '');
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private static function parseDiscount(array $item, string $title = ''): array
    {
        $discountObj = $item['discount'] ?? null;
        if (is_array($discountObj)) {
            $dtype = strtolower(trim((string) ($discountObj['type'] ?? '')));
            $dvalue = isset($discountObj['value']) ? self::toDecimal($discountObj['value']) : null;
            if ($dvalue !== null && $dvalue > 0) {
                if (in_array($dtype, ['percentage', 'percent', 'pct', '%'], true)) {
                    if ($dvalue <= 1) {
                        $dvalue *= 100;
                    }
                    return ['type' => 'PERCENT', 'value' => round($dvalue, 2)];
                }
                if (in_array($dtype, ['amount', 'fixed', 'fixedamount', 'cash', 'currency', 'euro', 'eur'], true)) {
                    return ['type' => 'AMOUNT', 'value' => round($dvalue, 2)];
                }
                if (str_contains($title, '%')) {
                    return ['type' => 'PERCENT', 'value' => round($dvalue, 2)];
                }
                if (preg_match('/€|\bEUR\b|\beuro\b/i', $title)) {
                    return ['type' => 'AMOUNT', 'value' => round($dvalue, 2)];
                }
                if ($dvalue <= 100) {
                    return ['type' => 'PERCENT', 'value' => round($dvalue, 2)];
                }
                return ['type' => 'AMOUNT', 'value' => round($dvalue, 2)];
            }
        }

        $percentRaw = self::firstOf($item, [
            'discountPercent', 'discountPercentage', 'percentOff', 'discountRate', 'percent',
        ]);
        $percentValue = self::toDecimal($percentRaw);
        if ($percentValue !== null && $percentValue > 0) {
            if ($percentValue <= 1) {
                $percentValue *= 100;
            }
            return ['type' => 'PERCENT', 'value' => round($percentValue, 2)];
        }

        $amountRaw = self::firstOf($item, [
            'discountAmount', 'amountOff', 'fixedDiscount', 'saving', 'savingAmount',
        ]);
        $amountValue = self::toDecimal($amountRaw);
        if ($amountValue !== null && $amountValue > 0) {
            return ['type' => 'AMOUNT', 'value' => round($amountValue, 2)];
        }

        $fallbackDiscount = $item['discount'] ?? null;
        $fallbackCandidates = [
            is_string($fallbackDiscount) || is_numeric($fallbackDiscount) ? $fallbackDiscount : null,
            self::field($item, ['discountText', 'savingText', 'offerText', 'value']),
        ];

        $anyValue = null;
        foreach ($fallbackCandidates as $rawCandidate) {
            if ($rawCandidate === null) {
                continue;
            }

            $text = (string) $rawCandidate;
            $numeric = self::toDecimal($rawCandidate);
            if ($numeric === null || $numeric <= 0) {
                continue;
            }
            if ($anyValue === null) {
                $anyValue = $numeric;
            }

            if (str_contains($text, '%')) {
                return ['type' => 'PERCENT', 'value' => round($numeric, 2)];
            }
            if (preg_match('/€|\bEUR\b|\beuro\b/i', $text)) {
                return ['type' => 'AMOUNT', 'value' => round($numeric, 2)];
            }
        }

        if ($anyValue !== null && $anyValue > 0) {
            if (str_contains($title, '%')) {
                return ['type' => 'PERCENT', 'value' => round($anyValue, 2)];
            }
            if (preg_match('/€|\bEUR\b|\beuro\b/i', $title)) {
                return ['type' => 'AMOUNT', 'value' => round($anyValue, 2)];
            }
        }

        return ['type' => 'NONE', 'value' => null];
    }

    /**
     * Importa un set di voucher/prodotti selezionati nel database.
     *
     * @param array<int, array<string, mixed>> $items
     * @param bool $markFeatured Se true, marca gli elementi importati come "in evidenza" (comparsa in home)
     * @return array{imported: int, duplicates: int, errors: int, messages: array<int, string>}
     */
    public function import(array $items, string $system = 'VOUCHERS', bool $markFeatured = false): array
    {
        if ($this->db === null) {
            return ['imported' => 0, 'duplicates' => 0, 'errors' => count($items), 'messages' => ['Database non connesso.']];
        }

        $networkId = $this->ensureNetwork();
        $imported = 0;
        $duplicates = 0;
        $errors = 0;
        $messages = [];

        foreach ($items as $item) {
            try {
                $externalId = (string) self::firstOf($item, ['voucherId', 'productId', 'id'], '');
                $programId = (string) self::firstOf($item, [
                    'program.id', 'programId', 'advertiser.id', 'advertiserId', 'merchant.id',
                ], '');
                $programName = (string) self::firstOf($item, [
                    'program.name', 'programName', 'advertiser.name', 'advertiserName',
                    'merchant.name', 'program', 'advertiser',
                ], 'Store senza nome');
                $title = (string) self::firstOf($item, ['title', 'name', 'voucherTitle'], $programName);
                $description = (string) self::firstOf($item, [
                    'description', 'shortDescription', 'voucherDescription', 'offerDescription',
                ], '');
                $code = (string) self::firstOf($item, ['code', 'voucherCode', 'couponCode'], '');
                $discount = self::parseDiscount($item, $title);
                $url = (string) self::firstOf($item, [
                    'trackingUrl', 'program.trackingUrl', 'clickUrl', 'buyUrl', 'deepLink', 'link', 'url',
                ], '');
                $startDate = self::toMysqlDate(self::firstOf($item, ['startDate', 'validFrom', 'start', 'startdate']));
                $endDate = self::toMysqlDate(self::firstOf($item, ['endDate', 'validTo', 'end', 'expiryDate', 'enddate']));
                $logoUrl = (string) self::firstOf($item, [
                    'program.logoUrl', 'program.logo', 'program.imageUrl', 'program.image',
                    'advertiser.logoUrl', 'advertiser.logo',
                    'logoUrl', 'advertiserLogoUrl', 'programLogo', 'imageUrl', 'logo',
                ], '');
                $storeDescription = (string) self::firstOf($item, [
                    'program.description', 'program.shortDescription', 'program.summary',
                    'advertiser.description', 'advertiser.shortDescription',
                    'programDescription', 'advertiserDescription',
                ], '');
                $categoryName = (string) self::firstOf($item, [
                    'program.categoryName', 'program.category',
                    'advertiser.categoryName', 'advertiser.category',
                    'category.name', 'categoryName', 'category',
                    'productCategoryName', 'programCategory',
                ], '');

                if ($externalId === '' || $url === '') {
                    $errors++;
                    $messages[] = "Elemento scartato ({$title}): dati incompleti (id o tracking link mancante).";
                    continue;
                }

                $hash = sha1($externalId . '|' . $code . '|' . $title);

                $check = $this->db->prepare(
                    'SELECT am.id FROM affiliate_mappings am WHERE am.external_id = ? AND am.external_hash = ? LIMIT 1'
                );
                $check->execute([$externalId, $hash]);
                if ($check->fetch()) {
                    $duplicates++;
                    continue;
                }

                $categoryId = $categoryName !== '' ? $this->ensureCategory($categoryName) : null;
                $storeId = $this->ensureStore($programName, $logoUrl, $storeDescription, $categoryId);
                $programDbId = $this->ensureProgram($networkId, $storeId, $programId, $programName);

                $slug = Str::slug($title . '-' . $externalId);
                $offerType = $code !== '' ? 'CODICE' : 'OFFERTA';

                $insertOffer = $this->db->prepare(
                    'INSERT INTO offers
                       (store_id, category_id, title, slug, description, offer_type, coupon_code, affiliate_url, external_id, dedupe_hash, status, badge, discount_type, discount_value, starts_at, expires_at, is_featured)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'ACTIVE\', ?, ?, ?, ?, ?, ?)'
                );
                $badgeValue = ($discount['value'] !== null)
                    ? rtrim(rtrim(number_format((float) $discount['value'], 2, '.', ''), '0'), '.')
                    : null;
                $insertOffer->execute([
                    $storeId,
                    $categoryId,
                    $title,
                    $slug,
                    $description,
                    $offerType,
                    $code !== '' ? $code : null,
                    $url,
                    $externalId,
                    $hash,
                    $badgeValue,
                    $discount['type'],
                    $discount['value'],
                    $startDate,
                    $endDate,
                    $markFeatured ? 1 : 0,
                ]);
                $offerId = (int) $this->db->lastInsertId();

                $insertMapping = $this->db->prepare(
                    'INSERT INTO affiliate_mappings (offer_id, program_id, external_id, external_hash)
                     VALUES (?, ?, ?, ?)'
                );
                $insertMapping->execute([
                    $offerId,
                    $programDbId,
                    $externalId,
                    $hash,
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $messages[] = 'Errore import elemento: ' . $e->getMessage();
                error_log('TradeDoublerImportService::import failed: ' . $e->getMessage());
            }
        }

        $this->logImport($networkId, count($items), $imported, $duplicates, $errors);

        return ['imported' => $imported, 'duplicates' => $duplicates, 'errors' => $errors, 'messages' => $messages];
    }

    private function ensureNetwork(): int
    {
        $stmt = $this->db->prepare('SELECT id FROM affiliate_networks WHERE slug = ? LIMIT 1');
        $stmt->execute(['tradedoubler']);
        $row = $stmt->fetch();
        if ($row) {
            return (int) $row['id'];
        }

        $insert = $this->db->prepare(
            'INSERT INTO affiliate_networks (name, slug, api_base_url, is_active) VALUES (?, ?, ?, 1)'
        );
        $insert->execute(['TradeDoubler', 'tradedoubler', 'https://api.tradedoubler.com/1.0']);
        return (int) $this->db->lastInsertId();
    }

    private function ensureStore(string $name, string $logoUrl = '', string $description = '', ?int $categoryId = null): int
    {
        $slug = Str::slug($name);
        $stmt = $this->db->prepare('SELECT id FROM stores WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) {
            // Aggiorna i campi mancanti se disponibili
            if ($logoUrl !== '' || $description !== '' || $categoryId !== null) {
                $updates = [];
                $params = [];
                if ($logoUrl !== '') {
                    $updates[] = 'logo_path = COALESCE(NULLIF(logo_path, \'\'), ?)';
                    $params[] = $logoUrl;
                }
                if ($description !== '') {
                    $updates[] = 'description = COALESCE(NULLIF(description, \'\'), ?)';
                    $params[] = $description;
                }
                if ($categoryId !== null) {
                    $updates[] = 'category_id = COALESCE(category_id, ?)';
                    $params[] = $categoryId;
                }
                if (! empty($updates)) {
                    $params[] = (int) $row['id'];
                    $this->db->prepare('UPDATE stores SET ' . implode(', ', $updates) . ' WHERE id = ?')
                        ->execute($params);
                }
            }
            return (int) $row['id'];
        }

        $insert = $this->db->prepare(
            'INSERT INTO stores (name, slug, description, logo_path, category_id, is_active) VALUES (?, ?, ?, ?, ?, 1)'
        );
        $insert->execute([
            $name,
            $slug,
            $description !== '' ? $description : null,
            $logoUrl !== '' ? $logoUrl : null,
            $categoryId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Crea (se non esiste) e restituisce l'id di una categoria a partire dal nome fornito da TradeDoubler.
     */
    private function ensureCategory(string $name): int
    {
        $slug = Str::slug($name);
        $stmt = $this->db->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) {
            return (int) $row['id'];
        }

        $insert = $this->db->prepare(
            'INSERT INTO categories (name, slug, is_active) VALUES (?, ?, 1)'
        );
        $insert->execute([$name, $slug]);
        return (int) $this->db->lastInsertId();
    }

    private function ensureProgram(int $networkId, int $storeId, string $externalProgramId, string $name): int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM affiliate_programs WHERE network_id = ? AND external_program_id = ? LIMIT 1'
        );
        $stmt->execute([$networkId, $externalProgramId]);
        $row = $stmt->fetch();
        if ($row) {
            $update = $this->db->prepare('UPDATE affiliate_programs SET last_synced_at = NOW() WHERE id = ?');
            $update->execute([(int) $row['id']]);
            return (int) $row['id'];
        }

        $insert = $this->db->prepare(
            'INSERT INTO affiliate_programs (network_id, store_id, external_program_id, name, status, last_synced_at)
             VALUES (?, ?, ?, ?, \'ACTIVE\', NOW())'
        );
        $insert->execute([$networkId, $storeId, $externalProgramId, $name]);
        return (int) $this->db->lastInsertId();
    }

    private function logImport(int $networkId, int $processed, int $imported, int $duplicates, int $errors): void
    {
        try {
            $status = $errors > 0 ? 'ERROR' : ($duplicates === $processed ? 'DUPLICATE' : 'UPDATED');
            $stmt = $this->db->prepare(
                'INSERT INTO import_logs (network_id, status, processed_count, duplicate_count, error_count)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$networkId, $status, $imported, $duplicates, $errors]);
        } catch (\Throwable $e) {
            error_log('TradeDoublerImportService::logImport failed: ' . $e->getMessage());
        }
    }
}