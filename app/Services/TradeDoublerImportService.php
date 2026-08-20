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
            if (! empty($item['trackingUrl'])) {
                continue;
            }

            $programId = (string) self::field($item, ['programId'], '');
            if ($programId === '' || $websiteId === '') {
                continue;
            }

            $trackingUrl = 'https://clk.tradedoubler.com/click?p=' . rawurlencode($programId) . '&a=' . rawurlencode((string) $websiteId);

            $deeplink = (string) self::field($item, ['landingUrl', 'deepLink', 'productUrl', 'url'], '');
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
            $externalId = (string) self::field($item, ['voucherId', 'productId', 'id'], '');
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
            $externalId = (string) self::field($item, ['voucherId', 'productId', 'id'], '');
            $code = (string) self::field($item, ['code', 'voucherCode'], '');
            $title = (string) self::field($item, ['title', 'name'], '');
            $hash = sha1($externalId . '|' . $code . '|' . $title);

            $item['_already_imported'] = isset($existingHashes[$externalId . '|' . $hash]);
        }
        unset($item);

        return $items;
    }

    private static function field(array $item, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($item[$key]) && $item[$key] !== '' && $item[$key] !== null) {
                return $item[$key];
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

    private static function formatDiscount($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            $num = (float) $raw;
            if ($num > 0 && $num <= 1) {
                $percent = round($num * 100, 2);
                $formatted = rtrim(rtrim((string) $percent, '0'), '.');
                return $formatted . '%';
            }
            return (string) $raw . '%';
        }
        return (string) $raw;
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
                $externalId = (string) self::field($item, ['voucherId', 'productId', 'id'], '');
                $programId = (string) self::field($item, ['programId'], '');
                $programName = (string) self::field($item, ['programName', 'program', 'advertiserName'], 'Store senza nome');
                $title = (string) self::field($item, ['title', 'name'], $programName);
                $description = (string) self::field($item, ['description', 'shortDescription'], '');
                $code = (string) self::field($item, ['code', 'voucherCode'], '');
                $discountRaw = self::field($item, ['discount', 'discountAmount', 'value', 'discountValue', 'discountText'], null);
                $discountType = self::detectDiscountType($item, $discountRaw);
                $discount = self::formatDiscountValue($discountRaw, $discountType);
                $url = (string) self::field($item, ['trackingUrl', 'clickUrl', 'deepLink', 'link', 'url'], '');
                $startDate = self::toMysqlDate(self::field($item, ['startDate', 'validFrom', 'start']));
                $endDate = self::toMysqlDate(self::field($item, ['endDate', 'validTo', 'end']));
                $categoryName = (string) self::field($item, ['categoryName', 'category'], '');
                $logoUrl = (string) self::field($item, ['logoUrl', 'imageUrl', 'logo', 'programLogoUrl'], '');
                $storeDescription = (string) self::field($item, ['programDescription', 'advertiserDescription', 'description'], '');

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

                $storeId = $this->ensureStore($programName, $storeDescription, $logoUrl, $categoryName);
                $programDbId = $this->ensureProgram($networkId, $storeId, $programId, $programName);
                $categoryId = $categoryName !== '' ? $this->ensureCategory($categoryName) : null;

                $slug = Str::slug($title . '-' . $externalId);
                $offerType = $code !== '' ? 'CODICE' : 'OFFERTA';

                $insertOffer = $this->db->prepare(
                    'INSERT INTO offers
                        (store_id, category_id, title, slug, description, offer_type, coupon_code, affiliate_url, external_id, dedupe_hash, status, badge, discount_type, starts_at, expires_at, is_featured)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'ACTIVE\', ?, ?, ?, ?, ?)'
                );
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
                    $discount,
                    $discountType,
                    $startDate,
                    $endDate,
                    $markFeatured ? 1 : 0,
                ]);
                $offerId = (int) $this->db->lastInsertId();

                $insertMapping = $this->db->prepare(
                    'INSERT INTO affiliate_mappings (offer_id, program_id, external_id, external_hash, raw_payload)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $insertMapping->execute([
                    $offerId,
                    $programDbId,
                    $externalId,
                    $hash,
                    json_encode($item, JSON_UNESCAPED_UNICODE),
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

    private function ensureStore(string $name, string $description = '', string $logoUrl = '', string $categoryName = ''): int
    {
        $slug = Str::slug($name);
        $stmt = $this->db->prepare('SELECT id FROM stores WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) {
            $storeId = (int) $row['id'];
            // Aggiorna solo i campi attualmente NULL o vuoti (non sovrascrivere dati manualmente curati).
            $updates = [];
            $params = [];
            if ($description !== '') {
                $updates[] = 'description = IF(description IS NULL OR description = \'\', ?, description)';
                $params[] = $description;
            }
            if ($logoUrl !== '') {
                $updates[] = 'logo_path = IF(logo_path IS NULL OR logo_path = \'\', ?, logo_path)';
                $params[] = $logoUrl;
            }
            if ($categoryName !== '') {
                $catId = $this->ensureCategory($categoryName);
                $updates[] = 'category_id = IF(category_id IS NULL, ?, category_id)';
                $params[] = $catId;
            }
            if ($updates !== []) {
                $params[] = $storeId;
                $this->db->prepare('UPDATE stores SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
            }
            return $storeId;
        }

        $categoryId = $categoryName !== '' ? $this->ensureCategory($categoryName) : null;

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
     * Detecta il tipo di sconto dall'item del feed.
     * Restituisce 'PERCENT' o 'AMOUNT'.
     */
    private static function detectDiscountType(array $item, $raw): string
    {
        // Se il feed fornisce esplicitamente il tipo
        $type = strtoupper((string) self::field($item, ['discountType', 'valueType', 'type', 'discountUnit'], ''));
        if (in_array($type, ['PERCENT', 'PERCENTAGE', '%'], true)) {
            return 'PERCENT';
        }
        if (in_array($type, ['AMOUNT', 'FIXED', 'EUR', 'EURO', '€'], true)) {
            return 'AMOUNT';
        }

        // Controlla se il valore raw contiene simboli espliciti
        $rawStr = (string) $raw;
        if (str_contains($rawStr, '€') || str_contains($rawStr, 'EUR')) {
            return 'AMOUNT';
        }
        if (str_contains($rawStr, '%')) {
            return 'PERCENT';
        }

        // Euristico: se è un numero e supera 90, è quasi certamente un importo fisso, non una percentuale
        if (is_numeric($raw)) {
            $num = (float) $raw;
            if ($num > 90) {
                return 'AMOUNT';
            }
        }

        return 'PERCENT';
    }

    private static function formatDiscountValue($raw, string $discountType): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            $num = (float) $raw;
            if ($num <= 0) {
                return null;
            }
            if ($num > 0 && $num <= 1 && $discountType === 'PERCENT') {
                $percent = round($num * 100, 2);
                $formatted = rtrim(number_format($percent, 2, '.', ''), '0');
                $formatted = rtrim($formatted, '.');
                return $formatted . '%';
            }
            if ($discountType === 'AMOUNT') {
                $formatted = rtrim(number_format($num, 2, '.', ''), '0');
                return rtrim($formatted, '.');
            }
            $formatted = rtrim(number_format($num, 2, '.', ''), '0');
            return rtrim($formatted, '.') . '%';
        }
        // Rimuovi simboli per estrarre il valore numerico
        $clean = trim(str_replace(['€', 'EUR', '%', ' '], '', (string) $raw));
        return $clean !== '' ? (string) $raw : null;
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
                'INSERT INTO import_logs (network_id, network_name, status, processed_count, duplicate_count, error_count)
                 VALUES (?, \'TradeDoubler\', ?, ?, ?, ?)'
            );
            $stmt->execute([$networkId, $status, $imported, $duplicates, $errors]);
        } catch (\Throwable $e) {
            error_log('TradeDoublerImportService::logImport failed: ' . $e->getMessage());
        }
    }
}