<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // mt_dictionary: создаём только если нет
        if (!Schema::hasTable('mt_dictionary')) {
            $this->command->info('Table mt_dictionary does not exist. Creating...');
            $this->createDictionaryTable();
        }

        // mt_settings: создаём только если нет
        // ВАЖНО: схема создаётся в том виде, как у тебя на сервере (title/value/description/active/sort)
        if (!Schema::hasTable('mt_settings')) {
            $this->command->info('Table mt_settings does not exist. Creating...');
            $this->createSettingsTable();
        }

        $this->seedDictionary();
        $this->seedSettings();
        $this->seedPagesTitles();
    }

    /**
     * Создаёт mt_dictionary в "новой" схеме (если таблицы нет).
     * На сервере таблица уже может быть другая — тогда сидер просто подстроится через hasColumn().
     */
    private function createDictionaryTable(): void
    {
        Schema::create('mt_dictionary', function (Blueprint $table) {
            $table->id();
            $table->integer('section_id')->default(1);
            $table->string('code')->unique();
            $table->text('title_uk')->nullable();
            $table->text('title_ru')->nullable();
            $table->text('title_en')->nullable();
            $table->text('comments')->nullable(); // если в твоей реальной БД NOT NULL — сидер всё равно будет давать ''
            $table->integer('edit_by_user')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Создаёт mt_settings в схеме как у тебя на сервере (со скрина):
     * id, active, title(32), code(255), value, description, sort
     */
    private function createSettingsTable(): void
    {
        Schema::create('mt_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('active')->default(1);
            $table->string('title', 32);
            $table->string('code', 255)->unique();
            $table->text('value');
            $table->text('description');
            $table->integer('sort')->default(500);
        });
    }

    private function seedDictionary(): void
    {
        $translations = [
            // Header translations
            [
                'code' => 'MSG_REGULAR_TOURS',
                'title_uk' => 'Регулярні рейси',
                'title_ru' => 'Регулярные рейсы',
                'title_en' => 'Regular tours',
            ],
            [
                'code' => 'MSG__KUPUJ_BEZPECHNO_NA_BLABLACAR',
                'title_uk' => 'Купуй безпечно на BlaBlaCar',
                'title_ru' => 'Покупай безопасно на BlaBlaCar',
                'title_en' => 'Buy safely on BlaBlaCar',
            ],
            [
                'code' => 'MSG_ALL_MI_U_SOCMEREZHAH',
                'title_uk' => 'Ми у соцмережах',
                'title_ru' => 'Мы в соцсетях',
                'title_en' => 'We are in social networks',
            ],
            [
                'code' => 'MSG_ALL_SLUZHBA_PIDTRIMKI',
                'title_uk' => 'Служба підтримки',
                'title_ru' => 'Служба поддержки',
                'title_en' => 'Support service',
            ],
            [
                'code' => 'MSG_ALL_OSOBISTIJ_KABINET',
                'title_uk' => 'Особистий кабінет',
                'title_ru' => 'Личный кабинет',
                'title_en' => 'Personal account',
            ],

            // Home page translations
            [
                'code' => 'MSG__MAX_TRANS_TEPER_BLABLACAR',
                'title_uk' => 'MaxTrans тепер на BlaBlaCar',
                'title_ru' => 'MaxTrans теперь на BlaBlaCar',
                'title_en' => 'MaxTrans is now on BlaBlaCar',
            ],
            [
                'code' => 'MSG__TI_ZH_AVTOBUSNI_REJSI_ZA_BILISH_VIGIDNOYU_CINOYU',
                'title_uk' => 'Ті ж автобусні рейси за більш вигідною ціною',
                'title_ru' => 'Те же автобусные рейсы по более выгодной цене',
                'title_en' => 'The same bus routes at a better price',
            ],
            [
                'code' => 'MSG__DETALINISHE_PRO_NAS',
                'title_uk' => 'Детальніше про нас',
                'title_ru' => 'Подробнее о нас',
                'title_en' => 'More about us',
            ],
            [
                'code' => 'MSG__NASHI_NAPRAVLENNYA',
                'title_uk' => 'Наші напрямлення',
                'title_ru' => 'Наши направления',
                'title_en' => 'Our directions',
            ],
            [
                'code' => 'MSG__BEZLICH_VARIANTIV_AVTOBUSNIH_POZDOK_DLYA_VASHIH_PODOROZHEJ_U_BUDI-YAKOMU_NAPRYAMKU',
                'title_uk' => 'Безліч варіантів автобусних поїздок для ваших подорожей у будь-якому напрямку',
                'title_ru' => 'Множество вариантов автобусных поездок для ваших путешествий в любом направлении',
                'title_en' => 'Many bus travel options for your travels in any direction',
            ],
            [
                'code' => 'MSG_ALL_KRANI',
                'title_uk' => 'Країни',
                'title_ru' => 'Страны',
                'title_en' => 'Countries',
            ],
            [
                'code' => 'MSG_ALL_ROZKLAD',
                'title_uk' => 'Розклад',
                'title_ru' => 'Расписание',
                'title_en' => 'Schedule',
            ],
            [
                'code' => 'ROUTE_AND_SCHEDULE',
                'title_uk' => 'Маршрут і розклад',
                'title_ru' => 'Маршрут и расписание',
                'title_en' => 'Route and schedule',
            ],
            [
                'code' => 'ALL_ROUTES',
                'title_uk' => 'Усі маршрути',
                'title_ru' => 'Все маршруты',
                'title_en' => 'All routes',
            ],
            [
                'code' => 'MSG_ALL_MIZHNARODNI',
                'title_uk' => 'Міжнародні',
                'title_ru' => 'Международные',
                'title_en' => 'International',
            ],
            [
                'code' => 'MSG_ALL_VNUTRISHNI',
                'title_uk' => 'Внутрішні',
                'title_ru' => 'Внутренние',
                'title_en' => 'Domestic',
            ],
            [
                'code' => 'PAYMENT_LIQPAY',
                'title_uk' => 'Оплата карткою онлайн (LiqPay) Visa / Mastercard',
                'title_ru' => 'Оплата картой онлайн (LiqPay) Visa / Mastercard',
                'title_en' => 'Online card payment (LiqPay) Visa / Mastercard',
            ],
            [
                'code' => 'PAYMENT_MONOPAY',
                'title_uk' => 'Оплата через MonoPay Apple Pay / Google Pay / картка Monobank',
                'title_ru' => 'Оплата через MonoPay Apple Pay / Google Pay / карта Monobank',
                'title_en' => 'MonoPay payment Apple Pay / Google Pay / Monobank card',
            ],
            [
                'code' => 'PAYMENT_CASH',
                'title_uk' => 'Готівкою при посадці',
                'title_ru' => 'Наличными при посадке',
                'title_en' => 'Cash on boarding',
            ],
            [
                'code' => 'MSG_ALL_ROZKLAD_AVTOBUSIV',
                'title_uk' => 'Розклад автобусів',
                'title_ru' => 'Расписание автобусов',
                'title_en' => 'Bus schedule',
            ],
            [
                'code' => 'MSG_ALL_ROZKLAD_MARSHRUTI_STANCI',
                'title_uk' => 'Розклад, маршрути, станції',
                'title_ru' => 'Расписание, маршруты, станции',
                'title_en' => 'Schedule, routes, stations',
            ],
            [
                'code' => 'MSG_ALL_POVERNENNYA_KVITKIV',
                'title_uk' => 'Повернення квитків',
                'title_ru' => 'Возврат билетов',
                'title_en' => 'Ticket refund',
            ],
            [
                'code' => 'MSG_ALL_ZMINILISI_PLANI_POVERNITI_KOSHTI_ZA_KVITOK_CHEREZ_NASH_SAJT',
                'title_uk' => 'Змінилися плани? Поверніть кошти за квиток через наш сайт',
                'title_ru' => 'Изменились планы? Верните деньги за билет через наш сайт',
                'title_en' => 'Plans changed? Get a refund for your ticket through our website',
            ],
            [
                'code' => 'MSG_ALL_BEZ_KAS_TA_CHERG',
                'title_uk' => 'Без кас та черг',
                'title_ru' => 'Без касс и очередей',
                'title_en' => 'No cash desks and queues',
            ],
            [
                'code' => 'MSG_ALL_KVITKI_ONLAJN_U_BUDI-YAKIJ_CHAS_NA_NASHOMU_SAJTI_DLYA_ZRUCHNOGO_PRIDBANNYA_ABO_BRONYUVANNYA',
                'title_uk' => 'Квитки онлайн у будь-який час на нашому сайті для зручного придбання або бронювання',
                'title_ru' => 'Билеты онлайн в любое время на нашем сайте for convenient purchase or booking',
                'title_en' => 'Tickets online at any time on our website for convenient purchase or booking',
            ],
            [
                'code' => 'MSG__ZAMOVITI_KVITOK',
                'title_uk' => 'Замовити квиток',
                'title_ru' => 'Заказать билет',
                'title_en' => 'Order a ticket',
            ],
            [
                'code' => 'MSG_ALL_NASHI_AVTOBUSI',
                'title_uk' => 'Наші автобуси',
                'title_ru' => 'Наши автобусы',
                'title_en' => 'Our buses',
            ],
            [
                'code' => 'MSG_ALL_OUR_BUSES_SUBTITLE',
                'title_uk' => 'Комфортні та безпечні автобуси для ваших подорожей',
                'title_ru' => 'Комфортные и безопасные автобусы для ваших путешествий',
                'title_en' => 'Comfortable and safe buses for your travels',
            ],
            [
                'code' => 'MSG_ALL_PEREGLYANUTI_AVTOPARK',
                'title_uk' => 'Переглянути автопарк',
                'title_ru' => 'Просмотреть автопарк',
                'title_en' => 'View fleet',
            ],
            [
                'code' => 'MSG_ALL_VIDGUKI',
                'title_uk' => 'Відгуки',
                'title_ru' => 'Отзывы',
                'title_en' => 'Reviews',
            ],
            [
                'code' => 'exit',
                'title_uk' => 'Вийти',
                'title_ru' => 'Выйти',
                'title_en' => 'Exit',
            ],
            [
                'code' => 'MSG_MSG_PAYMENT_PAGE_BONUSAMI',
                'title_uk' => 'Розрахуватись бонусами',
                'title_ru' => 'Рассчитаться бонусами',
                'title_en' => 'Pay with bonuses',
            ],
            [
                'code' => 'MSG_MSG_PAYMENT_PAGE_USE_BONUSES',
                'title_uk' => 'Використати бонуси',
                'title_ru' => 'Использовать бонусы',
                'title_en' => 'Use bonuses',
            ],
            [
                'code' => 'MSG_MSG_PAYMENT_PAGE_BONUSES_AVAILABLE',
                'title_uk' => 'Доступно бонусів',
                'title_ru' => 'Доступно бонусов',
                'title_en' => 'Bonuses available',
            ],
            [
                'code' => 'MSG_MSG_PAYMENT_PAGE_TOTAL_WITH_BONUSES',
                'title_uk' => 'До сплати з бонусами',
                'title_ru' => 'К оплате с бонусами',
                'title_en' => 'Total with bonuses',
            ],
        ];

        foreach ($translations as $translation) {
            $payload = [
                'title_uk' => $translation['title_uk'] ?? null,
                'title_ru' => $translation['title_ru'] ?? null,
                'title_en' => $translation['title_en'] ?? null,

                // ВАЖНО: comments может быть NOT NULL в старой схеме -> всегда строка
                'comments' => (string)($translation['comments'] ?? ''),

                'section_id' => 1,
                'edit_by_user' => 1,
            ];

            $payload = $this->withTimestampsIfAvailable('mt_dictionary', $payload);

            DB::table('mt_dictionary')->updateOrInsert(
                ['code' => $translation['code']],
                $payload
            );
        }
    }

    /**
     * mt_settings (как на твоём скрине) НЕ мультиязычные:
     * title(32), value, description NOT NULL.
     */
    private function seedSettings(): void
    {
        $settings = [
            [
                'code' => 'SUPPORT_PHONE_1',
                'title' => 'Support phone 1',
                'value' => '+38 (097) 000-00-00',
                'description' => '',
                'active' => 1,
                'sort' => 500,
            ],
            [
                'code' => 'SUPPORT_PHONE_2',
                'title' => 'Support phone 2',
                'value' => '+38 (068) 000-00-00',
                'description' => '',
                'active' => 1,
                'sort' => 500,
            ],
            [
                'code' => 'VIBER',
                'title' => 'Viber link',
                'value' => 'https://viber.com',
                'description' => '',
                'active' => 1,
                'sort' => 500,
            ],
            [
                'code' => 'TELEGRAM',
                'title' => 'Telegram link',
                'value' => 'https://t.me/maxtrans',
                'description' => '',
                'active' => 1,
                'sort' => 500,
            ],
            [
                'code' => 'FB',
                'title' => 'Facebook link',
                'value' => 'https://facebook.com/maxtrans',
                'description' => '',
                'active' => 1,
                'sort' => 500,
            ],
            [
                'code' => 'INST',
                'title' => 'Instagram link',
                'value' => 'https://instagram.com/maxtrans',
                'description' => '',
                'active' => 1,
                'sort' => 500,
            ],
        ];

        foreach ($settings as $setting) {
            $payload = [
                'title' => mb_substr((string)$setting['title'], 0, 32),
                'value' => (string)$setting['value'],
                'description' => (string)($setting['description'] ?? ''),
                'active' => (int)($setting['active'] ?? 1),
                'sort' => (int)($setting['sort'] ?? 500),
            ];

            // timestamps в твоей таблице нет, но пусть будет защита
            $payload = $this->withTimestampsIfAvailable('mt_settings', $payload);

            DB::table('mt_settings')->updateOrInsert(
                ['code' => $setting['code']],
                $payload
            );
        }
    }

    private function seedPagesTitles(): void
    {
        // Таблица pages_title
        if (Schema::hasTable('mt_pages_title')) {
            $pages = [
                [
                    'code' => 'main',
                    'title_uk' => 'Головна',
                    'title_ru' => 'Главная',
                    'title_en' => 'Home',
                ],
                [
                    'code' => 'regular_races',
                    'title_uk' => 'Регулярні рейси',
                    'title_ru' => 'Регулярные рейсы',
                    'title_en' => 'Regular races',
                ],
                [
                    'code' => 'schedule',
                    'title_uk' => 'Розклад',
                    'title_ru' => 'Расписание',
                    'title_en' => 'Schedule',
                ],
                [
                    'code' => 'avtopark',
                    'title_uk' => 'Автопарк',
                    'title_ru' => 'Автопарк',
                    'title_en' => 'Fleet',
                ],
                [
                    'code' => 'about_us',
                    'title_uk' => 'Про нас',
                    'title_ru' => 'О нас',
                    'title_en' => 'About us',
                ],
                [
                    'code' => 'kontakti',
                    'title_uk' => 'Контакти',
                    'title_ru' => 'Контакты',
                    'title_en' => 'Contacts',
                ],
                [
                    'code' => 'faq',
                    'title_uk' => 'Часті питання',
                    'title_ru' => 'Частые вопросы',
                    'title_en' => 'FAQ',
                ],
            ];

            foreach ($pages as $page) {
                DB::table('mt_pages_title')->updateOrInsert(
                    ['code' => $page['code']],
                    $page
                );
            }
        }

        // Таблица pages_menu_title
        if (Schema::hasTable('mt_pages_menu_title')) {
            $menuItems = [
                [
                    'code' => 'schedule',
                    'title_uk' => 'Розклад руху',
                    'title_ru' => 'Расписание движения',
                    'title_en' => 'Schedule',
                ],
                [
                    'code' => 'avtopark',
                    'title_uk' => 'Наш автопарк',
                    'title_ru' => 'Наш автопарк',
                    'title_en' => 'Our fleet',
                ],
                [
                    'code' => 'about_us',
                    'title_uk' => 'Про компанію',
                    'title_ru' => 'О компании',
                    'title_en' => 'About company',
                ],
                [
                    'code' => 'kontakti',
                    'title_uk' => 'Контакти',
                    'title_ru' => 'Контакты',
                    'title_en' => 'Contacts',
                ],
                [
                    'code' => 'faq',
                    'title_uk' => 'Питання та відповіді',
                    'title_ru' => 'Вопросы и ответы',
                    'title_en' => 'Questions and answers',
                ],
            ];

            foreach ($menuItems as $menuItem) {
                DB::table('mt_pages_menu_title')->updateOrInsert(
                    ['code' => $menuItem['code']],
                    $menuItem
                );
            }
        }
    }

    /**
     * Добавляет timestamps только если они реально есть в таблице.
     */
    private function withTimestampsIfAvailable(string $table, array $payload): array
    {
        if (Schema::hasColumn($table, 'created_at') && Schema::hasColumn($table, 'updated_at')) {
            // created_at лучше не перетирать при update, но updateOrInsert не различает insert/update
            // поэтому даём оба поля, если они существуют
            $payload['created_at'] = now();
            $payload['updated_at'] = now();
        }

        return $payload;
    }
}
