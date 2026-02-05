<?php

namespace App\Translation;

use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class DatabaseTranslator extends Translator
{
    private static array $missingLogged = [];

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        if (is_string($key)) {
            $key = $this->normalizeDictionaryKey($key);
        }
        $value = parent::get($key, $replace, $locale, $fallback);
        $resolvedLocale = $locale ?? app()->getLocale();

        // key в Laravel обычно string, но оставим защиту
        if (!is_string($key)) {
            return $value;
        }

        // Если переводчик вернул массив (например запросили группу), это НЕ missing, просто отдаём как есть
        if (is_array($value)) {
            // при желании можно логировать отдельным каналом, но обычно не надо
            return $value;
        }

        // На этом этапе $value либо string, либо что-то ещё (например null) — нормализуем
        if (!is_string($value)) {
            // лучше вернуть как есть, но чтобы не ломать вывод - приводим к строке
            $value = (string)$value;
        }

        // Если нашёлся реальный перевод — возвращаем, но при этом можем логировать "сломанные" dictionary.* вида key==value
        if ($value !== $key) {
            if ($this->isDictionaryKeyMissing($key, $value)) {
                $this->logMissingTranslation($key, $resolvedLocale);
            }
            return $value;
        }

        // ВАЖНО:
        // Если ключ С ТОЧКОЙ (booking.index / dictionary.schedule.xxx) — НЕ пытайся мапить в dictionary.*
        // Твой кейс: booking.index — это валидный ключ, и если он не найден, просто логируем и возвращаем key
        if (str_contains($key, '.')) {
            $this->logMissingTranslation($key, $resolvedLocale);
            return $value;
        }

        // Legacy ключи без точки: пробуем кандидатов внутри dictionary.*
        $candidates = [
            'dictionary.' . $key,
            'dictionary.' . strtoupper($key),
            'dictionary.' . strtolower($key),
        ];

        foreach ($candidates as $mappedKey) {
            $mappedValue = parent::get($mappedKey, $replace, $locale, $fallback);

            if (is_array($mappedValue)) {
                // если вдруг вернули группу - это не подходит как строковый перевод
                continue;
            }

            $mappedValue = is_string($mappedValue) ? $mappedValue : (string)$mappedValue;

            if ($mappedValue !== $mappedKey) {
                return $mappedValue;
            }
        }

        $this->logMissingTranslation($key, $resolvedLocale);
        return $value;
    }
    
        private function normalizeDictionaryKey(string $key): string
    {
        if (preg_match('/^dictionary\.(ru|uk|en)\.(.+)$/', $key, $matches)) {
            return 'dictionary.' . $matches[2];
        }

        return $key;
    }

    private function isDictionaryKeyMissing(string $key, string $value): bool
    {
        // ловим только dictionary.* ключи
        if (!str_starts_with($key, 'dictionary.')) {
            return false;
        }

        $dictionaryKey = substr($key, strlen('dictionary.'));

        // "сломанный" перевод когда value == dictionaryKey (или == key, в зависимости от того как loader отдаёт)
        return $value === $dictionaryKey || $value === $key;
    }

    private function logMissingTranslation(string $key, string $locale): void
    {
        $source = Route::currentRouteName() ?? request()->path();
        $logKey = $locale . '|' . $key . '|' . $source;

        if (isset(self::$missingLogged[$logKey])) {
            return;
        }

        self::$missingLogged[$logKey] = true;

        Log::warning('Missing translation', [
            'key' => $key,
            'locale' => $locale,
            'source' => $source,
        ]);
    }
}
