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
    ) {}

    public function load($locale, $group, $namespace = null)
    {
        // 1) Сначала стандартные переводы Laravel (resources/lang + json)
        $lines = $this->fileLoader->load($locale, $group, $namespace);

        // 2) Namespace пакетов не трогаем
        if ($namespace !== null && $namespace !== '*') {
            return $lines;
        }

        /**
         * ВАЖНО:
         * У тебя все "dictionary + pages_*" лежит в mt_dictionary.
         * Поэтому подмешиваем БД ТОЛЬКО в группу "dictionary".
         *
         * А ключи типа pages_title_main / pages_menu_title_schedule
         * должны храниться в mt_dictionary.code как есть.
         */
        if ($group !== 'dictionary') {
            return $lines;
        }

        $dbLines = $this->loadDictionaryFromDatabase((string) $locale);

        // DB перекрывает файлы
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

    private function loadDictionaryFromDatabase(string $locale): array
    {
        $loc = $this->normalizeLocale($locale);
        $fb  = $this->normalizeLocale((string) config('app.fallback_locale', 'ru'));

        $cacheKey = "translations:dictionary:{$loc}:{$fb}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($loc, $fb) {
            $table = $this->prefix . '_dictionary';

            try {
                if (!Schema::hasTable($table)) {
                    return [];
                }

                $colLoc = "title_{$loc}";
                $colFb  = "title_{$fb}";

                // Колонки должны существовать
                if (!Schema::hasColumn($table, 'code') || !Schema::hasColumn($table, $colLoc)) {
                    return [];
                }

                $select = ['id', 'code', "{$colLoc} as title_locale"];

                if ($colFb !== $colLoc && Schema::hasColumn($table, $colFb)) {
                    $select[] = "{$colFb} as title_fallback";
                }

                /**
                 * Критично для тебя:
                 * При дублях code нам нужно брать "самую старую/стабильную" запись (или лучшую),
                 * иначе значение будет случайным.
                 *
                 * Сейчас делаем просто: orderBy('id','asc'), а дальше в foreach
                 * НЕ перезаписываем уже найденный перевод (то есть first-wins).
                 */
                $rows = DB::table($table)
                    ->select($select)
                    ->orderBy('id', 'asc')
                    ->get();
            } catch (Throwable $e) {
                return [];
            }

            $out = [];

            foreach ($rows as $row) {
                $code = (string) $row->code;

                // first-wins: если уже есть перевод — не перетираем мусором
                if (array_key_exists($code, $out)) {
                    continue;
                }

                $value = $row->title_locale ?? null;

                if (($value === null || $value === '') && property_exists($row, 'title_fallback')) {
                    $value = $row->title_fallback;
                }

                $out[$code] = (is_string($value) && $value !== '') ? $value : $code;
            }

            return $out;
        });
    }

    private function normalizeLocale(?string $locale): string
    {
        $locale = strtolower(trim((string)($locale ?: 'ru')));

        // Часто встречается "ua" вместо "uk"
        if ($locale === 'ua') {
            $locale = 'uk';
        }

        return in_array($locale, ['ru', 'uk', 'en'], true) ? $locale : 'ru';
    }
}
