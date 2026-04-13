<?php

namespace App\Exports;

use App\Models\Ipr;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IprsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private $lastProdiName = '';

    public function collection()
    {
        // Diurutkan berdasarkan nama prodi agar grouping berfungsi
        return Ipr::leftJoin('dosen', 'iprs.sinta_id', '=', 'dosen.Sinta_ID')
            ->select('iprs.*', 'dosen.Nama as nama_dosen')
            ->orderBy('iprs.nama_prodi', 'asc')
            ->orderBy('iprs.year', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Program Studi',
            'Inventor',
            'Judul HKI',
            'Kategori',
            'Tahun'
        ];
    }

    public function map($ipr): array
    {
        // Logika Grouping Program Studi
        $currentProdi = $ipr->nama_prodi ?? '-';
        $displayProdi = ($currentProdi === $this->lastProdiName) ? '' : $currentProdi;
        $this->lastProdiName = $currentProdi;

        // Logika Inventor
        $displayInventor = $ipr->inventor && $ipr->inventor !== '-' 
            ? $ipr->inventor 
            : ($ipr->nama_dosen ?? 'Data Institusi/Prodi');

        return [
            $displayProdi,
            $displayInventor,
            $ipr->title,
            $ipr->category,
            $ipr->year
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Program Studi
            'B' => 45, // Inventor
            'C' => 60, // Judul HKI
            'D' => 15, // Kategori
            'E' => 10, // Tahun
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header Style
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2D3748']
                ],
                'alignment' => ['horizontal' => 'center']
            ],
            // Alignment & Wrap Text
            'A' => ['font' => ['bold' => true], 'alignment' => ['vertical' => 'top']],
            'B' => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
            'C' => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
            'D' => ['alignment' => ['horizontal' => 'center', 'vertical' => 'top']],
            'E' => ['alignment' => ['horizontal' => 'center', 'vertical' => 'top']],
        ];
    }
}