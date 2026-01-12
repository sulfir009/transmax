<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = [
        'title_ru',
        'title_uk', 
        'title_en'
    ];

    public $timestamps = false;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        $prefix = env('DB_PREFIX', 'mt');
        return $prefix . '_buses';
    }

    /**
     * Получить название автобуса на текущем языке
     */
    public function getTitle($lang = 'ru')
    {
        $field = 'title_' . $lang;
        return $this->$field ?? $this->title_ru;
    }

    /**
     * Получить опции автобуса
     */
    public function options()
    {
        $prefix = env('DB_PREFIX', 'mt');
        return $this->belongsToMany(
            BusOption::class,
            $prefix . '_buses_options_connector',
            'bus_id',
            'option_id'
        );
    }
}
