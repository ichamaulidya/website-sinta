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
            $stats = [
                'totalDosen' => Dosen::count(),
                'totalPublikasi' => Dosen::sum('jumlah_publikasi') ?? 0,
                'totalSitasi' => Dosen::sum('total_sitasi') ?? 0,
                'avgHIndex' => round(Dosen::avg('h_index') ?? 0, 1)
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
     * Get top 10 dosen berdasarkan SINTA score
     */
    public function getTopDosen()
    {
        try {
            $topDosen = Dosen::select(
                    'nama', 
                    'departemen', 
                    'sinta_score', 
                    'h_index', 
                    'jumlah_publikasi as publikasi'
                )
                ->whereNotNull('sinta_score')
                ->orderBy('sinta_score', 'desc')
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
                    'departemen',
                    DB::raw('COUNT(*) as jumlah_dosen'),
                    DB::raw('COALESCE(SUM(jumlah_publikasi), 0) as total_publikasi'),
                    DB::raw('COALESCE(AVG(sinta_score), 0) as avg_sinta_score')
                )
                ->whereNotNull('departemen')
                ->groupBy('departemen')
                ->orderBy('avg_sinta_score', 'desc')
                ->get();

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
}