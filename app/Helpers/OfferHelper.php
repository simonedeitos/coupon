<?php

declare(strict_types=1);

namespace App\Helpers;

final class OfferHelper
{
    public static function formatDiscount(array $offer): string
    {
        $discountType = strtoupper((string) ($offer['discount_type'] ?? ''));
        $discount = trim((string) ($offer['discount'] ?? ''));
        $numericValue = is_numeric($discount) ? (float) $discount : null;

        // Se abbiamo tipo esplicito e valore numerico > 0
        if ($discountType === 'PERCENT' && $numericValue !== null && $numericValue > 0) {
            return 'SCONTO ' . rtrim(rtrim(number_format($numericValue, 2), '0'), '.') . '%';
        }
        if ($discountType === 'AMOUNT' && $numericValue !== null && $numericValue > 0) {
            return 'SCONTO ' . rtrim(rtrim(number_format($numericValue, 2), '0'), '.') . '€';
        }

        // Valore già formattato (es. "20%", "10€", "5 EUR")
        if ($discount !== '' && $discount !== '0' && $discount !== 'null') {
            // Contiene già simbolo % o € -> restituisci come badge SCONTO
            if (str_contains($discount, '%') || preg_match('/[€$£]/', $discount)) {
                return 'SCONTO ' . $discount;
            }
            // Valore numerico senza tipo: tenta di interpretarlo come percentuale solo se <= 100
            if ($numericValue !== null && $numericValue > 0) {
                return 'SCONTO ' . rtrim(rtrim(number_format($numericValue, 2), '0'), '.') . '%';
            }
            // Stringa libera (es. "Gratis spedizione") -> restituisci così
            return $discount;
        }

        // Nessun valore di sconto -> non mostrare nulla
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
