<?php

namespace App\Repository\Admin;

use Illuminate\Support\Facades\DB;

class MenuRepository
{
    const TABLE = 'mt_menu_admin';

    public function getSection(string $link): array
    {
        $query = DB::table(self::TABLE, 'm')
            ->select([
                'assoc_table',
                'title',
                'access',
                'num_page',
                'access_delete',
                'access_edit',
                'page_id',
                'id',
            ])->where('m.link', $link);

        return (array) $query->first();
    }
}
