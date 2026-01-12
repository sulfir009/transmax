<?php

namespace App\Extended;

use App\Helpers\DBUtil;
use App\Repository\Site\TranslationRepository;
use App\Service\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\Translator;

class ExtendedTranslator extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $lang = Site::lang();
        $table = 'mt_';
        $locale = $locale ?? app()->getLocale();
        $segments = explode('.', $key, 2);
        $table .= count($segments) > 1 ? $segments[0] : 'dictionary';
        $code = count($segments) > 1 ? $segments[1] : $segments[0];
        $column = match ($table) {
            'mt_settings' => 'value',
            default => 'title_' . $lang . ' as value'
        };

        $result = '';
        try {
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
}
