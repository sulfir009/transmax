<?php
// Файл патч для SQL запросов с DISTINCT и ORDER BY
// Этот файл должен быть включен в начало ajax.php

namespace App\Patches;

class SqlDistinctOrderByPatch 
{
    /**
     * Исправляет SQL запрос с DISTINCT и ORDER BY для совместимости с MySQL 5.7+
     * 
     * @param string $sql Исходный SQL запрос
     * @return string Исправленный SQL запрос
     */
    public static function fixDistinctOrderBy($sql) 
    {
        // Проверяем, есть ли в запросе DISTINCT и ORDER BY
        if (stripos($sql, 'DISTINCT') !== false && stripos($sql, 'ORDER BY') !== false) {
            // Если есть SELECT DISTINCT t.days и ORDER BY dc.section_id
            if (preg_match('/SELECT\s+DISTINCT\s+t\.days/i', $sql) && 
                preg_match('/ORDER\s+BY\s+[^,\s]+\.section_id/i', $sql)) {
                
                // Добавляем dc.section_id в SELECT список
                $sql = preg_replace(
                    '/SELECT\s+DISTINCT\s+t\.days/i',
                    'SELECT DISTINCT t.days, dc.section_id',
                    $sql
                );
            }
        }
        
        return $sql;
    }
    
    /**
     * Альтернативное решение - убрать ORDER BY из запроса с DISTINCT
     */
    public static function removeOrderByFromDistinct($sql) 
    {
        if (stripos($sql, 'DISTINCT') !== false && stripos($sql, 'ORDER BY') !== false) {
            // Удаляем ORDER BY clause
            $sql = preg_replace('/\s+ORDER\s+BY\s+[^)]+$/i', '', $sql);
        }
        return $sql;
    }
}

// Инструкция по использованию:
// В файле legacy/public/pages/ajax.php перед выполнением запроса с DISTINCT добавьте:
// $sql = \App\Patches\SqlDistinctOrderByPatch::fixDistinctOrderBy($sql);
