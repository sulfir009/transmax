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
                arrival_city.id AS arrival_city_id,
                departure_city.slug_{$lang} AS departure_city_slug,
                arrival_city.slug_{$lang} AS arrival_city_slug
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
        $ukraineCountryId = $this->getUkraineCountryId($dbPrefix);

        if ($ukraineCountryId === null) {
            return [];
        }

        return DB::table($dbPrefix . '_tours_stops_prices as tsp')
            ->join($dbPrefix . '_tours as t', 't.id', '=', 'tsp.tour_id')
            ->join($dbPrefix . '_cities as from_station', 'from_station.id', '=', 'tsp.from_stop')
            ->join($dbPrefix . '_cities as to_station', 'to_station.id', '=', 'tsp.to_stop')
            ->join($dbPrefix . '_cities as departure_city', 'departure_city.id', '=', 'from_station.section_id')
            ->join($dbPrefix . '_cities as arrival_city', 'arrival_city.id', '=', 'to_station.section_id')
            ->selectRaw("
                MIN(t.id) as id,
                departure_city.id as departure_city_id,
                arrival_city.id as arrival_city_id,
                departure_city.id as departure,
                arrival_city.id as arrival,
                departure_city.title_{$lang} AS departure_city,
                arrival_city.title_{$lang} AS arrival_city,
                departure_city.slug_{$lang} AS departure_city_slug,
                arrival_city.slug_{$lang} AS arrival_city_slug
            ")
            ->where('t.active', 1)
            ->where('tsp.price', '>', 0)
            ->where('departure_city.section_id', $ukraineCountryId)
            ->where('arrival_city.section_id', $ukraineCountryId)
            ->whereColumn('departure_city.id', '!=', 'arrival_city.id')
            ->groupBy([
                'departure_city.id',
                'arrival_city.id',
                'departure_city.title_' . $lang,
                'arrival_city.title_' . $lang,
                'departure_city.slug_' . $lang,
                'arrival_city.slug_' . $lang,
            ])
            ->orderBy('departure_city.title_' . $lang)
            ->orderBy('arrival_city.title_' . $lang)
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
    }

    private function getUkraineCountryId(string $dbPrefix): ?int
    {
        $countryId = DB::table($dbPrefix . '_cities')
            ->where('section_id', 0)
            ->where('title_en', 'Ukraine')
            ->value('id');

        return $countryId !== null ? (int) $countryId : null;
    }
}
