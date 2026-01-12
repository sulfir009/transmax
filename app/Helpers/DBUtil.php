<?php

namespace App\Helpers;

class DBUtil
{
    /**
     * for subQuery conflict Bindings
     *
     * @param $query
     * @return string sql Raw with Bindings
     */
    public static function getSql($query): string
    {
        return array_reduce(
            $query->getBindings(),
            function ($sql, $binding) {
                return preg_replace(
                    '/\?/',
                    is_numeric($binding) ? $binding : "'" . $binding . "'",
                    $sql,
                    1
                );
            },
            $query->toSql()
        );
    }
}
