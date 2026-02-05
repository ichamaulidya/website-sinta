<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data SINTA - Sekolah Vokasi IPB</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9fafb; }
        .container { display: flex; height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .header { background-color: white; border-bottom: 1px solid #e5e7eb; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { font-size: 24px; color: #1f2937; margin-left: 15px; }
        .content { flex: 1; overflow-y: auto; padding: 30px; }
        .card { background-color: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 24px; }
        .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; transition: all 0.2s; }
        .form-control:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1); }
        .search-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: end; }
        .dropdown { position: relative; }
        .dropdown-menu { position: absolute; top: 100%; left: 0; right: 0; background-color: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-height: 240px; overflow-y: auto; z-index: 10; display: none; margin-top: 4px; }
        .dropdown-menu.show { display: block; }
        .dropdown-item { padding: 12px 16px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background 0.2s; }
        .dropdown-item:hover { background-color: #f9fafb; }
        .dropdown-item-title { font-weight: 500; color: #1f2937; }
        .dropdown-item-nip { font-size: 13px; color: #6b7280; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; justify-content: center; }
        .btn-primary { background-color: #dc2626; color: white; width: 100%; }
        .btn-success { background-color: #059669; color: white; }
        .profile-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 24px; }
        .profile-info { display: flex; gap: 16px; }
        .profile-avatar { width: 96px; height: 96px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .profile-details h3 { font-size: 24px; color: #1f2937; margin-bottom: 4px; }
        .profile-details p { color: #6b7280; font-size: 14px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 24px; }
        .stat-card { padding: 16px; border-radius: 8px; }
        .stat-card.orange { background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%); }
        .stat-card.blue { background: linear-gradient(135deg, #eff6ff 0%, #bfdbfe 100%); }
        .stat-card.purple { background: linear-gradient(135deg, #faf5ff 0%, #e9d5ff 100%); }
        .stat-card.green { background: linear-gradient(135deg, #f0fdf4 0%, #bbf7d0 100%); }
        .stat-value { font-size: 32px; font-weight: bold; }
        .tabs { display: flex; border-bottom: 1px solid #e5e7eb; }
        .tab { padding: 16px 24px; background-color: #f9fafb; border: none; font-size: 14px; font-weight: 600; cursor: pointer; color: #6b7280; }
        .tab.active { background-color: #dc2626; color: white; }
        .tab-content { padding: 24px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px; }
        .metric-box { text-align: center; padding: 12px; background-color: #f9fafb; border-radius: 8px; }
        .metric-value { font-size: 24px; font-weight: bold; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background-color: #1f2937; color: white; }
        th, td { padding: 12px 16px; text-align: left; font-size: 13px; border-bottom: 1px solid #e5e7eb; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; }
        .badge-orange { background-color: #fed7aa; color: #9a3412; }
        .badge-blue { background-color: #bfdbfe; color: #1e3a8a; }
        .hidden { display: none; }
        .empty-state { text-align: center; padding: 48px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content">
            <header class="header">
                <h2>Data SINTA Sekolah Vokasi IPB University</h2>
            </header>

            <main class="content">
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
                            <button class="btn btn-success" onclick="downloadExcel()">Download Excel</button>
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
                            <button class="tab" onclick="changeTab('scholar')">Scholar</button>
                            <button class="tab" onclick="changeTab('garuda')">Garuda</button>
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

                <div id="emptyState" class="card"><div class="empty-state"><h3>Belum Ada Data</h3><p>Silakan cari data dosen menggunakan form di atas</p></div></div>
            </main>
        </div>
    </div>

    <script>
        // Update NIP dengan SINTA ID asli agar scraping jalan
        let dosenList = [];
        let selectedSintaId = '';

        // Ambil data dosen dari database MySQL saat halaman dimuat
        async function initDosenList() {
            try {
                const res = await fetch('/api/get-all-dosen');
                dosenList = await res.json();
                console.log("Database Dosen Terhubung:", dosenList);
            } catch (e) {
                console.error("Gagal mengambil daftar dosen:", e);
            }
        }
        initDosenList();

        let selectedNip = '';
        let currentFullData = null;

        const searchInput = document.getElementById('searchInput');
        const dropdownMenu = document.getElementById('dropdownMenu');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            if (query.length === 0) { dropdownMenu.classList.remove('show'); return; }
            const filtered = dosenList.filter(d => d.nama.toLowerCase().includes(query) || d.sinta_id && d.sinta_id.includes(query));
            // 2. Update tampilan item agar mengirim sinta_id saat diklik
            if (filtered.length > 0) {
                dropdownMenu.innerHTML = filtered.map(d => `<div class="dropdown-item" onclick="selectDosen('${d.sinta_id}')"> <div class="dropdown-item-title">${d.nama}</div> <div class="dropdown-item-nip">ID: ${d.sinta_id}</div></div>`).join('');
                dropdownMenu.classList.add('show');
            } else { dropdownMenu.classList.remove('show'); }
        });

        function selectDosen(id) {
            selectedNip = id;
            const dosen = dosenList.find(d => d.sinta_id === id);
            if (dosen) {
                searchInput.value = dosen.nama;
            }
            dropdownMenu.classList.remove('show');
        }

        // Fungsi Final dengan Fetch API
        async function searchDosen() {
            // Priority: Check input value directly first
            const inputVal = searchInput.value.trim();
            
            // If input is purely numeric, assume it's SINTA ID
            const isSintaId = /^\d+$/.test(inputVal);
            
            let idToSearch = isSintaId ? inputVal : selectedNip;
            
            if (!idToSearch) { alert('Masukkan SINTA ID atau pilih dosen!'); return; }
            
            const btn = document.querySelector('.btn-primary');
            btn.innerHTML = 'Memuat...';
            
            try {
                // Use new scrape endpoint
                const res = await fetch(`/api/sinta/scrape?id=${idToSearch}`);
                const response = await res.json();
                
                if (!response.success) {
                    throw new Error(response.error || 'Gagal mengambil data');
                }

                const data = response.data;
                currentFullData = data;

                // Update UI Profile
                document.getElementById('dosenNama').textContent = data.profile.name;
                document.getElementById('dosenInstitusi').textContent = data.profile.affiliation;
                document.getElementById('dosenDepartemen').textContent = data.profile.department;
                document.getElementById('dosenSintaId').textContent = data.profile.sinta_id_label || ('SINTA ID: ' + data.sinta_id);

                // Update UI Metrics
                // Map keys from python script to UI
                document.getElementById('sintaOverall').textContent = data.metrics['SINTA Score Overall'] || '0';
                document.getElementById('sinta3yr').textContent = data.metrics['SINTA Score 3Yr'] || '0';
                document.getElementById('affilOverall').textContent = data.metrics['Affil Score'] || '0';
                document.getElementById('affil3yr').textContent = data.metrics['Affil Score 3Yr'] || '0';

                // Update Stats
                document.getElementById('metricArticle').textContent = data.stats['Article'] || '0';
                document.getElementById('metricCitation').textContent = data.stats['Citation'] || '0';
                document.getElementById('metricHIndex').textContent = data.stats['H-Index'] || '0';
                document.getElementById('metrici10Index').textContent = data.stats['i10-Index'] || '0';

                document.getElementById('emptyState').classList.add('hidden');
                document.getElementById('resultsSection').classList.remove('hidden');
                
                // Render documents
                // Initially show Scopus or default to first available
                changeTab('scopus');

            } catch (e) { 
                console.error(e);
                alert('Gagal ambil data: ' + e.message); 
            }
            finally { btn.innerHTML = 'Cari Data'; }
        }

        function renderDocuments(docs) {
            const tableBody = document.getElementById('publicationTable');
            if (!docs || docs.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" style="text-align:center">Tidak ada data dokumen</td></tr>';
                return;
            }

            tableBody.innerHTML = docs.map(item => `
                <tr>
                    <td>
                        <div style="font-weight:500">${item.title}</div>
                        <div style="font-size:12px;color:#666">
                            ${item.journal || ''} 
                            ${item.type ? '<span class="badge badge-orange">' + item.type + '</span>' : ''}
                        </div>
                    </td>
                    <td>${item.year || '-'}</td>
                    <td><span class="badge badge-blue">${item.cited || '0'}</span></td>
                </tr>
            `).join('');
        }

        function changeTab(tabName) {
            // Update active state tab button
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
                if(t.textContent.toLowerCase().includes(tabName.toLowerCase()) || 
                   (tabName === 'googlescholar' && t.textContent.toLowerCase().includes('scholar'))) {
                    t.classList.add('active');
                }
            });

            if (!currentFullData || !currentFullData.documents) return;

            // Filter documents based on source
            // Map tab names to source keys from python script
            // Python uses: 'scopus', 'googlescholar', 'garuda', 'wos'
            
            let sourceKey = tabName;
            if (tabName === 'scholar') sourceKey = 'googlescholar'; 
            
            const filteredDocs = currentFullData.documents.filter(doc => doc.source === sourceKey);
            
            renderDocuments(filteredDocs);
        }

        // Old function kept for reference but unused in new flow
        function loadPublications(tab) {
             // ...
        }

        function downloadExcel() { alert('Fitur Excel sedang diproses tim!'); }
    </script>
</body>
</html>