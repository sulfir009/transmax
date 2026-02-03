<?php

namespace App\Repository\Races;

use App\Service\Site;
use Illuminate\Support\Facades\DB;

class TourStopsRepository
{
    const TABLE = 'mt_tours_stops';
    const TABLE_CITY = 'mt_cities';
    const TABLE_TOURS_STOPS_PRICE = 'mt_tours_stops_prices';

    public function getStopsByTourIds($tourIds)
    {
        $lang = Site::lang();
        $query = DB::table(self::TABLE, 'tt')
            ->select(
                [
                    'tt.tour_id',
                    DB::raw('MIN(tt.stop_id) as stop_id'),
                    DB::raw('MIN(tt.arrival_time) as arrival_time'),
                    DB::raw('MIN(c.section_id) as section_id'),
                    DB::raw('MIN(c.title_' . $lang . ') as stopTitle'),
                    DB::raw('MIN(stopC.title_' . $lang . ') as stopCity'),
                    DB::raw('MIN(p.price) as price'),
                    DB::raw('MIN(tt.stop_num) as stop_num'),
                ]
            )->join(
                table: self::TABLE_CITY . ' as c',
                first: 'c.id',
                operator: '=',
                second: 'tt.stop_id',
                type: 'left'
            )
            ->join(
                table: self::TABLE_CITY . ' as stopC',
                first: 'stopC.id',
                operator: '=',
                second: 'c.section_id',
                type: 'left'
            )
            ->join(
                table: self::TABLE_TOURS_STOPS_PRICE . ' as p',
                first: 'p.tour_id',
                operator: '=',
                second: 'tt.tour_id',
                type: 'left'
            )
            ->whereIn('tt.tour_id', $tourIds)
            ->groupBy('tt.tour_id', 'tt.stop_id');

        return $query->get();
    }
}
