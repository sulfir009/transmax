<?php

namespace App\Repository\Order;

use Illuminate\Support\Facades\DB;

class CallbackRepository
{
    const TABLE = 'mt_callback';

    public function add($data)
    {
        DB::table(self::TABLE)->insert(
            $data
        );
    }
}
