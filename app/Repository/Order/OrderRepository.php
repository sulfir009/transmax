<?php

namespace App\Repository\Order;

use Illuminate\Support\Facades\DB;

class OrderRepository
{
    const TABLE = 'mt_orders';

    public function getOrderOnline()
    {
        return DB::table(self::TABLE , 'o')
            ->select([
                'id',
                "",
                DB::raw('LOWER(rra.title_en) as alias')
            ])
            ->get();
    }
}
