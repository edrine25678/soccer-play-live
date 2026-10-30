<?php

use App\Http\Controllers\Football\FixtureFeedController;
use App\Http\Controllers\Football\LiveMatchFeedController;
use App\Http\Middleware\VerifyFootballFeedSignature;
use Illuminate\Support\Facades\Route;

Route::get('/live-matches', LiveMatchFeedController::class)
    ->middleware('throttle:60,1')
    ->name('api.matches.live');

Route::post('/fixture-feed', FixtureFeedController::class)
    ->middleware(['throttle:10,1', VerifyFootballFeedSignature::class])
    ->name('api.fixtures.sync');
