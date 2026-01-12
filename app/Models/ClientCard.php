<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCard extends Model
{
    protected $fillable = [
        'card_number',
        'valid_date',
        'cardholder_name',
        'client_id'
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
        return $prefix . '_clients_cards';
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}
