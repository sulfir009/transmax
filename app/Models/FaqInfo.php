<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqInfo extends Model
{
    protected $fillable = [
        'title_ru',
        'title_uk',
        'title_en',
        'text_ru',
        'text_uk',
        'text_en',
        'image'
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
        return $prefix . '_faq_txt';
    }

    /**
     * Get title in current language
     */
    public function getTitle($lang = 'ru')
    {
        $field = 'title_' . $lang;
        return $this->$field ?? $this->title_ru;
    }

    /**
     * Get text in current language
     */
    public function getText($lang = 'ru')
    {
        $field = 'text_' . $lang;
        return $this->$field ?? $this->text_ru;
    }
}
