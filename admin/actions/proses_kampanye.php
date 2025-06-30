<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

// ACTION: Add Campaign
if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_marketing = $_SESSION['user_id'];
    $nama_kampanye = sanitize($_POST['nama_kampanye']);
    $subjek = sanitize($_POST['subjek']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $jenis_kampanye = sanitize($_POST['jenis_kampanye']);
    $target_segmentasi = sanitize($_POST['target_segmentasi']);
    $tanggal_mulai = sanitize($_POST['tanggal_mulai']);
    
    // Logika status otomatis berdasarkan peran
    $status = ($_SESSION['user_role'] == 'Admin') ? 'aktif' : 'menunggu_acc';

    $stmt = $conn->prepare("INSERT INTO Campaign (id_marketing, nama_kampanye, subjek, deskripsi, jenis_kampanye, target_segmentasi, tanggal_mulai, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $id_marketing, $nama_kampanye, $subjek, $deskripsi, $jenis_kampanye, $target_segmentasi, $tanggal_mulai, $status);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Kampanye baru berhasil dibuat!";
    } else {
        $_SESSION['error_message'] = "Gagal membuat kampanye: " . $stmt->error;
    }
    header("Location: ../marketing/kampanye.php");
    exit();
}

// ACTION: Approve Campaign
if ($action == 'approve' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_campaign = (int)$_POST['id_campaign'];
    $id_admin = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE Campaign SET status = 'aktif', approved_by = ? WHERE id_campaign = ?");
    $stmt->bind_param("ii", $id_admin, $id_campaign);
    if ($stmt->execute()) {
        // Di sini nantinya bisa ditambahkan trigger untuk mulai mengirim email
        $_SESSION['success_message'] = "Kampanye berhasil disetujui.";
    } else {
        $_SESSION['error_message'] = "Gagal menyetujui kampanye.";
    }
    header("Location: ../marketing/kampanye.php");
    exit();
}

// ACTION: Reject Campaign
if ($action == 'reject' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_campaign = (int)$_POST['id_campaign'];
    $rejection_reason = sanitize($_POST['rejection_reason']);

    $stmt = $conn->prepare("UPDATE Campaign SET status = 'ditolak', rejection_reason = ? WHERE id_campaign = ?");
    $stmt->bind_param("si", $rejection_reason, $id_campaign);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Kampanye telah ditolak.";
    } else {
        $_SESSION['error_message'] = "Gagal menolak kampanye.";
    }
    header("Location: ../marketing/kampanye.php");
    exit();
}

?>