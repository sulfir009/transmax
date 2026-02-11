<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_settings';

        if (!Schema::hasTable($table)) {
            return;
        }

        $templates = [
    'SEO_ROUTE_TITLE_RU' => [
        'title_short' => 'SEO route title RU',
        'label' => 'SEO шаблон маршрутов: Title (RU)',
        'value' => 'Автобус [Название маршрута] - Купить билеты онлайн | MaxTrans',
    ],
    'SEO_ROUTE_DESC_RU' => [
        'title_short' => 'SEO route desc RU',
        'label' => 'SEO шаблон маршрутов: Description (RU)',
        'value' => 'Билеты на автобус [Название маршрута] от [price] онлайн! ⏩ Актуальное расписание рейсов [Название маршрута] ⭐️ Комфортные автобусы ⚡ 18 лет опыта в перевозках',
    ],
    'SEO_ROUTE_TITLE_UK' => [
        'title_short' => 'SEO route title UK',
        'label' => 'SEO шаблон маршрутов: Title (UK)',
        'value' => 'Автобус [Назва маршруту] - Купити квитки онлайн | MaxTrans',
    ],
    'SEO_ROUTE_DESC_UK' => [
        'title_short' => 'SEO route desc UK',
        'label' => 'SEO шаблон маршрутов: Description (UK)',
        'value' => 'Квитки на автобус [Назва маршруту] від [price] онлайн! ⏩ Актуальний розклад рейсів [Назва маршруту] ⭐️ Комфортні автобуси ⚡ 18 років досвіду в перевезеннях',
    ],
    'SEO_ROUTE_TITLE_EN' => [
        'title_short' => 'SEO route title EN',
        'label' => 'SEO route templates: Title (EN)',
        'value' => 'Bus [Route Name] - Buy Tickets Online | MaxTrans',
    ],
    'SEO_ROUTE_DESC_EN' => [
        'title_short' => 'SEO route desc EN',
        'label' => 'SEO route templates: Description (EN)',
        'value' => 'Bus tickets for [Route Name] from [price] online! ⏩ Current timetable for [Route Name] ⭐️ Comfortable buses ⚡ 18 years of transportation experience',
    ],
];


        $columns = Schema::getColumnListing($table);

        foreach ($templates as $code => $template) {
            $row = DB::table($table)->where('code', $code)->first();

            if ($row) {
                continue;
            }

            $payload = ['code' => $code];
            if (in_array('title', $columns, true)) {
    $payload['title'] = $template['title_short'] ?? $template['label'];
}
            if (in_array('description', $columns, true)) {
                $payload['description'] = 'SEO route template';
            }
            if (in_array('value', $columns, true)) {
                $payload['value'] = $template['value'];
            }
            if (in_array('active', $columns, true)) {
                $payload['active'] = 1;
            }
            if (in_array('sort', $columns, true)) {
                $payload['sort'] = 0;
            }

            DB::table($table)->insert($payload);
        }
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_settings';

        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->whereIn('code', [
            'SEO_ROUTE_TITLE_RU',
            'SEO_ROUTE_DESC_RU',
            'SEO_ROUTE_TITLE_UK',
            'SEO_ROUTE_DESC_UK',
            'SEO_ROUTE_TITLE_EN',
            'SEO_ROUTE_DESC_EN',
        ])->delete();
    }
};
