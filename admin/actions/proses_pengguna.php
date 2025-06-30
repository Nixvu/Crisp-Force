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

    // Cegah admin mengubah role-nya sendiri jika dia satu-satunya admin
    // (logika ini bisa ditambahkan nanti untuk keamanan ekstra)

    $password_sql = '';
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_sql = ', password = ?';
    }

    $sql = "UPDATE User SET nama_lengkap = ?, email = ?, no_hp = ?, role = ? $password_sql WHERE id_user = ?";
    $stmt = $conn->prepare($sql);

    if (!empty($password_sql)) {
        $stmt->bind_param("sssssi", $nama_lengkap, $email, $no_hp, $role, $password, $id_user);
    } else {
        $stmt->bind_param("ssssi", $nama_lengkap, $email, $no_hp, $role, $id_user);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Data pengguna berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data: " . $stmt->error;
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