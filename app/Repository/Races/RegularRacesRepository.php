<?php

namespace App\Repository\Races;

use App\Repository\Races\Params\RegularRaceParams;
use App\Service\Site;
use App\Service\Tour\Enums\TourEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RegularRacesRepository
{
    const TABLE = 'mt_regular_races';
    const TABLE_REGULAR_RACES_ALIAS = 'mt_regular_race_alias';
    const TABLE_TOURS = 'mt_tours';
    const TABLE_TOURS_STOP = 'mt_tours_stops';
    const TABLE_CITIES = 'mt_cities';

    public function getAllAlias(): Collection
    {
        $lang = Site::lang();

        return DB::table(self::TABLE_REGULAR_RACES_ALIAS, 'rra')
            ->select([
                'rra.id as id',
                "rra.title_en as title",
                DB::raw('LOWER(rra.title_en) as alias')
            ])
            ->get();
    }

    public function getTitle(): Collection
    {
        $lang = Site::lang();
        $lang = $lang == 'uk' ? 'ua' : $lang;

        return DB::table(self::TABLE_REGULAR_RACES_ALIAS, 'rra')
            ->select([
                'rra.id as id',
                "rra.title_" . $lang . " as title",
                DB::raw('LOWER(rra.title_en) as alias')
            ])
            ->get();
    }

    public function getImagesByAlias($alias)
    {
       /* dd($alias);*/
        return DB::table(self::TABLE_REGULAR_RACES_ALIAS, 'rra')
            ->select([
                'rra.image_mob',
                "rra.image_desc",
            ])
            ->where(DB::raw('LOWER(rra.title_en)'), '=', strtolower($alias))
            ->get();
    }

    public function getNightRegularRacesById(RegularRaceParams $params)
    {
        $query = $this->basicRegularTourQuery();

        return $query
            ->whereIn('t.id', $params->getTourIds())
            ->where(function ($query) {
                $query->where('ts.departure_time', '>=', TourEnum::NIGHT_TOUR)
                    ->orWhere('ts.departure_time', '<', TourEnum::DAYS_TOUR);
            })
            ->get();
    }

    public function getDaysRegularRacesById(RegularRaceParams $params)
    {
        $query = $this->basicRegularTourQuery();

        return $query
            ->whereIn('t.id', $params->getTourIds())
            ->whereBetween('ts.departure_time', [TourEnum::DAYS_TOUR, TourEnum::NIGHT_TOUR])
            ->get();
    }

    public function getRegularRacesById(RegularRaceParams $params)
    {
        $query = $this->basicRegularTourQuery();

        return $query
            ->whereIn('t.id', $params->getTourIds())
            ->get();
    }

    public function basicRegularTourQuery()
    {
        return DB::table(self::TABLE , 'rr')
            ->select([
                't.id as id',
                DB::raw('ANY_VALUE(rra.image_mob) as image_mob'),
                DB::raw('ANY_VALUE(rra.image_desc) as image_desc'),
                DB::raw('ANY_VALUE(cityDeparture.title_ru) as departure'),
                DB::raw('ANY_VALUE(cityArrive.title_ru) as arrive'),
                DB::raw('ANY_VALUE(t.days) as days'),
                DB::raw('ANY_VALUE(tsid.id) as toursStopsId'),
                DB::raw('ANY_VALUE(ts.departure_time) as depTime'),
                DB::raw('ANY_VALUE(t.departure) as departureId'),
                DB::raw('ANY_VALUE(t.arrival) as arrivalId')
            ])
            ->leftJoin(self::TABLE_REGULAR_RACES_ALIAS . ' as rra', 'rr.regular_race_alias_id', '=', 'rra.id')
            ->leftJoin(self::TABLE_TOURS . ' as t', 't.id', '=', 'rr.tour_id')
            ->leftJoin(self::TABLE_CITIES . ' as cityDeparture', 'cityDeparture.id', '=', 't.departure')
            ->leftJoin(self::TABLE_CITIES . ' as cityArrive', 'cityArrive.id', '=', 't.arrival')
            ->leftJoin(self::TABLE_TOURS_STOP . ' as tsid', function ($join) {
                $join->on('tsid.tour_id', '=', 't.id')
                    ->whereRaw('tsid.stop_num = (SELECT MIN(tsid2.stop_num) FROM mt_tours_stops tsid2 WHERE tsid2.tour_id = t.id)');
            })
            ->leftJoin(self::TABLE_TOURS_STOP . ' as ts', function ($join) {
                $join->on('ts.tour_id', '=', 't.id')
                    ->whereRaw('ts.stop_num = (SELECT MIN(ts2.stop_num) FROM mt_tours_stops ts2 WHERE ts2.tour_id = t.id)');
            })
            ->groupBy('t.id')
            ->orderBy('depTime');
    }

    public function getTourIdsByAlias($alias)
    {
        return DB::table(self::TABLE, 'rr')
            ->select([
                't.id'
            ])
            ->join(
                table: self::TABLE_REGULAR_RACES_ALIAS . ' as rra',
                first: 'rr.regular_race_alias_id',
                operator: '=',
                second: 'rra.id',
                type: 'left'
            )
            ->join(
                table: self::TABLE_TOURS . ' as t',
                first: 't.id',
                operator: '=',
                second: 'rr.tour_id',
                type: 'inner'
            )
            ->where(DB::raw('LOWER(rra.title_en)'), '=', strtolower($alias))
            ->get();
    }

}
