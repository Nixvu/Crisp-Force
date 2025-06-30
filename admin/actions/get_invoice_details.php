<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

if (!isset($_GET['id'])) {
    die('ID Transaksi tidak ditemukan.');
}

$id_transaction = (int)$_GET['id'];

// 1. Get Transaction and Customer/Guest Data
$query_trx = "SELECT t.*, u.nama_lengkap, u.email, u.no_hp
              FROM Transaction t
              LEFT JOIN Customer c ON t.id_customer = c.id_customer
              LEFT JOIN User u ON c.id_user = u.id_user
              WHERE t.id_transaction = ?";
$stmt_trx = $conn->prepare($query_trx);
$stmt_trx->bind_param("i", $id_transaction);
$stmt_trx->execute();
$transaction = $stmt_trx->get_result()->fetch_assoc();

if (!$transaction) {
    die('Transaksi tidak ditemukan.');
}

// 2. Get Business Profile
$business = $conn->query("SELECT * FROM BusinessProfile WHERE id = 1")->fetch_assoc();
?>

<div class="bg-white p-6 rounded-lg shadow-sm">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 pb-6 border-b border-slate-200">
        <div class="mb-4 md:mb-0">
            <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($business['nama_bisnis'] ?? 'Nama Bisnis') ?></h2>
            <p class="text-sm text-slate-600"><?= nl2br(htmlspecialchars($business['alamat'] ?? 'Alamat Bisnis')) ?></p>
            <p class="text-sm text-slate-600">Telp: <?= htmlspecialchars($business['telepon'] ?? '-') ?></p>
        </div>
        <div class="text-right">
            <h3 class="text-lg font-bold text-slate-800">INVOICE</h3>
            <p class="text-sm text-slate-600"><span class="font-medium">No. Invoice:</span> TRX-<?= str_pad($transaction['id_transaction'], 4, '0', STR_PAD_LEFT) ?></p>
            <p class="text-sm text-slate-600"><span class="font-medium">Tanggal:</span> <?= formatDate($transaction['tanggal_transaksi']) ?></p>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="mb-6 pb-6 border-b border-slate-200">
        <h4 class="text-sm font-semibold text-slate-700 mb-2">Kepada Yth:</h4>
        <p class="text-sm text-slate-800 font-medium"><?= htmlspecialchars($transaction['nama_lengkap'] ?? $transaction['guest_name']) ?></p>
        <p class="text-sm text-slate-600"><?= htmlspecialchars($transaction['email'] ?? '-') ?></p>
        <p class="text-sm text-slate-600"><?= htmlspecialchars($transaction['no_hp'] ?? '-') ?></p>
    </div>

    <!-- Items Table -->
    <div class="mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Item</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Qty</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Harga Satuan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php
                    $item_html = '';
                    if ($transaction['jenis_transaksi'] == 'barang') {
                        $items_stmt = $conn->prepare("SELECT p.nama_product, tpd.qty, tpd.harga_satuan, tpd.subtotal FROM TransactionProductDetail tpd JOIN Product p ON tpd.id_product = p.id_product WHERE tpd.id_transaction = ?");
                        $items_stmt->bind_param("i", $id_transaction);
                        $items_stmt->execute();
                        $items = $items_stmt->get_result();
                        while ($item = $items->fetch_assoc()) {
                            $item_html .= "<tr>
                                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-900\">" . htmlspecialchars($item['nama_product']) . "</td>
                                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">{$item['qty']}</td>
                                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">" . formatCurrency($item['harga_satuan']) . "</td>
                                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">" . formatCurrency($item['subtotal']) . "</td>
                            </tr>";
                        }
                    } else { // Service
                        $items_stmt = $conn->prepare("SELECT sr.nama_service, tsd.biaya_service, tsd.biaya_sparepart, tsd.subtotal FROM TransactionServiceDetail tsd JOIN ServiceRequest sr ON tsd.id_service = sr.id_service WHERE tsd.id_transaction = ?");
                        $items_stmt->bind_param("i", $id_transaction);
                        $items_stmt->execute();
                        $item = $items_stmt->get_result()->fetch_assoc();
                        if($item['biaya_service'] > 0) $item_html .= "<tr>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-900\">Jasa " . htmlspecialchars($item['nama_service']) . "</td>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">1</td>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">" . formatCurrency($item['biaya_service']) . "</td>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">" . formatCurrency($item['biaya_service']) . "</td>
                        </tr>";
                        if($item['biaya_sparepart'] > 0) $item_html .= "<tr>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-900\">Total Biaya Sparepart</td>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">1</td>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">" . formatCurrency($item['biaya_sparepart']) . "</td>
                            <td class=\"px-6 py-4 whitespace-nowrap text-sm text-slate-500\">" . formatCurrency($item['biaya_sparepart']) . "</td>
                        </tr>";
                    }
                    echo $item_html;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary -->
    <div class="flex justify-end">
        <div class="w-full md:w-1/3">
            <table class="min-w-full divide-y divide-slate-200">
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-slate-700">Subtotal</td>
                    <td class="px-4 py-2 text-sm text-slate-500 text-right"><?= formatCurrency($transaction['total_harga'] - $transaction['ppn_pajak'] + $transaction['discount']) ?></td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-slate-700">Diskon</td>
                    <td class="px-4 py-2 text-sm text-slate-500 text-right">- <?= formatCurrency($transaction['discount']) ?></td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-slate-700">Pajak</td>
                    <td class="px-4 py-2 text-sm text-slate-500 text-right"><?= formatCurrency($transaction['ppn_pajak']) ?></td>
                </tr>
                <tr class="border-t border-slate-200">
                    <td class="px-4 py-2 text-sm font-bold text-slate-800">Grand Total</td>
                    <td class="px-4 py-2 text-sm font-bold text-slate-800 text-right"><?= formatCurrency($transaction['total_harga']) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Payment Info -->
    <div class="mt-8 pt-6 border-t border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Metode Pembayaran</h4>
                <p class="text-sm text-slate-600"><?= ucfirst($transaction['metode_bayar']) ?></p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Status Pembayaran</h4>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $transaction['status_pembayaran'] == 'lunas' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                    <?= ucfirst($transaction['status_pembayaran']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-12 pt-6 border-t border-slate-200 text-center text-xs text-slate-500">
        <p>Terima kasih telah berbelanja di <?= htmlspecialchars($business['nama_bisnis'] ?? 'kami') ?></p>
        <p class="mt-1">Invoice ini sah dan diproses oleh sistem</p>
    </div>
</div>