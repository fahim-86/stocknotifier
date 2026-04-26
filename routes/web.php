<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AlertController;
// use App\Services\DseScraperService;
use Illuminate\Support\Facades\Route;
use League\Uri\Http;

Route::get('/', function () {
    return view('auth/login');
});
// Route::get('/', function (DseScraperService $dseScraperService) {
//     return $dseScraperService->fetchLatestPrices();
// });

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [AlertController::class, 'index'])->name('dashboard');
    Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
    Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
