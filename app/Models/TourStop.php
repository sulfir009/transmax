<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourStop extends Model
{
    protected $fillable = [
        'tour_id',
        'stop_id',
        'departure_time',
        'arrival_time'
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
        return $prefix . '_tours_stops';
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'stop_id', 'id');
    }

    public function stopCity()
    {
        return $this->belongsTo(City::class, 'stop_id', 'id');
    }

    /**
     * Получить цену между остановками
     */
    public function getPriceToStop($toStopId)
    {
        $priceModel = new TourStopPrice();
        return $priceModel->where('tour_id', $this->tour_id)
            ->where('from_stop', $this->stop_id)
            ->where('to_stop', $toStopId)
            ->first();
    }
}
