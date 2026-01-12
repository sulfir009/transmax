<?php

namespace App\Repository\Races;

use App\Service\Site;
use Illuminate\Support\Facades\DB;

class ToursRepository
{
    const TABLE = 'mt_tours';
    const TABLE_TOURS_STOPS = 'mt_tours_stops';

    const TABLE_CITIES = 'mt_cities';
    const TABLE_TOURS_STOPS_PRICE = 'mt_tours_stops_prices';

    public function getTourInfo($tourId, $stopId)
    {
        $lang = Site::lang();
        $query = DB::table(self::TABLE, 't')
            ->select([
                "c2.title_{$lang} as departure_city",
                "c1.title_{$lang} as departure_station",
                "ts.departure_time as departure_time",
                "tsp.price as price"
            ])
            ->join(
                table: self::TABLE_TOURS_STOPS . ' as ts',
                first: 'ts.tour_id',
                operator: '=',
                second: 't.id',
                type: 'left'
            )
            ->join(
                table: self::TABLE_CITIES . ' as c1',
                first: 'c1.id',
                operator: '=',
                second: 'ts.stop_id',
                type: 'left'
            )
            ->join(
                table: self::TABLE_CITIES . ' as c2',
                first: 'c1.section_id',
                operator: '=',
                second: 'c2.id',
                type: 'left'
            )
            ->join(
                table: self::TABLE_TOURS_STOPS_PRICE . ' as tsp',
                first: 'tsp.tour_id',
                operator: '=',
                second: 't.id',
                type: 'left'
            )
            ->where('t.id', '=', $tourId)
            ->where('tsp.to_stop', '=', $stopId)
            ->orderBy("ts.stop_num", 'ASC');


        return $query->get();
    }

    /**
     * Получить международные туры
     */
    public function getInternationalTours(string $lang): array
    {
        $dbPrefix = config('database.prefix', 'mt');
        
        return DB::table($dbPrefix . '_tours as t')
            ->join($dbPrefix . '_cities as departure_city', 't.departure', '=', 'departure_city.id')
            ->join($dbPrefix . '_cities as arrival_city', 't.arrival', '=', 'arrival_city.id')
            ->selectRaw("
                t.id,
                t.departure,
                t.arrival,
                departure_city.title_{$lang} AS departure_city,
                arrival_city.title_{$lang} AS arrival_city,
                departure_city.id AS departure_city_id,
                arrival_city.id AS arrival_city_id
            ")
            ->whereRaw('departure_city.section_id != arrival_city.section_id')
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
    }

    /**
     * Получить внутренние туры (в пределах Украины)
     */
    public function getHomeTours(string $lang): array
    {
        $dbPrefix = config('database.prefix', 'mt');
        
        return DB::table($dbPrefix . '_tours as t')
            ->join($dbPrefix . '_cities as departure_city', 't.departure', '=', 'departure_city.id')
            ->join($dbPrefix . '_cities as arrival_city', 't.arrival', '=', 'arrival_city.id')
            ->selectRaw("
                t.id,
                t.departure,
                t.arrival,
                departure_city.title_{$lang} AS departure_city,
                arrival_city.title_{$lang} AS arrival_city,
                departure_city.id AS departure_city_id,
                arrival_city.id AS arrival_city_id
            ")
            ->where('departure_city.section_id', '13')
            ->where('arrival_city.section_id', '13')
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
    }
}
