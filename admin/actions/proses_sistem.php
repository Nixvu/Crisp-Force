<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Memuat autoloader dari Composer untuk PHPMailer
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

if ($action == 'save_smtp' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $server = sanitize($_POST['server']);
    $port = (int)$_POST['port'];
    $username = sanitize($_POST['username']);
    $security = sanitize($_POST['security']);
    $sender_name = sanitize($_POST['sender_name']);
    $sender_email = sanitize($_POST['sender_email']);
    
    // PERBAIKAN KRITIS: Jangan hash password SMTP. Simpan sebagai plain text.
    // Aplikasi perlu password asli untuk login ke server SMTP.
    if (!empty($_POST['password'])) {
        $password = $_POST['password']; // Simpan langsung, tanpa hash
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

// FITUR BARU: Aksi untuk tes koneksi SMTP via AJAX
if ($action == 'test_smtp' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');

    $settings = $conn->query("SELECT * FROM SmtpSettings WHERE id = 1")->fetch_assoc();

    if (!$settings || empty($settings['server'])) {
        echo json_encode(['success' => false, 'message' => 'Pengaturan SMTP belum disimpan.']);
        exit();
    }

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $settings['server'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['username'];
        $mail->Password   = $settings['password'];
        $mail->SMTPSecure = $settings['security'];
        $mail->Port       = $settings['port'];

        //Recipients
        $mail->setFrom($settings['sender_email'], $settings['sender_name']);
        // Kirim email tes ke alamat pengirim itu sendiri
        $mail->addAddress($settings['sender_email'], 'SMTP Test User');

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Tes Koneksi SMTP Berhasil - Crisp Force';
        $mail->Body    = 'Ini adalah email tes otomatis dari sistem Crisp Force Anda. Jika Anda menerima ini, pengaturan SMTP Anda sudah benar.';
        $mail->AltBody = 'Ini adalah email tes otomatis dari sistem Crisp Force Anda. Jika Anda menerima ini, pengaturan SMTP Anda sudah benar.';

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Koneksi berhasil! Email tes telah dikirim ke ' . htmlspecialchars($settings['sender_email'])]);
    } catch (Exception $e) {
        // Memberikan pesan error yang lebih informatif
        echo json_encode(['success' => false, 'message' => "Koneksi Gagal. Pesan dari PHPMailer: " . $mail->ErrorInfo]);
    }
    exit();
}