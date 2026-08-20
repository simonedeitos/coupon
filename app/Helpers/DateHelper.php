<?php

declare(strict_types=1);

namespace App\Helpers;

final class DateHelper
{
    private const MONTHS_IT = [
        1 => 'gennaio',
        2 => 'febbraio',
        3 => 'marzo',
        4 => 'aprile',
        5 => 'maggio',
        6 => 'giugno',
        7 => 'luglio',
        8 => 'agosto',
        9 => 'settembre',
        10 => 'ottobre',
        11 => 'novembre',
        12 => 'dicembre',
    ];

    /**
     * Returns the SEO month in Italian.
     * If today is >= day 25, returns next month's name to target upcoming searches.
     */
    public static function getSeoMonth(): string
    {
        $month = self::getSeoMonthNumber();
        return self::MONTHS_IT[$month];
    }

    /**
     * Returns the SEO year (current or next if month wraps to January).
     */
    public static function getSeoYear(): int
    {
        $now = new \DateTimeImmutable('now');
        $day = (int) $now->format('j');
        $month = (int) $now->format('n');
        $year = (int) $now->format('Y');

        if ($day >= 25 && $month === 12) {
            return $year + 1;
        }

        return $year;
    }

    /**
     * Returns a formatted SEO date string like "agosto 2026".
     */
    public static function getSeoDateString(): string
    {
        return self::getSeoMonth() . ' ' . self::getSeoYear();
    }

    /**
     * Format a date string (YYYY-MM-DD or datetime) in Italian format (DD/MM/YYYY).
     */
    public static function formatDate(string $date): string
    {
        try {
            $dt = new \DateTimeImmutable($date);
            return $dt->format('d/m/Y');
        } catch (\Exception) {
            return $date;
        }
    }

    private static function getSeoMonthNumber(): int
    {
        $now = new \DateTimeImmutable('now');
        $day = (int) $now->format('j');
        $month = (int) $now->format('n');

        if ($day >= 25) {
            return $month === 12 ? 1 : $month + 1;
        }

        return $month;
    }
}
