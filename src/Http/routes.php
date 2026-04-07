<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'Apokavkos\SeatAssets\Http\Controllers',
    'prefix' => 'seat-assets',
    'middleware' => ['web', 'auth', 'locale'],
], function () {
    Route::get('/', 'DashboardController@index')->name('seat-assets::dashboard');
    Route::get('/assets', 'IndustryController@assets')->name('seat-assets::assets');

    Route::get('/stockpiles', 'StockpileController@index')->name('seat-assets::stockpiles');
    Route::get('/stockpiles/workflow', 'StockpileController@workflow')->name('seat-assets::stockpiles.workflow');
    Route::post('/stockpiles/from-requirements', 'StockpileController@createFromRequirements')->name('seat-assets::stockpiles.from-requirements');
    Route::post('/stockpiles', 'StockpileController@store')->name('seat-assets::stockpiles.store');
    Route::delete('/stockpiles/{id}', 'StockpileController@delete')->name('seat-assets::stockpiles.delete');
    Route::post('/stockpiles/items/{itemId}/location', 'StockpileController@updateItemLocation')->name('seat-assets::stockpiles.item.location');
    Route::get('/stockpiles/{id}/industry', 'StockpileController@industry')->name('seat-assets::stockpiles.industry');
    Route::get('/stockpiles/search/locations', 'StockpileController@searchLocations')->name('seat-assets::stockpiles.search.locations');

    // Industry Calculator
    Route::get('/industry', 'IndustryController@index')->name('seat-assets::industry.calculator');
    Route::get('/industry/guide', 'IndustryController@guide')->name('seat-assets::industry.guide');
    Route::post('/industry/calculate', 'IndustryController@calculate')->name('seat-assets::industry.calculate');
    Route::get('/industry/search', 'IndustryController@searchItems')->name('seat-assets::industry.search');
    Route::get('/industry/systems', 'IndustryController@searchSystems')->name('seat-assets::industry.systems');
    Route::get('/industry/system-index/{systemName}', 'IndustryController@getSystemIndex')->name('seat-assets::industry.system-index');
    Route::get('/industry/blueprints', 'IndustryController@listOwnedBlueprints')->name('seat-assets::industry.blueprints');
    Route::get('/industry/blueprint/{itemId}', 'IndustryController@getOwnedBlueprint')->name('seat-assets::industry.blueprint.detail');
    Route::get('/industry/warmup', 'IndustryController@warmup')->name('seat-assets::industry.warmup');

    // Reactions Planner
    Route::get('/reactions', 'ReactionController@index')->name('seat-assets::reactions.planner');
    Route::post('/reactions/calculate', 'ReactionController@calculate')->name('seat-assets::reactions.calculate');
    Route::post('/reactions/config', 'ReactionController@saveConfig')->name('seat-assets::reactions.config.save');
    Route::get('/reactions/warmup-prices', 'ReactionController@warmupPrices')->name('seat-assets::reactions.warmup-prices');

    // Market Dashboards
    Route::get('/market/markup', 'MarketController@markup')->name('seat-assets::market.markup');
    Route::get('/market/stock', 'MarketController@stock')->name('seat-assets::market.stock');
    Route::get('/market/doctrine', 'MarketController@doctrineDashboard')->name('seat-assets::market.doctrine');
    Route::match(['get', 'post'], '/market/fittings', 'MarketController@fittings')->name('seat-assets::market.fittings');
    Route::post('/market/fittings/save', 'MarketController@saveFit')->name('seat-assets::market.fittings.save');
    Route::post('/market/fittings/batch-restock', 'MarketController@batchRestock')->name('seat-assets::market.fittings.batch-restock');
    Route::post('/market/groups/save', 'MarketController@saveGroup')->name('seat-assets::market.groups.save');
    Route::delete('/market/groups/{id}', 'MarketController@deleteGroup')->name('seat-assets::market.groups.delete');
    Route::delete('/market/fittings/{id}', 'MarketController@deleteFit')->name('seat-assets::market.fittings.delete');

    Route::post('/market/exports/save', 'MarketController@saveExport')->name('seat-assets::market.exports.save');
    Route::delete('/market/exports/{id}', 'MarketController@deleteExport')->name('seat-assets::market.exports.delete');
    Route::get('/market/exports/dedupe', 'MarketController@dedupeExports')->name('seat-assets::market.exports.dedupe');

    Route::get('/market/hubs/search', 'MarketController@searchHubs')->name('seat-assets::market.hubs.search');
    Route::post('/market/hubs', 'MarketController@addHub')->name('seat-assets::market.hubs.add');
    Route::post('/market/hubs/sync/{hubId}', 'MarketController@syncHub')->name('seat-assets::market.hubs.sync');
    });
