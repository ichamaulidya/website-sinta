@extends('app')

@section('title', 'Daftar HKI/IPR')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Hak Kekayaan Intelektual (HKI)</h3>
    </div>

    <div style="margin-top: 20px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Inventor / Dosen</th>
                    <th>Judul HKI</th>
                    <th>Kategori</th>
                    <th style="text-align: center;">Tahun</th>
                    <th style="text-align: center;">Link</th>
                </tr>
            </thead>
            <tbody>
                @forelse($iprs as $ipr)
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #374151;">
                            {{ $ipr->inventor && $ipr->inventor !== '-' ? $ipr->inventor : ($ipr->nama_dosen ?? 'Data Institusi/Prodi') }}
                        </div>
                    </td>
                    <td style="font-size: 0.9rem;">{{ $ipr->title }}</td>
                    <td><span class="badge badge-orange">{{ $ipr->category }}</span></td>
                    <td style="text-align: center;">{{ $ipr->year }}</td>
                    <td style="text-align: center;">
                        @if($ipr->link && $ipr->link !== '#')
                            <a href="{{ $ipr->link }}" target="_blank" class="btn btn-sm btn-primary">Buka ↗️</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
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
</style>
@endpush