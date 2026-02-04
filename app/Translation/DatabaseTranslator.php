<?php

namespace App\Translation;

use Illuminate\Translation\Translator;

class DatabaseTranslator extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        // 1) Обычный перевод Laravel (файлы/lang, json и т.д.)
        $value = parent::get($key, $replace, $locale, $fallback);

        // Если ключ уже перевёлся или ключ не строка — выходим
        if (!is_string($key) || $value !== $key) {
            return $value;
        }

        // Если ключ в формате group.key — оставляем как есть (не лезем в "умную" подмену)
        if (str_contains($key, '.')) {
            return $value;
        }

        // 2) Наши legacy-ключи без точки: пробуем разные варианты ДОКАЗАННО нужные для твоей БД
        //    - сначала dictionary.<key>
        //    - потом dictionary.<KEY_UPPER> (потому что у тебя в БД встречаются PAGES_TITLE_MAIN)
        //    - потом dictionary.<key_lower> (на всякий)
        $candidates = [
            'dictionary.' . $key,
            'dictionary.' . strtoupper($key),
            'dictionary.' . strtolower($key),
        ];

        foreach ($candidates as $mappedKey) {
            $mappedValue = parent::get($mappedKey, $replace, $locale, $fallback);

            // если нашёл реальный перевод — возвращаем
            if ($mappedValue !== $mappedKey) {
                return $mappedValue;
            }
        }

        // 3) Если ничего не нашли — отдаём исходное (как Laravel и делает)
        return $value;
    }
}
