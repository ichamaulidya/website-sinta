

<?php $__env->startSection('title', 'Daftar Dosen'); ?>

<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Dosen Sekolah Vokasi IPB</h3>
        <button class="btn btn-success" onclick="openAddModal()">➕ Tambah Dosen</button>
    </div>

    <div class="search-bar">
        <form onsubmit="applyFilter(); return false;">
            <input type="text" class="form-control" id="filterInput" placeholder="🔍 Cari Nama / NIDN / SINTA ID">
        </form>
    </div>


    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIDN</th>
                <th>NUPTK</th>
                <th>NPI</th>
                <th>SINTA ID</th>
                <th>Departemen</th>
                <th style="text-align: center; width: 180px;">Aksi</th>
            </tr>
        </thead>
        <tbody id="dosenTableBody">
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    Memuat data...
                </td>
            </tr>
        </tbody>
    </table>

    <!-- PAGINATION -->
    <div class="pagination-container">
        <div class="pagination-info">
            Menampilkan <span id="showingStart">0</span> - <span id="showingEnd">0</span> dari <span id="totalData">0</span> data
        </div>
        <div class="pagination-buttons">
            <button class="btn btn-sm btn-secondary" id="prevBtn" onclick="previousPage()" disabled>
                ← Previous
            </button>
            <span class="page-info">
                Halaman <span id="currentPage">1</span> dari <span id="totalPages">1</span>
            </span>
            <button class="btn btn-sm btn-secondary" id="nextBtn" onclick="nextPage()" disabled>
                Next →
            </button>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH/EDIT DOSEN -->
<div id="dosenModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Tambah Dosen</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form id="dosenForm" onsubmit="submitDosen(event)">
            <input type="hidden" id="dosenId">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" class="form-control" id="inputNama" required>
            </div>
            <div class="form-group">
                <label class="form-label">NIDN</label>
                <input type="text" class="form-control" id="inputNIDN" placeholder="Opsional">
            </div>
            <div class="form-group">
                <label class="form-label">NUPTK</label>
                <input type="text" class="form-control" id="inputNUPTK" placeholder="Opsional">
            </div>
            <div class="form-group">
                <label class="form-label">NPI</label>
                <input type="text" class="form-control" id="inputNPI" placeholder="Opsional">
            <div class="form-group">
                <label class="form-label">SINTA ID *</label>
                <input type="text" class="form-control" id="inputSintaId" required>
            </div>
            <div class="form-group">
                <label class="form-label">Departemen</label>
                <input type="text" class="form-control" id="inputDepartemen" placeholder="Opsional">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* Pagination Styles */
    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-top: 1px solid #e5e7eb;
        margin-top: 20px;
    }

    .pagination-info {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .pagination-buttons {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-info {
        color: #374151;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .pagination-buttons .btn {
        min-width: 100px;
    }

    /* MODAL STYLES - CRITICAL */
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
    }

    .modal.show {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: white;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        color: #1f2937;
    }

    .modal-content form {
        padding: 20px;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 20px;
        margin-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 768px) {
        .pagination-container {
            flex-direction: column;
            gap: 15px;
        }

        .modal-content {
            width: 95%;
            margin: 20px auto;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
let allDosenData = [];
let filteredDosenData = [];
let editingDosenId = null;


let currentPage = 1;
let itemsPerPage = 10;  
let totalPages = 1;

/**
 * LOAD DAFTAR DOSEN
 */
async function loadDosenList() {
    const tbody = document.getElementById('dosenTableBody');
    
    try {
        console.log('Fetching data from API...');
        const res = await fetch('/api/dosen/get-all');
        const json = await res.json();
        
        console.log('API Response:', json);

        if (!json.success || !json.data || json.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                        Belum ada data dosen
                    </td>
                </tr>
            `;
            updatePaginationInfo(0, 0, 0, 0);
            return;
        }

        allDosenData = json.data;
        filteredDosenData = json.data;
        currentPage = 1;
        
        console.log(`Loaded ${allDosenData.length} dosen, items per page: ${itemsPerPage}`);
        renderDosenTable();

    } catch (err) {
        console.error('Error loading dosen:', err);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">
                    Gagal memuat data: ${err.message}
                </td>
            </tr>
        `;
        updatePaginationInfo(0, 0, 0, 0);
    }
}

/**
 * RENDER TABEL DOSEN DENGAN PAGINATION
 */
function renderDosenTable() {
    const tbody = document.getElementById('dosenTableBody');
    
    // Hitung total halaman
    totalPages = Math.ceil(filteredDosenData.length / itemsPerPage);
    
    console.log(`Rendering table: ${filteredDosenData.length} items, ${itemsPerPage} per page, ${totalPages} pages`);
    
    // Pastikan currentPage tidak melebihi totalPages
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    
    // Hitung index awal dan akhir untuk halaman saat ini
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredDosenData.length);
    
    // Ambil data untuk halaman saat ini
    const pageData = filteredDosenData.slice(startIndex, endIndex);
    
    console.log(`Showing items ${startIndex + 1} to ${endIndex}`);
    
    // Render tabel
    if (pageData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                    Tidak ada data yang sesuai
                </td>
            </tr>
        `;
        updatePaginationInfo(0, 0, filteredDosenData.length, currentPage);
        return;
    }
    
    tbody.innerHTML = pageData.map((d, idx) => {
        const globalIndex = startIndex + idx + 1;
        return `
            <tr>
                <td>${globalIndex}</td>
                <td><strong>${escapeHtml(d.nama)}</strong></td>
                <td>${d.nidn || '-'}</td>
                <td>${d.nuptk || '-'}</td>
                <td>${d.npi || '-'}</td>
                <td>${d.sinta_id ?? '-'}</td>
                <td>${d.departemen || '-'}</td>
                <td style="text-align: center;">
                    <button class="btn btn-sm btn-edit" onclick="editDosen(${d.id})">Edit</button>
                    <button class="btn btn-sm btn-delete" onclick="deleteDosen(${d.id})">Hapus</button>
                </td>
            </tr>
        `;
    }).join('');

    
    // Update pagination info
    updatePaginationInfo(startIndex + 1, endIndex, filteredDosenData.length, currentPage);
}

/**
 * UPDATE PAGINATION INFO & BUTTONS
 */
function updatePaginationInfo(start, end, total, page) {
    document.getElementById('showingStart').textContent = start;
    document.getElementById('showingEnd').textContent = end;
    document.getElementById('totalData').textContent = total;
    document.getElementById('currentPage').textContent = page;
    document.getElementById('totalPages').textContent = Math.max(1, totalPages);
    
    // Update button states
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    if (prevBtn && nextBtn) {
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages || totalPages === 0;
    }
}

/**
 * PREVIOUS PAGE
 */
function previousPage() {
    console.log('Previous page clicked');
    if (currentPage > 1) {
        currentPage--;
        renderDosenTable();
    }
}

/**
 * NEXT PAGE
 */
function nextPage() {
    console.log('Next page clicked');
    if (currentPage < totalPages) {
        currentPage++;
        renderDosenTable();
    }
}

/**
 * FILTER TABEL
 */
function applyFilter() {
    const keyword = document.getElementById('filterInput')
        .value
        .toLowerCase()
        .trim();

    console.log('SEARCH:', keyword);

    if (keyword === '') {
        filteredDosenData = [...allDosenData];
    } else {
        filteredDosenData = allDosenData.filter(d => {
            const nama = (d.nama ?? '').toString().toLowerCase();
            const nidn = (d.nidn ?? '').toString().toLowerCase();
            const sinta = (d.sinta_id ?? '').toString().toLowerCase();
            const nuptk = (d.nuptk ?? '').toString().toLowerCase();
            const npi   = (d.npi ?? '').toString().toLowerCase();

            return (
                nama.includes(keyword) ||
                nidn.includes(keyword) ||
                sinta.includes(keyword)
            );
        });
    }

    currentPage = 1;
    renderDosenTable();
}


document.addEventListener('DOMContentLoaded', function () {
    console.log('Page loaded');
    loadDosenList();

    const filterInput = document.getElementById('filterInput');

    filterInput.addEventListener('input', function () {
        applyFilter();
    });

    filterInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilter();
        }
    });
});





/**
 * MODAL TAMBAH DOSEN
 */
function openAddModal() {
    console.log('Opening add modal');
    editingDosenId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Dosen';
    document.getElementById('dosenForm').reset();
    
    const modal = document.getElementById('dosenModal');
    modal.classList.add('show');
    console.log('Modal should be visible now');
}

function editDosen(id) {
    const dosen = allDosenData.find(d => d.id == id);

    if (!dosen) {
        alert('Data dosen tidak ditemukan');
        return;
    }

    editingDosenId = id;
    document.getElementById('modalTitle').textContent = 'Edit Dosen';
    document.getElementById('inputNama').value = dosen.nama || '';
    document.getElementById('inputNIDN').value = dosen.nidn || '';
    document.getElementById('inputNUPTK').value = dosen.nuptk || '';
    document.getElementById('inputNPI').value = dosen.npi || '';
    document.getElementById('inputSintaId').value = dosen.sinta_id || '';
    document.getElementById('inputDepartemen').value = dosen.departemen || '';

    document.getElementById('dosenModal').classList.add('show');
}


/**
 * CLOSE MODAL
 */
function closeModal() {
    console.log('Closing modal');
    const modal = document.getElementById('dosenModal');
    modal.classList.remove('show');
    document.getElementById('dosenForm').reset();
    editingDosenId = null;
}

/**
 * SUBMIT FORM (TAMBAH/EDIT)
 */
async function submitDosen(event) {
    event.preventDefault();
    
    console.log('Submitting form...');
    
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const data = {
        nama: document.getElementById('inputNama').value,
        nidn: document.getElementById('inputNIDN').value || null,
        nuptk: document.getElementById('inputNUPTK').value || null,
        npi: document.getElementById('inputNPI').value || null,
        sinta_id: document.getElementById('inputSintaId').value,
        departemen: document.getElementById('inputDepartemen').value || null
    };

    console.log('Data to submit:', data);

    try {
        const url = editingDosenId 
            ? `/api/dosen/update/${editingDosenId}` 
            : '/api/dosen/add';
        
        const method = editingDosenId ? 'PUT' : 'POST';

        console.log(`${method} ${url}`);

        const res = await fetch(url, {
            method: method,
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });

        const json = await res.json();
        
        console.log('Response:', json);

        if (json.success) {
            alert(editingDosenId ? 'Data berhasil diupdate!' : 'Dosen berhasil ditambahkan!');
            closeModal();
            loadDosenList();
        } else {
            throw new Error(json.error || 'Gagal menyimpan data');
        }

    } catch (err) {
        console.error('Error submitting:', err);
        alert('Gagal menyimpan: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

async function deleteDosen(id) {
    if (!confirm("Yakin hapus?")) return;

    const token = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute('content');

    try {
        const res = await fetch(`/api/dosen/delete/${id}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": token,
                "Accept": "application/json"
            }
        });

        const data = await res.json();

        if (data.success) {
            alert(data.message);
            loadDosenList(); // 🔥 INI KUNCI
        } else {
            alert(data.error || "Gagal menghapus data");
        }

    } catch (err) {
        console.error(err);
        alert("Terjadi kesalahan saat menghapus data");
    }
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

// Close modal ketika klik di luar
window.onclick = function(event) {
    const modal = document.getElementById('dosenModal');
    if (event.target === modal) {
        closeModal();
    }
}

console.log('Script loaded successfully');
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\SKRIPSI\project_sinta\website-sinta\sinta-app\resources\views/daftar-dosen.blade.php ENDPATH**/ ?>