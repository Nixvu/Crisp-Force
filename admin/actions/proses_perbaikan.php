<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

// ACTION: Create New Repair
if ($action == 'create_repair' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = sanitize($_POST['customer_name']);
    $phone = sanitize($_POST['phone']);
    $device_name = sanitize($_POST['device_name']);
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $serial_no = sanitize($_POST['serial_no'] ?? '');
    $device_category = sanitize($_POST['device_category']);
    $issue_description = sanitize($_POST['issue_description']);
    $accessories = sanitize($_POST['accessories'] ?? '');
    $entry_date = sanitize($_POST['entry_date']);
    $estimated_date = sanitize($_POST['estimated_date'] ?? null);
    $id_sales = $_SESSION['user_id']; // Sales yang membuat permintaan

    // Validasi input
    if (empty($customer_name) || empty($phone) || empty($device_name) || empty($device_category) || empty($issue_description) || empty($entry_date)) {
        $_SESSION['error_message'] = "Semua field wajib diisi kecuali yang opsional.";
        header("Location: ../sales/perbaikan.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        // 1. Cari atau buat customer baru
        $customer_id = null;
        $customer_query = $conn->prepare("SELECT id_customer FROM Customer c JOIN User u ON c.id_user = u.id_user WHERE u.no_hp = ?");
        $customer_query->bind_param("s", $phone);
        $customer_query->execute();
        $result = $customer_query->get_result();
        
        if ($result->num_rows > 0) {
            $customer_id = $result->fetch_assoc()['id_customer'];
        } else {
            // Buat user baru untuk customer
            $conn->query("INSERT INTO User (nama_lengkap, email, no_hp, password, role) VALUES ('$customer_name', '', '$phone', '".password_hash('default123', PASSWORD_DEFAULT)."', 'Customer')");
            $user_id = $conn->insert_id;
            
            // Buat customer baru
            $conn->query("INSERT INTO Customer (id_user, segmentasi, origin) VALUES ($user_id, 'baru', 'manual_input')");
            $customer_id = $conn->insert_id;
        }

        // 2. Generate service code
        $service_code = '';
        $stmt_code = $conn->prepare("CALL generate_service_code(?)");
        $stmt_code->bind_param("s", $service_code);
        $stmt_code->execute();
        $stmt_code->close();

        // 3. Buat service request
        $stmt_service = $conn->prepare("INSERT INTO ServiceRequest (
            id_customer, id_sales, kode_service, nama_service, merk, model, 
            serial_no, kategori_barang, kelengkapan, deskripsi_kerusakan, 
            tanggal_masuk, tanggal_estimasi, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        $stmt_service->bind_param(
            "iissssssssss", 
            $customer_id, $id_sales, $service_code, $device_name, $brand, 
            $model, $serial_no, $device_category, $accessories, 
            $issue_description, $entry_date, $estimated_date
        );
        $stmt_service->execute();
        $service_id = $stmt_service->insert_id;

        $conn->commit();
        $_SESSION['success_message'] = "Permintaan perbaikan baru berhasil dibuat dengan ID: SRV-" . str_pad($service_id, 4, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error creating repair: " . $e->getMessage());
        $_SESSION['error_message'] = "Gagal membuat permintaan perbaikan: " . $e->getMessage();
    }
    header("Location: ../sales/perbaikan.php");
    exit();
}

// ACTION: Approve Service Request
if ($action == 'approve' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_service = (int)$_POST['id_service'];
    $id_teknisi = (int)$_POST['id_teknisi'];
    $tanggal_estimasi = sanitize($_POST['tanggal_estimasi']);

    $conn->begin_transaction();
    try {
        // Update service request
        $stmt_approve = $conn->prepare("UPDATE ServiceRequest 
            SET status = 'diterima', id_teknisi = ?, tanggal_estimasi = ?
            WHERE id_service = ? AND status = 'pending'");
        $stmt_approve->bind_param("isi", $id_teknisi, $tanggal_estimasi, $id_service);
        $stmt_approve->execute();

        // Add progress
        $stmt_progress = $conn->prepare("INSERT INTO ServiceProgress 
            (id_service, status, catatan) 
            VALUES (?, 'diterima_digerai', 'Permintaan perbaikan diterima dan teknisi ditetapkan.')");
        $stmt_progress->bind_param("i", $id_service);
        $stmt_progress->execute();

        $conn->commit();
        $_SESSION['success_message'] = "Permintaan perbaikan berhasil diterima dan teknisi ditetapkan.";
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error approving service: " . $e->getMessage());
        $_SESSION['error_message'] = "Gagal menerima permintaan: " . $e->getMessage();
    }
    header("Location: ../sales/perbaikan.php");
    exit();
}

// ACTION: Reject Service Request
if ($action == 'reject' && isset($_GET['id'])) {
    $id_service = (int)$_GET['id'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE ServiceRequest SET status = 'ditolak' WHERE id_service = ?");
        $stmt->bind_param("i", $id_service);
        $stmt->execute();
        
        $stmt_progress = $conn->prepare("INSERT INTO ServiceProgress 
            (id_service, status, catatan) 
            VALUES (?, 'ditolak', 'Permintaan perbaikan ditolak oleh admin.')");
        $stmt_progress->bind_param("i", $id_service);
        $stmt_progress->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Permintaan perbaikan telah ditolak.";
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error rejecting service: " . $e->getMessage());
        $_SESSION['error_message'] = "Gagal menolak permintaan: " . $e->getMessage();
    }
    header("Location: ../sales/perbaikan.php");
    exit();
}

// ACTION: Update Progress
if ($action == 'update_progress' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_service = (int)$_POST['id_service'];
    $new_status = sanitize($_POST['new_status']);
    $catatan = sanitize($_POST['catatan'] ?? '');
    $biaya_service_tambahan = !empty($_POST['biaya_service']) ? (float)$_POST['biaya_service'] : 0;
    $tanggal_estimasi = !empty($_POST['tanggal_estimasi']) ? sanitize($_POST['tanggal_estimasi']) : null;

    $conn->begin_transaction();
    try {
        // 1. Insert progress baru
        $stmt_progress = $conn->prepare("INSERT INTO ServiceProgress (id_service, status, catatan) VALUES (?, ?, ?)");
        $stmt_progress->bind_param("iss", $id_service, $new_status, $catatan);
        $stmt_progress->execute();

        // 2. Update tanggal estimasi jika diisi
        if ($tanggal_estimasi) {
            $stmt_estimasi = $conn->prepare("UPDATE ServiceRequest SET tanggal_estimasi = ? WHERE id_service = ?");
            $stmt_estimasi->bind_param("si", $tanggal_estimasi, $id_service);
            $stmt_estimasi->execute();
        }

        // 3. Proses sparepart jika ada
        $total_biaya_sparepart = 0;
        if (isset($_POST['spareparts'])) {
            $stmt_sparepart = $conn->prepare("INSERT INTO ServiceSparepart 
                (id_service, id_product, jumlah, harga_saat_pemasangan) 
                VALUES (?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE Product SET stok = stok - ? WHERE id_product = ?");

            foreach ($_POST['spareparts'] as $sp) {
                $id_product = (int)$sp['id'];
                $qty = (int)$sp['qty'];

                // Dapatkan harga produk
                $stmt_price = $conn->prepare("SELECT harga FROM Product WHERE id_product = ?");
                $stmt_price->bind_param("i", $id_product);
                $stmt_price->execute();
                $prod_res = $stmt_price->get_result()->fetch_assoc();
                $harga_satuan = $prod_res['harga'];
                $total_biaya_sparepart += $harga_satuan * $qty;

                // Tambahkan sparepart yang digunakan
                $stmt_sparepart->bind_param("iiid", $id_service, $id_product, $qty, $harga_satuan);
                $stmt_sparepart->execute();

                // Update stok
                $stmt_stock->bind_param("ii", $qty, $id_product);
                $stmt_stock->execute();
            }
        }

        // 4. Jika status "Perbaikan Selesai", buat transaksi otomatis
        if ($new_status == 'perbaikan_selesai') {
            $stmt_sr = $conn->prepare("SELECT id_customer, id_sales FROM ServiceRequest WHERE id_service = ?");
            $stmt_sr->bind_param("i", $id_service);
            $stmt_sr->execute();
            $sr_details = $stmt_sr->get_result()->fetch_assoc();

            $total_harga = $biaya_service_tambahan + $total_biaya_sparepart;

            // Cek jika totalnya lebih dari 0 baru buat transaksi
            if ($total_harga > 0) {
                $stmt_trx = $conn->prepare("INSERT INTO Transaction 
                    (id_customer, id_sales, jenis_transaksi, tanggal_transaksi, total_harga, 
                    status_pembayaran, metode_bayar, metode_pengambilan) 
                    VALUES (?, ?, 'service', CURDATE(), ?, 'belum_bayar', 'tunai', 'ambil_sendiri')");
                $stmt_trx->bind_param("iid", $sr_details['id_customer'], $sr_details['id_sales'], $total_harga);
                $stmt_trx->execute();
                $id_transaction = $stmt_trx->insert_id;

                $stmt_trx_detail = $conn->prepare("INSERT INTO TransactionServiceDetail 
                    (id_transaction, id_service, biaya_service, biaya_sparepart, subtotal) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt_trx_detail->bind_param("iiddd", $id_transaction, $id_service, $biaya_service_tambahan, $total_biaya_sparepart, $total_harga);
                $stmt_trx_detail->execute();
            }
        }

        $conn->commit();
        $_SESSION['success_message'] = "Progress perbaikan berhasil diperbarui.";
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating progress: " . $e->getMessage());
        $_SESSION['error_message'] = "Gagal memperbarui progress: " . $e->getMessage();
    }
    header("Location: ../sales/perbaikan.php");
    exit();
}

// Default redirect if no action matched
header("Location: ../sales/perbaikan.php");
exit();