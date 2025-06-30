<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
checkRole(['Admin']);

if (($_POST['action'] ?? '') == 'save_smtp') {
    $server = $_POST['server'];
    $port = (int)$_POST['port'];
    $username = $_POST['username'];
    $security = $_POST['security'];
    $sender_name = $_POST['sender_name'];
    $sender_email = $_POST['sender_email'];
    
    // Hanya update password jika diisi
    if (!empty($_POST['password'])) {
        // Enkripsi password sebelum disimpan
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO SmtpSettings (id, server, port, username, password, security, sender_name, sender_email) VALUES (1, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE server=VALUES(server), port=VALUES(port), username=VALUES(username), password=VALUES(password), security=VALUES(security), sender_name=VALUES(sender_name), sender_email=VALUES(sender_email)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssss", $server, $port, $username, $password, $security, $sender_name, $sender_email);
    } else {
        $sql = "INSERT INTO SmtpSettings (id, server, port, username, security, sender_name, sender_email) VALUES (1, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE server=VALUES(server), port=VALUES(port), username=VALUES(username), security=VALUES(security), sender_name=VALUES(sender_name), sender_email=VALUES(sender_email)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissss", $server, $port, $username, $security, $sender_name, $sender_email);
    }
    
    if($stmt->execute()){
        $_SESSION['success_message'] = "Pengaturan SMTP berhasil disimpan.";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan pengaturan SMTP: " . $stmt->error;
    }
    header("Location: ../manajemen/sistem.php");
    exit();
}