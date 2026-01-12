<?php

namespace App\Repositories;

use App\Models\Tour;
use App\Models\TourStop;
use App\Models\City;
use App\Models\Bus;
use App\Models\Month;
use App\Models\BusOption;
use App\Models\TourStopPrice;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TicketRepository implements TicketRepositoryInterface
{
    /**
     * Получить информацию о билете по данным заказа
     */
    public function getTicketInfo(array $orderData, string $lang = 'ru'): ?array
    {
        $tourId = (int)$orderData['tour_id'];
        $fromStopId = (int)$orderData['from'];
        $toStopId = (int)$orderData['to'];

        // Исправляем SQL запрос - добавляем правильный алиас для таблицы tours
        $ticketInfo = DB::table((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'tours_stops as from_stop')
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'cities as from_city', 'from_stop.stop_id', '=', 'from_city.id')
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'tours as tours', 'from_stop.tour_id', '=', 'tours.id') // Добавляем алиас
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'cities as departure_city', 'departure_city.id', '=', 'tours.departure')
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'tours_stops as to_stop', function($join) use ($tourId, $toStopId) {
                $join->on('from_stop.tour_id', '=', 'to_stop.tour_id')
                     ->where('to_stop.stop_id', '=', $toStopId);
            })
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'cities as to_city', 'to_stop.stop_id', '=', 'to_city.id')
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'cities as arrival_city', 'arrival_city.id', '=', 'tours.arrival')
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'buses as bus', 'tours.bus', '=', 'bus.id')
            ->join((env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'tours_stops_prices as prices', function($join) use ($fromStopId, $toStopId) {
                $join->on('prices.tour_id', '=', 'from_stop.tour_id')
                     ->where('prices.from_stop', '=', $fromStopId)
                     ->where('prices.to_stop', '=', $toStopId);
            })
            ->select([
                'from_stop.departure_time',
                "from_city.title_{$lang} as departure_station",
                "departure_city.title_{$lang} as departure_city",
                'to_stop.arrival_time',
                "to_city.title_{$lang} as arrival_station", 
                "arrival_city.title_{$lang} as arrival_city",
                "bus.title_{$lang} as bus",
                'bus.id as bus_id',
                'prices.price'
            ])
            ->where('from_stop.tour_id', $tourId)
            ->where('from_stop.stop_id', $fromStopId)
            ->first();

        return $ticketInfo ? (array)$ticketInfo : null;
    }

    /**
     * Получить опции автобуса по ID
     */
    public function getBusOptions(int $busId, string $lang = 'ru'): array
    {
        $prefix = env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '';
        
        // Используем Query Builder для совместимости с существующей структурой
        $options = DB::table("{$prefix}buses_options as options")
            ->join("{$prefix}buses_options_connector as connector", 'options.id', '=', 'connector.option_id')
            ->where('connector.bus_id', $busId)
            ->where('options.active', 1)
            ->select(['options.id', "options.title_{$lang} as title"])
            ->orderBy('options.sort')
            ->get();

        // Преобразуем каждый объект в массив
        return $options->map(function ($option) {
            return [
                'id' => $option->id,
                'title' => $option->title
            ];
        })->toArray();
    }

    /**
     * Получить информацию о месяце по номеру
     */
    public function getMonthInfo(int $monthNumber, string $lang = 'ru'): ?array
    {
        $prefix = env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '';
        
        $month = DB::table("{$prefix}months")
            ->where('id', $monthNumber)
            ->select(['id', "title_{$lang} as title"])
            ->first();

        // Преобразуем объект в массив
        return $month ? [
            'id' => $month->id,
            'title' => $month->title
        ] : null;
    }

    /**
     * Вычислить общую стоимость
     */
    public function calculateTotalPrice(array $orderData): float
    {
        $ticketInfo = $this->getTicketInfo($orderData);
        if (!$ticketInfo) {
            return 0.0;
        }

        $passengers = (int)$orderData['passengers'];
        $pricePerTicket = (float)$ticketInfo['price'];

        return $passengers * $pricePerTicket;
    }

    /**
     * Форматировать дату и время платежа
     */
    public function formatPaymentDateTime(string $date, string $departureTime, array $month): string
    {
        $day = (int)explode('-', $date)[2];
        $time = date('H:i', strtotime($departureTime));
        
        return $day . ' ' . $month['title'] . ' ' . $time;
    }
}
