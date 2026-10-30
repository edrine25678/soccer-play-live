<?php

use App\Http\Controllers\Football\FixtureController;
use App\Http\Controllers\Football\MatchController;
use App\Http\Controllers\Football\MatchWatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FixtureController::class, 'index'])->name('home');
Route::get('/fixtures', [FixtureController::class, 'index'])->name('fixtures.index');
Route::get('/matches', [FixtureController::class, 'index'])->name('matches.index');
Route::get('/results', [FixtureController::class, 'results'])->name('results.index');
Route::get('/matches/{fixture:slug}', [MatchController::class, 'show'])->name('matches.show');
Route::get('/watch/{fixture:slug}', MatchWatchController::class)->name('matches.watch');
