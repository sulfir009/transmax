<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    /**
     * Преобразует "12.34" / 12 / "12" в копейки (int).
     * Без float-ошибок: лучше передавать строкой из БД/формы.
     */
    public static function uahToKopeks(string|int|float $uah): int
    {
        $s = trim((string) $uah);
        $s = str_replace(["\xC2\xA0", ' '], '', $s);
        $s = str_replace(',', '.', $s);

        if ($s === '') {
            return 0;
        }

        // Разрешаем: 123, 123.4, 123.45
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $s)) {
            throw new InvalidArgumentException("Invalid money format: {$s}");
        }

        [$hryvnia, $kop] = array_pad(explode('.', $s, 2), 2, '0');
        $kop = substr($kop, 0, 2);
        $kop = str_pad($kop, 2, '0');

        return ((int) $hryvnia) * 100 + (int) $kop;
    }

    /**
     * Эвристика для цен из БД:
     * - "12.34" => 1234 коп
     * - "95"    => 9500 коп (если сумма похожа на гривны)
     * - "9500"  => 9500 коп (если похоже на копейки)
     */
    public static function priceToKopeksFromDb(string|int|float $price): int
    {
        $s = trim((string) $price);
        $s = str_replace(["\xC2\xA0", ' '], '', $s);
        $s = str_replace(',', '.', $s);

        if ($s === '') {
            return 0;
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $s)) {
            throw new InvalidArgumentException("Invalid price format: {$s}");
        }

        if (str_contains($s, '.')) {
            return self::uahToKopeks($s);
        }

        return ((int) $s) * 100;
    }

    public static function kopeksToUahString(int $kopeks, bool $trimZeros = false): string
    {
        $sign = $kopeks < 0 ? '-' : '';
        $abs = abs($kopeks);
        $hryvnia = intdiv($abs, 100);
        $kop = $abs % 100;
        $formatted = $sign . $hryvnia . '.' . str_pad((string) $kop, 2, '0', STR_PAD_LEFT);

        if ($trimZeros) {
            return preg_replace('/\.00$/', '', $formatted);
        }

        return $formatted;
    }

    public static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    public static function min(int $a, int $b): int
    {
        return $a < $b ? $a : $b;
    }

    public static function max(int $a, int $b): int
    {
        return $a > $b ? $a : $b;
    }
}
