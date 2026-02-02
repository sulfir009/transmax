<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_dictionary';

        if (!Schema::hasTable($table)) {
            return;
        }

        $translations = [
            'ROUTE_AND_SCHEDULE' => [
                'title_ru' => 'Маршрут и расписание',
                'title_uk' => 'Маршрут і розклад',
                'title_en' => 'Route and schedule',
            ],
            'ALL_ROUTES' => [
                'title_ru' => 'Все маршруты',
                'title_uk' => 'Усі маршрути',
                'title_en' => 'All routes',
            ],
            'PAYMENT_LIQPAY' => [
                'title_ru' => 'Оплата картой онлайн (LiqPay) Visa / Mastercard',
                'title_uk' => 'Оплата карткою онлайн (LiqPay) Visa / Mastercard',
                'title_en' => 'Online card payment (LiqPay) Visa / Mastercard',
            ],
            'PAYMENT_MONOPAY' => [
                'title_ru' => 'Оплата через MonoPay Apple Pay / Google Pay / карта Monobank',
                'title_uk' => 'Оплата через MonoPay Apple Pay / Google Pay / картка Monobank',
                'title_en' => 'MonoPay payment Apple Pay / Google Pay / Monobank card',
            ],
            'PAYMENT_CASH' => [
                'title_ru' => 'Наличными при посадке',
                'title_uk' => 'Готівкою при посадці',
                'title_en' => 'Cash on boarding',
            ],
        ];

        foreach ($translations as $code => $titles) {
            $exists = DB::table($table)->where('code', $code)->exists();
            if ($exists) {
                continue;
            }

            DB::table($table)->insert([
                'section_id' => 1,
                'code' => $code,
                'title_ru' => $titles['title_ru'],
                'title_uk' => $titles['title_uk'],
                'title_en' => $titles['title_en'],
                'comments' => 'Auto-added by migration: schedule/payment labels',
                'edit_by_user' => 1,
            ]);
        }
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_dictionary';

        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->whereIn('code', [
                'ROUTE_AND_SCHEDULE',
                'ALL_ROUTES',
                'PAYMENT_LIQPAY',
                'PAYMENT_MONOPAY',
                'PAYMENT_CASH',
            ])
            ->delete();
    }
};
