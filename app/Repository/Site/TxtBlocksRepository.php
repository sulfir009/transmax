<?php

namespace App\Repository\Site;

use App\Service\Site;
use Illuminate\Support\Facades\DB;

class TxtBlocksRepository
{
    const TABLE = 'mt_txt_blocks';

    public function getTextById(int $id)
    {
        $lang = Site::lang();
        $query = DB::table(self::TABLE, 't')
            ->select([
                "t.text_{$lang} as text"
            ])->where('t.id', '=', $id);


        return $query->first();
    }
}
