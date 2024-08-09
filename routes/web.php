<?php

use App\Http\Controllers\SocialMediaScrapingController;
use App\Http\Controllers\WebScrapingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('web-scrap.index');
});

Route::controller(WebScrapingController::class)->group(function () {
    Route::get('/web-scrap', 'index')->name('web-scrap.index');
    Route::post('/web-scrap',  'store')->name('web-scrap.store');
    Route::get('/web-scrap/show/{id}',  'show')->name('web-scrap.show');
    Route::delete('/web-scrap/{id}',  'destroy')->name('web-scrap.destroy');
    Route::get('/web-scrap/json/{id?}',  'toJSON')->name('web-scrap.json');
    Route::get('/web-scrap/analysist/{id}',  'analysist')->name('web-scrap.analysist');
    Route::post('/web-scrap/analysist/sentiment',  'sentiment')->name('web-scrap.sentiment');
    Route::get('/web-scrap/analysist/solution/{reason}',  'solution')->name('web-scrap.solution');
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
