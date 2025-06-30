<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
// Inisialisasi session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Start output buffering
ob_start();

// Set headers
header('Content-Type: text/html; charset=utf-8');

try {
    // Check user role and authentication
    checkRole(['Customer']);

    // Validate transaction ID
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID Transaksi tidak valid.');
    }

    $id_transaction = (int)$_GET['id'];
    if ($id_transaction <= 0) {
        throw new Exception('ID Transaksi tidak valid.');
    }

    // Get user data
    $user_id = $_SESSION['user_id'];
    $customer = getCustomerData($user_id);
    
    if (!$customer) {
        throw new Exception('Data pelanggan tidak ditemukan.');
    }

    $id_customer = $customer['id_customer'];

    // Check database connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // 1. Get Transaction and Customer Data (with ownership validation)
    $query_trx = "SELECT t.*, u.nama_lengkap, u.email, u.no_hp
                FROM Transaction t
                JOIN Customer c ON t.id_customer = c.id_customer
                JOIN User u ON c.id_user = u.id_user
                WHERE t.id_transaction = ? AND t.id_customer = ?";
    $stmt_trx = $conn->prepare($query_trx);
    
    if (!$stmt_trx) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt_trx->bind_param("ii", $id_transaction, $id_customer);
    $stmt_trx->execute();
    $transaction = $stmt_trx->get_result()->fetch_assoc();

    if (!$transaction) {
        http_response_code(404);
        throw new Exception('Invoice tidak ditemukan atau Anda tidak memiliki akses.');
    }

    // 2. Get Business Profile
    $business = $conn->query("SELECT * FROM BusinessProfile WHERE id = 1")->fetch_assoc();
    if (!$business) {
        $business = [
            'nama_bisnis' => 'CRISP FORCE',
            'alamat' => 'Alamat Bisnis Anda',
            'telepon' => '-',
            'email' => '-',
            'website' => '',
            'jam_operasional' => ''
        ];
    }

    // Clear any previous output
    ob_clean();
?>
<div class="max-w-4xl mx-auto bg-white">
    <!-- Invoice Header -->
    <div class="border-b border-slate-200 pb-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start">
            <div class="mb-4 md:mb-0">
                <div class="flex items-center mb-4">
                    <i data-lucide="zap" class="w-8 h-8 text-blue-600 mr-2"></i>
                    <h2 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($business['nama_bisnis']) ?></h2>
                </div>
                <div class="text-slate-600">
                    <p><?= nl2br(htmlspecialchars($business['alamat'])) ?></p>
                    <p>Telp: <?= htmlspecialchars($business['telepon']) ?></p>
                    <p>Email: <?= htmlspecialchars($business['email']) ?></p>
                </div>
            </div>
            <div class="text-right">
                <h1 class="text-3xl font-bold text-slate-800 mb-2">INVOICE</h1>
                <div class="bg-slate-100 p-4 rounded-lg">
                    <p class="text-sm text-slate-600">No. Invoice</p>
                    <p class="font-bold text-lg">TRX-<?= str_pad($transaction['id_transaction'], 4, '0', STR_PAD_LEFT) ?></p>
                    <p class="text-sm text-slate-600 mt-2">Tanggal</p>
                    <p class="font-semibold"><?= formatDate($transaction['tanggal_transaksi']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="mb-8">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Kepada Yth:</h3>
        <div class="bg-slate-50 p-4 rounded-lg">
            <p class="font-bold text-slate-800"><?= htmlspecialchars($transaction['nama_lengkap']) ?></p>
            <p class="text-slate-600"><?= htmlspecialchars($transaction['email']) ?></p>
            <p class="text-slate-600"><?= htmlspecialchars($transaction['no_hp']) ?></p>
        </div>
    </div>

    <!-- Items Table -->
    <div class="mb-8">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Detail Transaksi</h3>
        <div class="overflow-x-auto">
            <table class="w-full border border-slate-200 rounded-lg overflow-hidden">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Item</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Qty</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Harga Satuan</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php
                    if ($transaction['jenis_transaksi'] == 'barang') {
                        $items_stmt = $conn->prepare("SELECT p.nama_product, tpd.qty, tpd.harga_satuan, tpd.subtotal 
                                                    FROM TransactionProductDetail tpd 
                                                    JOIN Product p ON tpd.id_product = p.id_product 
                                                    WHERE tpd.id_transaction = ?");
                        if ($items_stmt) {
                            $items_stmt->bind_param("i", $id_transaction);
                            $items_stmt->execute();
                            $items = $items_stmt->get_result();
                            while ($item = $items->fetch_assoc()) {
                                echo "<tr class='hover:bg-slate-50'>";
                                echo "<td class='px-4 py-3 text-slate-800'>" . htmlspecialchars($item['nama_product']) . "</td>";
                                echo "<td class='px-4 py-3 text-center text-slate-800'>{$item['qty']}</td>";
                                echo "<td class='px-4 py-3 text-right text-slate-800'>" . formatCurrency($item['harga_satuan']) . "</td>";
                                echo "<td class='px-4 py-3 text-right font-semibold text-slate-800'>" . formatCurrency($item['subtotal']) . "</td>";
                                echo "</tr>";
                            }
                        }
                    } else { // Service
                        $items_stmt = $conn->prepare("SELECT sr.nama_service, tsd.biaya_service, tsd.biaya_sparepart, tsd.subtotal 
                                                    FROM TransactionServiceDetail tsd 
                                                    JOIN ServiceRequest sr ON tsd.id_service = sr.id_service 
                                                    WHERE tsd.id_transaction = ?");
                        if ($items_stmt) {
                            $items_stmt->bind_param("i", $id_transaction);
                            $items_stmt->execute();
                            $item = $items_stmt->get_result()->fetch_assoc();
                            if ($item) {
                                if ($item['biaya_service'] > 0) {
                                    echo "<tr class='hover:bg-slate-50'>";
                                    echo "<td class='px-4 py-3 text-slate-800'>Jasa " . htmlspecialchars($item['nama_service']) . "</td>";
                                    echo "<td class='px-4 py-3 text-center text-slate-800'>1</td>";
                                    echo "<td class='px-4 py-3 text-right text-slate-800'>" . formatCurrency($item['biaya_service']) . "</td>";
                                    echo "<td class='px-4 py-3 text-right font-semibold text-slate-800'>" . formatCurrency($item['biaya_service']) . "</td>";
                                    echo "</tr>";
                                }
                                if ($item['biaya_sparepart'] > 0) {
                                    echo "<tr class='hover:bg-slate-50'>";
                                    echo "<td class='px-4 py-3 text-slate-800'>Total Biaya Sparepart</td>";
                                    echo "<td class='px-4 py-3 text-center text-slate-800'>1</td>";
                                    echo "<td class='px-4 py-3 text-right text-slate-800'>" . formatCurrency($item['biaya_sparepart']) . "</td>";
                                    echo "<td class='px-4 py-3 text-right font-semibold text-slate-800'>" . formatCurrency($item['biaya_sparepart']) . "</td>";
                                    echo "</tr>";
                                }
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="flex justify-end mb-8">
        <div class="w-full max-w-sm">
            <div class="bg-slate-50 p-6 rounded-lg">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Ringkasan Pembayaran</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Subtotal</span>
                        <span class="font-semibold"><?= formatCurrency($transaction['total_harga'] - $transaction['ppn_pajak'] + $transaction['discount']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Diskon</span>
                        <span class="font-semibold text-green-600">- <?= formatCurrency($transaction['discount']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Pajak</span>
                        <span class="font-semibold"><?= formatCurrency($transaction['ppn_pajak']) ?></span>
                    </div>
                    <div class="border-t border-slate-300 pt-3">
                        <div class="flex justify-between">
                            <span class="text-lg font-bold text-slate-800">Grand Total</span>
                            <span class="text-lg font-bold text-blue-600"><?= formatCurrency($transaction['total_harga']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-slate-50 p-4 rounded-lg">
            <h4 class="font-bold text-slate-800 mb-2">Metode Pembayaran</h4>
            <p class="text-slate-600 capitalize"><?= htmlspecialchars($transaction['metode_bayar']) ?></p>
        </div>
        <div class="bg-slate-50 p-4 rounded-lg">
            <h4 class="font-bold text-slate-800 mb-2">Status Pembayaran</h4>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $transaction['status_pembayaran'] == 'lunas' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                <span class="w-1.5 h-1.5 mr-1.5 rounded-full <?= $transaction['status_pembayaran'] == 'lunas' ? 'bg-green-400' : 'bg-yellow-400' ?>"></span>
                <?= $transaction['status_pembayaran'] == 'lunas' ? 'Lunas' : 'Belum Bayar' ?>
            </span>
        </div>
    </div>

    <!-- Footer -->
    <div class="border-t border-slate-200 pt-6 text-center">
        <p class="text-slate-600 mb-2">Terima kasih telah bertransaksi dengan kami.</p>
        <p class="text-sm text-slate-500">Invoice ini dibuat secara otomatis dan sah tanpa tanda tangan.</p>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
</script>
<?php
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    http_response_code(500);
    error_log('Invoice Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
?>
<div class="bg-red-50 border-l-4 border-red-500 p-4 max-w-4xl mx-auto">
    <div class="flex">
        <div class="flex-shrink-0">
            <i data-lucide="alert-triangle" class="h-5 w-5 text-red-500"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Terjadi Kesalahan</h3>
            <div class="mt-2 text-sm text-red-700">
                <p><?= htmlspecialchars($e->getMessage()) ?></p>
            </div>
            <div class="mt-4">
                <a href="transactions.php" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none">
                    Kembali ke Daftar Transaksi
                </a>
            </div>
        </div>
    </div>
</div>
<?php
}

// Flush the output buffer
ob_end_flush();
?>