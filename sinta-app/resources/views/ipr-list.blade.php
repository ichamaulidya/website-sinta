@extends('app')

@section('title', 'Daftar HKI/IPR')

@section('content')
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title" style="margin: 0;">Daftar Hak Kekayaan Intelektual (HKI)</h3>
        <a href="{{ route('ipr.export') }}" class="btn" style="background: #c026d3; color: white; font-weight: bold; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 0.875rem;">
            📥 Download Excel
        </a>
    </div>

    <div style="margin-top: 20px;">
        <table class="table">
            <thead>
                <tr>
                    {{-- Tambahan kolom Program Studi --}}
                    <th>Program Studi</th>
                    <th>Inventor</th>
                    <th>Judul HKI</th>
                    <th>Kategori</th>
                    <th style="text-align: center;">Tahun</th>
                    {{-- Kolom Link telah dihapus --}}
                </tr>
            </thead>
            <tbody>
                @forelse($iprs as $ipr)
                <tr>
                    {{-- Menampilkan data Program Studi --}}
                    <td style="font-size: 0.85rem; color: #6b7280; font-weight: 500;">
                        {{ $ipr->nama_prodi ?? '-' }}
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #374151;">
                            {{-- Jika ada data inventor di tabel iprs, tampilkan itu. Jika kosong baru fallback ke nama dosen dari join --}}
                            {{ $ipr->inventor && $ipr->inventor !== '-' ? $ipr->inventor : ($ipr->nama_dosen ?? 'Data Institusi/Prodi') }}
                        </div>
                    </td>
                    <td style="font-size: 0.9rem;">{{ $ipr->title }}</td>
                    <td><span class="badge badge-orange">{{ $ipr->category }}</span></td>
                    <td style="text-align: center;">{{ $ipr->year }}</td>
                    {{-- Bagian td Link telah dihapus --}}
                </tr>
                @empty
                <tr>
                    {{-- Colspan diubah menjadi 5 karena kolom berkurang satu --}}
                    <td colspan="5" style="text-align: center; padding: 40px;">Belum ada data IPR.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
    .badge-orange {
        background-color: #f59e0b;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
    }
    .btn-primary {
        background-color: #3b82f6;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.8rem;
    }
    .btn-primary:hover {
        background-color: #2563eb;
    }
    /* Menjaga agar tampilan tabel konsisten */
    .table td {
        vertical-align: middle;
    }
</style>
@endpush