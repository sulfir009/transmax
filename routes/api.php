<?php

use App\Http\Controllers\Payments\MonobankWebhookController;

Route::post('/webhooks/monobank', [MonobankWebhookController::class, 'handle'])
    ->name('monobank.webhook');
