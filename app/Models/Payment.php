<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_id',
        'status',
        'amount',
        'currency',
        'description',
        'sender_card_mask',
        'sender_card_type',
        'sender_card_bank',
        'response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    /**
     * Статусы платежей LiqPay
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';
    const STATUS_ERROR = 'error';
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const STATUS_REVERSED = 'reversed';
    const STATUS_SANDBOX = 'sandbox';
    const STATUS_3DS_VERIFY = '3ds_verify';
    const STATUS_CVV_VERIFY = 'cvv_verify';
    const STATUS_OTP_VERIFY = 'otp_verify';
    const STATUS_RECEIVER_VERIFY = 'receiver_verify';
    const STATUS_WAIT_QR = 'wait_qr';
    const STATUS_WAIT_SENDER = 'wait_sender';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PREPARED = 'prepared';

    /**
     * Проверить, оплачен ли платеж
     */
    public function isPaid(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_SANDBOX]);
    }

    /**
     * Проверить, не удался ли платеж
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILURE, self::STATUS_ERROR]);
    }

    /**
     * Проверить, ожидает ли платеж подтверждения
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_3DS_VERIFY,
            self::STATUS_CVV_VERIFY,
            self::STATUS_OTP_VERIFY,
            self::STATUS_RECEIVER_VERIFY,
            self::STATUS_WAIT_QR,
            self::STATUS_WAIT_SENDER,
            self::STATUS_PROCESSING,
            self::STATUS_PREPARED,
        ]);
    }

    /**
     * Получить пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить декодированный ответ от LiqPay
     */
    public function getResponseAttribute($value): ?array
    {
        return $value ? json_decode($value, true) : null;
    }
}
