<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Scout\ScoutShotController;
use App\Livewire\Scout\MatchScouting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/partidas/{gameMatch}/scout', MatchScouting::class)
        ->name('scout.match');

    Route::post('/partidas/{gameMatch}/scout/lote', [ScoutShotController::class, 'storeBatch'])
        ->name('scout.batch');
});

require __DIR__.'/auth.php';