<?php

namespace App\Repository\TextPage;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TextPageRepository
{
    private string $prefix;

    public function __construct()
    {
        $this->prefix = config('database.prefix', 'mt');
    }

    /**
     * Get page data by route (legacy logic implementation)
     */
    public function getPageByRoute(string $route, string $lang): array
    {
        $cacheKey = "text_page_{$route}_{$lang}";
        
        return Cache::remember($cacheKey, 3600, function () use ($route, $lang) {
            // Step 1: Get page_id from routes table
            $routeData = $this->getRouteData($route, $lang);
            
            if (!$routeData) {
                return [];
            }
            
            // Step 2: Get page data
            return $this->getPageData($routeData->page_id, $routeData->elem_id ?? 0, $lang);
        });
    }

    /**
     * Get page data by slug
     */
    public function getPageBySlug(string $slug, string $lang): array
    {
        // Map slugs to routes
        $routeMap = [
            'privacy-policy' => '/politika-konfidencijnosti/',
            'terms-of-use' => '/usloviya-ispolzovaniya/',
            'offer' => '/oferta/',
            'transport-rules' => '/pravila-perevozok/',
            'return-conditions' => '/usloviya-vozvrata/',
            'data-deletion-instructions' => '/instrukciya-po-udaleniyu-dannyh/',
        ];
        
        $route = $routeMap[$slug] ?? null;
        
        if (!$route) {
            return [];
        }
        
        return $this->getPageByRoute($route, $lang);
    }

    /**
     * Get route data from routes table
     */
    private function getRouteData(string $route, string $lang)
    {
        // Проверяем существование таблицы
        if (!DB::getSchemaBuilder()->hasTable($this->prefix . '_routes')) {
            return null;
        }
        
        // Пробуем разные варианты путей
        $routeVariants = [
            $route,
            rtrim($route, '/'),
            rtrim($route, '/') . '/',
            '/' . ltrim($route, '/'),
            '/' . trim($route, '/') . '/',
        ];
        
        $routeVariants = array_unique($routeVariants);
        
        foreach ($routeVariants as $variant) {
            // Сначала с языком
            $query = DB::table($this->prefix . '_routes')
                ->where('route', $variant)
                ->where('lang', $lang);
                
            $result = $query->first();
            
            if ($result) {
                return $result;
            }
            
            // Потом без языка
            $result = DB::table($this->prefix . '_routes')
                ->where('route', $variant)
                ->first();
                
            if ($result) {
                return $result;
            }
        }
        
        return null;
    }

    /**
     * Get page data (legacy GetPageData logic)
     */
    private function getPageData(int $pageId, int $elemId, string $lang): array
    {
        // Check if table exists
        if (!DB::getSchemaBuilder()->hasTable($this->prefix . '_pages')) {
            return [];
        }
        
        // Get columns of the pages table
        $columns = DB::getSchemaBuilder()->getColumnListing($this->prefix . '_pages');
        
        // Build select fields dynamically
        $selectFields = ['id', 'id as page_id'];
        
        // Add language-specific fields if they exist
        if (in_array("title_{$lang}", $columns)) {
            $selectFields[] = "title_{$lang} AS title";
        }
        
        if (in_array("page_title_{$lang}", $columns)) {
            $selectFields[] = "page_title_{$lang} AS page_title";
        }
        
        if (in_array("meta_description_{$lang}", $columns)) {
            $selectFields[] = "meta_description_{$lang} AS meta_description";
        }
        
        if (in_array("meta_keywords_{$lang}", $columns)) {
            $selectFields[] = "meta_keywords_{$lang} AS meta_keywords";
        }
        
        if (in_array("text_{$lang}", $columns)) {
            $selectFields[] = "text_{$lang} AS text";
        }
        
        if (in_array('assoc_table', $columns)) {
            $selectFields[] = 'assoc_table';
        }
        
        // Only add updated_at if it exists
        if (in_array('updated_at', $columns)) {
            $selectFields[] = 'updated_at';
        }
        
        // Get page from pages table
        $page = DB::table($this->prefix . '_pages')
            ->selectRaw(implode(', ', $selectFields))
            ->where('id', $pageId)
            ->first();
            
        if (!$page) {
            return [];
        }
        
        // Check if we need to get data from associated table
        if (!empty($page->assoc_table) && $elemId > 0) {
            return $this->getDataFromAssocTable($page->assoc_table, $elemId, $lang, $pageId);
        }
        
        // Return data from pages table
        return (array) $page;
    }

    /**
     * Get data from associated table
     */
    private function getDataFromAssocTable(string $table, int $elemId, string $lang, int $pageId): array
    {
        // Check if table exists
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return [];
        }
        
        // Get columns of the table
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        
        // Build select fields
        $selectFields = ['id'];
        
        $fieldMap = [
            "title_{$lang}" => 'title',
            "page_title_{$lang}" => 'page_title',
            "meta_description_{$lang}" => 'meta_description',
            "meta_keywords_{$lang}" => 'meta_keywords',
            "text_{$lang}" => 'text'
        ];
        
        foreach ($fieldMap as $column => $alias) {
            if (in_array($column, $columns)) {
                $selectFields[] = "{$column} AS {$alias}";
            }
        }
        
        if (in_array('updated_at', $columns)) {
            $selectFields[] = 'updated_at';
        }
        
        $data = DB::table($table)
            ->selectRaw(implode(', ', $selectFields))
            ->where('id', $elemId)
            ->first();
            
        if ($data) {
            $data = (array) $data;
            $data['page_id'] = $pageId;
            $data['assoc_table'] = $table;
            return $data;
        }
        
        return [];
    }

    /**
     * Get page data from txt_blocks table
     */
    public function getPageFromTxtBlocks(string $slug, string $lang): array
    {
        // Проверяем существование таблицы
        if (!DB::getSchemaBuilder()->hasTable($this->prefix . '_txt_blocks')) {
            return [];
        }
        
        // Маппинг slug на ID в таблице txt_blocks
        $idMapping = [
            'privacy-policy' => 4,
            'privacy_policy' => 4,
            'terms-of-use' => 5,
            'terms_of_use' => 5,
            'offer' => 6,
            'oferta' => 6,
            'transport-rules' => 7,
            'transport_rules' => 7,
            'return-conditions' => 8,
            'return_conditions' => 8,
            'data-deletion-instructions' => 9,
        ];
        
        $blockId = $idMapping[$slug] ?? 0;
        
        if ($blockId === 0) {
            return [];
        }
        
        $columns = DB::getSchemaBuilder()->getColumnListing($this->prefix . '_txt_blocks');
        
        $selectFields = [];
        
        if (in_array("title_{$lang}", $columns)) {
            $selectFields[] = "title_{$lang} AS title";
        }
        
        if (in_array("text_{$lang}", $columns)) {
            $selectFields[] = "text_{$lang} AS text";
        }
        
        if (empty($selectFields)) {
            return [];
        }
        
        $result = DB::table($this->prefix . '_txt_blocks')
            ->selectRaw(implode(', ', $selectFields))
            ->where('id', $blockId)
            ->first();
        
        return $result ? (array) $result : [];
    }

    /**
     * Clear page cache
     */
    public function clearCache(string $route = null, string $lang = null): void
    {
        if ($route && $lang) {
            Cache::forget("text_page_{$route}_{$lang}");
        } else {
            // Clear all text page cache
            Cache::flush();
        }
    }
}
