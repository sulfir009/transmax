<?php

namespace App\Repository\Schedule;

use App\Models\City;
use App\Models\Tour;
use App\Models\TourStop;
use App\Models\TourStopPrice;
use App\Service\Site;
use Illuminate\Support\Facades\DB;

class ScheduleRepository
{
    /**
     * Get filtered routes with pagination
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredRoutes(array $filters, int $page = 1, int $perPage = 16)
    {
        $lang = Site::lang();
        $dbPrefix = env('DB_PREFIX', 'mt');
        
        // Сначала получаем уникальные ID туров с применением фильтров
        $tourIdsQuery = DB::table("{$dbPrefix}_tours as t")
            ->select('t.id')
            ->leftJoin("{$dbPrefix}_cities as dc", 'dc.id', '=', 't.departure')
            ->leftJoin("{$dbPrefix}_cities as ac", 'ac.id', '=', 't.arrival')
            ->leftJoin("{$dbPrefix}_tours_stops_prices as tsp", 'tsp.tour_id', '=', 't.id')
            ->where('t.active', '1');

        // Применяем фильтры к подзапросу
        if (!empty($filters['departure']) && !empty($filters['arrival'])) {
            $tourIdsQuery->where('t.departure', $filters['departure'])
                         ->where('t.arrival', $filters['arrival']);
        }

        if (!empty($filters['country'])) {
            $tourIdsQuery->where(function($q) use ($filters) {
                $q->where('dc.section_id', $filters['country'])
                  ->orWhere('ac.section_id', $filters['country']);
            });
        }

        if (!empty($filters['city'])) {
            $cityId = $filters['city'];
            
            $tourIdsQuery->where(function($q) use ($cityId, $dbPrefix) {
                $q->where('t.departure', $cityId)
                  ->orWhereIn('t.id', function($subQuery) use ($cityId, $dbPrefix) {
                      $subQuery->select('tour_id')
                          ->from("{$dbPrefix}_tours_stops_prices")
                          ->whereIn('from_stop', function($subSubQuery) use ($cityId, $dbPrefix) {
                              $subSubQuery->select('id')
                                  ->from("{$dbPrefix}_cities")
                                  ->where('section_id', $cityId);
                          });
                  })
                  ->orWhere('t.arrival', $cityId)
                  ->orWhereIn('t.id', function($subQuery) use ($cityId, $dbPrefix) {
                      $subQuery->select('tour_id')
                          ->from("{$dbPrefix}_tours_stops_prices")
                          ->whereIn('to_stop', function($subSubQuery) use ($cityId, $dbPrefix) {
                              $subSubQuery->select('id')
                                  ->from("{$dbPrefix}_cities")
                                  ->where('section_id', $cityId);
                          });
                  });
            })
            ->where(function($q) use ($cityId, $dbPrefix) {
                $q->whereIn('tsp.from_stop', function($subQuery) use ($cityId, $dbPrefix) {
                    $subQuery->select('id')
                        ->from("{$dbPrefix}_cities")
                        ->where('section_id', $cityId);
                })
                ->orWhereIn('tsp.to_stop', function($subQuery) use ($cityId, $dbPrefix) {
                    $subQuery->select('id')
                        ->from("{$dbPrefix}_cities")
                        ->where('section_id', $cityId);
                });
            });
        }

        // Получаем уникальные ID туров
        $tourIds = $tourIdsQuery->distinct()->pluck('id');

        // Если нет подходящих туров, возвращаем пустой результат
        if ($tourIds->isEmpty()) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $page);
        }

        // Теперь получаем полные данные для этих туров
        $query = DB::table("{$dbPrefix}_tours as t")
            ->select([
                't.id',
                't.departure',
                't.arrival',
                't.days',
                "dc.title_{$lang} as departure_city",
                'dc.section_id as departure_city_section_id',
                "dcountry.title_{$lang} as departure_country",
                "ac.title_{$lang} as arrival_city",
                'ac.section_id as arrival_city_section_id',
                "b.title_{$lang} as bus_title",
                DB::raw('(SELECT MAX(price) FROM ' . $dbPrefix . '_tours_stops_prices WHERE tour_id = t.id) as max_price')
            ])
            ->leftJoin("{$dbPrefix}_cities as dc", 'dc.id', '=', 't.departure')
            ->leftJoin("{$dbPrefix}_cities as ac", 'ac.id', '=', 't.arrival')
            ->leftJoin("{$dbPrefix}_cities as dcountry", 'dcountry.id', '=', 'dc.section_id')
            ->leftJoin("{$dbPrefix}_buses as b", 'b.id', '=', 't.bus')
            ->whereIn('t.id', $tourIds)
            ->orderByRaw("
                CASE
                    WHEN dc.section_id = 13 THEN ac.section_id
                    ELSE dc.section_id
                END ASC
            ")
            ->orderBy('max_price', 'DESC');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get countries for home display
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCountriesForHome()
    {
        $lang = Site::lang();
        
        return City::where('active', '1')
            ->where('section_id', '0')
            ->where('show_home', '1')
            ->orderBy('sort', 'DESC')
            ->select(['id', "title_{$lang} as title"])
            ->get();
    }

    /**
     * Get popular cities
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getPopularCities(int $limit = 10)
    {
        $lang = Site::lang();
        
        return City::where('active', '1')
            ->where('section_id', '!=', 0)
            ->where('section_id', '!=', 175)
            ->where('station', '0')
            ->orderBy('sort', 'DESC')
            ->limit($limit)
            ->select(['id', "title_{$lang} as title"])
            ->get();
    }

    /**
     * Get international routes
     *
     * @return \Illuminate\Support\Collection
     */
    public function getInternationalRoutes()
    {
        $lang = Site::lang();
        $dbPrefix = env('DB_PREFIX', 'mt');
        
        return DB::table("{$dbPrefix}_tours as t")
            ->join("{$dbPrefix}_cities as departure_city", 't.departure', '=', 'departure_city.id')
            ->join("{$dbPrefix}_cities as arrival_city", 't.arrival', '=', 'arrival_city.id')
            ->whereColumn('departure_city.section_id', '!=', 'arrival_city.section_id')
            ->select([
                't.id',
                't.departure',
                't.arrival',
                "departure_city.title_{$lang} as departure_city",
                'departure_city.id as departure_city_id',
                "arrival_city.title_{$lang} as arrival_city",
                'arrival_city.id as arrival_city_id'
            ])
            ->distinct()
            ->get();
    }

    /**
     * Get domestic routes (Ukraine)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDomesticRoutes()
    {
        $lang = Site::lang();
        $dbPrefix = env('DB_PREFIX', 'mt');
        
        return DB::table("{$dbPrefix}_tours as t")
            ->join("{$dbPrefix}_cities as departure_city", 't.departure', '=', 'departure_city.id')
            ->join("{$dbPrefix}_cities as arrival_city", 't.arrival', '=', 'arrival_city.id')
            ->where('departure_city.section_id', '13')
            ->where('arrival_city.section_id', '13')
            ->select([
                't.id',
                't.departure',
                't.arrival',
                "departure_city.title_{$lang} as departure_city",
                'departure_city.id as departure_city_id',
                "arrival_city.title_{$lang} as arrival_city",
                'arrival_city.id as arrival_city_id'
            ])
            ->distinct()
            ->get();
    }

    /**
     * Get tour details
     *
     * @param int $tourId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTourDetails(int $tourId)
    {
        return Tour::with(['stops', 'departureCityRelation', 'arrivalCityRelation', 'busRelation'])
            ->where('id', $tourId)
            ->where('active', '1')
            ->first();
    }

    /**
     * Get station details for a city
     *
     * @param int $cityId
     * @param int $tourId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getStationDetails(int $cityId, int $tourId)
    {
        $lang = Site::lang();
        $dbPrefix = env('DB_PREFIX', 'mt');
        
        return DB::table("{$dbPrefix}_cities as station")
            ->leftJoin("{$dbPrefix}_cities as city", 'city.id', '=', 'station.section_id')
            ->leftJoin("{$dbPrefix}_tours_stops as stop", function($join) use ($tourId) {
                $join->on('stop.stop_id', '=', 'station.id')
                     ->where('stop.tour_id', '=', $tourId);
            })
            ->where('station.station', 1)
            ->where('station.section_id', $cityId)
            ->whereIn('station.id', function($query) use ($tourId, $dbPrefix) {
                $query->select('stop_id')
                    ->from("{$dbPrefix}_tours_stops")
                    ->where('tour_id', $tourId);
            })
            ->select([
                'station.id',
                "station.title_{$lang} as station",
                "city.title_{$lang} as city",
                'stop.departure_time',
                'stop.arrival_time'
            ])
            ->first();
    }

    /**
     * Get tour stops
     *
     * @param int $tourId
     * @return \Illuminate\Support\Collection
     */
    public function getTourStops(int $tourId)
    {
        return TourStop::where('tour_id', $tourId)
            ->orderBy('id', 'ASC')
            ->get();
    }

    /**
     * Get ticket price between stops
     *
     * @param int $tourId
     * @param int $fromStopId
     * @param int $toStopId
     * @return float|null
     */
    public function getTicketPrice(int $tourId, int $fromStopId, int $toStopId)
    {
        $price = TourStopPrice::where('tour_id', $tourId)
            ->where('from_stop', $fromStopId)
            ->where('to_stop', $toStopId)
            ->value('price');

        return $price;
    }

    /**
     * Get country title by ID
     *
     * @param int $countryId
     * @return string|null
     */
    public function getCountryTitle(int $countryId)
    {
        $lang = Site::lang();
        return City::where('id', $countryId)->value("title_{$lang}");
    }

    /**
     * Get all stop prices for a tour
     *
     * @param int $tourId
     * @return \Illuminate\Support\Collection
     */
    public function getTourStopPrices(int $tourId)
    {
        return TourStopPrice::where('tour_id', $tourId)
            ->with(['fromStop', 'toStop'])
            ->get();
    }
}
