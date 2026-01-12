<?php

namespace App\Repository\Site;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ImageRepository
{
    const TABLE_LOGO = 'mt_logos';

    public function getLogo(): array
    {
        $lang = Session::get('lang', 'ru');

        $query = DB::table(self::TABLE_LOGO, 'l')
            ->select([
                "white_logo_$lang AS white_logo", "black_logo_$lang AS black_logo"
            ])->where('l.id', 1);


        return (array) $query->first();
    }
}
