<?php

namespace App\Repository\Site;

use Illuminate\Support\Facades\DB;

class RouterRepository
{
    const TABLE = 'mt_routes';
    const TABLE_PAGES = 'mt_pages';

    public function getURLs($page_id, $elem_id = 0)
    {
        $query = DB::table(self::TABLE, 'r')
            ->select([
                "*"
            ])->where('r.page_id', $page_id)
            ->where('r.elem_id', $elem_id);


        return $query->get();
    }

    public function getId(string $url)
    {
        $query = DB::table(self::TABLE, 'r')
            ->select([
                "id"
            ])->where('r.route', $url);


        return $query->first();
    }

    public function getByUrl(string $url)
    {
        $url = rtrim($url, '/');
        $query = DB::table(self::TABLE, 'r')
            ->select([
                "*"
            ])->where('r.route', $url)
            ->orWhere('r.route', $url . '/');

        return $query->first();
    }

    public function getPageById($id)
    {
        $query = DB::table(self::TABLE_PAGES, 'r')
            ->select([
                "page"
            ])->where('r.id', $id);

        return $query->first();
    }
}
