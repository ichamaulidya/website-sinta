<?php

namespace App\Exports;

use App\Models\Publication;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PublicationsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $year, $month;

    // Menangkap filter bulan dan tahun dari Controller
    public function __construct($year, $month) {
        $this->year = $year;
        $this->month = $month;
    }

    /**
     * Query data publikasi dan Join dengan tabel dosen untuk ambil Nama
     */
    public function query() {
        return Publication::query()
            ->join('dosen', 'publications.sinta_id', '=', 'dosen.Sinta_ID')
            ->select('publications.*', 'dosen.Nama as nama_dosen')
            ->whereYear('publications.created_at', $this->year)
            ->whereMonth('publications.created_at', $this->month);
    }

    /**
     * Header kolom di Excel
     */
    public function headings(): array {
        return [
            "Nama Dosen", 
            "Sumber", 
            "Judul Publikasi", 
            "Jurnal/Penerbit", 
            "Tahun Terbit", 
            "Sitasi", 
            "Kategori/Quartile"
        ];
    }

    /**
     * Mapping data agar urutannya pas dengan header
     */
    public function map($pub): array {
        return [
            $pub->nama_dosen,
            strtoupper($pub->source),
            $pub->title,
            $pub->journal ?? '-',
            $pub->year,
            $pub->cited,
            $pub->type ?? '-'
        ];
    }
}