<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is a customer
if (!isLoggedIn() || $_SESSION['user_role'] !== 'Customer') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $customer = getCustomerData($user_id);

    if (!$customer) {
        throw new Exception('Data customer tidak ditemukan');
    }

    $id_customer = $customer['id_customer'];

    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $payment_method = sanitize($_POST['payment_method']);
    $delivery_method = sanitize($_POST['delivery_method']);
    $shipping_address = sanitize($_POST['shipping_address'] ?? '');

    // Validate input
    if ($product_id <= 0 || $quantity <= 0) {
        throw new Exception('Data produk atau jumlah tidak valid');
    }

    if (!in_array($payment_method, ['transfer', 'tunai'])) {
        throw new Exception('Metode pembayaran tidak valid');
    }

    if (!in_array($delivery_method, ['ambil_sendiri', 'diantar'])) {
        throw new Exception('Metode pengambilan tidak valid');
    }

    if ($delivery_method === 'diantar' && empty($shipping_address)) {
        throw new Exception('Alamat pengiriman harus diisi untuk metode pengantaran');
    }

    // Get product details
    $sql_product = "SELECT * FROM Product WHERE id_product = ? AND deleted_at IS NULL";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $product = $stmt_product->get_result()->fetch_assoc();

    if (!$product) {
        throw new Exception('Produk tidak ditemukan');
    }

    if ($product['stok'] < $quantity) {
        throw new Exception('Stok produk tidak mencukupi');
    }

    // Calculate totals
    $subtotal = $product['harga'] * $quantity;
    $discount = 0; // Could be calculated based on campaigns
    $tax = $subtotal * 0.1; // 10% tax
    $total = $subtotal - $discount + $tax;

    // Get a random sales user (or set to null if no sales user exists)
    $sql_sales = "SELECT id_user FROM User WHERE role = 'Sales' ORDER BY RAND() LIMIT 1";
    $sales_result = $conn->query($sql_sales);
    $sales_user = $sales_result->fetch_assoc();
    $id_sales = $sales_user ? $sales_user['id_user'] : null;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert transaction
        $sql_transaction = "INSERT INTO Transaction (id_customer, id_sales, jenis_transaksi, tanggal_transaksi, total_harga, discount, ppn_pajak, metode_bayar, status_pembayaran, metode_pengambilan) 
                           VALUES (?, ?, 'barang', CURDATE(), ?, ?, ?, ?, 'belum_bayar', ?)";
        $stmt_transaction = $conn->prepare($sql_transaction);

        if (!$stmt_transaction) {
            throw new Exception('Gagal menyiapkan query transaksi: ' . $conn->error);
        }

        $stmt_transaction->bind_param("iidddss", $id_customer, $id_sales, $total, $discount, $tax, $payment_method, $delivery_method);

        if (!$stmt_transaction->execute()) {
            throw new Exception('Gagal menyimpan transaksi: ' . $stmt_transaction->error);
        }

        $transaction_id = $conn->insert_id;

        // Insert transaction product detail
        $sql_detail = "INSERT INTO TransactionProductDetail (id_transaction, id_product, qty, harga_satuan, subtotal) 
                      VALUES (?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);

        if (!$stmt_detail) {
            throw new Exception('Gagal menyiapkan query detail transaksi: ' . $conn->error);
        }

        $stmt_detail->bind_param("iiidd", $transaction_id, $product_id, $quantity, $product['harga'], $subtotal);

        if (!$stmt_detail->execute()) {
            throw new Exception('Gagal menyimpan detail transaksi: ' . $stmt_detail->error);
        }

        // Update product stock
        $sql_update_stock = "UPDATE Product SET stok = stok - ? WHERE id_product = ?";
        $stmt_update_stock = $conn->prepare($sql_update_stock);

        if (!$stmt_update_stock) {
            throw new Exception('Gagal menyiapkan query update stok: ' . $conn->error);
        }

        $stmt_update_stock->bind_param("ii", $quantity, $product_id);

        if (!$stmt_update_stock->execute()) {
            throw new Exception('Gagal mengupdate stok produk: ' . $stmt_update_stock->error);
        }

        // Update customer statistics
        $sql_update_customer = "UPDATE Customer SET 
                               total_transaksi = total_transaksi + 1,
                               total_pengeluaran = total_pengeluaran + ?
                               WHERE id_customer = ?";
        $stmt_update_customer = $conn->prepare($sql_update_customer);

        if (!$stmt_update_customer) {
            throw new Exception("Gagal menyiapkan query update customer: " . $conn->error);
        }

        $stmt_update_customer->bind_param("di", $total, $id_customer);

        if (!$stmt_update_customer->execute()) {
            throw new Exception('Gagal mengupdate data customer: ' . $stmt_update_customer->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Pembelian berhasil diproses! ID Transaksi: TRX-' . str_pad($transaction_id, 6, '0', STR_PAD_LEFT),
            'transaction_id' => $transaction_id
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
