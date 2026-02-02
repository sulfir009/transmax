<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusTransaction extends Model
{
    protected $fillable = [
        'client_id',
        'amount_cents',
        'type',
        'order_id',
        'admin_id',
        'meta',
    ];

    protected $casts = [
        'amount_cents' => 'int',
        'order_id' => 'int',
        'admin_id' => 'int',
        'meta' => 'array',
    ];

    public function getTable()
    {
        $prefix = env('DB_PREFIX', 'mt');
        return $prefix . '_bonus_transactions';
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
