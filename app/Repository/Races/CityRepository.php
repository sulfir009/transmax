<?php

namespace App\Repository\Races;

use App\Service\Site;
use Illuminate\Support\Facades\DB;

class CityRepository
{
    const TABLE = 'mt_cities';

    const ACTIVE = 1;

    public function getCities()
    {
        $lang = Site::lang();
        $query = DB::table(self::TABLE, 'c')
            ->select([
                "c.id", "c.title_{$lang} as title", "c.station"
            ])->where('c.active', '=', self::ACTIVE)
            ->where('c.section_id', '>', 0)
            ->where('c.station', '=', 0)
            ->orderBy("title_{$lang}", 'ASC');


        return $query->get();
    }

    public function getStationNameUk($id)
    {
        return DB::table(self::TABLE, 'c')
            ->select(['c.title_uk'])
            ->where('id', '=' ,$id)
            ->value('title_uk');
    }

}
