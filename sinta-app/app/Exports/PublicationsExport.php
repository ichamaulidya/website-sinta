<?php

namespace App\Exports;

use App\Models\Publication;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PublicationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private $lastDosenName = '';

    public function collection()
    {
        // Tetap diurutkan berdasarkan nama dosen agar grouping rapi
        return Publication::join('dosen', 'publications.sinta_id', '=', 'dosen.Sinta_ID')
            ->select('publications.*', 'dosen.Nama as nama_dosen')
            ->orderBy('dosen.Nama', 'asc')
            ->orderBy('publications.year', 'desc')
            ->get();
    }

    /**
     * Header Excel (Kolom Tgl Scrape dihapus)
     */
    public function headings(): array
    {
        return [
            'Nama Dosen',
            'Judul Publikasi',
            'Sumber',
            'Jurnal',
            'Tahun',
            'Sitasi',
            'Tipe/Ranking',
        ];
    }

    /**
     * Mapping data
     */
    public function map($pub): array
    {
        // Logika Grouping Nama Dosen
        $currentDosen = $pub->nama_dosen;
        $displayDosen = ($currentDosen === $this->lastDosenName) ? '' : $currentDosen;
        $this->lastDosenName = $currentDosen;

        // Logika hapus kata "accred" pada bagian Tipe/Ranking
        // Contoh: "S2 Accredited" atau "accred S2" jadi "S2" saja
        $cleanType = str_ireplace(['accredited', 'accred', ':' ,' '], ['', '', ' '], $pub->type ?? '-');
        $cleanType = trim($cleanType);

        return [
            $displayDosen,
            $pub->title,
            strtoupper($pub->source),
            $pub->journal ?? '-',
            $pub->year,
            $pub->cited,
            $cleanType, // Hasil yang sudah bersih dari kata accred
        ];
    }

    /**
     * Pengaturan Lebar Kolom
     */
    public function columnWidths(): array
    {
        return [
            'A' => 35, // Nama Dosen
            'B' => 65, // Judul
            'C' => 12, // Sumber
            'D' => 35, // Jurnal
            'E' => 10, // Tahun
            'F' => 10, // Sitasi
            'G' => 15, // Tipe/Ranking
        ];
    }

    /**
     * Styling Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style Header
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E75B6']
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ]
            ],
            // Alignment data
            'B' => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
            'A' => ['font' => ['bold' => true], 'alignment' => ['vertical' => 'top']],
            'C' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'G' => ['alignment' => ['horizontal' => 'center']],
        ];
    }
}