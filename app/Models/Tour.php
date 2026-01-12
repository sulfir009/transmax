<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $fillable = [
        'departure',
        'arrival', 
        'bus',
        'date',
        'status'
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
        return $prefix . '_tours';
    }

    public function departureCityRelation()
    {
        return $this->belongsTo(City::class, 'departure', 'id');
    }

    public function arrivalCityRelation()
    {
        return $this->belongsTo(City::class, 'arrival', 'id');
    }

    public function busRelation()
    {
        return $this->belongsTo(Bus::class, 'bus', 'id');
    }

    public function stops()
    {
        return $this->hasMany(TourStop::class, 'tour_id', 'id');
    }
}
