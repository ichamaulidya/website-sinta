@extends('app')
@section('title', 'SIMAKER Dashboard')

@section('content')
<div class="card">
    <div class="card-title" style="background: #c026d3; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: bold;">INDIKATOR KINERJA (SIMAKER)</span>
        <a href="{{ route('simaker.export') }}" class="btn" style="background: white; color: #c026d3; font-weight: bold; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 0.875rem;">
            📥 Download Excel
        </a>
    </div>
    
    <table class="table">
        <thead>
            <tr style="background: #f3f4f6;">
                <th width="50">No</th>
                <th>INDIKATOR KINERJA</th>
                <th class="text-center">HASIL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    a. Rasio publikasi bereputasi internasional per dosen (IKU 6)<br>
                    b. Persentase publikasi Q1<br>
                    c. Persentase penelitian berkolaborasi internasional
                </td>
                <td class="text-center">
                    {{ number_format($iku6, 2) }}<br>
                    {{ number_format($persenQ1, 1) }}%<br>
                    {{ number_format($persenKolaborasi, 1) }}%
                </td>
            </tr>
            <tr>
                <td>2</td>
                <td>Publikasi nasional terindeks SINTA (1–4) per dosen</td>
                <td class="text-center">{{ number_format($rasioSinta, 2) }}</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Sitasi artikel ilmiah Scopus per dosen (5 tahun terakhir)</td>
                <td class="text-center">{{ number_format($rasioSitasi, 2) }}</td>
            </tr>
            <tr>
                <td>4</td>
                <td>Jumlah HKI per dosen (Paten, Hak Cipta, Merk, dll)</td>
                <td class="text-center">{{ number_format($rasioHKI, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection