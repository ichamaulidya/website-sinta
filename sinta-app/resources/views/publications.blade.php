@extends('app')

@section('title', 'Data Publikasi')

@push('styles')
<style>
    .modal {
        display: none; 
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); 
        align-items: center;
        justify-content: center;
    }
    .modal.show {
        display: flex !important;
    }
    .modal-content {
        background-color: white;
        border-radius: 8px;
        width: 90%;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .modal-header, .modal-footer {
        padding: 15px 20px;
        border-bottom: 1px solid #e5e7eb;
    }
    .modal-footer {
        border-bottom: none;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .modal-body {
        padding: 20px;
    }
    .modal-title {
        margin: 0;
        font-size: 1.1rem;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;      
        cursor: pointer;
        color: #6b7280;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Data Publikasi Terkumpul</h3>
        
        <div class="download-box">
            <button class="btn btn-success" onclick="openExportModal()">📥 Download Excel</button>
        </div>
</div>

<div id="customExportModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Pilih Periode Laporan</h3>
            <button class="modal-close" onclick="closeExportModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Bulan</label>
                <select id="selMonth" class="form-control">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Tahun</label>
                <select id="selYear" class="form-control">
                    @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeExportModal()">Batal</button>
            <button class="btn btn-success" onclick="exportData()">Mulai Unduh</button>
        </div>
    </div>
</div>

    <div style="margin-top: 20px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Dosen</th>
                    <th>Judul</th>
                    <th>Tahun & Sumber</th>
                    <th>Sitasi</th>
                    <th>Tgl Scrape</th>
                </tr>
            </thead>
            <tbody id="pubTableBody">
                </tbody>
        </table>
    </div>
</div>

<script>
    // Fungsi untuk membuka modal
    function openExportModal() {
        document.getElementById('customExportModal').classList.add('show');
    }

    // Fungsi untuk menutup modal
    function closeExportModal() {
        document.getElementById('customExportModal').classList.remove('show');
    }

    async function loadPubs() {
        const res = await fetch('/api/publications/all');
        const json = await res.json();
        const tbody = document.getElementById('pubTableBody');
        
        if (json.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">Belum ada data publikasi terkumpul. Silakan lakukan pencarian di menu Cari Data.</td></tr>';
            return;
        }
        
        tbody.innerHTML = json.data.map(d => {
            let sourceColor = 'badge-blue';
            if(d.source === 'googlescholar') sourceColor = 'badge-green';
            if(d.source === 'scopus') sourceColor = 'badge-purple';
            
            return `
            <tr>
                <td><strong>${d.nama_dosen}</strong></td>
                <td style="font-size: 0.9rem;">
                    ${d.title}
                    <br>
                    <small class="text-muted">${d.journal || ''}</small>
                </td>
                <td>
                    <div style="margin-bottom: 5px;"><strong>${d.year}</strong></div>
                    <span class="badge ${sourceColor}">${d.source.toUpperCase()}</span>
                </td>
                <td>
                    <div style="font-size: 1.1rem; font-weight: bold; color: #2563eb;">
                        ${d.cited} 
                    </div>
                    <small style="color: #6b7280;">Sitasi di ${d.source.charAt(0).toUpperCase() + d.source.slice(1)}</small>
                </td>
                <td>${new Date(d.created_at).toLocaleDateString('id-ID')}</td>
            </tr>
        `}).join('');
    }

    // Fungsi export yang dipanggil dari dalam modal
    function exportData() {
        const m = document.getElementById('selMonth').value;
        const y = document.getElementById('selYear').value;
         
        closeExportModal();
        
        window.location.href = `/api/sinta/export-excel?month=${m}&year=${y}`;
    }

    window.onclick = function(event) {
        const modal = document.getElementById('customExportModal');
        if (event.target == modal) {
            closeExportModal();
        }
    }

    document.addEventListener('DOMContentLoaded', loadPubs);
</script>
@endsection