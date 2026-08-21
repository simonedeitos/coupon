<?php

declare(strict_types=1);

namespace App\Helpers;

final class OfferHelper
{
    public static function formatDiscount(array $offer): string
    {
        $discountType = strtoupper((string) ($offer['discount_type'] ?? 'NONE'));
        $rawValue = $offer['discount_value'] ?? null;
        $discountValue = is_numeric($rawValue) ? (float) $rawValue : null;

        if ($discountValue === null || $discountValue <= 0) {
            return '';
        }

        if ($discountType === 'PERCENT') {
            return 'SCONTO ' . rtrim(rtrim(number_format($discountValue, 2, '.', ''), '0'), '.') . '%';
        }

        if ($discountType === 'AMOUNT') {
            return 'SCONTO ' . rtrim(rtrim(number_format($discountValue, 2, '.', ''), '0'), '.') . '€';
        }

        return '';
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
