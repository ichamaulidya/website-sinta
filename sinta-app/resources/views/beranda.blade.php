@extends('app')

@section('title', 'Cari Data')

@section('content')
<div class="card">
    <h3 class="card-title">Cari Data Dosen</h3>
    <div class="search-grid">
        <div class="form-group">
            <label class="form-label">NIP / Nama Dosen</label>
            <div class="dropdown">
                <input type="text" class="form-control" id="searchInput" placeholder="Masukkan NIP atau Nama Dosen..." autocomplete="off">
                <div class="dropdown-menu" id="dropdownMenu"></div>
            </div>
        </div>
        <div class="form-group">
            <button class="btn btn-primary" onclick="searchDosen()">Cari Data</button>
        </div>
    </div>
</div>

<div id="resultsSection" class="hidden">
    <div class="card">
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar">👤</div>
                <div class="profile-details">
                    <h3 id="dosenNama"></h3>
                    <p id="dosenInstitusi"></p>
                    <p id="dosenDepartemen"></p>
                    <p class="sinta-id" id="dosenSintaId"></p>
                </div>
            </div>
            <div class="export-container">
                <button class="btn btn-success" onclick="downloadExcelSingle()">
                    <span class="icon">📥</span> Download Excel
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card orange"><span class="stat-label">SINTA Overall</span><div class="stat-value" id="sintaOverall"></div></div>
            <div class="stat-card blue"><span class="stat-label">SINTA 3Yr</span><div class="stat-value" id="sinta3yr"></div></div>
            <div class="stat-card purple"><span class="stat-label">Affil Overall</span><div class="stat-value" id="affilOverall"></div></div>
            <div class="stat-card green"><span class="stat-label">Affil 3Yr</span><div class="stat-value" id="affil3yr"></div></div>
        </div>
    </div>

    <div class="card" style="padding: 0;">
        <div class="tabs">
            <button class="tab active" onclick="changeTab('scopus')">Scopus</button>
            <button class="tab" onclick="changeTab('garuda')">Garuda</button>
            <button class="tab" onclick="changeTab('wos')">Wos</button>
        </div>
        <div class="tab-content">
            <div class="metrics-grid">
                <div class="metric-box"><div class="metric-label">Article</div><div class="metric-value" id="metricArticle"></div></div>
                <div class="metric-box"><div class="metric-label">Citation</div><div class="metric-value" id="metricCitation"></div></div>
                <div class="metric-box"><div class="metric-label">H-Index</div><div class="metric-value" id="metricHIndex"></div></div>
                <div class="metric-box"><div class="metric-label">i10-Index</div><div class="metric-value" id="metrici10Index"></div></div>
            </div>
            <table>
                <thead><tr><th>Judul</th><th>Tahun</th><th>Cited</th></tr></thead>
                <tbody id="publicationTable"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="emptyState" class="card">
    <div class="empty-state">
        <h3>Belum Ada Data</h3>
        <p>Silakan cari data dosen menggunakan form di atas</p>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Additional styles specific to beranda page */
    .search-grid {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 15px;
        align-items: end;
    }

    .export-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .export-filters {
        display: flex;
        gap: 5px;
    }

    .form-control-sm {
        padding: 8px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.875rem;
        outline: none;
    }

    .form-control-sm:focus {
        border-color: #10b981;
    }

    .dropdown {
        position: relative;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        margin-top: 4px;
        max-height: 300px;
        overflow-y: auto;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 1000;
    }

    .dropdown-menu.show {
        display: block;
    }

    .dropdown-item {
        padding: 12px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
    }

    .dropdown-item:hover {
        background-color: #f9fafb;
    }

    .dropdown-item-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .dropdown-item-nip {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .hidden {
        display: none;
    }

    .profile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .profile-info {
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .profile-avatar {
        font-size: 4rem;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f3f4f6;
        border-radius: 50%;
    }

    .profile-details h3 {
        margin-bottom: 5px;
        color: #1f2937;
    }

    .profile-details p {
        margin: 2px 0;
        color: #6b7280;
        font-size: 0.875rem;
    }

    .sinta-id {
        color: #3b82f6 !important;
        font-weight: 500;
    }

    .tabs {
        display: flex;
        border-bottom: 2px solid #e5e7eb;
    }

    .tab {
        padding: 12px 24px;
        border: none;
        background: none;
        cursor: pointer;
        font-weight: 500;
        color: #6b7280;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.3s;
    }

    .tab:hover {
        color: #3b82f6;
    }

    .tab.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }

    .tab-content {
        padding: 20px;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .metric-box {
        background-color: #f9fafb;
        padding: 15px;
        border-radius: 6px;
        text-align: center;
    }

    .metric-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 8px;
    }

    .metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state h3 {
        margin-bottom: 10px;
        color: #374151;
    }

    @media (max-width: 768px) {
        .search-grid {
            grid-template-columns: 1fr;
        }

        .profile-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endpush

@push('scripts')
<script>
let selectedSintaId = null;
let currentFullData = null;

const searchInput = document.getElementById('searchInput');
const dropdownMenu = document.getElementById('dropdownMenu');

/**
 * AUTOCOMPLETE REAL-TIME DARI DATABASE
 */
searchInput.addEventListener('input', async function () {
    const query = this.value.trim();

    dropdownMenu.innerHTML = '';
    dropdownMenu.classList.remove('show');

    if (query.length < 2) return;

    try {
        const res = await fetch(`/api/sinta/get-all-dosen?q=${encodeURIComponent(query)}`);
        const json = await res.json();

        if (!json.success || json.data.length === 0) return;

        dropdownMenu.innerHTML = json.data.map(d => `
            <div class="dropdown-item"
                onclick="selectDosen('${d.sinta_id || ''}', '${escapeHtml(d.nama)}')">
                <div class="dropdown-item-title">${escapeHtml(d.nama)}</div>
                <div class="dropdown-item-nip">
                    ${d.sinta_id ? `SINTA ID: ${d.sinta_id}` : '<span style="color:red">SINTA ID Belum Ada</span>'} 
                    | NIDN: ${d.nidn ?? '-'} | Dept: ${d.departemen ?? '-'}
                </div>
            </div>
        `).join('');

        dropdownMenu.classList.add('show');
    } catch (err) {
        console.error('Autocomplete error:', err);
    }
});

/**
 * PILIH DOSEN DARI DROPDOWN
 */
function selectDosen(sintaId, nama) {
    if (!sintaId) {
        alert('Dosen ini belum memiliki SINTA ID di database.');
        searchInput.value = nama;
        selectedSintaId = null;
        return;
    }
    selectedSintaId = sintaId;
    searchInput.value = nama;
    dropdownMenu.classList.remove('show');
}

/**
 * TOMBOL "CARI DATA"
 */
async function searchDosen() {
    let idToSearch = null;

    const inputVal = searchInput.value.trim();

    // Jika user paste SINTA ID manual
    if (/^\d{6,}$/.test(inputVal)) {
        idToSearch = inputVal;
    } else {
        idToSearch = selectedSintaId;
    }

    if (!idToSearch) {
        alert('Pilih dosen dari dropdown atau masukkan SINTA ID');
        return;
    }

    const btn = document.querySelector('.btn-primary');
    btn.innerHTML = 'Memuat...';
    btn.disabled = true;

    try {
        const res = await fetch(`/api/sinta/scrape?id=${idToSearch}`);
        
        // Cek jika response bukan JSON
        const contentType = res.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await res.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server tidak mengembalikan JSON. Kemungkinan terjadi error di server.');
        }

        const json = await res.json();

        if (!json.success) throw new Error(json.error || 'Terjadi kesalahan saat mengambil data');

        const data = json.data;
        currentFullData = data;

        /* ===== PROFIL ===== */
        document.getElementById('dosenNama').textContent = data.profile?.name ?? '-';
        document.getElementById('dosenInstitusi').textContent = data.profile?.affiliation ?? '-';
        document.getElementById('dosenDepartemen').textContent = data.profile?.department ?? '-';
        document.getElementById('dosenSintaId').textContent = 'SINTA ID: ' + idToSearch;

        /* ===== SCORE ===== */
        document.getElementById('sintaOverall').textContent = data.metrics?.['SINTA Score Overall'] ?? '0';
        document.getElementById('sinta3yr').textContent = data.metrics?.['SINTA Score 3Yr'] ?? '0';
        document.getElementById('affilOverall').textContent = data.metrics?.['Affil Score'] ?? '0';
        document.getElementById('affil3yr').textContent = data.metrics?.['Affil Score 3Yr'] ?? '0';

        /* ===== STAT ===== */
        document.getElementById('metricArticle').textContent = data.stats?.['Article'] ?? '0';
        document.getElementById('metricCitation').textContent = data.stats?.['Citation'] ?? '0';
        document.getElementById('metricHIndex').textContent = data.stats?.['H-Index'] ?? '0';
        document.getElementById('metrici10Index').textContent = data.stats?.['i10-Index'] ?? '0';

        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('resultsSection').classList.remove('hidden');

        changeTab('scopus');

    } catch (err) {
        console.error(err);
        alert('Gagal mengambil data: ' + err.message);
    } finally {
        btn.innerHTML = 'Cari Data';
        btn.disabled = false;
    }
}

/**
 * TAB PUBLIKASI
 */
function changeTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => {
        t.classList.remove('active');
        if (t.textContent.trim().toLowerCase() === tabName.toLowerCase()) {
            t.classList.add('active');
        }
    });

    if (!currentFullData?.documents) return;

    // Filter hanya berdasarkan tab yang diklik (scopus, garuda, atau wos)
    const docs = currentFullData.documents.filter(d => d.source === tabName);
    renderDocuments(docs, tabName);
}

/**
 * RENDER TABEL PUBLIKASI
 */
function renderDocuments(docs, source) {
    const tbody = document.getElementById('publicationTable');

    if (!docs || docs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" style="text-align:center">Tidak ada data</td></tr>`;
        return;
    }

    tbody.innerHTML = docs.map(d => {
        // PERBAIKAN: Jika ada 'link' dari SINTA, buat angka sitasi jadi biru dan bisa diklik
        const citationDisplay = d.link 
            ? `<a href="${d.link}" target="_blank" class="badge badge-blue" title="Klik untuk lihat penyitasi di sumber asli" style="text-decoration:none; cursor:pointer;">
                 ${d.cited ?? '0'} ↗️
               </a>`
            : `<span class="badge badge-blue">${d.cited ?? '0'}</span>`;

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(d.title)}</strong><br>
                    <small>${escapeHtml(d.journal ?? '')}</small>
                    ${(source === 'scopus' || source === 'garuda') && d.type
                        ? `<br><br><small class="badge badge-orange">${escapeHtml(d.type)}</small>`
                        : ''
                    }
                </td>
                <td>${d.year ?? '-'}</td>
                <td style="text-align:center">${citationDisplay}</td>
            </tr>
        `;
    }).join('');
}

/**
 * Download Excel
 */
function downloadExcel() {
    // Ambil ID SINTA dari dosen yang sedang ditampilkan
    const sintaId = selectedSintaId || (currentFullData ? currentFullData.sinta_id : null);

    if (!sintaId) {
        alert('Tidak ada data dosen untuk diunduh');
        return;
    }

    // Arahkan ke route export dengan parameter ID SINTA
    // (Kita akan buat route ini sebentar lagi)
    const url = `/api/sinta/export-excel-single?id=${sintaId}`;
    window.location.href = url;
}

/**
 * Escape HTML untuk keamanan
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        dropdownMenu.classList.remove('show');
    }

    /**
    * TOMBOL "CARI DATA"
    */
    async function searchDosen() {
        let idToSearch = null;

        const inputVal = searchInput.value.trim();

        // Jika user paste SINTA ID manual
        if (/^\d{6,}$/.test(inputVal)) {
            idToSearch = inputVal;
        } else {
            idToSearch = selectedSintaId;
        }

        if (!idToSearch) {
            alert('Pilih dosen dari dropdown atau masukkan SINTA ID');
            return;
        }

        const btn = document.querySelector('.btn-primary');
        btn.innerHTML = 'Memuat...';
        btn.disabled = true;

        try {
            const res = await fetch(`/api/sinta/scrape?id=${idToSearch}`);
            const json = await res.json();

            if (!json.success) throw new Error(json.error);

            const data = json.data;
            currentFullData = data;

            /* ===== PROFIL ===== */
            document.getElementById('dosenNama').textContent = data.profile?.name ?? '-';
            document.getElementById('dosenInstitusi').textContent = data.profile?.affiliation ?? '-';
            document.getElementById('dosenDepartemen').textContent = data.profile?.department ?? '-';
            document.getElementById('dosenSintaId').textContent = 'SINTA ID: ' + idToSearch;

            /* ===== SCORE ===== */
            document.getElementById('sintaOverall').textContent = data.metrics?.['SINTA Score Overall'] ?? '0';
            document.getElementById('sinta3yr').textContent = data.metrics?.['SINTA Score 3Yr'] ?? '0';
            document.getElementById('affilOverall').textContent = data.metrics?.['Affil Score'] ?? '0';
            document.getElementById('affil3yr').textContent = data.metrics?.['Affil Score 3Yr'] ?? '0';

            /* ===== STAT ===== */
            document.getElementById('metricArticle').textContent = data.stats?.['Article'] ?? '0';
            document.getElementById('metricCitation').textContent = data.stats?.['Citation'] ?? '0';
            document.getElementById('metricHIndex').textContent = data.stats?.['H-Index'] ?? '0';
            document.getElementById('metrici10Index').textContent = data.stats?.['i10-Index'] ?? '0';

            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('resultsSection').classList.remove('hidden');

            changeTab('scopus');

        } catch (err) {
            console.error(err);
            alert('Gagal mengambil data: ' + err.message);
        } finally {
            btn.innerHTML = 'Cari Data';
            btn.disabled = false;
        }
    }

    /**
    * TAB PUBLIKASI
    */
    function changeTab(tabName) {
        document.querySelectorAll('.tab').forEach(t => {
            t.classList.remove('active');
            if (t.textContent.toLowerCase().includes(tabName)) {
                t.classList.add('active');
            }
        });

        if (!currentFullData?.documents) return;

        let source = tabName;
        if (tabName === 'scholar') source = 'googlescholar';

        const docs = currentFullData.documents.filter(d => d.source === source);
        renderDocuments(docs, source);
    }

    /**
    * RENDER TABEL PUBLIKASI
    */
    function renderDocuments(docs, source) {
        const tbody = document.getElementById('publicationTable');

        if (!docs || docs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" style="text-align:center">Tidak ada data</td></tr>`;
            return;
        }

        tbody.innerHTML = docs.map(d => `
            <tr>
                <td>
                    <strong>${d.title}</strong><br>
                    <small>${d.journal ?? ''}</small>
                    ${
                        (source === 'scopus' || source === 'garuda') && d.type
                            ? `<br><br><small class="badge badge-orange">${d.type}</small>`
                            : ''
                    }
                </td>
                <td>${d.year ?? '-'}</td>
                <td><span class="badge badge-blue">${d.cited ?? '0'}</span></td>
            </tr>
        `).join('');
    }

    function downloadExcel() {
        alert('Export Excel akan diaktifkan setelah data stabil');
    }
});
</script>
@endpush