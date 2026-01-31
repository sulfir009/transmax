<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'title_uk',
        'title_en',
        'slug_ru',
        'slug_uk',
        'slug_en',
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
        return $prefix . '_cities';
    }

    /**
     * Получить название города на текущем языке
     */
    public function getTitle($lang = 'ru')
    {
        $field = 'title_' . $lang;
        return $this->$field ?? $this->title_ru;
    }
    
        public function getSlug(string $lang = 'ru'): string
    {
        $field = 'slug_' . $lang;
        $slug = $this->$field ?? '';

        return $slug !== '' ? $slug : \Illuminate\Support\Str::slug($this->getTitle($lang));
    }
}
