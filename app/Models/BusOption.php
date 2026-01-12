<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusOption extends Model
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
        return $prefix . '_buses_options';
    }

    /**
     * Получить название опции на текущем языке
     */
    public function getTitle($lang = 'ru')
    {
        $field = 'title_' . $lang;
        return $this->$field ?? $this->title_ru;
    }

    /**
     * Автобусы с этой опцией
     */
    public function buses()
    {
        $prefix = env('DB_PREFIX', 'mt');
        return $this->belongsToMany(
            Bus::class,
            $prefix . '_buses_options_connector',
            'option_id',
            'bus_id'
        );
    }
}
