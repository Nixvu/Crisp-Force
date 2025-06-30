<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Pastikan hanya Admin yang bisa mengakses halaman ini
checkRole(['Admin']);

// --- PENGAMBILAN DATA UNTUK WIDGET ---

// 1. Total Pendapatan (dari transaksi lunas)
$result_pendapatan = $conn->query("SELECT SUM(total_harga) as total FROM Transaction WHERE status_pembayaran = 'lunas'");
$total_pendapatan = $result_pendapatan->fetch_assoc()['total'] ?? 0;

// 2. Total Penjualan (transaksi barang)
$result_penjualan = $conn->query("SELECT COUNT(id_transaction) as total FROM Transaction WHERE jenis_transaksi = 'barang'");
$total_penjualan = $result_penjualan->fetch_assoc()['total'] ?? 0;

// 3. Total Perbaikan
$result_perbaikan = $conn->query("SELECT COUNT(id_service) as total FROM ServiceRequest");
$total_perbaikan = $result_perbaikan->fetch_assoc()['total'] ?? 0;

// 4. Total Kampanye Aktif
$result_kampanye = $conn->query("SELECT COUNT(id_campaign) as total FROM Campaign WHERE status = 'aktif'");
$total_kampanye = $result_kampanye->fetch_assoc()['total'] ?? 0;


// --- PENGAMBILAN DATA UNTUK CHART ---

// 1. Chart Komposisi Pendapatan (Penjualan vs Perbaikan)
$query_komposisi = "SELECT jenis_transaksi, SUM(total_harga) as total FROM Transaction WHERE status_pembayaran = 'lunas' GROUP BY jenis_transaksi";
$result_komposisi = $conn->query($query_komposisi);
$data_komposisi = [];
while ($row = $result_komposisi->fetch_assoc()) {
    $data_komposisi['labels'][] = ucfirst($row['jenis_transaksi']);
    $data_komposisi['data'][] = $row['total'];
}

// 2. Chart Status Perbaikan (Proses, Selesai, Gagal)
$query_status_perbaikan = "SELECT 
    CASE 
        WHEN status IN ('diterima_digerai', 'analisis_kerusakan', 'menunggu_sparepart', 'dalam_perbaikan') THEN 'Proses'
        WHEN status = 'perbaikan_selesai' THEN 'Selesai'
        ELSE 'Gagal'
    END as status_group,
    COUNT(id_progress) as total
    FROM ServiceProgress
    WHERE status != 'diambil_pelanggan'
    GROUP BY status_group";
$result_status_perbaikan = $conn->query($query_status_perbaikan);
$data_status_perbaikan = [];
while ($row = $result_status_perbaikan->fetch_assoc()) {
    $data_status_perbaikan['labels'][] = $row['status_group'];
    $data_status_perbaikan['data'][] = $row['total'];
}


// --- PENGAMBILAN DATA UNTUK TABEL ---

// 1. Log Aktivitas Terakhir
$query_logs = "SELECT a.*, u.nama_lengkap 
               FROM ActivityLog a 
               LEFT JOIN User u ON a.id_user = u.id_user 
               ORDER BY a.created_at DESC LIMIT 5";
$logs = $conn->query($query_logs);

// 2. Transaksi Terakhir
$query_transaksi = "SELECT t.*, c.id_user, u.nama_lengkap, t.guest_name
                    FROM Transaction t
                    LEFT JOIN Customer c ON t.id_customer = c.id_customer
                    LEFT JOIN User u ON c.id_user = u.id_user
                    ORDER BY t.created_at DESC LIMIT 5";
$transactions = $conn->query($query_transaksi);

include '../includes/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Dashboard Admin';
</script>

<!-- Kontainer utama dengan animasi masuk -->
<div class="page-enter">

    <!-- Baris Widget Statistik Atas -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Widget Total Pendapatan -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                <i data-lucide="dollar-sign" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Pendapatan</p>
                <p class="text-2xl font-bold"><?= formatCurrency($total_pendapatan) ?></p>
            </div>
        </div>
        
        <!-- Widget Total Penjualan -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-green-100 text-green-600 p-3 rounded-full">
                <i data-lucide="shopping-cart" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Penjualan</p>
                <p class="text-2xl font-bold"><?= $total_penjualan ?></p>
            </div>
        </div>
        
        <!-- Widget Total Perbaikan -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-orange-100 text-orange-600 p-3 rounded-full">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Perbaikan</p>
                <p class="text-2xl font-bold"><?= $total_perbaikan ?></p>
            </div>
        </div>
        
        <!-- Widget Kampanye Aktif -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-purple-100 text-purple-600 p-3 rounded-full">
                <i data-lucide="megaphone" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Kampanye Aktif</p>
                <p class="text-2xl font-bold"><?= $total_kampanye ?></p>
            </div>
        </div>
    </div>

    <!-- Baris Chart Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Chart Komposisi Pendapatan -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Komposisi Pendapatan</h3>
            <div class="h-64 flex items-center justify-center">
                <canvas id="chartKomposisiPendapatan" class="max-w-full max-h-full"></canvas>
            </div>
        </div>
        
        <!-- Chart Status Perbaikan -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Status Perbaikan</h3>
            <div class="h-64 flex items-center justify-center">
                <canvas id="chartStatusPerbaikan" class="max-w-full max-h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Baris Tabel Section -->
    <div class="grid grid-cols-1 lg:grid-cols-7 gap-8">
        <!-- Log Aktivitas Terbaru (Lebih Lebar) -->
        <div class="lg:col-span-4">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-lg text-slate-800 mb-4">Log Aktivitas Terbaru</h3>
                <div class="space-y-4">
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while($log = $logs->fetch_assoc()): ?>
                        <div class="flex items-center space-x-4 p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-full flex-shrink-0">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-800 truncate"><?= htmlspecialchars($log['action']) ?></p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="bg-slate-100 text-slate-600 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <?= htmlspecialchars($log['nama_lengkap'] ?? 'Sistem') ?>
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        <?= formatDate($log['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="bg-slate-100 text-slate-400 p-3 rounded-full w-12 h-12 mx-auto mb-3 flex items-center justify-center">
                                <i data-lucide="inbox" class="w-6 h-6"></i>
                            </div>
                            <p class="text-slate-500">Belum ada aktivitas terbaru.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Transaksi Terakhir (Lebih Sempit) -->
        <div class="lg:col-span-3">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-lg text-slate-800 mb-4">Transaksi Terakhir</h3>
                <div class="space-y-4">
                    <?php if ($transactions && $transactions->num_rows > 0): ?>
                        <?php while($trx = $transactions->fetch_assoc()): ?>
                        <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h5 class="font-semibold text-slate-800">TRX-<?= str_pad($trx['id_transaction'], 6, '0', STR_PAD_LEFT) ?></h5>
                                    <p class="text-sm text-slate-600"><?= htmlspecialchars($trx['nama_lengkap'] ?? $trx['guest_name'] ?? 'N/A') ?></p>
                                </div>
                                <span class="bg-<?= $trx['status_pembayaran'] == 'lunas' ? 'green' : 'yellow' ?>-100 text-<?= $trx['status_pembayaran'] == 'lunas' ? 'green' : 'yellow' ?>-800 text-xs font-medium px-2.5 py-1 rounded-full">
                                    <?= $trx['status_pembayaran'] == 'lunas' ? 'Lunas' : 'Belum Bayar' ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="font-bold text-slate-800"><?= formatCurrency($trx['total_harga']) ?></p>
                                <p class="text-xs text-slate-500"><?= formatDate($trx['created_at']) ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="bg-slate-100 text-slate-400 p-3 rounded-full w-12 h-12 mx-auto mb-3 flex items-center justify-center">
                                <i data-lucide="receipt" class="w-6 h-6"></i>
                            </div>
                            <p class="text-slate-500">Belum ada transaksi.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data untuk Chart Komposisi Pendapatan
    const dataKomposisi = {
        labels: <?= json_encode($data_komposisi['labels'] ?? []) ?>,
        datasets: [{
            label: 'Pendapatan',
            data: <?= json_encode($data_komposisi['data'] ?? []) ?>,
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)', // Blue
                'rgba(16, 185, 129, 0.8)', // Green
            ],
            borderColor: [
                'rgba(59, 130, 246, 1)',
                'rgba(16, 185, 129, 1)',
            ],
            borderWidth: 2
        }]
    };

    const configKomposisi = {
        type: 'pie',
        data: dataKomposisi,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    }
                }
            }
        }
    };

    var chartKomposisi = new Chart(
        document.getElementById('chartKomposisiPendapatan'),
        configKomposisi
    );


    // Data untuk Chart Status Perbaikan
    const dataStatusPerbaikan = {
        labels: <?= json_encode($data_status_perbaikan['labels'] ?? []) ?>,
        datasets: [{
            label: 'Jumlah Perbaikan',
            data: <?= json_encode($data_status_perbaikan['data'] ?? []) ?>,
            backgroundColor: [
                'rgba(251, 146, 60, 0.8)', // Orange - Proses
                'rgba(34, 197, 94, 0.8)',  // Green - Selesai
                'rgba(239, 68, 68, 0.8)',  // Red - Gagal
            ],
            borderColor: [
                'rgba(251, 146, 60, 1)',
                'rgba(34, 197, 94, 1)',
                'rgba(239, 68, 68, 1)',
            ],
            borderWidth: 2
        }]
    };

    const configStatusPerbaikan = {
        type: 'doughnut',
        data: dataStatusPerbaikan,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    }
                }
            }
        }
    };
    
    var chartStatusPerbaikan = new Chart(
        document.getElementById('chartStatusPerbaikan'),
        configStatusPerbaikan
    );
});
</script>

<style>
/* Animasi masuk halaman */
.page-enter {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover effects untuk cards */
.bg-white:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Smooth transitions */
* {
    transition: all 0.2s ease;
}
</style>

