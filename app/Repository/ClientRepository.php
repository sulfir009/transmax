<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class ClientRepository
{
    protected $table = '_clients';
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
     * Получить информацию о клиенте
     */
    public function getClientInfo($clientId)
    {
        if ($this->db) {
            $prefix = DB_PREFIX;
            return $this->db->getOne(
                "SELECT name, second_name, patronymic, email, phone, birth_date, phone_code 
                FROM `{$prefix}{$this->table}` 
                WHERE id = ?",
                [(int)$clientId]
            ) ?? [];
        }
        
        // Laravel fallback
        return DB::table(DB_PREFIX . $this->table)
            ->select('name', 'second_name', 'patronymic', 'email', 'phone', 'birth_date', 'phone_code')
            ->where('id', $clientId)
            ->first();
    }
    
    /**
     * Обновить данные клиента
     */
    public function updateClientData($clientId, array $data)
    {
        $updateData = [
            'name' => $data['name'] ?? '',
            'second_name' => $data['family_name'] ?? '',
            'patronymic' => $data['patronymic'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'phone_code' => $data['phone_code'] ?? null,
            'birth_date' => $data['birth_date'] ?? null
        ];
        
        if ($this->db) {
            $prefix = DB_PREFIX;
            $this->db->query(
                "UPDATE `{$prefix}{$this->table}` SET 
                name = ?, second_name = ?, patronymic = ?, 
                email = ?, phone = ?, phone_code = ?, birth_date = ?
                WHERE id = ?",
                [
                    $updateData['name'],
                    $updateData['second_name'],
                    $updateData['patronymic'],
                    $updateData['email'],
                    $updateData['phone'],
                    $updateData['phone_code'],
                    $updateData['birth_date'],
                    (int)$clientId
                ]
            );
        } else {
            // Laravel fallback
            DB::table(DB_PREFIX . $this->table)
                ->where('id', $clientId)
                ->update($updateData);
        }
    }
}
