<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SintaController;

// Halaman utama
Route::get('/', function () {
    return view('beranda');
});

// API Endpoints untuk SINTA
Route::get('/api/get-all-dosen', [SintaController::class, 'getAllDosen']); // Autocomplete endpoint
Route::prefix('api/sinta')->group(function () {
    Route::get('/search', [SintaController::class, 'search']);
    Route::get('/scrape', [SintaController::class, 'scrape']); // New route
    Route::get('/profile/{id}', [SintaController::class, 'getProfile']);
    Route::get('/publications/{id}/{source?}', [SintaController::class, 'getPublications']);
});