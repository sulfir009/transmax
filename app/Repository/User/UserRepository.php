<?php

namespace App\Repository\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class UserRepository
{
    const TABLE_CLIENT = 'mt_clients';

    public function getSection(string $link): array
    {
        $query = DB::table(self::TABLE_CLIENT, 'm')
            ->select([
                'id','name', 'email','password'
            ])->where('m.link', $link);

        return (array) $query->first();
    }
}
