<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\HomeMetricsController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::get('/', function () {
    return view('home');
});

// Webhook Telegram tidak boleh kena CSRF protection.
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/api/limbah-summary', [HomeMetricsController::class, 'limbahSummary']);
