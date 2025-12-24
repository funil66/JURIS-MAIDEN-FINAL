<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GoogleDriveController;
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

// Google Drive OAuth Routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/google/redirect', [GoogleDriveController::class, 'redirect'])
        ->name('google.redirect');
    Route::get('/google/callback', [GoogleDriveController::class, 'callback'])
        ->name('google.callback');
    Route::post('/google/disconnect', [GoogleDriveController::class, 'disconnect'])
        ->name('google.disconnect');
});
