<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

// ... (kode 'add_customer' dari bagian sebelumnya tetap ada di sini) ...

// [KODE BARU] ACTION: Add Team Member by Admin
if ($action == 'add_tim' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $no_hp = sanitize($_POST['no_hp']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = sanitize($_POST['role']);

    // Pastikan role valid
    if (!in_array($role, ['Admin', 'Sales', 'Marketing'])) {
        $_SESSION['error_message'] = "Peran tidak valid.";
        header("Location: ../manajemen/pengguna.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO User (nama_lengkap, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nama_lengkap, $email, $no_hp, $password, $role);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Akun tim baru berhasil ditambahkan.";
    } else {
        $_SESSION['error_message'] = "Gagal: Email mungkin sudah terdaftar.";
    }
    header("Location: ../manajemen/pengguna.php");
    exit();
}

// ACTION: Edit User by Admin
if ($action == 'edit_user' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = (int)$_POST['id_user'];
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $no_hp = sanitize($_POST['no_hp']);
    $role = sanitize($_POST['role']);

    $conn->begin_transaction();
    try {
        // Update tabel User
        $password_sql = '';
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_sql = ', password = ?';
        }

        $sql_user = "UPDATE User SET nama_lengkap = ?, email = ?, no_hp = ?, role = ? $password_sql WHERE id_user = ?";
        $stmt_user = $conn->prepare($sql_user);

        if (!empty($password_sql)) {
            $stmt_user->bind_param("sssssi", $nama_lengkap, $email, $no_hp, $role, $password, $id_user);
        } else {
            $stmt_user->bind_param("ssssi", $nama_lengkap, $email, $no_hp, $role, $id_user);
        }
        $stmt_user->execute();

        // Jika peran adalah Customer, update juga tabel Customer
        if ($role == 'Customer') {
            $segmentasi = sanitize($_POST['segmentasi']);
            $sql_customer = "UPDATE Customer SET segmentasi = ? WHERE id_user = ?";
            $stmt_customer = $conn->prepare($sql_customer);
            $stmt_customer->bind_param("si", $segmentasi, $id_user);
            $stmt_customer->execute();
        }

        $conn->commit();
        $_SESSION['success_message'] = "Data pengguna berhasil diperbarui.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Gagal memperbarui data: " . $e->getMessage();
    }
    header("Location: ../manajemen/pengguna.php");
    exit();
}

// [KODE BARU] ACTION: Soft Delete User (Customer or Team)
if ($action == 'delete_user' && isset($_GET['id'])) {
    $id_user = (int)$_GET['id'];
    
    // Admin tidak bisa menghapus dirinya sendiri
    if ($id_user == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "Anda tidak bisa menonaktifkan akun Anda sendiri.";
        header("Location: ../manajemen/pengguna.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE User SET deleted_at = NOW() WHERE id_user = ?");
    $stmt->bind_param("i", $id_user);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Pengguna berhasil dinonaktifkan.";
    } else {
        $_SESSION['error_message'] = "Gagal menonaktifkan pengguna.";
    }
    header("Location: ../manajemen/pengguna.php");
    exit();
}
?>