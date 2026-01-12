<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'active',
        'client_id',
        'tour_id',
        'from_stop',
        'to_stop',
        'tour_date',
        'passagers', // Соответствует схеме БД
        'document',
        'date',
        'ticket_return',
        'return_reason',
        'return_payment_type',
        'return_date',
        'client_name',
        'client_surname',
        'client_email',
        'client_phone',
        'uniqid',
        'payment_status'
    ];

    protected $casts = [
        'tour_date' => 'date',
        'date' => 'datetime',
        'return_date' => 'datetime',
        'active' => 'boolean',
        'ticket_return' => 'boolean'
    ];

    public $timestamps = false; // В существующей таблице нет timestamps

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        $prefix = env('DB_PREFIX', 'mt');
        return $prefix . '_orders';
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

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    /**
     * Проверка статуса оплаты
     */
    public function isPaid(): bool
    {
        return $this->payment_status == 2; // Согласно существующей логике
    }

    public function isPending(): bool
    {
        return $this->payment_status == 1;
    }

    public function isFailed(): bool
    {
        return $this->payment_status == 3;
    }

    /**
     * Получить количество пассажиров (с учетом старого поля)
     */
    public function getPassengersAttribute()
    {
        return $this->passagers;
    }

    /**
     * Установить количество пассажиров
     */
    public function setPassengersAttribute($value)
    {
        $this->attributes['passagers'] = $value;
    }

    /**
     * Получить email
     */
    public function getEmailAttribute()
    {
        return $this->client_email;
    }

    /**
     * Получить телефон
     */
    public function getPhoneAttribute()
    {
        return $this->client_phone;
    }
}
