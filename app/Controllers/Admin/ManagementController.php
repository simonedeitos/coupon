<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

final class ManagementController
{
    public function index(string $section): array
    {
        $data = $this->sectionData($section);
        $meta = app('seo')->meta(['title' => ucfirst($section) . ' admin', 'path' => '/admin/' . $section]);
        return response_view('admin/' . $section . '/index', $data + compact('meta'), 'admin');
    }

    public function form(string $section, ?int $id = null): array
    {
        $data = $this->sectionData($section);
        $item = null;
        if ($id !== null) {
            foreach ($data['items'] ?? [] as $candidate) {
                if ((int) $candidate['id'] === $id) {
                    $item = $candidate;
                }
            }
        }
        $meta = app('seo')->meta(['title' => ucfirst($section) . ' form', 'path' => '/admin/' . $section]);
        return response_view('admin/' . $section . '/form', $data + compact('item', 'meta', 'section'), 'admin');
    }

    public function save(string $section): array
    {
        $payload = $_POST;
        unset($payload['_token']);
        if ($section === 'offers') {
            $item = app('offerRepository')->save($payload);
        } elseif ($section === 'stores') {
            $item = app('storeRepository')->save($payload);
        } elseif ($section === 'categories') {
            $item = app('categoryRepository')->save($payload);
        } elseif ($section === 'settings') {
            $item = app('settingsRepository')->saveSection('system', $payload);
        } else {
            $item = app('settingsRepository')->saveSection('feature_flags', array_map(static fn ($value): bool => (bool) $value, $payload));
        }
        $entityId = isset($item['id']) ? (int) $item['id'] : null;
        $this->writeAudit('save:' . $section, $section, $entityId, ['payload_keys' => array_keys($payload)]);
        flash('success', ucfirst($section) . ' aggiornato.');
        return redirect('/admin/' . $section);
    }

    public function delete(string $section, int $id): array
    {
        if ($section === 'offers') {
            app('offerRepository')->delete($id);
        } elseif ($section === 'stores') {
            app('storeRepository')->delete($id);
        } elseif ($section === 'categories') {
            app('categoryRepository')->delete($id);
        }
        $this->writeAudit('delete:' . $section, $section, $id);
        flash('success', 'Elemento eliminato.');
        return redirect('/admin/' . $section);
    }

    public function status(int $id): array
    {
        $status = strtoupper((string) request_input('status', 'DRAFT'));
        app('offerRepository')->updateStatus($id, $status);
        $this->writeAudit('status:offers', 'offers', $id, ['status' => $status]);
        flash('success', 'Stato coupon aggiornato.');
        return redirect('/admin/offers');
    }

    private function writeAudit(string $action, string $entityType, ?int $entityId, array $extra = []): void
    {
        $user = app('auth')->user();
        $userId = isset($user['id']) ? (int) $user['id'] : null;
        $actor = $user['username'] ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        // Scrivi nel DB
        $db = app('db');
        if ($db !== null) {
            try {
                $db->prepare(
                    'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, payload, created_at)
                     VALUES (?, ?, ?, ?, INET6_ATON(?), ?, NOW())'
                )->execute([
                    $userId,
                    $action,
                    $entityType,
                    $entityId,
                    $ip,
                    json_encode(['actor' => $actor] + $extra, JSON_UNESCAPED_UNICODE),
                ]);
            } catch (\Throwable $e) {
                error_log('ManagementController::writeAudit DB failed: ' . $e->getMessage());
            }
        }

        // Mantieni anche il log su file come backup
        app('cache')->appendJsonLine('logs', 'audit.log', [
            'action' => $action,
            'actor' => $actor,
            'target' => $entityId ?? $entityType,
            'created_at' => date('c'),
        ] + $extra);
    }

    private function sectionData(string $section): array
    {
        return match ($section) {
            'offers' => ['items' => app('offerRepository')->all(), 'stores' => app('storeRepository')->all(), 'categories' => app('categoryRepository')->all(), 'statuses' => ['DRAFT', 'ACTIVE', 'SCHEDULED', 'EXPIRED']],
            'stores' => ['items' => app('storeRepository')->all()],
            'categories' => ['items' => app('categoryRepository')->all()],
            'settings' => ['items' => [], 'settings' => app('settingsRepository')->section('system')],
            'feature-flags' => ['items' => [], 'flags' => app('settingsRepository')->section('feature_flags')],
            default => ['items' => []],
        };
    }
}
