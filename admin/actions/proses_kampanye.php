<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
// [BARU] Memuat PHPMailer
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

// ACTION: Add Campaign (Tidak ada perubahan)
if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (logika ini tetap sama)
    $id_marketing = $_SESSION['user_id'];
    $nama_kampanye = sanitize($_POST['nama_kampanye']);
    $subjek = sanitize($_POST['subjek']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $jenis_kampanye = sanitize($_POST['jenis_kampanye']);
    $target_segmentasi = sanitize($_POST['target_segmentasi']);
    $tanggal_mulai = sanitize($_POST['tanggal_mulai']);
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

// [PERUBAHAN] Logika pengiriman email ditambahkan di sini
if ($action == 'approve' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_campaign = (int)$_POST['id_campaign'];
    $id_admin = $_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        // 1. Setujui kampanye di database
        $stmt_approve = $conn->prepare("UPDATE Campaign SET status = 'aktif', approved_by = ? WHERE id_campaign = ?");
        $stmt_approve->bind_param("ii", $id_admin, $id_campaign);
        $stmt_approve->execute();

        // 2. Ambil detail kampanye & pengaturan SMTP
        $campaign_data = $conn->query("SELECT * FROM Campaign WHERE id_campaign = $id_campaign")->fetch_assoc();
        $smtp_settings = $conn->query("SELECT * FROM SmtpSettings WHERE id = 1")->fetch_assoc();

        // Hanya kirim email jika channel adalah 'email' atau 'semua' dan pengaturan SMTP ada
        if (($campaign_data['jenis_kampanye'] == 'email' || $campaign_data['jenis_kampanye'] == 'semua') && $smtp_settings) {
            
            // 3. Tentukan query untuk target pelanggan
            $customer_query_sql = "";
            switch ($campaign_data['target_segmentasi']) {
                case 'semua':
                    $customer_query_sql = "SELECT u.email FROM User u JOIN Customer c ON u.id_user = c.id_user WHERE u.deleted_at IS NULL AND u.email IS NOT NULL AND u.email != ''";
                    break;
                case 'baru':
                case 'loyal':
                case 'perusahaan':
                    $customer_query_sql = "SELECT u.email FROM User u JOIN Customer c ON u.id_user = c.id_user WHERE c.segmentasi = '{$campaign_data['target_segmentasi']}' AND u.deleted_at IS NULL AND u.email IS NOT NULL AND u.email != ''";
                    break;
            }
            
            $customers = $conn->query($customer_query_sql);
            if ($customers->num_rows > 0) {
                // 4. Konfigurasi PHPMailer (satu kali)
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtp_settings['server'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_settings['username'];
                $mail->Password   = $smtp_settings['password'];
                $mail->SMTPSecure = $smtp_settings['security'];
                $mail->Port       = $smtp_settings['port'];
                $mail->setFrom($smtp_settings['sender_email'], $smtp_settings['sender_name']);
                $mail->isHTML(true);
                $mail->Subject = $campaign_data['subjek'];
                $mail->Body    = nl2br($campaign_data['deskripsi']); // Gunakan nl2br untuk mengubah baris baru menjadi <br>

                // 5. Loop dan kirim email
                while ($customer = $customers->fetch_assoc()) {
                    try {
                        $mail->addAddress($customer['email']);
                        $mail->send();
                        $mail->clearAddresses(); // Hapus penerima untuk iterasi berikutnya
                    } catch (Exception $e) {
                        // Opsi: catat email yang gagal ke log, tapi jangan hentikan seluruh proses
                        error_log("Gagal mengirim email kampanye #{$id_campaign} ke {$customer['email']}: {$mail->ErrorInfo}");
                    }
                }
            }
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Kampanye berhasil disetujui dan proses pengiriman email telah dimulai.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    header("Location: ../marketing/kampanye.php?tab=approval");
    exit();
}

// ACTION: Reject Campaign (Tidak ada perubahan)
if ($action == 'reject' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (logika ini tetap sama)
    $id_campaign = (int)$_POST['id_campaign'];
    $rejection_reason = sanitize($_POST['rejection_reason']);

    $stmt = $conn->prepare("UPDATE Campaign SET status = 'ditolak', rejection_reason = ? WHERE id_campaign = ?");
    $stmt->bind_param("si", $rejection_reason, $id_campaign);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Kampanye telah ditolak.";
    } else {
        $_SESSION['error_message'] = "Gagal menolak kampanye.";
    }
    header("Location: ../marketing/kampanye.php?tab=approval");
    exit();
}