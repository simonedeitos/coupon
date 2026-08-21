<?php

declare(strict_types=1);

namespace App\Services;

final class TradeDoublerClient
{
    /**
     * @param array<string, array{label: string, website_id: string, tokens: array<string, string>}> $sites
     * @param array<string, string> $publisherTokens
     */
    public function __construct(
        private readonly string $apiBase,
        private readonly array $sites,
        private readonly array $publisherTokens,
        private readonly string $defaultSite,
    ) {
    }

    public function sites(): array
    {
        return $this->sites;
    }

    public function site(string $siteKey): ?array
    {
        return $this->sites[$siteKey] ?? null;
    }

    public function defaultSite(): string
    {
        return $this->defaultSite;
    }

    public function isConfigured(string $siteKey, string $system): bool
    {
        $site = $this->site($siteKey);
        if ($site === null) {
            return false;
        }
        return ($site['website_id'] ?? '') !== '' && ! empty($site['tokens'][$system] ?? '');
    }

    /**
     * Cerca voucher/coupon disponibili su TradeDoubler per un sito specifico.
     *
     * @return array{vouchers: array<int, array<string, mixed>>, error: ?string}
     */
    public function searchVouchers(string $siteKey, array $filters = []): array
    {
        return $this->request($siteKey, 'VOUCHERS', 'vouchers.json', $filters, 'vouchers');
    }

    /**
     * Cerca prodotti disponibili su TradeDoubler per un sito specifico.
     *
     * @return array{products: array<int, array<string, mixed>>, error: ?string}
     */
    public function searchProducts(string $siteKey, array $filters = []): array
    {
        $result = $this->request($siteKey, 'PRODUCTS', 'products.json', $filters, 'products');
        return ['products' => $result['vouchers'], 'error' => $result['error']];
    }

    private function request(string $siteKey, string $system, string $endpoint, array $filters, string $responseKey): array
    {
        $site = $this->site($siteKey);
        if ($site === null) {
            return ['vouchers' => [], 'error' => "Sito TradeDoubler '{$siteKey}' non configurato."];
        }

        $token = $site['tokens'][$system] ?? '';
        $websiteId = $site['website_id'] ?? '';

        if ($token === '' || $websiteId === '') {
            return ['vouchers' => [], 'error' => "Token o websiteId mancante per il sistema {$system} sul sito '{$site['label']}'. Verifica il file .env."];
        }

        $query = array_filter([
            'token' => $token,
            'websiteId' => $websiteId,
            'keywords' => $filters['keywords'] ?? null,
            'programId' => $filters['programId'] ?? null,
            'pageSize' => $filters['pageSize'] ?? 100,
            'page' => $filters['page'] ?? 1,
        ], static fn ($value): bool => $value !== null && $value !== '');

        $url = rtrim($this->apiBase, '/') . '/' . $endpoint . '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            return ['vouchers' => [], 'error' => 'Errore di connessione a TradeDoubler: ' . $curlError];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return ['vouchers' => [], 'error' => "TradeDoubler ha risposto con HTTP {$httpCode}: " . substr((string) $response, 0, 300)];
        }

        $data = json_decode((string) $response, true);
        if (! is_array($data)) {
            return ['vouchers' => [], 'error' => 'Risposta TradeDoubler non valida (JSON non decodificabile).'];
        }

        $items = $data[$responseKey]
            ?? ($data['data'][$responseKey] ?? null)
            ?? ($data['response'][$responseKey] ?? null)
            ?? ($data['result'][$responseKey] ?? null)
            ?? ($data[rtrim($responseKey, 's')] ?? null)
            ?? (array_is_list($data) ? $data : []);

        if (empty($items)) {
            error_log('[TradeDoubler] Response keys for ' . $responseKey . ': ' . implode(', ', array_keys($data)));
            error_log('[TradeDoubler] First 500 chars: ' . substr((string) json_encode($data), 0, 500));
        }

        return ['vouchers' => array_values($items), 'error' => null];
    }
}