<?php

use Illuminate\Support\Facades\Route;
use Apokavkos\JEveSeAT\Http\Controllers\JEveSeATController;

Route::group([
    'prefix' => 'jeveseat',
    'middleware' => ['web', 'auth', 'can:jeveseat.view'],
], function () {
    Route::get('/', [JEveSeATController::class, 'index'])->name('jeveseat.index');
});
