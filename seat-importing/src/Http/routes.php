<?php

use Illuminate\Support\Facades\Route;
use Apokavkos\SeatImporting\Http\Controllers\MarketHubController;

Route::group([
    'namespace'  => 'Apokavkos\SeatImporting\Http\Controllers',
    'prefix'     => 'seat-importing',
    'middleware' => ['web', 'auth'],
], function () {

    // Dashboard — default hub or redirect to first active hub
    Route::get('/', [MarketHubController::class, 'index'])->name('seat-importing.dashboard');
    Route::get('/hub/{hub}', [MarketHubController::class, 'show'])->name('seat-importing.hub.show');

    // Hub management (requires manage permission)
    Route::post('/hubs', [MarketHubController::class, 'storeHub'])->name('seat-importing.hub.store');
    Route::put('/hubs/{hub}', [MarketHubController::class, 'updateHub'])->name('seat-importing.hub.update');
    Route::delete('/hubs/{hub}', [MarketHubController::class, 'destroyHub'])->name('seat-importing.hub.destroy');

    // Settings
    Route::get('/settings', [MarketHubController::class, 'settings'])->name('seat-importing.settings');
    Route::post('/settings', [MarketHubController::class, 'saveSettings'])->name('seat-importing.settings.save');

    // Item detail popup (AJAX — returns JSON for Bootstrap modal)
    Route::get('/item/{typeId}', [MarketHubController::class, 'itemDetail'])->name('seat-importing.item.detail');

    // Trigger import via web (requires import permission)
    Route::post('/import/run', [MarketHubController::class, 'triggerImport'])->name('seat-importing.import.run');

    // API-ready JSON endpoints (future-proof, for external consumers)
    Route::prefix('api/v1')->group(function () {
        Route::get('/hubs', [MarketHubController::class, 'apiHubs'])->name('seat-importing.api.hubs');
        Route::get('/hubs/{hub}/metrics', [MarketHubController::class, 'apiMetrics'])->name('seat-importing.api.metrics');
        Route::get('/hubs/{hub}/items', [MarketHubController::class, 'apiItems'])->name('seat-importing.api.items');
    });
});
