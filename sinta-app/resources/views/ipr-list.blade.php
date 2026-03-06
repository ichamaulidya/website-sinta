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
                    <th>Dosen</th>
                    <th>Judul HKI</th>
                    <th>Kategori</th>
                    <th>Tahun</th>
                    <th style="text-align: center;">Link</th>
                </tr>
            </thead>
            <tbody>
                @forelse($iprs as $ipr)
                <tr>
                    <td><strong>{{ $ipr->nama_dosen }}</strong></td>
                    <td style="font-size: 0.9rem;">{{ $ipr->title }}</td>
                    <td><span class="badge badge-orange">{{ $ipr->category }}</span></td>
                    <td>{{ $ipr->year }}</td>
                    <td style="text-align: center;">
                        @if($ipr->link)
                            <a href="{{ $ipr->link }}" target="_blank" class="btn btn-sm btn-primary">Buka ↗️</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">Belum ada data IPR. Silakan lakukan scrape data dosen.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection