<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class TicketRepository
{
    protected $lang;
    protected $dbPrefix;
    
    public function __construct()
    {
        $this->dbPrefix = config('database.prefix', 'mt');
    }
    
    /**
     * Установить язык для запросов
     */
    public function setLanguage(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }
    
    /**
     * Получить цены билетов для фильтра
     */
    public function getTicketPrices(int $filterDeparture = 0, int $filterArrival = 0): array
    {
        $query = DB::table($this->dbPrefix . '_tours_stops_prices as tsp')
            ->select(DB::raw('MAX(tsp.price) AS price'))
            ->leftJoin($this->dbPrefix . '_tours as t', 't.id', '=', 'tsp.tour_id')
            ->leftJoin($this->dbPrefix . '_tours_stops as ts', 'ts.tour_id', '=', 'tsp.tour_id')
            ->where('t.active', 1)
            ->groupBy('tsp.tour_id');
        
        if ($filterDeparture > 0) {
            $departureStations = DB::table($this->dbPrefix . '_cities')
                ->where('section_id', $filterDeparture)
                ->pluck('id');
            
            $query->whereIn('tsp.from_stop', $departureStations);
        }
        
        if ($filterArrival > 0) {
            $arrivalStations = DB::table($this->dbPrefix . '_cities')
                ->where('section_id', $filterArrival)
                ->pluck('id');
            
            $query->whereIn('tsp.to_stop', $arrivalStations);
        }
        
        // Убираем сортировку по tsp.id, так как она не нужна для получения цен
        // и вызывает ошибку с only_full_group_by
        return $query->get()->pluck('price')->toArray();
    }
    
    /**
     * Получить доступные дни для рекомендаций
     */
    public function getAvailableDays(int $filterDeparture, int $filterArrival): array
    {
        $query = DB::table($this->dbPrefix . '_tours as t')
            ->selectRaw('DISTINCT t.days')
            ->leftJoin($this->dbPrefix . '_cities as dc', 'dc.id', '=', 't.departure')
            ->leftJoin($this->dbPrefix . '_cities as ac', 'ac.id', '=', 't.arrival')
            ->leftJoin($this->dbPrefix . '_cities as dcountry', 'dcountry.id', '=', 'dc.section_id')
            ->leftJoin($this->dbPrefix . '_buses as b', 't.bus', '=', 'b.id')
            ->leftJoin($this->dbPrefix . '_tours_stops_prices as tsp', 'tsp.tour_id', '=', 't.id')
            ->leftJoin($this->dbPrefix . '_tours_stops as ts', 'ts.tour_id', '=', 't.id')
            ->where('t.active', '1');
        
        // Фильтр отправления
        if ($filterDeparture > 0) {
            $query->where(function($q) use ($filterDeparture) {
                $q->where('t.departure', $filterDeparture)
                  ->orWhereIn('t.id', function($subQuery) use ($filterDeparture) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('from_stop', function($subSubQuery) use ($filterDeparture) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $filterDeparture);
                          });
                  });
            });
        }
        
        // Фильтр прибытия
        if ($filterArrival > 0) {
            $query->where(function($q) use ($filterArrival) {
                $q->where('t.arrival', $filterArrival)
                  ->orWhereIn('t.id', function($subQuery) use ($filterArrival) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('to_stop', function($subSubQuery) use ($filterArrival) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $filterArrival);
                          });
                  });
            });
        }
        
        // Убираем сортировку полностью, так как используем DISTINCT t.days
        // и не можем сортировать по полям, которых нет в SELECT
        // $query->orderBy('dc.section_id', 'ASC'); // Закомментировано из-за ошибки MySQL
        
        $results = $query->get();
        
        $availableDays = [];
        foreach ($results as $row) {
            if (!empty($row->days)) {
                $daysOfWeek = explode(',', $row->days);
                $availableDays = array_merge($availableDays, $daysOfWeek);
            }
        }
        
        return array_unique(array_filter($availableDays));
    }
    
    /**
     * Получить остановки для билета
     */
    public function getTicketStops(int $tourId): array
    {
        return DB::table($this->dbPrefix . '_tours_stops')
            ->select('stop_id', 'arrival_time', 'departure_time', 'arrival_day')
            ->where('tour_id', $tourId)
            ->orderBy('id', 'ASC')
            ->get()
            ->toArray();
    }
    
    /**
     * Получить детали станции отправления
     */
    public function getDepartureDetails(int $tourId, int $tourDeparture): ?array
    {
        $result = DB::table($this->dbPrefix . '_cities as station')
            ->select([
                'station.id',
                DB::raw('station.title_' . $this->lang . ' as station'),
                DB::raw('city.title_' . $this->lang . ' as city'),
                'stop.departure_time'
            ])
            ->leftJoin($this->dbPrefix . '_cities as city', 'city.id', '=', 'station.section_id')
            ->leftJoin($this->dbPrefix . '_tours_stops as stop', function($join) use ($tourId) {
                $join->on('stop.stop_id', '=', 'station.id')
                     ->where('stop.tour_id', '=', $tourId);
            })
            ->where('station.station', 1)
            ->where('station.section_id', $tourDeparture)
            ->whereIn('station.id', function($query) use ($tourId) {
                $query->select('stop_id')
                      ->from($this->dbPrefix . '_tours_stops')
                      ->where('tour_id', $tourId);
            })
            ->first();
        
        return $result ? (array)$result : null;
    }
    
    /**
     * Получить детали станции прибытия
     */
    public function getArrivalDetails(int $tourId, int $tourArrival): ?array
    {
        $result = DB::table($this->dbPrefix . '_cities as station')
            ->select([
                'station.id',
                DB::raw('station.title_' . $this->lang . ' as station'),
                DB::raw('city.title_' . $this->lang . ' as city'),
                'stop.arrival_time',
                'stop.arrival_day'
            ])
            ->leftJoin($this->dbPrefix . '_cities as city', 'city.id', '=', 'station.section_id')
            ->leftJoin($this->dbPrefix . '_tours_stops as stop', function($join) use ($tourId) {
                $join->on('stop.stop_id', '=', 'station.id')
                     ->where('stop.tour_id', '=', $tourId);
            })
            ->where('station.station', 1)
            ->where('station.section_id', $tourArrival)
            ->whereIn('station.id', function($query) use ($tourId) {
                $query->select('stop_id')
                      ->from($this->dbPrefix . '_tours_stops')
                      ->where('tour_id', $tourId);
            })
            ->first();
        
        return $result ? (array)$result : null;
    }
    
    /**
     * Получить цену билета
     */
    public function getTicketPrice(int $tourId, int $fromStop, int $toStop): ?float
    {
        return DB::table($this->dbPrefix . '_tours_stops_prices')
            ->where('tour_id', $tourId)
            ->where('from_stop', $fromStop)
            ->where('to_stop', $toStop)
            ->value('price');
    }
    
    /**
     * Подсчет билетов для пагинации
     */
    public function countTickets(array $filters = []): int
    {
        $query = DB::table($this->dbPrefix . '_tours as t')
            ->where('active', '1');
        
        if (!empty($filters['departure'])) {
            $query->where(function($q) use ($filters) {
                $q->where('t.departure', $filters['departure'])
                  ->orWhereIn('t.id', function($subQuery) use ($filters) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('from_stop', function($subSubQuery) use ($filters) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $filters['departure']);
                          });
                  });
            });
        }
        
        if (!empty($filters['arrival'])) {
            $query->where(function($q) use ($filters) {
                $q->where('t.arrival', $filters['arrival'])
                  ->orWhereIn('t.id', function($subQuery) use ($filters) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('to_stop', function($subSubQuery) use ($filters) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $filters['arrival']);
                          });
                  });
            });
        }
        
        if (!empty($filters['weekDay'])) {
            $query->where('t.days', 'LIKE', '%' . $filters['weekDay'] . '%');
        }
        
        return $query->count();
    }
    
    /**
     * Получить билеты с фильтрами
     */
    public function getTicketsWithFilters(array $filters = [], int $limit = 6, int $offset = 0): array
    {
        $query = DB::table($this->dbPrefix . '_tours as t')
            ->select([
                't.id',
                't.departure',
                't.arrival',
                't.days',
                't.bus',
                DB::raw('dc.title_' . $this->lang . ' AS departure_city'),
                'dc.section_id AS departure_city_section_id',
                DB::raw('ac.title_' . $this->lang . ' AS arrival_city'),
                'ac.section_id AS arrival_city_section_id',
                DB::raw('b.title_' . $this->lang . ' AS bus_title')
            ])
            ->leftJoin($this->dbPrefix . '_cities as dc', 'dc.id', '=', 't.departure')
            ->leftJoin($this->dbPrefix . '_cities as ac', 'ac.id', '=', 't.arrival')
            ->leftJoin($this->dbPrefix . '_buses as b', 't.bus', '=', 'b.id')
            ->where('t.active', '1');
        
        // Применяем фильтры
        if (!empty($filters['departure'])) {
            $query->where(function($q) use ($filters) {
                $q->where('t.departure', $filters['departure'])
                  ->orWhereIn('t.id', function($subQuery) use ($filters) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('from_stop', function($subSubQuery) use ($filters) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $filters['departure']);
                          });
                  });
            });
        }
        
        if (!empty($filters['arrival'])) {
            $query->where(function($q) use ($filters) {
                $q->where('t.arrival', $filters['arrival'])
                  ->orWhereIn('t.id', function($subQuery) use ($filters) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('to_stop', function($subSubQuery) use ($filters) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $filters['arrival']);
                          });
                  });
            });
        }
        
        if (!empty($filters['weekDay'])) {
            $query->where('t.days', 'LIKE', '%' . $filters['weekDay'] . '%');
        }
        
        // Фильтр по цене
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->whereExists(function ($q) use ($filters) {
                $q->select(DB::raw(1))
                  ->from($this->dbPrefix . '_tours_stops_prices as tsp')
                  ->whereColumn('tsp.tour_id', 't.id');
                
                if (!empty($filters['min_price'])) {
                    $q->where('tsp.price', '>=', $filters['min_price']);
                }
                if (!empty($filters['max_price'])) {
                    $q->where('tsp.price', '<=', $filters['max_price']);
                }
            });
        }
        
        // Добавляем лимит и смещение
        if ($limit > 0) {
            $query->limit($limit);
        }
        if ($offset > 0) {
            $query->offset($offset);
        }
        
        // Убедимся, что получаем уникальные результаты
        $query->distinct();
        
        return $query->get()->toArray();
    }
}