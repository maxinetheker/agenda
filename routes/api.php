<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/google/callback', [GoogleCalendarController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/device/fcm', [ProfileController::class, 'registerDevice']);

    Route::get('/contacts', [ContactController::class, 'index']);
    Route::post('/contacts', [ContactController::class, 'store']);
    Route::post('/contacts/import', [ContactController::class, 'import']);
    Route::get('/contacts/{contact}', [ContactController::class, 'show']);
    Route::put('/contacts/{contact}', [ContactController::class, 'update']);
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);
    Route::post('/contacts/{contact}/called', [ContactController::class, 'markCalled']);

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
    Route::post('/tasks/{task}/google-sync', [TaskController::class, 'syncGoogle']);
    Route::delete('/tasks/{task}/google-sync', [TaskController::class, 'unsyncGoogle']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    Route::get('/availability', [AvailabilityController::class, 'index']);
    Route::post('/availability', [AvailabilityController::class, 'store']);
    Route::put('/availability/{availability_range}', [AvailabilityController::class, 'update']);
    Route::delete('/availability/{availability_range}', [AvailabilityController::class, 'destroy']);

    Route::get('/google/status', [GoogleCalendarController::class, 'status']);
    Route::get('/google/connect', [GoogleCalendarController::class, 'connect']);
    Route::post('/google/disconnect', [GoogleCalendarController::class, 'disconnect']);
});
