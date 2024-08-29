<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\WebScrapingController;
use App\Http\Controllers\SocialMediaScrapingController;

Route::get('/', function () {
    return to_route('web-scrap.index');
});

Route::controller(WebScrapingController::class)->group(function () {
    Route::get('/web-scrap', 'index')->name('web-scrap.index');
    Route::post('/web-scrap',  'store')->name('web-scrap.store');
    Route::get('/web-scrap/show/{id}',  'show')->name('web-scrap.show');
    Route::delete('/web-scrap/{id}',  'destroy')->name('web-scrap.destroy');
    Route::get('/web-scrap/json/{id?}',  'toJSON')->name('web-scrap.json');
});
Route::controller(AnalysisController::class)->group(function () {
    Route::get('/analysis/{id}',  'index')->name('analysis.index');
});

// Route::controller(SocialMediaScrapingController::class)->group(function () {
Route::get('/socmed-scrap', function () {
    abort(404);
});
    // Route::get('/socmed-scrap', 'index')->name('socmed-scrap.index');
    // Route::post('/socmed-scrap',  'store')->name('socmed-scrap.store');
    // Route::delete('/socmed-scrap/{id}',  'destroy')->name('socmed-scrap.destroy');
    // Route::get('/socmed-scrap/{id?}',  'toJSON')->name('socmed-scrap.json');
// });
