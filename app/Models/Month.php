<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Month extends Model
{
    protected $table;
    protected $fillable = [
        'title_ru',
        'title_uk', 
        'title_en'
    ];

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = (env('DB_PREFIX', '') ? env('DB_PREFIX') . '_' : '') . 'months';
    }

    /**
     * Получить название месяца на текущем языке
     */
    public function getTitle($lang = 'ru')
    {
        $field = 'title_' . $lang;
        return $this->$field ?? $this->title_ru;
    }
}
