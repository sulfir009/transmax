<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'active',
        'name',
        'second_name',
        'patronymic',
        'birth_date',
        'email',
        'phone',
        'phone_code',
        'password',
        'last_auth_date',
        'crypt',
        'registration_date',
        'uid',
        'miles',
        'code',
        'reset_token',
        'token_date'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'last_auth_date' => 'datetime',
        'registration_date' => 'datetime',
        'token_date' => 'datetime',
        'active' => 'boolean'
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
        return $prefix . '_clients';
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id', 'id');
    }

    public function cards()
    {
        return $this->hasMany(ClientCard::class, 'client_id', 'id');
    }

    /**
     * Получить полное имя
     */
    public function getFullNameAttribute()
    {
        return trim($this->name . ' ' . $this->second_name . ' ' . $this->patronymic);
    }
}
