<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dosen;
use App\Models\Publication;
use App\Models\Ipr; 
use App\Exports\SimakerExport; 
use Maatwebsite\Excel\Facades\Excel; 
use Illuminate\Support\Facades\DB;

class SimakerController extends Controller
{
    public function index()
    {
        $totalDosen = Dosen::count() ?: 1; 
        $tahunSekarang = date('Y');

        // 1. Rasio Scopus (IKU 6)
        $jmlScopus = Publication::whereIn('source', ['scopus', 'wos'])->count();
        $iku6 = $jmlScopus / $totalDosen;

        // 2. Persentase Q1
        $jmlQ1 = Publication::where('type', 'LIKE', '%Q1%')->count();
        $persenQ1 = ($jmlScopus > 0) ? ($jmlQ1 / $jmlScopus) * 100 : 0;

        // 3. SINTA 1-4 per Dosen
        $jmlSinta14 = Publication::whereIn('type', ['S1', 'S2', 'S3', 'S4'])->count();
        $rasioSinta = $jmlSinta14 / $totalDosen;

        // 4. Sitasi Scopus 5 Tahun Terakhir
        $sitasi5Thn = Publication::where('source', 'scopus')
            ->where('year', '>=', $tahunSekarang - 5)
            ->sum('cited');
        $rasioSitasi = $sitasi5Thn / $totalDosen;

        // 5. Kolaborasi Internasional
        $intNames = ['steven', 'jacob', 'michael', 'john', 'chen', 'kumar', 'ali'];
        $jmlKolaborasi = Publication::where(function($q) use ($intNames) {
            foreach($intNames as $name) {
                $q->orWhere('title', 'LIKE', "%$name%");
            }
        })->count();
        $persenKolaborasi = ($jmlScopus > 0) ? ($jmlKolaborasi / $jmlScopus) * 100 : 0;

        // 7. Jumlah HKI per Dosen (Mengambil dari tabel IPR)
        $jmlHKI = Ipr::count(); // Hitung dari tabel iprs
        $rasioHKI = $jmlHKI / $totalDosen;

        // Kembalikan semua data dalam SATU return saja
        return view('simaker', compact(
            'iku6', 
            'persenQ1', 
            'rasioSinta', 
            'rasioSitasi', 
            'persenKolaborasi', 
            'rasioHKI', // Tambahkan ini
            'totalDosen'
        ));
    }

    // Tambahkan di SimakerController.php
    public function iprList()
    {
        // Gunakan leftJoin agar data PRODI tetap muncul
        $iprs = Ipr::leftJoin('dosen', 'iprs.sinta_id', '=', 'dosen.Sinta_ID')
                ->select(
                    'iprs.*', 
                    'dosen.Nama as nama_dosen'
                )
                ->orderBy('iprs.created_at', 'desc')
                ->get();

        return view('ipr-list', compact('iprs'));
    }

    // Di SimakerController.php
    public function cariHki() {
        return view('cari-hki'); // Halaman form pencarian
    }

    public function scrapeHkiProdi(Request $request) {
        $deptId = $request->id;
        // Panggil script python dengan parameter ID Departemen
    }

    public function exportIprExcel()
    {
        $fileName = "Laporan_Data_HKI_BRAVO_" . date('Ymd_His') . ".xlsx";
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\IprsExport, $fileName);
    }

    public function exportExcel()
    {
        try {
            $totalDosen = Dosen::count() ?: 1;
            $tahunSekarang = date('Y');

            // 1. Rasio Scopus
            $jmlScopus = Publication::where('source', 'scopus')->count();
            $iku6 = number_format($jmlScopus / $totalDosen, 2);

            // 2. Q1
            $jmlQ1 = Publication::where('type', 'LIKE', '%Q1%')->count();
            $persenQ1 = ($jmlScopus > 0) ? number_format(($jmlQ1 / $jmlScopus) * 100, 1) : "0.0";

            // 3. SINTA 1-4
            $jmlSinta14 = Publication::whereIn('type', ['S1', 'S2', 'S3', 'S4'])->count();
            $rasioSinta = number_format($jmlSinta14 / $totalDosen, 2);

            // 4. Sitasi
            $sitasi5Thn = Publication::where('source', 'scopus')
                ->where('year', '>=', $tahunSekarang - 5)->sum('cited');
            $rasioSitasi = number_format($sitasi5Thn / $totalDosen, 2);

            // 5. Kolaborasi
            $intNames = ['steven', 'jacob', 'michael', 'john', 'chen', 'kumar', 'ali'];
            $jmlKolaborasi = Publication::where(function($q) use ($intNames) {
                foreach($intNames as $name) { $q->orWhere('title', 'LIKE', "%$name%"); }
            })->count();
            $persenKolaborasi = ($jmlScopus > 0) ? number_format(($jmlKolaborasi / $jmlScopus) * 100, 1) : "0.0";

            // 7. HKI
            $jmlHKI = Ipr::count();
            $rasioHKI = number_format($jmlHKI / $totalDosen, 2);

            $data = [
                'iku6' => $iku6,
                'persenQ1' => $persenQ1,
                'persenKolaborasi' => $persenKolaborasi,
                'rasioSinta' => $rasioSinta,
                'rasioSitasi' => $rasioSitasi,
                'rasioHKI' => $rasioHKI
            ];

            return Excel::download(new SimakerExport($data), 'Laporan_SIMAKER_BRAVO.xlsx');

        } catch (\Exception $e) {
            // Jika error, tampilkan pesan errornya biar kita tahu salahnya di mana
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}