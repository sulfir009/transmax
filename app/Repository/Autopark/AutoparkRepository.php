<?php

namespace App\Repository\Autopark;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AutoparkRepository
{
    private string $dbPrefix;

    public function __construct()
    {
        $this->dbPrefix = config('database.prefix', 'mt');
    }

    /**
     * Получить текстовые блоки для страницы автопарка
     */
    public function getPageTextBlocks(string $lang): array
    {
        return Cache::remember("autopark_text_blocks_{$lang}", 3600, function () use ($lang) {
            $result = DB::table($this->dbPrefix . '_txt_blocks')
                ->selectRaw("title_{$lang} AS title, text_{$lang} AS text")
                ->where('id', 10)
                ->first();

            return $result ? (array) $result : [];
        });
    }

    /**
     * Получить правила бронирования автобуса
     */
    public function getBookingRules(string $lang): array
    {
        return Cache::remember("autopark_booking_rules_{$lang}", 3600, function () use ($lang) {
            $bookingText = DB::table($this->dbPrefix . '_txt_blocks')
                ->selectRaw("text_{$lang} AS text")
                ->where('id', 8)
                ->first();

            $bookingWarning = DB::table($this->dbPrefix . '_txt_blocks')
                ->selectRaw("text_{$lang} AS text")
                ->where('id', 9)
                ->first();

            return [
                'text' => $bookingText ? $bookingText->text : '',
                'warning' => $bookingWarning ? $bookingWarning->text : ''
            ];
        });
    }

    /**
     * Получить количество активных автобусов
     */
    public function getActiveBusesCount(): int
    {
        return DB::table($this->dbPrefix . '_buses')
            ->where('active', '1')
            ->count();
    }

    /**
     * Получить список автобусов с пагинацией
     */
    public function getBuses(string $lang, int $limit = 6, int $offset = 0): array
    {
        $buses = DB::table($this->dbPrefix . '_buses')
            ->selectRaw("id, image, seats_qty, title_{$lang} AS title")
            ->where('active', '1')
            ->orderByDesc('sort')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $buses->map(function ($bus) {
            $bus->images = $this->getBusImages($bus->id);
            $bus->options = $this->getBusOptions($bus->id);
            return (array) $bus;
        })->toArray();
    }

    /**
     * Получить изображения автобуса
     */
    public function getBusImages(int $busId): array
    {
        return DB::table($this->dbPrefix . '_buses_images')
            ->select('bus_img')
            ->where('bus_id', $busId)
            ->get()
            ->pluck('bus_img')
            ->toArray();
    }

    /**
     * Получить опции автобуса
     */
    public function getBusOptions(int $busId): array
    {
        $lang = app()->getLocale();
        
        $optionIds = DB::table($this->dbPrefix . '_buses_options_connector')
            ->where('bus_id', $busId)
            ->pluck('option_id');

        if ($optionIds->isEmpty()) {
            return [];
        }

        return DB::table($this->dbPrefix . '_buses_options')
            ->selectRaw("title_{$lang} AS title")
            ->whereIn('id', $optionIds)
            ->where('active', '1')
            ->orderByDesc('sort')
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
    }

    /**
     * Получить коды телефонов
     */
    public function getPhoneCodes(): array
    {
        return Cache::remember("phone_codes", 3600, function () {
            $codes = DB::table($this->dbPrefix . '_phone_codes')
                ->select('*')
                ->where('active', '1')
                ->orderByDesc('sort')
                ->get();
            
            // Преобразуем коллекцию в массив массивов
            return $codes->map(function ($item) {
                return (array) $item;
            })->toArray();
        });
    }

    /**
     * Получить дополнительные автобусы для подгрузки
     */
    public function getMoreBuses(string $lang, int $currentCount): array
    {
        return $this->getBuses($lang, 6, $currentCount);
    }
}
