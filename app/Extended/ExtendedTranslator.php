<?php

namespace App\Extended;

use App\Helpers\DBUtil;
use App\Repository\Site\TranslationRepository;
use App\Service\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\Schema;

class ExtendedTranslator extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $lang = Site::lang();
        $locale = $locale ?? app()->getLocale();
        [$table, $code] = $this->resolveTranslationTarget($key);
        $column = match ($table) {
            default => 'title_' . $lang . ' as value',
        };

        $result = '';
        try {
            if (!Schema::hasTable($table)) {
                return $key;
            }
            $result = DB::table($table)
                ->select($column)
                ->where('code', $code)
                ->value('value');

            if ($result == '') {
                $this->addEmptyTranslation($code);
                $result = $code;
            }

        } catch (\Exception $e) {
            Log::error('Ошибка перевода: ' . $e->getMessage()) . ' Перевод на странице: ' .  url()->full();
            $this->addEmptyTranslation($code);
            $result = $code;
        }

        return $result;
    }

    private function addEmptyTranslation($code)
    {
        $rep = new TranslationRepository();
        $rep->addEmptyTranslation($code);
    }
     private function resolveTranslationTarget(string $key): array
    {
        $segments = explode('.', $key, 2);
        if (count($segments) > 1) {
            return match ($segments[0]) {
                'dictionary' => ['mt_dictionary', $segments[1]],
                'settings' => ['mt_settings', $segments[1]],
                'pages_title' => ['mt_pages_title', $segments[1]],
                'pages_menu_title' => ['mt_pages_menu_title', $segments[1]],
                default => ['mt_dictionary', $key],
            };
        }

        if (str_starts_with($key, 'pages_title_')) {
            return ['mt_pages_title', substr($key, strlen('pages_title_'))];
        }

        if (str_starts_with($key, 'pages_menu_title_')) {
            return ['mt_pages_menu_title', substr($key, strlen('pages_menu_title_'))];
        }

        return ['mt_dictionary', $key];
    }
}
