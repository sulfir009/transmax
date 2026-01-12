<?php

namespace App\Repository\Site;

use Illuminate\Support\Facades\DB;

class PhoneCodesRepository
{
    const TABLE = 'mt_phone_codes';

    public function getAll()
    {
        $query = DB::table(self::TABLE, 'pc')
            ->select([
                "*"
            ])->where('pc.active', '=', 1)
            ->orderBy('sort', 'DESC');

        return $query->get();
    }
}
