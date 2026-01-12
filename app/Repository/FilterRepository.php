<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class FilterRepository
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
     * Получить все активные города для фильтра
     */
    public function getActiveCities(): array
    {
        $titleColumn = 'title_' . $this->lang;

        return DB::table($this->dbPrefix . '_cities')
            ->select(
                'id',
                DB::raw($titleColumn . ' AS title'),
                'station',
                'section_id'
            )
            ->where('active', 1)
            ->where('section_id', '>', 0)
            ->where('station', 0)
            ->orderByDesc('sort')
            ->orderBy(DB::raw($titleColumn), 'ASC')
            ->get()
            ->map(function ($city) {
                return [
                    'id' => $city->id,
                    'title' => $city->title,
                    'station' => $city->station,
                    'section_id' => $city->section_id
                ];
            })
            ->toArray();
    }

    /**
     * Получить список городов для фильтра
     */
    public function getCitiesForFilter(): Collection
    {
        $titleColumn = 'title_' . $this->lang;

        return DB::table($this->dbPrefix . '_cities')
            ->select([
                'id',
                DB::raw($titleColumn . ' AS title'),
                'section_id',
                'station',
                'sort'
            ])
            ->where('active', 1)
            ->where('section_id', '>', 0)
            ->where('station', 0)
            ->orderByDesc('sort')
            ->orderBy($titleColumn, 'ASC')
            ->get();
    }

    /**
     * Получить все станции для города
     */
    public function getCityStations(int $cityId): array
    {
        $titleColumn = 'title_' . $this->lang;

        return DB::table($this->dbPrefix . '_cities as station')
            ->select(
                'station.id',
                DB::raw('station.' . $titleColumn . ' AS title'),
                DB::raw('city.' . $titleColumn . ' AS city_title'),
                'station.station'
            )
            ->leftJoin($this->dbPrefix . '_cities as city', 'city.id', '=', 'station.section_id')
            ->where('station.active', 1)
            ->where('station.section_id', $cityId)
            ->where('station.station', 1)
            ->orderBy(DB::raw('station.' . $titleColumn), 'ASC')
            ->get()
            ->map(function ($station) {
                return [
                    'id' => $station->id,
                    'title' => $station->city_title . ' ' . $station->title,
                    'station' => $station->station,
                    'station_title' => $station->title,
                    'city_title' => $station->city_title
                ];
            })
            ->toArray();
    }

    /**
     * Получить станции для города
     */
    public function getStationsForCity(int $cityId): Collection
    {
        $titleColumn = 'title_' . $this->lang;

        return DB::table($this->dbPrefix . '_cities as station')
            ->select([
                'station.id',
                DB::raw('station.' . $titleColumn . ' AS station_title'),
                DB::raw('city.' . $titleColumn . ' AS city_title'),
                'station.station'
            ])
            ->leftJoin($this->dbPrefix . '_cities as city', 'city.id', '=', 'station.section_id')
            ->where('station.active', 1)
            ->where('station.station', 1)
            ->where('station.section_id', $cityId)
            ->orderBy('station.' . $titleColumn, 'ASC')
            ->get();
    }

    /**
     * Получить доступные даты для маршрута
     */
    public function getAvailableDatesForRoute(int $departureId, int $arrivalId): array
    {
        // Используем GROUP BY вместо DISTINCT для избежания проблем с ORDER BY
        $query = DB::table($this->dbPrefix . '_tours as t')
            ->select('t.days')
            ->where('t.active', 1)
            ->whereNotNull('t.days')
            ->where('t.days', '!=', '');

        // Фильтр отправления
        if ($departureId > 0) {
            $query->where(function($q) use ($departureId) {
                $q->where('t.departure', $departureId)
                  ->orWhereIn('t.id', function($subQuery) use ($departureId) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('from_stop', function($subSubQuery) use ($departureId) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $departureId);
                          });
                  });
            });
        }

        // Фильтр прибытия
        if ($arrivalId > 0) {
            $query->where(function($q) use ($arrivalId) {
                $q->where('t.arrival', $arrivalId)
                  ->orWhereIn('t.id', function($subQuery) use ($arrivalId) {
                      $subQuery->select('tour_id')
                          ->from($this->dbPrefix . '_tours_stops_prices')
                          ->whereIn('to_stop', function($subSubQuery) use ($arrivalId) {
                              $subSubQuery->select('id')
                                  ->from($this->dbPrefix . '_cities')
                                  ->where('section_id', $arrivalId);
                          });
                  });
            });
        }

        // Используем группировку вместо DISTINCT
        $query->groupBy('t.days');

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
     * Проверить доступность маршрута
     */
    public function checkRouteAvailability(int $departureId, int $arrivalId): bool
    {
        $query = DB::table($this->dbPrefix . '_tours as t')
            ->where('t.active', 1);

        if ($departureId > 0 && $arrivalId > 0) {
            $query->where(function($q) use ($departureId, $arrivalId) {
                $q->where(function($sub) use ($departureId, $arrivalId) {
                    $sub->where('t.departure', $departureId)
                        ->where('t.arrival', $arrivalId);
                })->orWhereIn('t.id', function($subQuery) use ($departureId, $arrivalId) {
                    $subQuery->select('tour_id')
                        ->from($this->dbPrefix . '_tours_stops_prices as tsp')
                        ->whereIn('tsp.from_stop', function($fromQuery) use ($departureId) {
                            $fromQuery->select('id')
                                ->from($this->dbPrefix . '_cities')
                                ->where('section_id', $departureId);
                        })
                        ->whereIn('tsp.to_stop', function($toQuery) use ($arrivalId) {
                            $toQuery->select('id')
                                ->from($this->dbPrefix . '_cities')
                                ->where('section_id', $arrivalId);
                        });
                });
            });
        }

        return $query->exists();
    }

    /**
     * Получить все города и станции для выбора
     */
    public function getAllCitiesWithStations(): array
    {
        $cities = $this->getActiveCities();
        $result = [];

        foreach ($cities as $city) {
            $result[] = $city;

            // Получаем станции для города
            $stations = $this->getCityStations($city['id']);
            foreach ($stations as $station) {
                $result[] = $station;
            }
        }

        return $result;
    }

    /**
     * Получить город по умолчанию (первый на букву А)
     */
    public function getDefaultCity(): ?int
    {
        $titleColumn = 'title_' . $this->lang;

        $city = DB::table($this->dbPrefix . '_cities')
            ->select('id')
            ->where('active', 1)
            ->where('section_id', '>', 0)
            ->where('station', 0)
            ->where(DB::raw('UPPER(SUBSTRING(' . $titleColumn . ', 1, 1))'), 'А')
            ->orderBy(DB::raw($titleColumn), 'ASC')
            ->first();

        return $city ? $city->id : null;
    }
}
