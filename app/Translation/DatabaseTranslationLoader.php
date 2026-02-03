<?php

namespace App\Translation;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\FileLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseTranslationLoader implements Loader
{
    public function __construct(
        private FileLoader $fileLoader,
        private string $prefix = 'mt'
    ) {
    }

    public function load($locale, $group, $namespace = null)
    {
        $lines = $this->fileLoader->load($locale, $group, $namespace);

        if ($namespace !== null) {
            return $lines;
        }

        if (!in_array($group, ['dictionary', 'settings', 'pages_title', 'pages_menu_title'], true)) {
            return $lines;
        }

        $dbLines = $this->loadFromDatabase((string) $locale, (string) $group);

        return array_replace($lines, $dbLines);
    }

    public function addNamespace($namespace, $hint)
    {
        $this->fileLoader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path)
    {
        $this->fileLoader->addJsonPath($path);
    }

    public function namespaces()
    {
        return $this->fileLoader->namespaces();
    }

    private function loadFromDatabase(string $locale, string $group): array
    {
        $normalizedLocale = $this->normalizeLocale($locale);
        $fallbackLocale = $this->normalizeLocale((string) config('app.fallback_locale', 'en'));
        $cacheKey = "translations:{$group}:{$normalizedLocale}:{$fallbackLocale}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($group, $normalizedLocale, $fallbackLocale) {
            $table = $this->prefix . '_' . $group;

            try {
                if (!Schema::hasTable($table)) {
                    return [];
                }

                $rows = DB::table($table)
                    ->select([
                        'code',
                        "title_{$normalizedLocale} as title_locale",
                        "title_{$fallbackLocale} as title_fallback",
                    ])
                    ->get();
            } catch (Throwable $e) {
                return [];
            }

            $lines = [];

            foreach ($rows as $row) {
                $value = $row->title_locale ?: $row->title_fallback;
                $lines[$row->code] = is_string($value) && $value !== '' ? $value : $row->code;
            }

            return $lines;
        });
    }

    private function normalizeLocale(?string $locale): string
    {
        $locale = $locale ?: 'ru';

        return in_array($locale, ['ru', 'uk', 'en'], true) ? $locale : 'ru';
    }
}
