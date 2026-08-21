<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

final class TradeDoublerController
{
    public function search(): array
    {
        $import = app('tradeDoublerImport');
        $sites = $import->availableSites();
        $siteKey = (string) request_input('site', $import->defaultSite());
        $system = strtoupper((string) request_input('system', 'VOUCHERS'));
        $keywords = trim((string) request_input('keywords', ''));
        $page = max(1, (int) request_input('page', 1));

        if (! in_array($system, ['VOUCHERS', 'PRODUCTS'], true)) {
            $system = 'VOUCHERS';
        }
        if (! isset($sites[$siteKey])) {
            $siteKey = $import->defaultSite();
        }

        $response = $import->search($siteKey, $system, [
            'keywords' => $keywords !== '' ? $keywords : null,
            'pageSize' => 50,
            'page' => $page,
        ]);

        $results = $response['items'];
        $error = $response['error'];

        $meta = app('seo')->meta(['title' => 'Importa da TradeDoubler', 'path' => '/admin/tradedoubler']);
        return response_view('admin/tradedoubler/search', compact('sites', 'siteKey', 'system', 'keywords', 'results', 'error', 'page', 'meta'), 'admin');
    }

    public function import(): array
    {
        $system = strtoupper((string) request_input('system', 'VOUCHERS'));
        $markFeatured = ! empty($_POST['featured']);
        $selectedIndexes = $_POST['selected'] ?? [];
        $payloadJson = $_POST['payload'] ?? [];

        $items = [];
        foreach ($selectedIndexes as $index) {
            if (isset($payloadJson[$index])) {
                $decoded = json_decode((string) $payloadJson[$index], true);
                if (is_array($decoded)) {
                    $items[] = $decoded;
                }
            }
        }

        if ($items === []) {
            flash('error', 'Nessun elemento selezionato per l\'importazione.');
            return redirect('/admin/tradedoubler');
        }

        $result = app('tradeDoublerImport')->import($items, $system, $markFeatured);
        $this->writeAudit('import:tradedoubler', 'offers', null, [
            'system' => $system,
            'imported' => $result['imported'],
            'duplicates' => $result['duplicates'],
            'errors' => $result['errors'],
        ]);

        app('cache')->appendJsonLine('logs', 'audit.log', [
            'action' => 'tradedoubler:import',
            'actor' => app('auth')->user()['username'] ?? 'guest',
            'system' => $system,
            'imported' => $result['imported'],
            'duplicates' => $result['duplicates'],
            'errors' => $result['errors'],
            'created_at' => date('c'),
        ]);

        $message = "Importati: {$result['imported']} · Duplicati saltati: {$result['duplicates']} · Errori: {$result['errors']}";
        flash($result['errors'] > 0 ? 'error' : 'success', $message);

        return redirect('/admin/tradedoubler');
    }

    private function writeAudit(string $action, string $entityType, ?int $entityId, array $payload = []): void
    {
        $db = app('db');
        if ($db === null) {
            return;
        }
        $user = app('auth')->user();
        try {
            $db->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, payload, created_at)
                 VALUES (?, ?, ?, ?, INET6_ATON(?), ?, NOW())'
            )->execute([
                isset($user['id']) ? (int) $user['id'] : null,
                $action,
                $entityType,
                $entityId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            error_log('TradeDoublerController::writeAudit failed: ' . $e->getMessage());
        }
    }
}