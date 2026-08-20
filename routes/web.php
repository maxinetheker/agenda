<?php

use App\Http\Controllers\Api\GoogleCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'MAXCitas',
        'brand' => 'RE/MAX',
        'status' => 'ok',
        'api' => url('/api'),
    ]);
});

Route::get('/google/callback', [GoogleCalendarController::class, 'callback']);
