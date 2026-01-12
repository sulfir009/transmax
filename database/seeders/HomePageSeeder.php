<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomePageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Главный баннер
        DB::table('mt_main_banner')->truncate();
        DB::table('mt_main_banner')->insert([
            'title_uk' => 'Автобусні перевезення по Україні та Європі',
            'title_ru' => 'Автобусные перевозки по Украине и Европе',
            'title_en' => 'Bus transportation in Ukraine and Europe',
            'image' => 'main-banner.jpg'
        ]);

        // Преимущества
        DB::table('mt_advantages')->truncate();
        DB::table('mt_advantages')->insert([
            [
                'title_uk' => 'Безпека',
                'title_ru' => 'Безопасность',
                'title_en' => 'Safety',
                'preview_uk' => 'Всі наші автобуси проходять регулярний технічний огляд',
                'preview_ru' => 'Все наши автобусы проходят регулярный технический осмотр',
                'preview_en' => 'All our buses undergo regular technical inspection',
                'image' => 'safety.jpg',
                'active' => '1',
                'sort' => 3
            ],
            [
                'title_uk' => 'Комфорт',
                'title_ru' => 'Комфорт',
                'title_en' => 'Comfort',
                'preview_uk' => 'Зручні сидіння, кондиціонер, Wi-Fi',
                'preview_ru' => 'Удобные сиденья, кондиционер, Wi-Fi',
                'preview_en' => 'Comfortable seats, air conditioning, Wi-Fi',
                'image' => 'comfort.jpg',
                'active' => '1',
                'sort' => 2
            ],
            [
                'title_uk' => 'Пунктуальність',
                'title_ru' => 'Пунктуальность',
                'title_en' => 'Punctuality',
                'preview_uk' => 'Дотримуємось розкладу та цінуємо ваш час',
                'preview_ru' => 'Соблюдаем расписание и ценим ваше время',
                'preview_en' => 'We follow the schedule and value your time',
                'image' => 'punctuality.jpg',
                'active' => '1',
                'sort' => 1
            ]
        ]);

        // Приветствие
        DB::table('mt_wellcome')->truncate();
        DB::table('mt_wellcome')->insert([
            'title_uk' => 'Про компанію MaxTrans',
            'title_ru' => 'О компании MaxTrans',
            'title_en' => 'About MaxTrans Company',
            'text_uk' => 'MaxTrans - це надійний партнер для ваших подорожей. Ми працюємо з 2010 року та за цей час перевезли понад 1 мільйон пасажирів. Наша мета - зробити ваші подорожі комфортними та безпечними.',
            'text_ru' => 'MaxTrans - это надежный партнер для ваших путешествий. Мы работаем с 2010 года и за это время перевезли более 1 миллиона пассажиров. Наша цель - сделать ваши путешествия комфортными и безопасными.',
            'text_en' => 'MaxTrans is a reliable partner for your travels. We have been operating since 2010 and have transported over 1 million passengers during this time. Our goal is to make your travels comfortable and safe.',
            'image' => 'about-us.jpg'
        ]);

        // Цифры
        DB::table('mt_about_numbers')->truncate();
        DB::table('mt_about_numbers')->insert([
            'title_uk' => 'MaxTrans у цифрах',
            'title_ru' => 'MaxTrans в цифрах',
            'title_en' => 'MaxTrans in numbers',
            'text1_uk' => 'Автобусів',
            'text1_ru' => 'Автобусов',
            'text1_en' => 'Buses',
            'text2_uk' => 'Замовлень на рік',
            'text2_ru' => 'Заказов в год',
            'text2_en' => 'Orders per year',
            'text3_uk' => 'Міст України',
            'text3_ru' => 'Городов Украины',
            'text3_en' => 'Cities of Ukraine',
            'text4_uk' => 'Країн Європи',
            'text4_ru' => 'Стран Европы',
            'text4_en' => 'European countries',
            'number1' => 45,
            'number2' => 15000,
            'number3' => 180,
            'number4' => 12,
            'image' => 'map.jpg'
        ]);

        // Почему мы
        DB::table('mt_why_we')->truncate();
        DB::table('mt_why_we')->insert([
            [
                'title_uk' => 'Досвідчені водії',
                'title_ru' => 'Опытные водители',
                'title_en' => 'Experienced drivers',
                'subtitle_uk' => 'Професіонали своєї справи',
                'subtitle_ru' => 'Профессионалы своего дела',
                'subtitle_en' => 'Professionals in their field',
                'preview_uk' => 'Всі наші водії мають великий досвід роботи на міжнародних маршрутах',
                'preview_ru' => 'Все наши водители имеют большой опыт работы на международных маршрутах',
                'preview_en' => 'All our drivers have extensive experience on international routes',
                'image' => 'drivers.jpg',
                'active' => '1',
                'sort' => 1
            ],
            [
                'title_uk' => 'Сучасний автопарк',
                'title_ru' => 'Современный автопарк',
                'title_en' => 'Modern fleet',
                'subtitle_uk' => 'Комфортні автобуси',
                'subtitle_ru' => 'Комфортные автобусы',
                'subtitle_en' => 'Comfortable buses',
                'preview_uk' => 'Наш автопарк складається з сучасних автобусів європейського виробництва',
                'preview_ru' => 'Наш автопарк состоит из современных автобусов европейского производства',
                'preview_en' => 'Our fleet consists of modern European-made buses',
                'image' => 'fleet.jpg',
                'active' => '1',
                'sort' => 2
            ]
        ]);

        // Отзывы
        DB::table('mt_reviews')->truncate();
        DB::table('mt_reviews')->insert([
            [
                'name' => 'Олена Петренко',
                'review_uk' => 'Чудова компанія! Їздила з MaxTrans до Польщі. Все було на найвищому рівні - чистий автобус, ввічливий водій, прибули вчасно.',
                'review_ru' => 'Отличная компания! Ездила с MaxTrans в Польшу. Все было на высшем уровне - чистый автобус, вежливый водитель, прибыли вовремя.',
                'review_en' => 'Great company! I traveled with MaxTrans to Poland. Everything was at the highest level - clean bus, polite driver, arrived on time.',
                'image' => 'review1.jpg',
                'active' => '1',
                'sort' => 1
            ],
            [
                'name' => 'Микола Іванов',
                'review_uk' => 'Регулярно користуюсь послугами MaxTrans для поїздок по Україні. Завжди задоволений якістю обслуговування.',
                'review_ru' => 'Регулярно пользуюсь услугами MaxTrans для поездок по Украине. Всегда доволен качеством обслуживания.',
                'review_en' => 'I regularly use MaxTrans services for trips around Ukraine. Always satisfied with the quality of service.',
                'image' => 'review2.jpg',
                'active' => '1',
                'sort' => 2
            ],
            [
                'name' => 'Марія Коваленко',
                'review_uk' => 'Дякую за чудову поїздку! Комфортний автобус, Wi-Fi працював без перебоїв, водій допоміг з багажем.',
                'review_ru' => 'Спасибо за отличную поездку! Комфортный автобус, Wi-Fi работал без перебоев, водитель помог с багажом.',
                'review_en' => 'Thank you for a wonderful trip! Comfortable bus, Wi-Fi worked without interruption, the driver helped with luggage.',
                'image' => 'review3.jpg',
                'active' => '1',
                'sort' => 3
            ]
        ]);
    }
}
