<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeoTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_seo_templates';

        $templates = [
            [
                'key' => 'route_page_title',
                'lang' => 'ru',
                'template_text' => 'Автобус [route] - Купить билеты онлайн | MaxTrans',
            ],
            [
                'key' => 'route_page_description',
                'lang' => 'ru',
                'template_text' => 'Билеты на автобус [route] от [price] онлайн! ⏩ Актуальное расписание рейсов [route] ⭐️ Комфортные автобусы ⚡ 18 лет опыта в перевозках',
            ],
            [
                'key' => 'route_page_title',
                'lang' => 'uk',
                'template_text' => 'Автобус [route] - Купити квитки онлайн | MaxTrans',
            ],
            [
                'key' => 'route_page_description',
                'lang' => 'uk',
                'template_text' => 'Квитки на автобус [route] від [price] онлайн! ⏩ Актуальний розклад рейсів [route] ⭐️ Комфортні автобуси ⚡ 18 років досвіду в перевезеннях',
            ],
            [
                'key' => 'route_page_title',
                'lang' => 'en',
                'template_text' => 'Bus [route] - Buy Tickets Online | MaxTrans',
            ],
            [
                'key' => 'route_page_description',
                'lang' => 'en',
                'template_text' => 'Bus tickets for [route] from [price] online! ⏩ Current timetable for [route] ⭐️ Comfortable buses ⚡ 18 years of transportation experience',
            ],
        ];

        foreach ($templates as $template) {
            DB::table($table)->updateOrInsert(
                [
                    'key' => $template['key'],
                    'lang' => $template['lang'],
                ],
                [
                    'template_text' => $template['template_text'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}