<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourStopPrice extends Model
{
    protected $fillable = [
        'tour_id',
        'from_stop',
        'to_stop',
        'price'
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
        return $prefix . '_tours_stops_prices';
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'id');
    }

    public function fromStop()
    {
        return $this->belongsTo(City::class, 'from_stop', 'id');
    }

    public function toStop()
    {
        return $this->belongsTo(City::class, 'to_stop', 'id');
    }
}
