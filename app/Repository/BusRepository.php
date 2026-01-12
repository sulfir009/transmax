<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class BusRepository
{
    protected $db;
    protected $lang = 'ru';
    
    public function __construct()
    {
        // Определяем константу DB_PREFIX если она не определена
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'mt');
        }
        
        global $Db, $Router;
        $this->db = $Db;
        $this->lang = $Router->lang ?? 'ru';
    }
    
    /**
     * Получить опции автобуса
     */
    public function getBusOptions($busId)
    {
        if (!$busId) {
            return [];
        }
        
        $prefix = DB_PREFIX;
        $lang = $this->lang;
        
        if ($this->db) {
            return $this->db->getAll(
                "SELECT title_{$lang} AS title 
                FROM `{$prefix}_buses_options`
                WHERE id IN (
                    SELECT option_id 
                    FROM `{$prefix}_buses_options_connector` 
                    WHERE bus_id = ?
                )",
                [(int)$busId]
            ) ?? [];
        }
        
        // Laravel fallback
        $optionIds = DB::table($prefix . '_buses_options_connector')
            ->where('bus_id', $busId)
            ->pluck('option_id');
            
        $options = DB::table($prefix . '_buses_options')
            ->whereIn('id', $optionIds)
            ->select("title_{$lang} as title")
            ->get();
            
        // Преобразуем объекты в массивы
        return $options->map(function ($item) {
            return (array) $item;
        })->toArray();
    }
}
