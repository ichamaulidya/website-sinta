<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SintaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\SimakerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== HALAMAN UTAMA =====
Route::get('/', function () {
    return redirect()->route('beranda');
});

Route::get('/cari-data', [SintaController::class, 'beranda'])->name('beranda');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/daftar-dosen', [DosenController::class, 'index'])->name('daftar-dosen');
Route::get('/simaker', [SimakerController::class, 'index'])->name('simaker.index');
Route::get('/ipr-list', [SimakerController::class, 'iprList'])->name('ipr.list');

// Route menampilkan halaman hki.blade.php
Route::get('/hki', function () {
    return view('hki');
})->name('hki.index');

// Route menampilkan halaman publications.blade.php
Route::get('/publications', function () {
    return view('publications');
})->name('publications.index');

// ===== API SINTA (SCRAPING & SEARCH) =====
Route::prefix('api/sinta')->group(function () {
    Route::get('/search', [SintaController::class, 'search']);
    Route::get('/scrape', [SintaController::class, 'scrape']);
    Route::get('/profile/{id}', [SintaController::class, 'getProfile']);
    Route::get('/publications/{id}/{source?}', [SintaController::class, 'getPublications']);
    
    // Route Export Excel All (Tanpa Filter)
    Route::get('/export-excel-all', [SintaController::class, 'exportExcelAll'])->name('publications.export_all');
    
    Route::get('/export-excel-single', [SintaController::class, 'exportExcelSingle']);
    
    // API proses scrape HKI berdasarkan ID Departemen
    Route::get('/scrape-hki-prodi', [SintaController::class, 'scrapeHkiProdi']);
    
    // Autocomplete endpoint untuk search (beranda page)
    Route::get('/get-all-dosen', [SintaController::class, 'getAllDosen']);
});

// ===== API DOSEN (CRUD) =====
Route::prefix('api/dosen')->group(function () {
    Route::get('/get-all', [DosenController::class, 'getAllDosen']);
    Route::post('/add', [DosenController::class, 'addDosen']);
    Route::put('/update/{id}', [DosenController::class, 'updateDosen']);
    Route::delete('/delete/{id}', [DosenController::class, 'deleteDosen']);
});

// ===== API DASHBOARD (STATISTICS) =====
Route::prefix('api/dashboard')->group(function () {
    Route::get('/stats', [DashboardController::class, 'getStats']);
    Route::get('/top-dosen', [DashboardController::class, 'getTopDosen']);
    Route::get('/departemen-stats', [DashboardController::class, 'getDepartemenStats']);
    Route::get('/publication-trend', [DashboardController::class, 'getPublicationTrend']);
});

// ===== API PUBLICATIONS & IPR =====
Route::prefix('api/publications')->group(function () {
    Route::get('/all', function() {
        $data = \App\Models\Publication::join('dosen', 'publications.sinta_id', '=', 'dosen.Sinta_ID')
                ->select('publications.*', 'dosen.Nama as nama_dosen')
                ->orderBy('created_at', 'desc')
                ->get();
        return response()->json(['success' => true, 'data' => $data]);
    });
});

// API tambahan untuk mengambil data IPR secara JSON
Route::prefix('api/iprs')->group(function () {
    Route::get('/all', function() {
        $data = \App\Models\Ipr::join('dosen', 'iprs.sinta_id', '=', 'dosen.Sinta_ID')
                ->select('iprs.*', 'dosen.Nama as nama_dosen')
                ->orderBy('year', 'desc')
                ->get();
        return response()->json(['success' => true, 'data' => $data]);
    });
});

// ===== API SIMAKER (EXPORT) =====
Route::get('/simaker/export', [SimakerController::class, 'exportExcel'])->name('simaker.export');

Route::get('/ipr-list/export', [SimakerController::class, 'exportIprExcel'])->name('ipr.export');