<?php

namespace App\Translation;

use Illuminate\Translation\Translator;

class DatabaseTranslator extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $value = parent::get($key, $replace, $locale, $fallback);

        if (!is_string($key) || str_contains($key, '.') || $value !== $key) {
            return $value;
        }

        $mappedKey = $this->mapKeyToGroup($key);
        $mappedValue = parent::get($mappedKey, $replace, $locale, $fallback);

        return $mappedValue === $mappedKey ? $value : $mappedValue;
    }

    private function mapKeyToGroup(string $key): string
    {
        if (str_starts_with($key, 'pages_title_')) {
            return 'pages_title.' . substr($key, strlen('pages_title_'));
        }

        if (str_starts_with($key, 'pages_menu_title_')) {
            return 'pages_menu_title.' . substr($key, strlen('pages_menu_title_'));
        }

        return 'dictionary.' . $key;
    }
}
