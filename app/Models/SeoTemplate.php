<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTemplate extends Model
{
    protected $fillable = [
        'key',
        'lang',
        'template_text',
    ];

    public function getTable()
    {
        $prefix = env('DB_PREFIX', 'mt');
        return $prefix . '_seo_templates';
    }
}