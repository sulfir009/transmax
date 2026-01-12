<?php

namespace App\Repository\Site;

use Illuminate\Support\Facades\DB;

class LanguagesRepository
{
    const TABLE_LOGO = 'mt_site_languages';

    public function getSiteLangs()
    {
        $query = DB::table(self::TABLE_LOGO, 'l')
            ->select([
                "*"
            ])->where('l.active', 1);


        return $query->get();
    }
}
