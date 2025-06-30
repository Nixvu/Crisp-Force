<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRole(['Customer']);

$user_id = $_SESSION['user_id'];
$customer = getCustomerData($user_id);
$id_customer = $customer['id_customer'];

// Get total purchases
$sql_purchases = "SELECT COUNT(*) as total FROM Transaction WHERE id_customer = ? AND jenis_transaksi = 'barang'";
$stmt_purchases = $conn->prepare($sql_purchases);
$stmt_purchases->bind_param("i", $id_customer);
$stmt_purchases->execute();
$total_purchases = $stmt_purchases->get_result()->fetch_assoc()['total'];

// Get total repairs
$sql_repairs = "SELECT COUNT(*) as total FROM ServiceRequest WHERE id_customer = ?";
$stmt_repairs = $conn->prepare($sql_repairs);
$stmt_repairs->bind_param("i", $id_customer);
$stmt_repairs->execute();
$total_repairs = $stmt_repairs->get_result()->fetch_assoc()['total'];

// Get latest repair details including the last note
$sql_latest_repair = "SELECT sr.*, sp.status as progress_status, sp.catatan as progress_catatan
                     FROM ServiceRequest sr
                     LEFT JOIN (
                         SELECT id_service, status, catatan, created_at
                         FROM ServiceProgress 
                         WHERE (id_service, created_at) IN (
                             SELECT id_service, MAX(created_at) 
                             FROM ServiceProgress 
                             GROUP BY id_service
                         )
                     ) sp ON sr.id_service = sp.id_service
                     WHERE sr.id_customer = ?
                     ORDER BY sr.tanggal_masuk DESC LIMIT 1";
$stmt_latest_repair = $conn->prepare($sql_latest_repair);
$stmt_latest_repair->bind_param("i", $id_customer);
$stmt_latest_repair->execute();
$latest_repair = $stmt_latest_repair->get_result()->fetch_assoc();

// Get latest transactions
$sql_transactions = "SELECT t.*, 
                    CASE 
                        WHEN t.jenis_transaksi = 'barang' THEN 'Pembelian Produk'
                        ELSE 'Perbaikan'
                    END as jenis
                    FROM Transaction t
                    WHERE t.id_customer = ?
                    ORDER BY t.tanggal_transaksi DESC LIMIT 5";
$stmt_transactions = $conn->prepare($sql_transactions);
$stmt_transactions->bind_param("i", $id_customer);
$stmt_transactions->execute();
$transactions = $stmt_transactions->get_result();

// Get active campaigns
$sql_campaigns = "SELECT * FROM Campaign 
                 WHERE status = 'aktif' 
                 AND (target_segmentasi = ? OR target_segmentasi = 'semua')
                 ORDER BY tanggal_mulai DESC LIMIT 3";
$stmt_campaigns = $conn->prepare($sql_campaigns);
$segmentasi = $customer['segmentasi'];
$stmt_campaigns->bind_param("s", $segmentasi);
$stmt_campaigns->execute();
$campaigns = $stmt_campaigns->get_result();

include '../includes/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Dashboard';
</script>

<script>
    document.getElementById('page-title').textContent = 'Dashboard';
</script>

<!-- Kontainer utama dengan animasi masuk -->
<div class="page-enter">

    <!-- Baris Widget Statistik Atas -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Widget Pembelian -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-green-100 text-green-600 p-3 rounded-full">
                <i data-lucide="shopping-cart" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Pembelian</p>
                <p class="text-2xl font-bold"><?= $total_purchases ?></p>
            </div>
        </div>
        
        <!-- Widget Perbaikan -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Perbaikan</p>
                <p class="text-2xl font-bold"><?= $total_repairs ?></p>
            </div>
        </div>
        
        <!-- Widget Points -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-purple-100 text-purple-600 p-3 rounded-full">
                <i data-lucide="star" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Points</p>
                <p class="text-2xl font-bold"><?= floor($customer['total_pengeluaran'] / 100000) ?></p>
            </div>
        </div>
        
        <!-- Widget Pengeluaran -->
        <div class="bg-white p-6 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="bg-orange-100 text-orange-600 p-3 rounded-full">
                <i data-lucide="dollar-sign" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total Pengeluaran</p>
                <p class="text-2xl font-bold"><?= formatCurrency($customer['total_pengeluaran']) ?></p>
            </div>
        </div>
    </div>

    <!-- Baris Konten Tengah (Status Perbaikan dan Promo) -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mb-8">
        <!-- Kolom Kiri: Status Perbaikan Terkini (Lebih Lebar) -->
        <div class="lg:col-span-3">
            <?php if ($latest_repair): ?>
            <div class="bg-white p-6 rounded-xl shadow-sm h-full">
                <h3 class="font-bold text-lg text-slate-800 mb-4">Status Perbaikan Terkini</h3>
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <img src="../assets/images/device-placeholder.png" class="w-24 h-24 mx-auto mb-3 rounded-lg object-cover">
                        <h6 class="font-semibold"><?= htmlspecialchars($latest_repair['nama_service']) ?></h6>
                        <small class="text-slate-500">ID: <?= htmlspecialchars($latest_repair['kode_service']) ?></small>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-slate-500">Merk</p>
                                <p class="font-semibold"><?= htmlspecialchars($latest_repair['merk']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Model</p>
                                <p class="font-semibold"><?= htmlspecialchars($latest_repair['model']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Tanggal Masuk</p>
                                <p class="font-semibold"><?= formatDate($latest_repair['tanggal_masuk']) ?></p>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-sm text-slate-500 mb-2">Deskripsi Kerusakan</p>
                            <p class="text-slate-700"><?= htmlspecialchars($latest_repair['deskripsi_kerusakan']) ?></p>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm font-semibold text-blue-800 mb-1">Catatan Teknisi</p>
                            <p class="text-blue-700"><?= htmlspecialchars($latest_repair['progress_catatan'] ?? 'Belum ada catatan dari teknisi') ?></p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <?php
                            $status_map = [
                                'diterima_digerai' => ['Diterima', 'warning', 15], 
                                'analisis_kerusakan' => ['Analisis', 'warning', 30],
                                'menunggu_sparepart' => ['Menunggu Part', 'info', 50], 
                                'dalam_perbaikan' => ['Perbaikan', 'primary', 70],
                                'perbaikan_selesai' => ['Selesai', 'success', 100], 
                                'gagal' => ['Gagal', 'danger', 100],
                                'diambil_pelanggan' => ['Diambil', 'secondary', 100]
                            ];
                            $current_status_key = $latest_repair['progress_status'] ?? 'diterima_digerai';
                            [$status_text, $status_color, $progress_value] = $status_map[$current_status_key];
                        ?>
                        <div class="mt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-slate-700">Progres Saat Ini</span>
                                <span class="bg-<?= $status_color == 'warning' ? 'yellow' : ($status_color == 'info' ? 'blue' : ($status_color == 'primary' ? 'blue' : ($status_color == 'success' ? 'green' : ($status_color == 'danger' ? 'red' : 'gray')))) ?>-100 text-<?= $status_color == 'warning' ? 'yellow' : ($status_color == 'info' ? 'blue' : ($status_color == 'primary' ? 'blue' : ($status_color == 'success' ? 'green' : ($status_color == 'danger' ? 'red' : 'gray')))) ?>-800 text-xs font-medium px-2.5 py-1 rounded-full"><?= $status_text ?></span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-3">
                                <div class="bg-<?= $status_color == 'warning' ? 'yellow' : ($status_color == 'info' ? 'blue' : ($status_color == 'primary' ? 'blue' : ($status_color == 'success' ? 'green' : ($status_color == 'danger' ? 'red' : 'gray')))) ?>-600 h-3 rounded-full transition-all duration-300" style="width: <?= $progress_value ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Kolom Kanan: Promo dan Diskon + Log Perbaikan (Lebih Sempit) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Promo & Diskon -->
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-lg mb-4">Promo & Diskon</h3>
                <div class="space-y-4">
                    <?php if ($campaigns->num_rows > 0): ?>
                        <?php while ($campaign = $campaigns->fetch_assoc()): ?>
                        <div class="border border-slate-200 rounded-lg p-4">
                            <h5 class="font-semibold text-slate-800 mb-2"><?= htmlspecialchars($campaign['nama_kampanye']) ?></h5>
                            <p class="text-sm text-slate-600 mb-2"><?= substr(htmlspecialchars($campaign['deskripsi']), 0, 80) . '...' ?></p>
                            <?php if ($campaign['kode_promo']): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded p-2">
                                <span class="text-xs font-semibold text-blue-800">Kode: <?= htmlspecialchars($campaign['kode_promo']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-slate-500 text-center py-8">Tidak ada promo yang tersedia saat ini.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Log Perbaikan -->
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-lg mb-4">Log Perbaikan</h3>
                <div class="space-y-4">
                    <?php if ($total_repairs > 0): ?>
                        <?php 
                        // Get repair history
                        $sql_repair_history = "SELECT sr.kode_service, sr.nama_service, sp.status, sp.created_at 
                                             FROM ServiceRequest sr
                                             JOIN ServiceProgress sp ON sr.id_service = sp.id_service
                                             WHERE sr.id_customer = ?
                                             ORDER BY sp.created_at DESC LIMIT 3";
                        $stmt_repair_history = $conn->prepare($sql_repair_history);
                        $stmt_repair_history->bind_param("i", $id_customer);
                        $stmt_repair_history->execute();
                        $repair_history = $stmt_repair_history->get_result();
                        
                        if ($repair_history->num_rows > 0):
                            while ($log = $repair_history->fetch_assoc()):
                        ?>
                        <div class="border border-slate-200 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h5 class="font-semibold text-slate-800"><?= htmlspecialchars($log['nama_service']) ?></h5>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($log['kode_service']) ?></p>
                                </div>
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-100 text-blue-800">
                                    <?= ucfirst(str_replace('_', ' ', $log['status'])) ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-2"><?= formatDate($log['created_at']) ?></p>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <p class="text-slate-500 text-center py-8">Belum ada riwayat perbaikan.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-slate-500 text-center py-8">Belum ada riwayat perbaikan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Transaksi Terakhir (Full Width) -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <h3 class="font-bold text-lg text-slate-800 mb-4">Transaksi Terakhir</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 font-semibold">No</th>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">ID Transaksi</th>
                        <th class="px-4 py-3 font-semibold">Jenis</th>
                        <th class="px-4 py-3 font-semibold">Nama Produk/Perbaikan</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php 
                    mysqli_data_seek($transactions, 0); 
                    $counter = 1;
                    while ($transaction = $transactions->fetch_assoc()): 
                        // Get transaction details
                        $transaction_details = [];
                        if ($transaction['jenis_transaksi'] == 'barang') {
                            $sql_details = "SELECT p.nama_product 
                                           FROM TransactionProductDetail tpd
                                           JOIN Product p ON tpd.id_product = p.id_product
                                           WHERE tpd.id_transaction = ?";
                        } else {
                            $sql_details = "SELECT sr.nama_service 
                                           FROM TransactionServiceDetail tsd
                                           JOIN ServiceRequest sr ON tsd.id_service = sr.id_service
                                           WHERE tsd.id_transaction = ?";
                        }
                        $stmt_details = $conn->prepare($sql_details);
                        $stmt_details->bind_param("i", $transaction['id_transaction']);
                        $stmt_details->execute();
                        $transaction_details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 text-slate-700"><?= $counter++ ?></td>
                        <td class="px-4 py-3 text-slate-900"><?= formatDate($transaction['tanggal_transaksi']) ?></td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-slate-700">TRX-<?= str_pad($transaction['id_transaction'], 6, '0', STR_PAD_LEFT) ?></span>
                        </td>
                        <td class="px-4 py-3 text-slate-900"><?= htmlspecialchars($transaction['jenis']) ?></td>
                        <td class="px-4 py-3 text-slate-900">
                            <div class="line-clamp-2">
                                <?php 
                                if (!empty($transaction_details)) {
                                    $items = array_column($transaction_details, $transaction['jenis_transaksi'] == 'barang' ? 'nama_product' : 'nama_service');
                                    echo htmlspecialchars(implode(', ', $items));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-900"><?= formatCurrency($transaction['total_harga']) ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $transaction['status_pembayaran'] == 'lunas' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= $transaction['status_pembayaran'] == 'lunas' ? 'Lunas' : 'Belum Bayar' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button onclick="viewInvoice('<?= $transaction['id_transaction'] ?>')" 
                                    class="text-blue-600 hover:text-blue-800 flex items-center">
                                <i data-lucide="file-text" class="w-4 h-4 mr-1"></i>
                                Invoice
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if ($transactions->num_rows == 0): ?>
            <div class="text-center py-12">
                <i data-lucide="shopping-cart" class="w-12 h-12 text-slate-400 mx-auto mb-4"></i>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Belum Ada Transaksi</h3>
                <p class="text-slate-500">Anda belum melakukan transaksi apapun.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    function viewInvoice(transactionId) {
        const modal = document.getElementById('invoiceModal');
        const modalBody = document.getElementById('invoiceModalBody');
        
        modalBody.innerHTML = '<div class="text-center p-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-4 text-slate-600">Memuat data...</p></div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        fetch(`invoice.php?id=${transactionId}`)
            .then(response => {
                if (!response.ok) throw new Error(`Gagal memuat invoice. Status: ${response.status}`);
                return response.text();
            })
            .then(html => { modalBody.innerHTML = html; })
            .catch(error => { modalBody.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">${error.message}</div>`; });
    }

    function closeInvoiceModal() {
        const modal = document.getElementById('invoiceModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function printInvoice() {
        const content = document.getElementById('invoiceModalBody').innerHTML;
        const win = window.open('', '', 'height=700,width=900');
        win.document.write('<html><head><title>Cetak Invoice</title>');
        win.document.write('<script src="https://cdn.tailwindcss.com"></' + 'script>');
        win.document.write('<style>body { padding: 20px; font-family: Arial, sans-serif; } @media print { .no-print { display: none !important; } }</style>');
        win.document.write('</head><body>' + content + '</body></html>');
        win.document.close();
        win.addEventListener('load', function() {
            win.focus();
            win.print();
        });
    }

    // Initialize Lucide icons
    lucide.createIcons();
</script>

<?php include '../includes/footer.php'; ?>
