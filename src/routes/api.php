<?php

use App\Http\Controllers\Api\GlobalSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Global Search API
    Route::prefix('search')->group(function () {
        Route::get('/', [GlobalSearchController::class, 'search'])->name('api.search');
        Route::get('/uid', [GlobalSearchController::class, 'searchByUid'])->name('api.search.uid');
        Route::get('/suggestions', [GlobalSearchController::class, 'suggestions'])->name('api.search.suggestions');
        Route::get('/entities', [GlobalSearchController::class, 'entities'])->name('api.search.entities');
    });
});
