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

<div class="invoice-header mb-4">
    <div class="row">
        <div class="col-md-6">
            <h4><?= htmlspecialchars($business['nama_bisnis'] ?? 'Nama Bisnis') ?></h4>
            <p class="mb-0"><?= nl2br(htmlspecialchars($business['alamat'] ?? 'Alamat Bisnis')) ?></p>
            <p class="mb-0">Telp: <?= htmlspecialchars($business['telepon'] ?? '-') ?></p>
        </div>
        <div class="col-md-6 text-end">
            <h4>INVOICE</h4>
            <p class="mb-0"><strong>No. Invoice:</strong> TRX-<?= str_pad($transaction['id_transaction'], 4, '0', STR_PAD_LEFT) ?></p>
            <p class="mb-0"><strong>Tanggal:</strong> <?= formatDate($transaction['tanggal_transaksi']) ?></p>
        </div>
    </div>
</div>
<hr>
<h5>Kepada Yth:</h5>
<p class="mb-0"><strong><?= htmlspecialchars($transaction['nama_lengkap'] ?? $transaction['guest_name']) ?></strong></p>
<p class="mb-0"><?= htmlspecialchars($transaction['email'] ?? '-') ?></p>
<p class="mb-0"><?= htmlspecialchars($transaction['no_hp'] ?? '-') ?></p>
<hr>
<div class="table-responsive mb-4">
    <table class="table table-bordered">
        <thead><tr><th>Item</th><th>Qty</th><th>Harga Satuan</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php
            $item_html = '';
            if ($transaction['jenis_transaksi'] == 'barang') {
                $items_stmt = $conn->prepare("SELECT p.nama_product, tpd.qty, tpd.harga_satuan, tpd.subtotal FROM TransactionProductDetail tpd JOIN Product p ON tpd.id_product = p.id_product WHERE tpd.id_transaction = ?");
                $items_stmt->bind_param("i", $id_transaction);
                $items_stmt->execute();
                $items = $items_stmt->get_result();
                while ($item = $items->fetch_assoc()) {
                    $item_html .= "<tr><td>" . htmlspecialchars($item['nama_product']) . "</td><td>{$item['qty']}</td><td>" . formatCurrency($item['harga_satuan']) . "</td><td>" . formatCurrency($item['subtotal']) . "</td></tr>";
                }
            } else { // Service
                $items_stmt = $conn->prepare("SELECT sr.nama_service, tsd.biaya_service, tsd.biaya_sparepart, tsd.subtotal FROM TransactionServiceDetail tsd JOIN ServiceRequest sr ON tsd.id_service = sr.id_service WHERE tsd.id_transaction = ?");
                $items_stmt->bind_param("i", $id_transaction);
                $items_stmt->execute();
                $item = $items_stmt->get_result()->fetch_assoc();
                if($item['biaya_service'] > 0) $item_html .= "<tr><td>Jasa " . htmlspecialchars($item['nama_service']) . "</td><td>1</td><td>" . formatCurrency($item['biaya_service']) . "</td><td>" . formatCurrency($item['biaya_service']) . "</td></tr>";
                if($item['biaya_sparepart'] > 0) $item_html .= "<tr><td>Total Biaya Sparepart</td><td>1</td><td>" . formatCurrency($item['biaya_sparepart']) . "</td><td>" . formatCurrency($item['biaya_sparepart']) . "</td></tr>";
            }
            echo $item_html;
            ?>
        </tbody>
    </table>
</div>
<div class="row justify-content-end">
    <div class="col-md-5">
        <table class="table">
            <tr><th>Subtotal</th><td class="text-end"><?= formatCurrency($transaction['total_harga'] - $transaction['ppn_pajak'] + $transaction['discount']) ?></td></tr>
            <tr><th>Diskon</th><td class="text-end">- <?= formatCurrency($transaction['discount']) ?></td></tr>
            <tr><th>Pajak</th><td class="text-end"><?= formatCurrency($transaction['ppn_pajak']) ?></td></tr>
            <tr class="fw-bold"><th>Grand Total</th><td class="text-end"><?= formatCurrency($transaction['total_harga']) ?></td></tr>
        </table>
    </div>
</div>