<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InjectLegacyDictionary
{
    public function handle(Request $request, Closure $next)
    {
        // Если legacy уже заполнил — не трогаем (но можно убрать, если хочешь всегда форсировать)
        if (!isset($GLOBALS['dictionary']) || !is_array($GLOBALS['dictionary'])) {
            $GLOBALS['dictionary'] = [];
        }

        // Если таблицы нет — просто пропускаем
        if (!Schema::hasTable('mt_dictionary')) {
            return $next($request);
        }

        // Берём все коды из mt_dictionary
        // Важно: ключи отдаём ровно как code, чтобы $GLOBALS['dictionary'][CODE] работал
        $rows = DB::table('mt_dictionary')->select('code')->get();

        foreach ($rows as $row) {
            $code = (string) $row->code;
            if ($code === '') continue;

            // Поддержка двух кейсов: в БД может быть MSG_..., а в шаблоне иногда msg_...
            // Заполним оба варианта, чтобы не думать про регистр.
            $GLOBALS['dictionary'][$code] = __('dictionary.' . $code);

            $lower = strtolower($code);
            if ($lower !== $code) {
                $GLOBALS['dictionary'][$lower] = __('dictionary.' . $lower);
            }

            $upper = strtoupper($code);
            if ($upper !== $code) {
                $GLOBALS['dictionary'][$upper] = __('dictionary.' . $upper);
            }
        }

        return $next($request);
    }
}
