<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    /**
     * Преобразует "12.34" / 12.34 / 12 / "12" в копейки (int).
     * Без float-ошибок: лучше передавать строкой из БД/формы.
     */
    public static function uahToKop(string|int|float $uah): int
    {
        $s = trim((string)$uah);
        $s = str_replace(',', '.', $s);

        // Разрешаем: 123, 123.4, 123.45
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $s)) {
            throw new InvalidArgumentException("Invalid money format: {$s}");
        }

        [$hryvnia, $kop] = array_pad(explode('.', $s, 2), 2, '0');
        $kop = substr($kop, 0, 2);
        $kop = str_pad($kop, 2, '0');

        return ((int)$hryvnia) * 100 + (int)$kop;
    }

    public static function kopToUah(int $kop): string
    {
        // строкой, чтобы UI совпадал 1-в-1
        return number_format($kop / 100, 2, '.', '');
    }
}
