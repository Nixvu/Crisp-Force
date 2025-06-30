<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

// ACTION: Add Customer manually by Admin
if ($action == 'add_customer' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $no_hp = sanitize($_POST['no_hp']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $conn->begin_transaction();
    try {
        // 1. Insert ke tabel User
        $stmt_user = $conn->prepare("INSERT INTO User (nama_lengkap, email, no_hp, password, role) VALUES (?, ?, ?, ?, 'Customer')");
        $stmt_user->bind_param("ssss", $nama_lengkap, $email, $no_hp, $password);
        $stmt_user->execute();
        $id_user = $stmt_user->insert_id;

        // 2. Insert ke tabel Customer dengan origin manual
        $stmt_customer = $conn->prepare("INSERT INTO Customer (id_user, origin) VALUES (?, 'manual_input')");
        $stmt_customer->bind_param("i", $id_user);
        $stmt_customer->execute();

        $conn->commit();
        $_SESSION['success_message'] = "Pelanggan baru berhasil ditambahkan.";

    } catch (Exception $e) {
        $conn->rollback();
        // Cek jika error karena duplikat email
        if ($conn->errno == 1062) {
             $_SESSION['error_message'] = "Gagal menambahkan pelanggan: Email sudah terdaftar.";
        } else {
             $_SESSION['error_message'] = "Gagal menambahkan pelanggan: " . $e->getMessage();
        }
    }
    header("Location: ../marketing/pelanggan.php");
    exit();
}

// Aksi lain untuk pengguna (edit, delete, add team) akan ditambahkan di sini nanti
?>