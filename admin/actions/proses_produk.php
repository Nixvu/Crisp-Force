<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

// ADD ACTION
if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_product = sanitize($_POST['kode_product']);
    $nama_product = sanitize($_POST['nama_product']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $stok = (int)$_POST['stok'];
    $harga = (float)$_POST['harga'];
    $created_by = $_SESSION['user_id'];

    // Validate required fields
    if (empty($kode_product) || empty($nama_product) || empty($category) || $stok < 0 || $harga < 0) {
        $_SESSION['error_message'] = "Semua field wajib diisi dengan data yang valid.";
        header("Location: ../sales/produk.php");
        exit();
    }

    // Handle file upload
    $gambar_url = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../../assets/uploads/products/";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $_SESSION['error_message'] = "Gagal membuat direktori upload.";
                header("Location: ../sales/produk.php");
                exit();
            }
        }

        // Validate file
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file_type = $_FILES['gambar']['type'];
        $file_size = $_FILES['gambar']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Hanya file JPG dan PNG yang diperbolehkan.";
            header("Location: ../sales/produk.php");
            exit();
        }

        if ($file_size > $max_size) {
            $_SESSION['error_message'] = "Ukuran file terlalu besar. Maksimal 2MB.";
            header("Location: ../sales/produk.php");
            exit();
        }

        // Generate unique filename
        $file_ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            $gambar_url = "products/" . $file_name;
        } else {
            $_SESSION['error_message'] = "Gagal mengupload gambar produk.";
            header("Location: ../sales/produk.php");
            exit();
        }
    }

    // Insert product
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO Product (kode_product, nama_product, description, category, stok, harga, gambar_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssidii", $kode_product, $nama_product, $description, $category, $stok, $harga, $gambar_url, $created_by);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Produk baru berhasil ditambahkan!";
        } else {
            throw new Exception("Gagal menambahkan produk: " . $stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        // Delete uploaded file if transaction failed
        if (!empty($gambar_url) && file_exists("../../assets/uploads/" . $gambar_url)) {
            unlink("../../assets/uploads/" . $gambar_url);
        }
        $_SESSION['error_message'] = $e->getMessage();
    }
    header("Location: ../sales/produk.php");
    exit();
}

// EDIT ACTION
if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_product = (int)$_POST['id_product'];
    $kode_product = sanitize($_POST['kode_product']);
    $nama_product = sanitize($_POST['nama_product']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $stok = (int)$_POST['stok'];
    $harga = (float)$_POST['harga'];

    // Validate required fields
    if (empty($kode_product) || empty($nama_product) || empty($category) || $stok < 0 || $harga < 0) {
        $_SESSION['error_message'] = "Semua field wajib diisi dengan data yang valid.";
        header("Location: ../sales/produk.php");
        exit();
    }

    // Handle file upload
    $gambar_sql_part = "";
    $gambar_url = '';
    $old_image = '';
    
    // Get old image path
    $result = $conn->query("SELECT gambar_url FROM Product WHERE id_product = $id_product");
    if ($result->num_rows > 0) {
        $old_image = $result->fetch_assoc()['gambar_url'];
    }

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../../assets/uploads/products/";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $_SESSION['error_message'] = "Gagal membuat direktori upload.";
                header("Location: ../sales/produk.php");
                exit();
            }
        }

        // Validate file
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file_type = $_FILES['gambar']['type'];
        $file_size = $_FILES['gambar']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Hanya file JPG dan PNG yang diperbolehkan.";
            header("Location: ../sales/produk.php");
            exit();
        }

        if ($file_size > $max_size) {
            $_SESSION['error_message'] = "Ukuran file terlalu besar. Maksimal 2MB.";
            header("Location: ../sales/produk.php");
            exit();
        }

        // Generate unique filename
        $file_ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            $gambar_url = "products/" . $file_name;
            $gambar_sql_part = ", gambar_url = ?";
            
            // Delete old image if exists
            if (!empty($old_image) && file_exists("../../assets/uploads/" . $old_image)) {
                unlink("../../assets/uploads/" . $old_image);
            }
        } else {
            $_SESSION['error_message'] = "Gagal mengupload gambar produk.";
            header("Location: ../sales/produk.php");
            exit();
        }
    }

    // Update product
    $conn->begin_transaction();
    try {
        $sql = "UPDATE Product SET 
                kode_product = ?, 
                nama_product = ?, 
                description = ?, 
                category = ?, 
                stok = ?, 
                harga = ? 
                $gambar_sql_part 
                WHERE id_product = ?";
        
        $stmt = $conn->prepare($sql);

        if (!empty($gambar_sql_part)) {
            $stmt->bind_param("ssssidsi", $kode_product, $nama_product, $description, $category, $stok, $harga, $gambar_url, $id_product);
        } else {
            $stmt->bind_param("ssssidi", $kode_product, $nama_product, $description, $category, $stok, $harga, $id_product);
        }
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Produk berhasil diperbarui!";
        } else {
            throw new Exception("Gagal memperbarui produk: " . $stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        // Delete new uploaded file if transaction failed 
        if (!empty($gambar_url)) {
            unlink($target_file);
        }
        $_SESSION['error_message'] = $e->getMessage();
    }
    header("Location: ../sales/produk.php");
    exit(); // Fix: Missing semicolon
}

// DELETE ACTION (Soft Delete)
if ($action == 'delete' && isset($_GET['id'])) {
    $id_product = (int)$_GET['id'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE Product SET deleted_at = NOW() WHERE id_product = ?");
        $stmt->bind_param("i", $id_product);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Produk berhasil dihapus.";
        } else {
            throw new Exception("Gagal menghapus produk.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    header("Location: ../sales/produk.php");
    exit();
}

header("Location: ../sales/produk.php");
exit();