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
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-8">
        <!-- Kolom Kiri: Status Perbaikan Terkini (Lebih Lebar) -->
        <div class="lg:col-span-3">
            <?php if ($latest_repair): ?>
            <div class="bg-white p-6 rounded-xl shadow-sm h-full flex flex-col">
                <h3 class="font-bold text-lg text-slate-800 mb-4">Status Perbaikan Terkini</h3>
                
                <!-- Info Perangkat -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 pb-6 border-b border-slate-200">
                    <div class="md:col-span-1 text-center">
                        <?php
                            $deviceImage = '../assets/images/device-placeholder.png.png'; // Default
                            if (isset($latest_repair['kategori_barang'])) {
                                $deviceType = strtolower($latest_repair['kategori_barang']);
                                $imagePath = '../assets/images/' . $deviceType . '.png';
                                $absoluteImagePath = realpath(__DIR__ . '/../assets/images/' . $deviceType . '.png');
                                if ($absoluteImagePath && file_exists($absoluteImagePath)) {
                                    $deviceImage = $imagePath;
                                }
                            }
                        ?>
                        <img src="<?= $deviceImage ?>" alt="Gambar Perangkat" class="w-28 h-28 mx-auto mb-3 rounded-lg object-cover bg-slate-100">
                        <h6 class="font-semibold text-base"><?= htmlspecialchars($latest_repair['nama_service']) ?></h6>
                        <small class="text-sm text-slate-500"><?= htmlspecialchars($latest_repair['kode_service']) ?></small>
                    </div>
                    <div class="md:col-span-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-slate-500">Merk</p>
                                <p class="font-semibold text-base"><?= htmlspecialchars($latest_repair['merk']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Model</p>
                                <p class="font-semibold text-base"><?= htmlspecialchars($latest_repair['model']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Tanggal Masuk</p>
                                <p class="font-semibold text-base"><?= formatDate($latest_repair['tanggal_masuk']) ?></p>
                            </div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm font-semibold text-blue-800 mb-1">Catatan Teknisi Terakhir</p>
                            <p class="text-base text-blue-700"><?= htmlspecialchars($latest_repair['progress_catatan'] ?? 'Belum ada catatan dari teknisi.') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Progress Timeline -->
                <div>
                    <h4 class="font-semibold text-slate-700 mb-4">Progres Perbaikan</h4>
                    <?php
                        $status_timeline = [
                            'diterima_digerai' => ['Diterima', 'check-circle', 1],
                            'analisis_kerusakan' => ['Analisis Kerusakan', 'search', 2],
                            'menunggu_sparepart' => ['Menunggu Sparepart', 'clock', 3],
                            'dalam_perbaikan' => ['Dalam Perbaikan', 'wrench', 4],
                            'perbaikan_selesai' => ['Selesai & Siap Diambil', 'package-check', 5],
                        ];
                        $current_status_key = $latest_repair['progress_status'] ?? 'diterima_digerai';
                        $final_statuses = ['gagal', 'diambil_pelanggan'];

                        if (in_array($current_status_key, $final_statuses)) {
                            if ($current_status_key == 'gagal') {
                                echo '<div class="flex items-center bg-red-50 text-red-700 p-4 rounded-lg"><i data-lucide="x-circle" class="w-6 h-6 mr-3"></i><div><p class="font-bold">Perbaikan Gagal</p><p class="text-sm">Silakan hubungi kami untuk informasi lebih lanjut.</p></div></div>';
                            } else {
                                echo '<div class="flex items-center bg-green-50 text-green-700 p-4 rounded-lg"><i data-lucide="check-check" class="w-6 h-6 mr-3"></i><div><p class="font-bold">Perbaikan Selesai & Sudah Diambil</p><p class="text-sm">Terima kasih telah menggunakan layanan kami.</p></div></div>';
                            }
                        } else {
                            $current_step = $status_timeline[$current_status_key][2];
                    ?>
                    <div class="grid grid-cols-5 text-center text-xs font-medium text-slate-500">
                        <?php foreach ($status_timeline as $key => $details):
                            [$label, $icon, $step] = $details;
                            $state = 'pending';
                            if ($step < $current_step) $state = 'completed';
                            if ($step == $current_step) $state = 'active';
                        ?>
                        <div class="progress-step <?= $state ?> relative">
                            <div class="progress-step-circle">
                                <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                            </div>
                            <p class="mt-2 text-xs <?= ($state == 'active') ? 'font-bold text-blue-600' : '' ?>"><?= $label ?></p>
                            <?php if ($step < 5): ?>
                                <div class="progress-step-line"></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php } ?>
                </div>

            </div>
            <?php else: ?>
            <div class="bg-white p-6 rounded-xl shadow-sm h-full flex items-center justify-center">
                <div class="text-center">
                    <i data-lucide="wrench" class="w-12 h-12 text-slate-400 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Tidak Ada Perbaikan Aktif</h3>
                    <p class="text-slate-500">Anda tidak memiliki perbaikan yang sedang berjalan saat ini.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Kolom Kanan: Promo dan Log (Lebih Sempit) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Promo & Diskon -->
            <div class="bg-white p-4 rounded-xl shadow-sm flex flex-col h-64">
                <h3 class="font-bold text-base mb-3 flex-shrink-0">Promo & Diskon</h3>
                <div class="space-y-2 flex-grow overflow-y-auto">
                    <?php if ($campaigns->num_rows > 0): ?>
                        <?php mysqli_data_seek($campaigns, 0); ?>
                        <?php while ($campaign = $campaigns->fetch_assoc()): ?>
                        <a href="#" onclick="showPromoDetails(event, '<?= htmlspecialchars(addslashes($campaign['nama_kampanye'])) ?>', '<?= htmlspecialchars(addslashes($campaign['deskripsi'])) ?>', '<?= htmlspecialchars($campaign['kode_promo']) ?>')" class="block border border-slate-200 rounded-lg p-2.5 hover:bg-slate-50 hover:border-blue-400 transition-all">
                            <h5 class="font-semibold text-slate-800 text-sm mb-1 truncate"><?= htmlspecialchars($campaign['nama_kampanye']) ?></h5>
                            <p class="text-xs text-slate-500 mb-1.5 line-clamp-2"><?= htmlspecialchars($campaign['deskripsi']) ?></p>
                            <?php if ($campaign['kode_promo']): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded px-1.5 py-0.5 inline-block">
                                <span class="text-xs font-semibold text-blue-800">Kode: <?= htmlspecialchars($campaign['kode_promo']) ?></span>
                            </div>
                            <?php endif; ?>
                        </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="flex items-center justify-center h-full">
                            <p class="text-slate-500 text-sm text-center">Tidak ada promo saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Log Perbaikan -->
            <div class="bg-white p-4 rounded-xl shadow-sm flex flex-col h-64">
                <h3 class="font-bold text-base mb-3 flex-shrink-0">Log Perbaikan Terakhir</h3>
                <div class="space-y-2 flex-grow overflow-y-auto">
                    <?php if ($total_repairs > 0): ?>
                        <?php 
                        $sql_repair_history = "SELECT sr.kode_service, sr.nama_service, sp.status, sp.created_at 
                                             FROM ServiceRequest sr
                                             JOIN ServiceProgress sp ON sr.id_service = sp.id_service
                                             WHERE sr.id_customer = ?
                                             ORDER BY sp.created_at DESC LIMIT 5";
                        $stmt_repair_history = $conn->prepare($sql_repair_history);
                        $stmt_repair_history->bind_param("i", $id_customer);
                        $stmt_repair_history->execute();
                        $repair_history = $stmt_repair_history->get_result();
                        
                        if ($repair_history->num_rows > 0):
                            while ($log = $repair_history->fetch_assoc()):
                        ?>
                        <div class="border border-slate-200 rounded-lg p-2.5">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h5 class="font-semibold text-slate-800 text-sm truncate"><?= htmlspecialchars($log['nama_service']) ?></h5>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($log['kode_service']) ?></p>
                                </div>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 ml-2 flex-shrink-0">
                                    <?= ucfirst(str_replace('_', ' ', $log['status'])) ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5"><?= formatDate($log['created_at']) ?></p>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <div class="flex items-center justify-center h-full">
                            <p class="text-slate-500 text-sm text-center">Belum ada riwayat perbaikan.</p>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flex items-center justify-center h-full">
                            <p class="text-slate-500 text-sm text-center">Belum ada riwayat perbaikan.</p>
                        </div>
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
                    if ($transactions->num_rows > 0):
                        while ($transaction = $transactions->fetch_assoc()): 
                            $item_names = [];
                            if ($transaction['jenis_transaksi'] == 'barang') {
                                $sql_details = "SELECT p.nama_product 
                                               FROM TransactionProductDetail tpd
                                               JOIN Product p ON tpd.id_product = p.id_product
                                               WHERE tpd.id_transaction = ?";
                                $stmt_details = $conn->prepare($sql_details);
                                $stmt_details->bind_param("i", $transaction['id_transaction']);
                                $stmt_details->execute();
                                $details_result = $stmt_details->get_result();
                                while($row = $details_result->fetch_assoc()){
                                    $item_names[] = $row['nama_product'];
                                }
                            } else { // jenis_transaksi == 'jasa'
                                $sql_details = "SELECT sr.nama_service 
                                               FROM TransactionServiceDetail tsd
                                               JOIN ServiceRequest sr ON tsd.id_service = sr.id_service
                                               WHERE tsd.id_transaction = ?";
                                $stmt_details = $conn->prepare($sql_details);
                                $stmt_details->bind_param("i", $transaction['id_transaction']);
                                $stmt_details->execute();
                                $details_result = $stmt_details->get_result();
                                while($row = $details_result->fetch_assoc()){
                                    $item_names[] = $row['nama_service'];
                                }
                            }
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
                                    <?= !empty($item_names) ? htmlspecialchars(implode(', ', $item_names)) : '-' ?>
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
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-12">
                                    <i data-lucide="shopping-cart" class="w-12 h-12 text-slate-400 mx-auto mb-4"></i>
                                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Belum Ada Transaksi</h3>
                                    <p class="text-slate-500">Anda belum melakukan transaksi apapun.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
    .progress-step .progress-step-circle {
        width: 2.5rem; height: 2.5rem;
        border-radius: 9999px; display: flex;
        align-items: center; justify-content: center;
        margin: 0 auto; transition: all 0.3s ease;
        border: 2px solid;
    }
    .progress-step .progress-step-line {
        position: absolute; top: 1.25rem;
        left: 50%; width: 100%;
        height: 2px; transform: translateY(-50%);
        z-index: -1; transition: all 0.3s ease;
    }
    .progress-step.pending .progress-step-circle { background-color: #F1F5F9; border-color: #CBD5E1; color: #64748B; }
    .progress-step.pending .progress-step-line { background-color: #CBD5E1; }
    .progress-step.active .progress-step-circle { background-color: #DBEAFE; border-color: #3B82F6; color: #3B82F6; }
    .progress-step.active ~ .progress-step .progress-step-line { background-color: #CBD5E1; }
    .progress-step.completed .progress-step-circle { background-color: #22C55E; border-color: #22C55E; color: white; }
    .progress-step.completed .progress-step-line { background-color: #22C55E; }
</style>

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
            .then(html => { 
                modalBody.innerHTML = html; 
                if (typeof lucide !== 'undefined') { lucide.createIcons(); }
            })
            .catch(error => { modalBody.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">${error.message}</div>`; });
    }

    function closeInvoiceModal() {
        document.getElementById('invoiceModal').classList.add('hidden');
    }

    function printInvoice() {
        const content = document.getElementById('invoiceModalBody').innerHTML;
        const win = window.open('', '', 'height=700,width=900');
        win.document.write('<html><head><title>Cetak Invoice</title>');
        win.document.write('<script src="https://cdn.tailwindcss.com"><\/script>');
        win.document.write('<style>body { padding: 20px; font-family: Arial, sans-serif; } @media print { .no-print { display: none !important; } }</style>');
        win.document.write('</head><body>' + content + '</body></html>');
        win.document.close();
        win.addEventListener('load', function() {
            win.focus();
            win.print();
        });
    }

    function showPromoDetails(event, title, description, code) {
        event.preventDefault();
        document.getElementById('promoModalTitle').textContent = title;
        let bodyHtml = `<p class="text-slate-600 mb-4">${description}</p>`;
        if (code && code !== 'null') {
            bodyHtml += `<div class="bg-blue-50 border border-blue-200 rounded p-3 text-center">
                            <p class="text-sm text-slate-600 mb-1">Gunakan Kode Promo:</p>
                            <p class="text-lg font-bold text-blue-800 tracking-widest">${code}</p>
                         </div>`;
        }
        document.getElementById('promoModalBody').innerHTML = bodyHtml;
        document.getElementById('promoDetailModal').classList.remove('hidden');
        document.getElementById('promoDetailModal').classList.add('flex');
    }

    function closePromoDetailModal() {
        document.getElementById('promoDetailModal').classList.add('hidden');
    }

    // Initialize Lucide icons
    lucide.createIcons();
</script>

<?php include '../includes/footer.php'; ?>
