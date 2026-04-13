<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SimakerExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [
            [
                '1', 
                "a. Rasio publikasi bereputasi internasional per dosen (IKU 6)\nb. Persentase publikasi Q1\nc. Persentase penelitian berkolaborasi internasional", 
                $this->data['iku6'] . "\n" . $this->data['persenQ1'] . "%\n" . $this->data['persenKolaborasi'] . "%"
            ],
            ['2', 'Publikasi nasional terindeks SINTA (1–4) per dosen', $this->data['rasioSinta']],
            ['3', 'Sitasi artikel ilmiah Scopus per dosen (5 tahun terakhir)', $this->data['rasioSitasi']],
            ['4', 'Jumlah HKI per dosen (Paten, Hak Cipta, Merk, dll)', $this->data['rasioHKI']],
        ];
    }

    public function headings(): array
    {
        return [
            ['INDIKATOR KINERJA (SIMAKER)'],
            ['No', 'INDIKATOR KINERJA', 'HASIL']
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 70,
            'C' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:C1');
        
        // Mengaktifkan wrap text untuk semua sel data agar \n bekerja
        $sheet->getStyle('A1:C6')->getAlignment()->setWrapText(true);

        return [
            // Style Judul Ungu
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C026D3']],
                'alignment' => ['horizontal' => 'center']
            ],
            // Style Header Abu-abu
            2 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3F4F6']],
                'alignment' => ['horizontal' => 'center']
            ],
            // Kolom A (No) Rata Tengah
            'A' => ['alignment' => ['horizontal' => 'center', 'vertical' => 'top']],
            
            // Kolom B (Indikator) Rata Kiri
            'B' => ['alignment' => ['horizontal' => 'left', 'vertical' => 'top']],
            
            // INI YANG KAMU MINTA: Kolom C (Hasil) Rata Tengah
            'C' => ['alignment' => ['horizontal' => 'center', 'vertical' => 'top']],
            
            // Border untuk seluruh tabel
            'A1:C6' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}