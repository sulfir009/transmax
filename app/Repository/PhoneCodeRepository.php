<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class PhoneCodeRepository
{
    protected $table = '_phone_codes';
    protected $db;
    
    public function __construct()
    {
        // Определяем константу DB_PREFIX если она не определена
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'mt');
        }
        
        global $Db;
        $this->db = $Db;
    }
    
    /**
     * Получить активные телефонные коды
     */
    public function getActiveCodes()
    {
        $prefix = DB_PREFIX;
        
        if ($this->db) {
            return $this->db->getAll(
                "SELECT * FROM `{$prefix}{$this->table}` 
                WHERE active = '1' 
                ORDER BY sort DESC"
            ) ?? [];
        }
        
        // Laravel fallback
        return DB::table($prefix . $this->table)
            ->where('active', 1)
            ->orderBy('sort', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Получить телефонный код по ID
     */
    public function getPhoneCodeById($id)
    {
        $prefix = DB_PREFIX;
        
        if ($this->db) {
            return $this->db->getOne(
                "SELECT phone_example, phone_mask 
                FROM `{$prefix}{$this->table}` 
                WHERE id = ?",
                [(int)$id]
            );
        }
        
        // Laravel fallback
        return DB::table($prefix . $this->table)
            ->select('phone_example', 'phone_mask')
            ->where('id', $id)
            ->first();
    }
}
