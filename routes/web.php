<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScraperController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('leads', LeadController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::prefix('scraper')->name('scraper.')->group(function () {
        Route::get('/', [ScraperController::class, 'index'])->name('index');
        Route::post('/', [ScraperController::class, 'store'])->name('store');
        Route::get('/{scrapeJob}', [ScraperController::class, 'show'])->name('show');
        Route::get('/{scrapeJob}/status', [ScraperController::class, 'statusPoll'])->name('status');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
