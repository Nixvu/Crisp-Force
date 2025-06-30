<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// --- PERUBAHAN QUERY: Menambahkan data yang relevan untuk setiap tabel ---

// Get repair data for different tabs
// PERUBAHAN: Menambahkan u.no_hp untuk ditampilkan di tabel
$order_masuk = $conn->query("SELECT sr.*, u.nama_lengkap as customer_name, u.no_hp FROM ServiceRequest sr JOIN Customer c ON sr.id_customer = c.id_customer JOIN User u ON c.id_user = u.id_user WHERE sr.status = 'pending' ORDER BY sr.created_at DESC");

$dalam_pengerjaan = $conn->query("SELECT sr.*, u.nama_lengkap as customer_name, teknisi.nama_lengkap as technician_name, sp.status as latest_progress FROM ServiceRequest sr 
                                JOIN Customer c ON sr.id_customer = c.id_customer 
                                JOIN User u ON c.id_user = u.id_user
                                LEFT JOIN User teknisi ON sr.id_teknisi = teknisi.id_user
                                LEFT JOIN (
                                    SELECT id_service, status FROM ServiceProgress WHERE (id_service, created_at) IN 
                                    (SELECT id_service, MAX(created_at) FROM ServiceProgress GROUP BY id_service)
                                ) sp ON sr.id_service = sp.id_service
                                WHERE sr.status = 'diterima' AND (sp.status IS NULL OR sp.status NOT IN ('perbaikan_selesai', 'gagal', 'diambil_pelanggan'))
                                ORDER BY sr.tanggal_masuk DESC");

// PERUBAHAN: Menambahkan join untuk mengambil nama teknisi di riwayat
$riwayat_perbaikan = $conn->query("SELECT sr.*, u.nama_lengkap as customer_name, teknisi.nama_lengkap as technician_name, sp.status as latest_progress FROM ServiceRequest sr 
                                JOIN Customer c ON sr.id_customer = c.id_customer 
                                JOIN User u ON c.id_user = u.id_user
                                LEFT JOIN User teknisi ON sr.id_teknisi = teknisi.id_user
                                LEFT JOIN (
                                    SELECT id_service, status FROM ServiceProgress WHERE (id_service, created_at) IN 
                                    (SELECT id_service, MAX(created_at) FROM ServiceProgress GROUP BY id_service)
                                ) sp ON sr.id_service = sp.id_service
                                WHERE sp.status IN ('perbaikan_selesai', 'gagal', 'diambil_pelanggan')
                                ORDER BY sr.updated_at DESC");

$spareparts = $conn->query("SELECT id_product, nama_product, harga, stok FROM Product WHERE category = 'sparepart' AND deleted_at IS NULL AND stok > 0");

// Get technicians for assignment
$technicians = $conn->query("SELECT id_user, nama_lengkap FROM User WHERE role = 'Admin' AND deleted_at IS NULL");
?>

<?php include '../../includes/header.php'; ?>

<script>
    document.getElementById('page-title').textContent = 'Manajemen Perbaikan';
</script>

<div class="space-y-6">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-slate-800">Manajemen Perbaikan</h2>
        <button onclick="showCreateRepairModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Buat Perbaikan
        </button>
    </div>

    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button class="tab-button active group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="order">
                <i data-lucide="inbox" class="mr-2"></i>
                Order Masuk
                <span class="ml-2 bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                    <?= $order_masuk->num_rows ?>
                </span>
            </button>
            <button class="tab-button group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="progress">
                <i data-lucide="loader-2" class="mr-2"></i>
                Dalam Pengerjaan
            </button>
            <button class="tab-button group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="history">
                <i data-lucide="history" class="mr-2"></i>
                Riwayat
            </button>
        </nav>
    </div>


    <div class="space-y-6">
        <div id="order-tab" class="tab-content active">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Daftar Order Baru</h3>
                    <div class="relative w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                        </div>
                        <input type="text" id="order-search" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Cari order...">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Customer & Kontak</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Perangkat & Kerusakan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tanggal Masuk</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200" id="order-table-body">
                            <?php if ($order_masuk->num_rows > 0):
                                $i = 1;
                                while ($order = $order_masuk->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-500"><?= $i++ ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">SRV-<?= str_pad($order['id_service'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            <div class="font-medium text-slate-800"><?= htmlspecialchars($order['customer_name']) ?></div>
                                            <div class="text-xs text-slate-400"><?= htmlspecialchars($order['no_hp']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            <div class="font-medium text-slate-800"><?= htmlspecialchars($order['nama_service']) ?></div>
                                            <div class="text-xs text-slate-400 truncate max-w-xs"><?= htmlspecialchars($order['deskripsi_kerusakan']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= formatDate($order['tanggal_masuk']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <div class="flex justify-center space-x-2">
                                                <button onclick="showAssignTechnicianModal(<?= $order['id_service'] ?>)" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" title="Terima & Tetapkan Teknisi">
                                                    <i data-lucide="check" class="w-4 h-4"></i>
                                                </button>
                                                <a href="../actions/proses_perbaikan.php?action=reject&id=<?= $order['id_service'] ?>" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="return confirm('Yakin tolak perbaikan ini?')" title="Tolak Perbaikan">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-slate-500">Tidak ada order masuk saat ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="progress-tab" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Daftar Perbaikan Aktif</h3>
                    <div class="relative w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                        </div>
                        <input type="text" id="progress-search" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Cari perbaikan...">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Perangkat</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Progress</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Teknisi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Estimasi Selesai</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200" id="progress-table-body">
                            <?php if ($dalam_pengerjaan->num_rows > 0):
                                $i = 1;
                                while ($p = $dalam_pengerjaan->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-500"><?= $i++ ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">SRV-<?= str_pad($p['id_service'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= htmlspecialchars($p['customer_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= htmlspecialchars($p['nama_service']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?= $p['latest_progress'] ? ucfirst(str_replace('_', ' ', $p['latest_progress'])) : 'Diterima' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            <?= $p['technician_name'] ? htmlspecialchars($p['technician_name']) : '<span class="text-slate-400">Belum ditetapkan</span>' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            <?= $p['tanggal_estimasi'] ? formatDate($p['tanggal_estimasi']) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <button onclick="updateProgress(<?= $p['id_service'] ?>)" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Update Progress">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-10 text-slate-500">Tidak ada perbaikan dalam pengerjaan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="history-tab" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Riwayat Perbaikan</h3>
                    <div class="relative w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                        </div>
                        <input type="text" id="history-search" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Cari riwayat...">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Perangkat</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status Akhir</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Teknisi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tanggal Selesai</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200" id="history-table-body">
                            <?php if ($riwayat_perbaikan->num_rows > 0):
                                $i = 1;
                                while ($h = $riwayat_perbaikan->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-500"><?= $i++ ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">SRV-<?= str_pad($h['id_service'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= htmlspecialchars($h['customer_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= htmlspecialchars($h['nama_service']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $h['latest_progress'] == 'perbaikan_selesai' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= ucfirst(str_replace('_', ' ', $h['latest_progress'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            <?= $h['technician_name'] ? htmlspecialchars($h['technician_name']) : '<span class="text-slate-400">-</span>' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= formatDate($h['updated_at']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <button onclick="showServiceDetails(<?= $h['id_service'] ?>)" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" title="Lihat Detail Riwayat">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-10 text-slate-500">Belum ada riwayat perbaikan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed inset-0 overflow-y-auto hidden" id="createRepairModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900" id="modal-title">Buat Permintaan Perbaikan Baru</h3>
                        <div class="mt-4">
                            <form action="../actions/proses_perbaikan.php" method="POST" id="createRepairForm">
                                <input type="hidden" name="action" value="create_repair">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Pelanggan</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="customer_name" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Nomor HP</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="phone" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Perangkat</label>
                                    <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="device_name" required placeholder="Contoh: Laptop Acer Aspire 5">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Merk</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="brand">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Model</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="model">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Seri</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="serial_no">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori Barang</label>
                                    <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="device_category" required>
                                        <option value="komputer">Komputer</option>
                                        <option value="laptop">Laptop</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Kerusakan</label>
                                    <textarea class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="issue_description" rows="3" required></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Kelengkapan</label>
                                    <textarea class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="accessories" rows="2" placeholder="Contoh: Unit, Charger, Tas"></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Masuk</label>
                                        <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="entry_date" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Estimasi Selesai (Opsional)</label>
                                        <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="estimated_date">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="createRepairForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Simpan
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('createRepairModal')">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
<div class="fixed inset-0 overflow-y-auto hidden" id="assignTechnicianModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900" id="modal-title">Tetapkan Teknisi</h3>
                        <div class="mt-4">
                            <form action="../actions/proses_perbaikan.php" method="POST" id="assignTechnicianForm">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id_service" id="assign_id_service">

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Teknisi</label>
                                    <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="id_teknisi" required>
                                        <option value="" selected disabled>Pilih Teknisi</option>
                                        <?php mysqli_data_seek($technicians, 0);
                                        while ($tech = $technicians->fetch_assoc()): ?>
                                            <option value="<?= $tech['id_user'] ?>"><?= htmlspecialchars($tech['nama_lengkap']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Estimasi Selesai</label>
                                    <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="tanggal_estimasi" required>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="assignTechnicianForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Simpan
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('assignTechnicianModal')">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
<div class="fixed inset-0 overflow-y-auto hidden" id="updateProgressModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900" id="modal-title">Update Progress Perbaikan</h3>
                        <div class="mt-4">
                            <form action="../actions/proses_perbaikan.php" method="POST" id="updateProgressForm">
                                <input type="hidden" name="action" value="update_progress">
                                <input type="hidden" name="id_service" id="id_service_modal">

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Ubah Status Ke</label>
                                    <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="new_status" required>
                                        <option value="analisis_kerusakan">Analisis Kerusakan</option>
                                        <option value="menunggu_sparepart">Menunggu Sparepart</option>
                                        <option value="dalam_perbaikan">Dalam Perbaikan</option>
                                        <option value="perbaikan_selesai">Perbaikan Selesai</option>
                                        <option value="gagal">Perbaikan Gagal</option>
                                        <option value="diambil_pelanggan">Sudah Diambil Pelanggan</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Teknisi (Opsional)</label>
                                    <textarea class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="catatan" rows="2"></textarea>
                                </div>
                                <hr class="my-4">
                                <h6 class="text-sm font-medium text-slate-900 mb-2">Penggunaan Sparepart (Opsional)</h6>
                                <div id="sparepart-items" class="space-y-2 mb-4">
                                </div>
                                <button type="button" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="addSparepartItem()">
                                    <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Tambah Sparepart
                                </button>
                                <hr class="my-4">
                                <h6 class="text-sm font-medium text-slate-900 mb-2">Biaya & Estimasi</h6>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Biaya Jasa Service (Opsional)</label>
                                        <input type="number" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="biaya_service" placeholder="Isi jika ada tambahan biaya jasa">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Estimasi Selesai (Opsional)</label>
                                        <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="tanggal_estimasi">
                                    </div>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 p-3 rounded-md text-sm">
                                    <div class="flex items-start">
                                        <i data-lucide="info" class="w-4 h-4 inline-block mr-2 mt-0.5 flex-shrink-0"></i>
                                        <span>Jika status diubah menjadi "Perbaikan Selesai", sistem akan otomatis membuatkan tagihan/transaksi baru untuk pelanggan.</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="updateProgressForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Simpan Progress
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('updateProgressModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
<div class="fixed inset-0 overflow-y-auto hidden" id="serviceDetailsModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900" id="service-details-title">Detail Perbaikan</h3>
                        <div class="mt-4">
                            <div id="service-details-content">
                                <div class="animate-pulse flex space-x-4">
                                    <div class="flex-1 space-y-4 py-1">
                                        <div class="h-4 bg-slate-200 rounded w-3/4"></div>
                                        <div class="space-y-2">
                                            <div class="h-4 bg-slate-200 rounded"></div>
                                            <div class="h-4 bg-slate-200 rounded w-5/6"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('serviceDetailsModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // PERUBAHAN: Logika Tab yang lebih bersih
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');

            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            tabContents.forEach(content => {
                if (content.id === `${tabId}-tab`) {
                    content.classList.remove('hidden');
                    content.classList.add('active');
                } else {
                    content.classList.add('hidden');
                    content.classList.remove('active');
                }
            });
            lucide.createIcons();
        });
    });

    // Modal functions (no changes, but included for completeness)
    function showCreateRepairModal() {
        document.getElementById('createRepairModal').classList.remove('hidden');
    }

    function showAssignTechnicianModal(serviceId) {
        document.getElementById('assign_id_service').value = serviceId;
        document.getElementById('assignTechnicianModal').classList.remove('hidden');
    }

    function updateProgress(serviceId) {
        document.getElementById('id_service_modal').value = serviceId;
        document.getElementById('updateProgressModal').classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        if (modalId === 'updateProgressModal') {
            resetModal();
        }
    }

    function resetModal() {
        const form = document.querySelector('#updateProgressModal form');
        if (form) form.reset();
        document.getElementById('sparepart-items').innerHTML = '';
        sparepartIndex = 0;
    }

    // Show service details
    function showServiceDetails(serviceId) {
        document.getElementById('serviceDetailsModal').classList.remove('hidden');
        const contentDiv = document.getElementById('service-details-content');

        contentDiv.innerHTML = `<div class="text-center p-8"><p class="text-slate-500">Memuat detail...</p></div>`; // Loading state

        // Fetch service details via AJAX
        fetch(`../actions/get_service_details.php?id=${serviceId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(data => {
                contentDiv.innerHTML = data;
                lucide.createIcons();
            })
            .catch(error => {
                contentDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-md">
                        Gagal memuat detail perbaikan. Silakan coba lagi. Error: ${error.message}
                    </div>
                `;
            });
    }

    // Search functionality
    ['order', 'progress', 'history'].forEach(prefix => {
        const searchInput = document.getElementById(`${prefix}-search`);
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll(`#${prefix}-table-body tr`);

                rows.forEach(row => {
                    if (row.textContent.toLowerCase().includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });

    // Sparepart items
    let sparepartIndex = 0;

    function addSparepartItem() {
        const container = document.getElementById('sparepart-items');
        const newItem = document.createElement('div');
        newItem.classList.add('grid', 'grid-cols-12', 'gap-2', 'items-center', 'sparepart-item');
        newItem.innerHTML = `
            <div class="col-span-7">
                <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" name="spareparts[${sparepartIndex}][id]" required>
                    <option selected disabled value="">Pilih Sparepart</option>
                    <?php mysqli_data_seek($spareparts, 0);
                    while ($sp = $spareparts->fetch_assoc()) echo "<option value='{$sp['id_product']}'>{$sp['nama_product']} (Stok: {$sp['stok']})</option>"; ?>
                </select>
            </div>
            <div class="col-span-3">
                <input type="number" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" name="spareparts[${sparepartIndex}][qty]" placeholder="Qty" value="1" min="1" required>
            </div>
            <div class="col-span-2">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-3 py-2 bg-red-600 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="this.closest('.sparepart-item').remove()">
                    Hapus
                </button>
            </div>
        `;
        container.appendChild(newItem);
        sparepartIndex++;
    }

    // Close modal when clicking outside
    document.querySelectorAll('[id$="Modal"]').forEach(modal => {
        modal.addEventListener('click', function(e) {
            // Check if the click is on the dark overlay itself
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
</script>

<style>
    .tab-button {
        border-color: transparent;
        color: #64748b;
        /* text-slate-500 */
    }

    .tab-button:hover {
        border-color: #cbd5e1;
        /* border-slate-300 */
        color: #334155;
        /* text-slate-700 */
    }

    .tab-button.active {
        border-color: #3b82f6;
        /* border-blue-500 */
        color: #2563eb;
        /* text-blue-600 */
    }

    .tab-button .lucide {
        color: #94a3b8;
        /* text-slate-400 */
    }

    .tab-button:hover .lucide,
    .tab-button.active .lucide {
        color: #2563eb;
        /* text-blue-600 */
    }
</style>

<?php include '../../includes/footer.php'; ?>