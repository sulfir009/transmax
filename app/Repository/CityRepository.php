<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class CityRepository
{
    protected $lang;
    protected $dbPrefix;
    
    public function __construct()
    {
        $this->dbPrefix = config('database.prefix', 'mt');
    }
    
    /**
     * Установить язык для запросов
     */
    public function setLanguage(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }
    
    /**
     * Получить название города по ID
     */
    public function getCityTitle(int $cityId): ?array
    {
        $column = 'title_' . $this->lang;
        
        $result = DB::table($this->dbPrefix . '_cities')
            ->select(DB::raw($column . ' as title'))
            ->where('id', $cityId)
            ->first();
        
        return $result ? ['title' => $result->title] : null;
    }
    
    /**
     * Получить все месяцы
     */
    public function getMonths(): array
    {
        $column = 'title_' . $this->lang;
        
        return DB::table($this->dbPrefix . '_months')
            ->select('id', DB::raw($column . ' as title'))
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->title];
            })
            ->toArray();
    }
    
    /**
     * Получить название месяца по ID
     */
    public function getMonthTitle(int $monthId): ?array
    {
        $column = 'title_' . $this->lang;
        
        $result = DB::table($this->dbPrefix . '_months')
            ->select(DB::raw($column . ' as title'))
            ->where('id', $monthId)
            ->first();
        
        return $result ? ['title' => $result->title] : null;
    }
    
    /**
     * Получить страны для главной страницы
     */
    public function getCountriesForHome(string $lang): array
    {
        return DB::table($this->dbPrefix . '_cities')
            ->selectRaw("id, title_{$lang} AS title")
            ->where('active', '1')
            ->where('section_id', '0')
            ->where('show_home', '1')
            ->orderByDesc('sort')
            ->get()
            ->toArray();
    }

    /**
     * Получить города для главной страницы
     */
    public function getCitiesForHome(string $lang): array
    {
        return DB::table($this->dbPrefix . '_cities')
            ->selectRaw("id, title_{$lang} AS title")
            ->where('active', '1')
            ->where('section_id', '!=', 0)
            ->where('section_id', '!=', '175')
            ->where('station', '0')
            ->orderByDesc('sort')
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    /**
     * Получить города для фильтра
     */
    public function getFilterCities()
    {
        $lang = $this->lang ?: 'uk';
        
        return DB::table($this->dbPrefix . '_cities')
            ->selectRaw("id, title_{$lang} AS title")
            ->where('active', 1)
            ->where('section_id', '>', 0)
            ->where('station', 0)
            ->orderByDesc('sort')
            ->orderBy("title_{$lang}")
            ->get();
    }
    
    /**
     * Получить все города для фильтра (с языком)
     */
    public function getAllCitiesForFilter(string $lang): array
    {
        return DB::table($this->dbPrefix . '_cities')
            ->selectRaw("id, title_{$lang} AS title")
            ->where('active', 1)
            ->where('section_id', '>', 0)
            ->where('station', 0)
            ->orderByDesc('sort')
            ->orderBy("title_{$lang}")
            ->get()
            ->toArray();
    }
    
    /**
     * Получить города для фильтра (алиас для getAllCitiesForFilter)
     */
    public function getCitiesForFilter(string $lang): array
    {
        return $this->getAllCitiesForFilter($lang);
    }
}