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

            $programId = (string) self::nestedField($item, ['program.id', 'programId', 'advertiser.id'], '');
            if ($programId === '' || $websiteId === '') {
                continue;
            }

            $trackingUrl = 'https://clk.tradedoubler.com/click?p=' . rawurlencode($programId) . '&a=' . rawurlencode((string) $websiteId);

            $deeplink = (string) self::nestedField($item, ['landingUrl', 'deepLink', 'productUrl', 'url'], '');
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

    /**
     * Legge un campo dalla struttura nested usando dot-notation.
     * Es: nestedField($item, 'program.logoUrl') legge $item['program']['logoUrl']
     * Supporta anche array piatti (fallback a field() se non c'è punto).
     */
    private static function nestedField(array $item, array $dotKeys, $default = null)
    {
        foreach ($dotKeys as $dotKey) {
            if (!str_contains($dotKey, '.')) {
                // campo flat
                $val = $item[$dotKey] ?? null;
                if ($val !== null && $val !== '' && !is_array($val)) {
                    return $val;
                }
                continue;
            }
            [$parent, $child] = explode('.', $dotKey, 2);
            if (isset($item[$parent]) && is_array($item[$parent])) {
                $val = $item[$parent][$child] ?? null;
                if ($val !== null && $val !== '' && !is_array($val)) {
                    return $val;
                }
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
                $externalId     = (string) self::field($item, ['voucherId', 'productId', 'id'], '');
                $title          = (string) self::nestedField($item, ['title', 'name'], '');

                // Dati programma/negozio — legge prima da oggetto nested, poi flat
                $programId      = (string) self::nestedField($item, ['program.id', 'programId', 'advertiser.id', 'advertiserID'], '');
                $programName    = (string) self::nestedField($item, [
                    'program.name', 'programName', 'advertiser.name', 'advertiserName', 'merchant.name',
                ], 'Store senza nome');

                // Logo negozio
                $logoUrl        = (string) self::nestedField($item, [
                    'program.logoUrl', 'program.logo', 'program.imageUrl',
                    'advertiser.logoUrl', 'advertiser.logo',
                    'logoUrl', 'advertiserLogoUrl', 'programLogo', 'imageUrl', 'logo',
                ], '');

                // Descrizione BREVE negozio (non usare description dell'offerta per il negozio)
                $storeDescription = (string) self::nestedField($item, [
                    'program.description', 'program.shortDescription',
                    'advertiser.description', 'advertiser.shortDescription',
                    'programDescription', 'advertiserDescription',
                ], '');

                // Categoria
                $categoryName   = (string) self::nestedField($item, [
                    'program.categoryName', 'program.category',
                    'advertiser.categoryName', 'advertiser.category',
                    'categoryName', 'category', 'category.name',
                ], '');

                // Dati offerta
                $description    = (string) self::field($item, ['description', 'shortDescription'], '');
                $code           = (string) self::field($item, ['code', 'voucherCode'], '');
                $url            = (string) self::field($item, ['trackingUrl', 'clickUrl', 'deepLink', 'link', 'url'], '');
                $startDate      = self::toMysqlDate(self::field($item, ['startDate', 'validFrom', 'start']));
                $endDate        = self::toMysqlDate(self::field($item, ['endDate', 'validTo', 'end']));

                // Sconto — usa il nuovo parseDiscount con il titolo per interpretazione simbolo
                $discount       = self::parseDiscount($item, $title);

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
                       (store_id, category_id, title, slug, description, offer_type, coupon_code,
                        affiliate_url, external_id, dedupe_hash, status, badge,
                        discount_type, discount_value, starts_at, expires_at, is_featured)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'ACTIVE\', ?, ?, ?, ?, ?, ?)'
                );
                $discountBadge = $discount['value'] !== null ? (string) $discount['value'] : null;
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
                    $discountBadge,
                    $discount['type'],
                    $discount['value'],
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
     * Analizza lo sconto dall'item TradeDoubler.
     * Restituisce ['type' => 'PERCENT'|'AMOUNT'|'NONE', 'value' => float|null]
     *
     * Priorità:
     * 1. Struttura nested ufficiale: discount.type + discount.value
     * 2. Campi flat strutturati (discountPercent, discountAmount, ecc.)
     * 3. Campo testuale con simbolo esplicito (%, €)
     * 4. Lettura del simbolo dal titolo dell'offerta
     * 5. NONE se non determinabile
     */
    private static function parseDiscount(array $item, string $title = ''): array
    {
        // ── STEP 1: struttura nested ufficiale { "discount": { "type": "...", "value": ... } } ──
        $discountObj = $item['discount'] ?? null;
        if (is_array($discountObj)) {
            $dtype  = strtolower(trim((string) ($discountObj['type'] ?? '')));
            $dvalue = isset($discountObj['value']) && is_numeric($discountObj['value'])
                ? (float) $discountObj['value']
                : null;

            if ($dvalue !== null && $dvalue > 0) {
                // Converti frazione decimale (0.20 → 20%)
                if (in_array($dtype, ['percentage', 'percent', 'pct'], true)) {
                    if ($dvalue <= 1.0) { $dvalue = round($dvalue * 100, 2); }
                    return ['type' => 'PERCENT', 'value' => round($dvalue, 2)];
                }
                if (in_array($dtype, ['amount', 'fixed', 'fixedamount', 'cash', 'currency', 'eur', 'euro'], true)) {
                    return ['type' => 'AMOUNT', 'value' => round($dvalue, 2)];
                }
                // Tipo presente ma non riconosciuto: tenta simbolo nel titolo
                if (str_contains($title, '%')) {
                    if ($dvalue <= 1.0) { $dvalue = round($dvalue * 100, 2); }
                    return ['type' => 'PERCENT', 'value' => round($dvalue, 2)];
                }
                if (preg_match('/€|\bEUR\b|\beuro\b/i', $title)) {
                    return ['type' => 'AMOUNT', 'value' => round($dvalue, 2)];
                }
            }
        }

        // ── STEP 2: campi flat strutturati PERCENT ──
        $percentRaw = self::field($item, [
            'discountPercent', 'discountPercentage', 'percentOff', 'discountRate', 'percent',
        ], null);
        if ($percentRaw !== null && is_numeric($percentRaw)) {
            $v = (float) $percentRaw;
            if ($v > 0) {
                if ($v <= 1.0) { $v = round($v * 100, 2); }
                return ['type' => 'PERCENT', 'value' => round($v, 2)];
            }
        }

        // ── STEP 3: campi flat strutturati AMOUNT ──
        $amountRaw = self::field($item, [
            'discountAmount', 'amountOff', 'fixedDiscount', 'savingAmount', 'saving',
        ], null);
        if ($amountRaw !== null && is_numeric($amountRaw)) {
            $v = (float) $amountRaw;
            if ($v > 0) {
                return ['type' => 'AMOUNT', 'value' => round($v, 2)];
            }
        }

        // ── STEP 4: campo testuale/numerico generico con simbolo ──
        // Usa il campo flat "discount" solo se è stringa o numero (non array, già gestito)
        $discountFlat = $item['discount'] ?? null;
        $fallbackSources = [
            self::field($item, ['discountText', 'savingText', 'offerText'], null),
            (is_string($discountFlat) || is_numeric($discountFlat)) ? $discountFlat : null,
        ];

        foreach ($fallbackSources as $raw) {
            if ($raw === null) { continue; }
            $rawStr = (string) $raw;
            $num = null;
            if (is_numeric($raw)) {
                $num = (float) $raw;
            } else {
                $clean = preg_replace('/[^0-9.,\-]/', '', $rawStr);
                $clean = str_replace(',', '.', $clean ?? '');
                if (is_numeric($clean)) { $num = (float) $clean; }
            }
            if ($num === null || $num <= 0) { continue; }

            if (str_contains($rawStr, '%')) {
                return ['type' => 'PERCENT', 'value' => round($num, 2)];
            }
            if (preg_match('/€|\bEUR\b|\beuro\b/i', $rawStr)) {
                return ['type' => 'AMOUNT', 'value' => round($num, 2)];
            }

            // ── STEP 5: simbolo dal TITOLO ──
            if ($title !== '') {
                if (str_contains($title, '%')) {
                    return ['type' => 'PERCENT', 'value' => round($num, 2)];
                }
                if (preg_match('/€|\bEUR\b|\beuro\b/i', $title)) {
                    return ['type' => 'AMOUNT', 'value' => round($num, 2)];
                }
            }
        }

        if ($title !== '') {
            if (preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*%/u', $title, $matches) === 1) {
                $num = (float) str_replace(',', '.', $matches[1]);
                if ($num > 0) {
                    return ['type' => 'PERCENT', 'value' => round($num, 2)];
                }
            }
            if (preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*(€|\bEUR\b|\beuro\b)/iu', $title, $matches) === 1) {
                $num = (float) str_replace(',', '.', $matches[1]);
                if ($num > 0) {
                    return ['type' => 'AMOUNT', 'value' => round($num, 2)];
                }
            }
            if (preg_match('/(€|\bEUR\b|\beuro\b)\s*([0-9]+(?:[.,][0-9]+)?)/iu', $title, $matches) === 1) {
                $num = (float) str_replace(',', '.', $matches[2]);
                if ($num > 0) {
                    return ['type' => 'AMOUNT', 'value' => round($num, 2)];
                }
            }
        }

        return ['type' => 'NONE', 'value' => null];
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