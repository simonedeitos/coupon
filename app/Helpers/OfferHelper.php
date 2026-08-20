<?php

declare(strict_types=1);

namespace App\Helpers;

final class OfferHelper
{
    public static function formatDiscount(array $offer): string
    {
        $discountType = strtoupper((string) ($offer['discount_type'] ?? 'PERCENT'));
        $badge = (string) ($offer['badge'] ?? $offer['discount'] ?? '');

        if ($badge === '' || $badge === '0' || $badge === '0%' || $badge === 'null') {
            return '';
        }

        // Estrai il valore numerico dal badge (può essere "10%", "10", "10€", etc.)
        $numericValue = (float) preg_replace('/[^0-9.]/', '', $badge);

        if ($numericValue <= 0) {
            return '';
        }

        // Format number: rimuovi solo gli zeri decimali superflui (non quelli interi).
        $formatted = rtrim(number_format($numericValue, 2, '.', ''), '0');
        $formatted = rtrim($formatted, '.');

        if ($discountType === 'AMOUNT') {
            return 'SCONTO ' . $formatted . '€';
        }

        // PERCENT (default)
        return 'SCONTO ' . $formatted . '%';
    }

    public static function formatExpiry(string $expiresAt): string
    {
        try {
            $dt = new \DateTimeImmutable($expiresAt);
            return $dt->format('d/m/Y');
        } catch (\Exception) {
            return $expiresAt;
        }
    }

    public static function getExpiryLabel(string $expiresAt): string
    {
        $days = DateHelper::daysUntilExpiry($expiresAt);
        if ($days < 0) {
            return 'Scaduto';
        }
        if ($days === 0) {
            return 'Scade oggi';
        }
        if ($days === 1) {
            return 'Scade domani';
        }
        if ($days <= 7) {
            return 'Scade tra ' . $days . ' giorni';
        }
        return 'Valido fino al ' . self::formatExpiry($expiresAt);
    }

    public static function getOfferTypeLabel(string $type): string
    {
        return match (strtoupper($type)) {
            'CODICE' => 'Codice sconto',
            'OFFERTA' => 'Offerta',
            default => $type,
        };
    }
}
