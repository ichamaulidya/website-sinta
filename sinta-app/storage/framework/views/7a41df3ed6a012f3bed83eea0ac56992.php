

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<h2 style="margin-bottom: 24px; color: #1f2937;">Dashboard Statistik SINTA</h2>

<!-- STATISTIK OVERVIEW -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card orange">
        <span class="stat-label">Total Dosen</span>
        <div class="stat-value" id="totalDosen">0</div>
    </div>
    <div class="stat-card blue">
        <span class="stat-label">Total Publikasi</span>
        <div class="stat-value" id="totalPublikasi">0</div>
    </div>
    <div class="stat-card purple">
        <span class="stat-label">Total Sitasi</span>
        <div class="stat-value" id="totalSitasi">0</div>
    </div>
    <div class="stat-card green">
        <span class="stat-label">Rata-rata H-Index</span>
        <div class="stat-value" id="avgHIndex">0</div>
    </div>
</div>

<!-- TOP DOSEN -->
<div class="card">
    <h3 class="card-title">Top 10 Dosen Berdasarkan SINTA Score</h3>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Nama Dosen</th>
                <th>Departemen</th>
                <th>SINTA Score</th>
                <th>H-Index</th>
                <th>Publikasi</th>
            </tr>
        </thead>
        <tbody id="topDosenTable">
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    Memuat data...
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- CHART PUBLIKASI PER TAHUN -->
<div class="card">
    <h3 class="card-title">Tren Publikasi per Tahun</h3>
    <div id="chartPublikasi" style="height: 300px; display: flex; align-items: center; justify-content: center; color: #6b7280;">
        Chart akan ditampilkan di sini (gunakan Chart.js atau library lain)
    </div>
</div>

<!-- DISTRIBUSI DEPARTEMEN -->
<div class="card">
    <h3 class="card-title">Distribusi Dosen per Departemen</h3>
    <table>
        <thead>
            <tr>
                <th>Departemen</th>
                <th>Jumlah Dosen</th>
                <th>Total Publikasi</th>
                <th>Rata-rata SINTA Score</th>
            </tr>
        </thead>
        <tbody id="departemenTable">
            <tr>
                <td colspan="4" style="text-align: center; padding: 40px;">
                    Memuat data...
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // LOAD DASHBOARD DATA
    async function loadDashboard() {
        try {
            // Load statistik overview
            const statsRes = await fetch('/api/dashboard/stats');
            const statsJson = await statsRes.json();
            
            if (statsJson.success) {
                document.getElementById('totalDosen').textContent = statsJson.data.totalDosen || 0;
                document.getElementById('totalPublikasi').textContent = statsJson.data.totalPublikasi || 0;
                document.getElementById('totalSitasi').textContent = statsJson.data.totalSitasi || 0;
                document.getElementById('avgHIndex').textContent = statsJson.data.avgHIndex || 0;
            }

            // Load top dosen
            const topRes = await fetch('/api/dashboard/top-dosen');
            const topJson = await topRes.json();
            
            if (topJson.success && topJson.data.length > 0) {
                renderTopDosen(topJson.data);
            } else {
                document.getElementById('topDosenTable').innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                            Belum ada data
                        </td>
                    </tr>
                `;
            }

            // Load departemen stats
            const deptRes = await fetch('/api/dashboard/departemen-stats');
            const deptJson = await deptRes.json();
            
            if (deptJson.success && deptJson.data.length > 0) {
                renderDepartemenStats(deptJson.data);
            } else {
                document.getElementById('departemenTable').innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #6b7280;">
                            Belum ada data
                        </td>
                    </tr>
                `;
            }

        } catch (err) {
            console.error('Error loading dashboard:', err);
        }
    }

    function renderTopDosen(data) {
        const tbody = document.getElementById('topDosenTable');
        tbody.innerHTML = data.map((d, idx) => `
            <tr>
                <td><strong>#${idx + 1}</strong></td>
                <td><strong>${d.nama}</strong></td>
                <td>${d.departemen || '-'}</td>
                <td><span class="badge badge-orange">${d.sinta_score || 0}</span></td>
                <td>${d.h_index || 0}</td>
                <td>${d.publikasi || 0}</td>
            </tr>
        `).join('');
    }

    function renderDepartemenStats(data) {
        const tbody = document.getElementById('departemenTable');
        tbody.innerHTML = data.map(d => `
            <tr>
                <td><strong>${d.departemen || 'Tidak diketahui'}</strong></td>
                <td>${d.jumlah_dosen}</td>
                <td>${d.total_publikasi}</td>
                <td>${parseFloat(d.avg_sinta_score).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    // Load data saat halaman dimuat
    document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\asa yuaziva\OneDrive\Documents\sinta project\sinta-app\resources\views/dashboard.blade.php ENDPATH**/ ?>