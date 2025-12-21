<?php

use App\Http\Controllers\GoogleCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google Calendar OAuth Routes
Route::middleware(['web', 'auth'])->prefix('funil')->group(function () {
    Route::get('/google-calendar/callback', [GoogleCalendarController::class, 'callback'])
        ->name('google-calendar.callback');
    Route::post('/google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])
        ->name('google-calendar.disconnect');
    Route::post('/google-calendar/sync', [GoogleCalendarController::class, 'sync'])
        ->name('google-calendar.sync');
});
