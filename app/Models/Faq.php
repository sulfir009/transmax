<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question_ru',
        'question_uk',
        'question_en',
        'answer_ru',
        'answer_uk',
        'answer_en',
        'active',
        'sort'
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
        return $prefix . '_faq';
    }

    /**
     * Get question in current language
     */
    public function getQuestion($lang = 'ru')
    {
        $field = 'question_' . $lang;
        return $this->$field ?? $this->question_ru;
    }

    /**
     * Get answer in current language
     */
    public function getAnswer($lang = 'ru')
    {
        $field = 'answer_' . $lang;
        return $this->$field ?? $this->answer_ru;
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('active', '1');
    }

    /**
     * Scope for sorting
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort', 'DESC');
    }
}
