@extends('app')

@section('title', 'Cari Data HKI')

@section('content')
<div class="card">
    <h3 class="card-title">Cari Data HKI per Program Studi</h3>
    <div class="search-grid">
        <div class="form-group">
            <label class="form-label">Pilih Program Studi</label>
            <select class="form-control" id="prodiSelect">
                <option value="">-- Pilih Program Studi --</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/D33AAF9F-5882-4E68-89C2-14D9A28CD6BD">Manajemen Agribisnis</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/8618BB82-190D-4A89-9CAF-A9E7A115ECBC">Teknologi dan Manajemen Pembenihan Ikan</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/85B5E75A-D612-4007-A090-BD3B1D6FE14F">Teknologi Rekayasa Komputer</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/89279B40-BC8B-429B-A9D2-AA2FA2B8FC58">Analisis Kimia</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/CF6DA465-2847-4446-9994-49CCF4E5382D">Teknologi Rekayasa Perangkat Lunak</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/ADDE2021-1C5A-43A4-8461-4DC781B2930E">Teknologi dan Manajemen Produksi Perkebunan</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/46D50D04-AC99-402D-B8B0-E3FDA10A26E0">Teknik dan Manajemen Lingkungan</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/AE6535C6-6A72-471B-8806-3B801DC366C1">Teknologi Produksi dan Pengembangan Masyarakat Pertanian</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/355B381F-876C-41DA-BC99-A631773115E8">Komunikasi Digital dan Media</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/6322A291-A8BE-4E42-A980-5696FB039B21">Akuntansi</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/28EF7ED5-8087-44AD-8F5E-3DB86830010C">Manajemen Industri</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/6A671952-F196-48D0-A7BA-AE3E59B18BA9">Teknologi dan Manajemen Ternak</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/B2E3D4AD-B14F-465D-8724-92A7F19EA168">Ekowisata</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/1639E1F3-4627-43BA-A7EF-8CF7F0806D4A">Supervisor Jaminan Mutu Pangan</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/4844CB92-8ADC-4E28-B846-FD0A579AEF19">Paramedik Veteriner</option>
                <option value="428/828FB966-3733-430E-86FF-909B764E2523/0B8460F7-5D4E-47F8-9E2A-E6D7216FDD24">Manajemen Industri Jasa Makanan dan Gizi</option>
                <option value="54307">Pemuliaan Tanaman dan Teknologi Benih</option>
            </select>
        </div>
        <div class="form-group">
            <button class="btn btn-primary" id="btnCariHki" onclick="searchHkiProdi()">Cari Data HKI</button>
        </div>
    </div>
</div>

<div id="resultsSection" class="hidden">
    <div class="card">
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar">🏫</div>
                <div class="profile-details">
                    <h3 id="prodiNama"></h3>
                    <p id="prodiInstitusi">Sekolah Vokasi IPB University</p>
                    <p class="sinta-id" id="prodiSintaId"></p>
                </div>
            </div>
        </div>
        
        <div class="card" style="padding: 0; margin-top: 20px;">
            <div class="tab-content">
                <h4 style="padding: 20px 20px 0 20px;">Daftar IPR (HKI) Terdeteksi</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Judul HKI</th>
                            <th>Inventor</th> <th style="text-align:center">Tahun</th>
                            <th style="text-align:center">Jenis</th>
                        </tr>
                    </thead>
                    <tbody id="hkiTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="emptyState" class="card">
    <div class="empty-state">
        <h3>Belum Ada Data</h3>
        <p>Silakan pilih Program Studi di atas untuk menarik data HKI terbaru dari SINTA</p>
    </div>
</div>
@endsection

@push('styles')
<style>
    .search-grid { display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end; }
    .hidden { display: none; }
    .profile-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 20px; }
    .profile-info { display: flex; gap: 20px; align-items: center; }
    .profile-avatar { font-size: 3rem; width: 70px; height: 70px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
    .sinta-id { color: #3b82f6; font-weight: 500; }
    .empty-state { text-align: center; padding: 60px; color: #6b7280; }
    table td { vertical-align: middle; }
    .badge-orange { background-color: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
</style>
@endpush

@push('scripts')
<script>
async function searchHkiProdi() {
    const prodiId = document.getElementById('prodiSelect').value;
    const prodiText = document.getElementById('prodiSelect').options[document.getElementById('prodiSelect').selectedIndex].text;

    if (!prodiId) {
        alert('Silakan pilih Program Studi terlebih dahulu');
        return;
    }

    const btn = document.getElementById('btnCariHki');
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Memuat Data...';
    btn.disabled = true;

    try {
        // PERUBAHAN: Menambahkan parameter nama_prodi agar tersimpan di database
        const res = await fetch(`/api/sinta/scrape-hki-prodi?id=${prodiId}&nama_prodi=${encodeURIComponent(prodiText)}`);
        const json = await res.json();

        if (!json.success) throw new Error(json.error || 'Gagal mengambil data');

        document.getElementById('prodiNama').textContent = prodiText;
        document.getElementById('prodiSintaId').textContent = 'ID Prodi: ' + prodiId;
        
        const tbody = document.getElementById('hkiTableBody');
        const docs = json.data.documents; 

        if (!docs || docs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px;">Tidak ada data HKI ditemukan</td></tr>';
        } else {
            tbody.innerHTML = docs.map(d => `
                <tr>
                    <td><strong>${d.title}</strong></td>
                    <td><small>${d.inventor ?? '-'}</small></td> <td style="text-align:center">${d.year ?? '-'}</td>
                    <td style="text-align:center"><span class="badge badge-orange">${d.type ?? 'HKI'}</span></td>
                </tr>
            `).join('');
        }

        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('resultsSection').classList.remove('hidden');

    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan: ' + err.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>
@endpush