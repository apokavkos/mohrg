<?php

use Illuminate\Support\Facades\Route;
use Apokavkos\SeatImporting\Http\Controllers\MarketHubController;

Route::group([
    'namespace'  => 'Apokavkos\SeatImporting\Http\Controllers',
    'prefix'     => 'seat-importing',
    'middleware' => ['web', 'auth'],
], function () {

    Route::get('/', [MarketHubController::class, 'index'])->name('seat-importing.dashboard');
    Route::get('/hub/{hub}', [MarketHubController::class, 'show'])->name('seat-importing.hub.show');

    // Hub management
    Route::post('/hubs', [MarketHubController::class, 'storeHub'])->name('seat-importing.hub.store');
    Route::put('/hubs/{hub}', [MarketHubController::class, 'updateHub'])->name('seat-importing.hub.update');
    Route::delete('/hubs/{hub}', [MarketHubController::class, 'destroyHub'])->name('seat-importing.hub.destroy');

    // Search endpoints for UI
    Route::get('/search/systems', [MarketHubController::class, 'searchSystems'])->name('seat-importing.search.systems');
    Route::get('/search/regions', [MarketHubController::class, 'searchRegions'])->name('seat-importing.search.regions');
    Route::get('/search/structures', [MarketHubController::class, 'searchStructures'])->name('seat-importing.search.structures');

    // Settings
    Route::get('/settings', [MarketHubController::class, 'settings'])->name('seat-importing.settings');
    Route::post('/settings', [MarketHubController::class, 'saveSettings'])->name('seat-importing.settings.save');

    Route::get('/item/{typeId}', [MarketHubController::class, 'itemDetail'])->name('seat-importing.item.detail');
    Route::post('/import/run', [MarketHubController::class, 'triggerImport'])->name('seat-importing.import.run');
});
