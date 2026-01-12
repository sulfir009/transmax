<?php

namespace App\Repository\Races;

use Illuminate\Support\Facades\DB;

class TransferStation
{
    const TABLE = 'mt_tours_transfers';

    public function getTransferIds($tourId)
    {
        return DB::table(self::TABLE, 'tt')
            ->select(['transfer_station_id'])
            ->where('tt.tour_id', '=', $tourId)
            ->get();
    }
}
