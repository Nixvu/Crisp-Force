<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {

    $conn->begin_transaction();
    try {
        $id_sales = $_SESSION['user_id'];
        $tanggal_transaksi = date('Y-m-d');
        $metode_bayar = sanitize($_POST['metode_bayar']);
        $status_pembayaran = sanitize($_POST['status_pembayaran']);
        $id_customer = null;
        $guest_name = null;

        $jenis_transaksi = isset($_POST['jenis_transaksi_produk']) ? 'barang' : 'service';

        if ($jenis_transaksi == 'barang') {
            // Logic for Product Transaction
            $customer_input = sanitize($_POST['customer_produk']);
            if (strpos($customer_input, '-') !== false) {
                $id_customer = (int)explode('-', $customer_input)[0];
            } else {
                $guest_name = $customer_input;
            }

            $total_harga = 0;
            $products_to_insert = [];

            foreach ($_POST['products'] as $p) {
                if (empty($p['id'])) continue;
                $id_product = (int)$p['id'];
                $qty = (int)$p['qty'];

                $prod_res = $conn->query("SELECT harga FROM Product WHERE id_product = $id_product");
                $product_data = $prod_res->fetch_assoc();
                $harga_satuan = $product_data['harga'];
                $subtotal = $harga_satuan * $qty;
                $total_harga += $subtotal;

                $products_to_insert[] = [
                    'id' => $id_product,
                    'qty' => $qty,
                    'harga' => $harga_satuan,
                    'subtotal' => $subtotal
                ];
            }

            // 1. Insert ke Transaction
            $stmt_trx = $conn->prepare("INSERT INTO Transaction (id_customer, guest_name, id_sales, jenis_transaksi, tanggal_transaksi, total_harga, status_pembayaran, metode_bayar, metode_pengambilan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ambil_sendiri')");
            $stmt_trx->bind_param("isisdsss", $id_customer, $guest_name, $id_sales, $jenis_transaksi, $tanggal_transaksi, $total_harga, $status_pembayaran, $metode_bayar);
            $stmt_trx->execute();
            $id_transaction = $stmt_trx->insert_id;

            // 2. Insert ke TransactionProductDetail & Update Stok
            $stmt_detail = $conn->prepare("INSERT INTO TransactionProductDetail (id_transaction, id_product, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE Product SET stok = stok - ? WHERE id_product = ?");
            foreach ($products_to_insert as $prod) {
                $stmt_detail->bind_param("iiidd", $id_transaction, $prod['id'], $prod['qty'], $prod['harga'], $prod['subtotal']);
                $stmt_detail->execute();

                $stmt_stock->bind_param("ii", $prod['qty'], $prod['id']);
                $stmt_stock->execute();
            }
        } else {
            // Logic for Service Transaction
            $customer_input = sanitize($_POST['customer_service']);
            if (strpos($customer_input, '-') !== false) {
                $id_customer = (int)explode('-', $customer_input)[0];
            } else {
                $guest_name = $customer_input;
            }
            $nama_service = sanitize($_POST['nama_service']);
            $deskripsi_kerusakan = sanitize($_POST['deskripsi_kerusakan']);
            $id_teknisi = (int)$_POST['id_teknisi'];
            $biaya_service = (float)$_POST['biaya_service'];
            $biaya_sparepart = (float)$_POST['biaya_sparepart'];
            $total_harga = $biaya_service + $biaya_sparepart;

            // 1. Insert ke ServiceRequest
            $kode_service = 'SRV' . date('YmdHis') . rand(100, 999); // Simple generator
            $stmt_sr = $conn->prepare("INSERT INTO ServiceRequest (id_customer, id_sales, id_teknisi, kode_service, nama_service, deskripsi_kerusakan, tanggal_masuk, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'diterima')");
            $stmt_sr->bind_param("iiissss", $id_customer, $id_sales, $id_teknisi, $kode_service, $nama_service, $deskripsi_kerusakan, $tanggal_transaksi);
            $stmt_sr->execute();
            $id_service = $stmt_sr->insert_id;

            // 2. Insert ke Transaction
            $stmt_trx = $conn->prepare("INSERT INTO Transaction (id_customer, guest_name, id_sales, jenis_transaksi, tanggal_transaksi, total_harga, status_pembayaran, metode_bayar, metode_pengambilan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ambil_sendiri')");
            $stmt_trx->bind_param("isisdsss", $id_customer, $guest_name, $id_sales, $jenis_transaksi, $tanggal_transaksi, $total_harga, $status_pembayaran, $metode_bayar);
            $stmt_trx->execute();
            $id_transaction = $stmt_trx->insert_id;

            // 3. Insert ke TransactionServiceDetail
            $stmt_detail = $conn->prepare("INSERT INTO TransactionServiceDetail (id_transaction, id_service, biaya_service, biaya_sparepart, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_detail->bind_param("iidds", $id_transaction, $id_service, $biaya_service, $biaya_sparepart, $total_harga);
            $stmt_detail->execute();
        }

        $conn->commit();
        $_SESSION['success_message'] = "Transaksi baru berhasil ditambahkan!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Gagal membuat transaksi: " . $e->getMessage();
    }

    header("Location: ../sales/transaksi.php");
    exit();
}


if ($action == 'delete' && isset($_GET['id'])) {
    $id_transaction = (int)$_GET['id'];

    $stmt = $conn->prepare("UPDATE Transaction SET deleted_at = NOW() WHERE id_transaction = ?");
    $stmt->bind_param("i", $id_transaction);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Transaksi berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus transaksi.";
    }
    header("Location: ../sales/transaksi.php");
    exit();
}
