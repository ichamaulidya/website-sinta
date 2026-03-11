<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dosen;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard
     */
    public function index()
    {
        return view('dashboard');
    }

    /**
     * Get statistik overview untuk dashboard
     */
    public function getStats()
    {
        try {
            $totalDosen = Dosen::count();
            $totalPublikasi = \App\Models\Publication::count();
            $totalSitasi = \App\Models\Publication::sum('cited');
            
            // H-Index dan stats lainnya mungkin belum ada di schema ini,
            // kita set 0 dulu agar tidak error
            $stats = [
                'totalDosen' => $totalDosen,
                'totalPublikasi' => $totalPublikasi,
                'totalSitasi' => $totalSitasi,
                'avgHIndex' => 0 
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top 10 dosen berdasarkan jumlah publikasi
     */
    public function getTopDosen()
    {
        try {
            // Karena sinta_score tidak ada di tabel dosen, 
            // kita ranking berdasarkan jumlah publikasi di tabel publications
            $topDosen = \App\Models\Publication::join('dosen', 'publications.sinta_id', '=', 'dosen.Sinta_ID')
                ->select(
                    'dosen.Nama as nama',
                    'dosen.Bagian as departemen',
                    DB::raw('COUNT(publications.id) as publikasi'),
                    DB::raw('SUM(publications.cited) as total_sitasi')
                )
                ->groupBy('dosen.Sinta_ID', 'dosen.Nama', 'dosen.Bagian')
                ->orderBy('publikasi', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $topDosen
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistik per departemen
     */
    public function getDepartemenStats()
    {
        try {
            $stats = Dosen::select(
                    'Bagian as departemen',
                    DB::raw('COUNT(*) as jumlah_dosen')
                )
                ->groupBy('Bagian')
                ->get();
            
            // Tambahkan data publikasi per departemen jika perlu
            foreach($stats as $s) {
                $s->total_publikasi = \App\Models\Publication::join('dosen', 'publications.sinta_id', '=', 'dosen.Sinta_ID')
                    ->where('dosen.Bagian', $s->departemen)
                    ->count();
                $s->avg_sinta_score = 0; // Kolom belum ada
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPublicationTrend()
    {
        try {
            // Kita hitung jumlah publikasi per tahun dari tabel publications
            $data = \App\Models\Publication::selectRaw('year as tahun, count(*) as jumlah')
                    ->whereNotNull('year')
                    ->groupBy('year')
                    ->orderBy('year', 'asc')
                    ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}