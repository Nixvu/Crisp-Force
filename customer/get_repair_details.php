<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Authenticate and authorize customer
checkRole(['Customer']);

// Validate service ID from GET request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('ID Service tidak valid.');
}
$id_service = (int)$_GET['id'];

// Get customer data from session
$user_id = $_SESSION['user_id'];
$customer = getCustomerData($user_id);
if (!$customer) {
    http_response_code(403);
    die('Data pelanggan tidak ditemukan.');
}
$id_customer = $customer['id_customer'];

// 1. Get Basic Service Details with Ownership Check
$query_sr = "SELECT sr.*, tech.nama_lengkap as technician_name
             FROM ServiceRequest sr
             LEFT JOIN User tech ON sr.id_teknisi = tech.id_user
             WHERE sr.id_service = ? AND sr.id_customer = ?";
$stmt_sr = $conn->prepare($query_sr);
$stmt_sr->bind_param("ii", $id_service, $id_customer);
$stmt_sr->execute();
$service = $stmt_sr->get_result()->fetch_assoc();

if (!$service) {
    http_response_code(404);
    die('Data perbaikan tidak ditemukan atau Anda tidak memiliki akses.');
}

// 2. Get Progress History
$query_progress = "SELECT * FROM ServiceProgress WHERE id_service = ? ORDER BY created_at ASC";
$stmt_progress = $conn->prepare($query_progress);
$stmt_progress->bind_param("i", $id_service);
$stmt_progress->execute();
$progress_history = $stmt_progress->get_result();

// 3. Get Spareparts Used
$query_spareparts = "SELECT p.nama_product, ss.jumlah, ss.harga_saat_pemasangan
                     FROM ServiceSparepart ss
                     JOIN Product p ON ss.id_product = p.id_product
                     WHERE ss.id_service = ?";
$stmt_spareparts = $conn->prepare($query_spareparts);
$stmt_spareparts->bind_param("i", $id_service);
$stmt_spareparts->execute();
$spareparts_used = $stmt_spareparts->get_result();

// 4. Get Transaction Details
$query_trx = "SELECT t.*, tsd.biaya_service, tsd.biaya_sparepart
              FROM Transaction t
              JOIN TransactionServiceDetail tsd ON t.id_transaction = tsd.id_transaction
              WHERE tsd.id_service = ?";
$stmt_trx = $conn->prepare($query_trx);
$stmt_trx->bind_param("i", $id_service);
$stmt_trx->execute();
$transaction = $stmt_trx->get_result()->fetch_assoc();

?>

<div class="space-y-6">
    <!-- Service Header -->
    <div>
        <h4 class="text-xl font-bold text-slate-800">Detail Perbaikan #<?= htmlspecialchars($service['kode_service']) ?></h4>
        <p class="text-sm text-slate-500">Tanggal Masuk: <?= formatDate($service['tanggal_masuk']) ?></p>
    </div>

    <!-- Grid Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Device Info -->
        <div class="bg-slate-50 p-4 rounded-lg space-y-3">
            <h5 class="font-semibold text-slate-700 border-b pb-2 mb-2">Informasi Perangkat</h5>
            <div class="text-sm">
                <strong class="block text-slate-600">Perangkat:</strong>
                <span><?= htmlspecialchars($service['nama_service']) ?> (<?= htmlspecialchars($service['merk']) ?> <?= htmlspecialchars($service['model']) ?>)</span>
            </div>
            <div class="text-sm">
                <strong class="block text-slate-600">No. Seri:</strong>
                <span><?= htmlspecialchars($service['serial_no'] ?: '-') ?></span>
            </div>
            <div class="text-sm">
                <strong class="block text-slate-600">Kelengkapan:</strong>
                <span><?= htmlspecialchars($service['kelengkapan'] ?: '-') ?></span>
            </div>
            <div class="text-sm">
                <strong class="block text-slate-600">Deskripsi Kerusakan:</strong>
                <p class="whitespace-pre-wrap"><?= htmlspecialchars($service['deskripsi_kerusakan']) ?></p>
            </div>
        </div>

        <!-- Financial & Technician Info -->
        <div class="bg-slate-50 p-4 rounded-lg space-y-3">
            <h5 class="font-semibold text-slate-700 border-b pb-2 mb-2">Informasi Biaya & Teknisi</h5>
            <div class="text-sm">
                <strong class="block text-slate-600">Teknisi:</strong>
                <span><?= htmlspecialchars($service['technician_name'] ?: 'Belum ditetapkan') ?></span>
            </div>
            <?php if ($transaction): ?>
                <div class="text-sm">
                    <strong class="block text-slate-600">ID Transaksi:</strong>
                    <button onclick="viewInvoice(<?= $transaction['id_transaction'] ?>)" class="text-blue-600 hover:underline">TRX-<?= str_pad($transaction['id_transaction'], 4, '0', STR_PAD_LEFT) ?></button>
                </div>
                <div class="text-sm">
                    <strong class="block text-slate-600">Biaya Jasa:</strong>
                    <span><?= formatCurrency($transaction['biaya_service']) ?></span>
                </div>
                <div class="text-sm">
                    <strong class="block text-slate-600">Biaya Sparepart:</strong>
                    <span><?= formatCurrency($transaction['biaya_sparepart']) ?></span>
                </div>
                <div class="text-sm font-bold">
                    <strong class="block text-slate-600">Total Biaya:</strong>
                    <span><?= formatCurrency($transaction['total_harga']) ?></span>
                </div>
            <?php else: ?>
                <div class="text-sm text-slate-500 italic">Belum ada transaksi yang dibuat untuk perbaikan ini.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress History -->
    <div>
        <h5 class="font-semibold text-slate-700 mb-3">Riwayat Progres</h5>
        <div class="border-l-2 border-slate-200 ml-2 pl-4 space-y-4">
            <?php if ($progress_history->num_rows > 0): ?>
                <?php while ($p = $progress_history->fetch_assoc()): ?>
                    <div class="relative">
                        <div class="absolute -left-[23px] top-1.5 w-3 h-3 bg-blue-500 rounded-full border-2 border-white"></div>
                        <p class="text-xs text-slate-500"><?= formatDate($p['created_at']) ?></p>
                        <p class="font-semibold text-slate-800"><?= ucfirst(str_replace('_', ' ', $p['status'])) ?></p>
                        <?php if (!empty($p['catatan'])): ?>
                            <p class="text-sm text-slate-600 bg-white p-2 rounded-md mt-1">Catatan: <?= htmlspecialchars($p['catatan']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-sm text-slate-500 italic">Belum ada progres yang tercatat.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Spareparts Used -->
    <?php if ($spareparts_used->num_rows > 0): ?>
        <div>
            <h5 class="font-semibold text-slate-700 mb-3">Sparepart yang Digunakan</h5>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm bg-white rounded-lg">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama Sparepart</th>
                            <th class="px-4 py-2 text-center font-medium">Jumlah</th>
                            <th class="px-4 py-2 text-right font-medium">Harga Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sp = $spareparts_used->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2"><?= htmlspecialchars($sp['nama_product']) ?></td>
                                <td class="px-4 py-2 text-center"><?= $sp['jumlah'] ?></td>
                                <td class="px-4 py-2 text-right"><?= formatCurrency($sp['harga_saat_pemasangan']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
    // Re-initialize Lucide icons if they are used in the loaded content
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>