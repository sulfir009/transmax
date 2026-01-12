<?php

namespace App\Repository\Races;

use Illuminate\Support\Facades\DB;

class TourStopPricesRepository
{
    const TABLE = 'mt_tours_stops_prices';

    public function getTicketPrice(
        $tourId,
        $fromId,
        $toId
    )
    {
        return DB::table(self::TABLE, 'tt')
            ->select([
                'price',
            ])
            ->where('from_stop', '=', $fromId)
            ->where('to_stop', '=', $toId)
            ->where('tour_id', '=', $tourId)
            ->first();
    }

    public function getStopPricesByTourIds($tourIds)
    {
        $query = DB::table(self::TABLE, 'ttp')
            ->select('*')
            ->whereIn('tour_id', $tourIds);

        return $query->get();
    }
}
