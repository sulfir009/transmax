<?php

namespace App\Repository\Races;

use App\Helpers\DBUtil;
use App\Repository\Races\Params\TicketParams;
use Illuminate\Support\Facades\DB;

class TicketRepository
{
    public function getCityTitle(int $id, $lang = 'ru')
    {
        return DB::table('mt_cities')
            ->where('id', $id)
            ->value('title_' . $lang);
    }

    public function getFreeTickets(int $tourId, int $rangeOfStop, string $date): int
    {
        return (int) DB::table('mt_orders as o')
            ->leftJoin('mt_tours_stops as tss', function($join) {
                $join->on('tss.tour_id', '=', 'o.tour_id')
                    ->on('o.to_stop', '=', 'tss.stop_id');
            })
            ->where('o.tour_id', $tourId)
            ->where('o.tour_date', $date)
            ->where('tss.stop_num', '<=', $rangeOfStop)
            ->sum('o.passagers');
    }

    public function getAllByParams(TicketParams $ticketParams)
    {
        $departureSectionId = $ticketParams->getDepartureSectionId();
        $arrivalSectionId = $ticketParams->getArrivalSectionId();
        $date = $ticketParams->getDate();
        
        // Вычисляем день недели из даты (1 = Понедельник, 7 = Воскресенье)
        $weekDay = null;
        if ($date && $date !== 'today') {
            $weekDay = date('N', strtotime($date));
        }

        $query = DB::table('mt_tours as t')
            ->select([
                't.id',
                't.departure',
                't.arrival',
                'dc.title_' . $ticketParams->getLang() . ' as departure_city',
                'dc.section_id as departure_city_section_id',
                'ac.title_' . $ticketParams->getLang() . ' as arrival_city',
                'ac.section_id as arrival_city_section_id',
                'b.title_' . $ticketParams->getLang() . ' as bus_title',
                'tsl.free_tickets',
            ])
            ->leftJoin('mt_cities as dc', 'dc.id', '=', 't.departure')
            ->leftJoin('mt_cities as ac', 'ac.id', '=', 't.arrival')
            ->leftJoin('mt_buses as b', 'b.id', '=', 't.bus')
            ->leftJoin('mt_tours_stops_prices as tsp', 'tsp.tour_id', '=', 't.id')
            ->leftJoin('mt_tours_stops as ts', 'ts.tour_id', '=', 't.id')
            ->leftJoin('mt_tours_sales as tsl', 'tsl.tour_id', '=', 't.id')
            ->where('t.active', 1)
            ->where('tsl.tour_date', $date)
            // Фильтрация по дню недели - тур должен выполняться в этот день
            ->when($weekDay, function ($query, $weekDay) {
                return $query->where('t.days', 'LIKE', '%' . $weekDay . '%');
            })
            ->where(function ($subQuery) use ($departureSectionId) {
                $subQuery->where('t.departure', $departureSectionId)
                    ->orWhereIn('t.id', function ($sub) use ($departureSectionId) {
                        $sub->select('tour_id')
                            ->from('mt_tours_stops_prices')
                            ->whereIn('from_stop', function ($sub2) use ($departureSectionId) {
                                $sub2->select('id')
                                    ->from('mt_cities')
                                    ->where('section_id', $departureSectionId);
                            });
                    });
            })
            ->where(function ($subQuery) use ($arrivalSectionId) {
                $subQuery->where('t.arrival', $arrivalSectionId)
                    ->orWhereIn('t.id', function ($sub) use ($arrivalSectionId) {
                        $sub->select('tour_id')
                            ->from('mt_tours_stops_prices')
                            ->whereIn('to_stop', function ($sub2) use ($arrivalSectionId) {
                                $sub2->select('id')
                                    ->from('mt_cities')
                                    ->where('section_id', $arrivalSectionId);
                            });
                    });
            })
            ->groupBy([
                't.id',
                't.departure',
                't.arrival',
                'dc.title_' . $ticketParams->getLang(),
                'dc.section_id',
                'ac.title_' . $ticketParams->getLang(),
                'ac.section_id',
                'b.title_' . $ticketParams->getLang(),
                'tsl.free_tickets',
            ]);
        return $query->get();
    }

    public function getTimeDepartureStation(int $tourId, int $sectionId, string $lang = 'ru')
    {
        $query = DB::table('mt_cities as c')
            ->select([
                'c.id as station_id',
                'c.title_' . $lang . ' as dep_station_title',
                'ts.departure_time as dep_time',
                'ts.stop_num'
            ])
            ->join('mt_tours_stops as ts', function ($join) use ($tourId) {
                $join->on('ts.stop_id', '=', 'c.id')
                    ->where('ts.tour_id', '=', $tourId);
            })
            ->join('mt_tours_stops_prices as tsp', function ($join) {
                $join->on('tsp.from_stop', '=', 'c.id')
                    ->where('tsp.price', '>', 0);
            })
            ->where([
                ['c.active', '=', 1],
                ['c.section_id', '=', $sectionId],
                ['c.station', '=', 1],
            ])
            ->groupBy('c.id', 'c.title_' . $lang, 'ts.departure_time', 'ts.stop_num')
            ->orderByDesc('c.sort');
        return $query->get();
    }

    public function getTimeArrivalStation(int $tourId, int $sectionId, string $lang = 'ru')
    {
        $query = DB::table('mt_cities as c')
            ->distinct()
            ->select([
                'c.id as station_id',
                'c.title_' . $lang . ' as arr_station_title',
                'ts.arrival_time as arr_time',
                'ts.arrival_day as days',
                'c.sort'
            ])
            ->leftJoin('mt_tours_stops_prices as tsp', 'tsp.to_stop', '=', 'c.id')
            ->leftJoin('mt_tours_stops as ts', 'ts.stop_id', '=', 'c.id')
            ->where('c.active', 1)
            ->where('c.section_id', $sectionId)
            ->where('ts.tour_id', $tourId)
            ->where('c.station', 1)
            ->where('tsp.price', '>', 0)
            ->orderBy('c.sort', 'desc');

        return $query->get();
    }

    public function getTransferStationIdByTourId(int $tourId)
    {
        return DB::table('mt_tours_transfers')
            ->where('tour_id', $tourId)
            ->first();
    }

    public function getStopsByTourId(int $tourId)
    {
        return DB::table('mt_tours_stops')
            ->select(['stop_id', 'arrival_time', 'departure_time', 'arrival_day'])
            ->where('tour_id', $tourId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getPricesByStops(int $tourId, int $fromId, int $toId)
    {
        return DB::table('mt_tours_stops_prices')
            ->where('from_stop', $fromId)
            ->where('to_stop', $toId)
            ->where('tour_id', $tourId)
            ->first();
    }
}
