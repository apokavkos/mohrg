<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'Apokavkos\SeatAssets\Http\Controllers',
    'prefix' => 'seat-assets',
    'middleware' => ['web', 'auth', 'locale'],
], function () {
    Route::get('/', 'DashboardController@index')->name('seat-assets::dashboard');
});
