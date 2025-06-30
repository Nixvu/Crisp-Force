<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);
$action = $_POST['action'] ?? '';

// [BARU] Logika untuk update profil admin sendiri
if ($action == 'update_my_profile') {
    $admin_id = $_SESSION['user_id'];
    $nama = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $no_hp = sanitize($_POST['no_hp']);

    $stmt = $conn->prepare("UPDATE User SET nama_lengkap = ?, email = ?, no_hp = ? WHERE id_user = ?");
    $stmt->bind_param("sssi", $nama, $email, $no_hp, $admin_id);
    if($stmt->execute()){
        $_SESSION['user_name'] = $nama; // Update session name
        $_SESSION['success_message'] = "Profil Anda berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui profil.";
    }
}

// [BARU] Logika untuk ubah password admin sendiri
if ($action == 'change_my_password') {
    $admin_id = $_SESSION['user_id'];
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $user = getUserData($admin_id);

    if (!password_verify($current_pass, $user['password'])) {
        $_SESSION['error_message'] = "Password saat ini salah!";
    } elseif ($new_pass !== $confirm_pass) {
        $_SESSION['error_message'] = "Password baru dan konfirmasi tidak sama!";
    } else {
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE User SET password = ? WHERE id_user = ?");
        $stmt->bind_param("si", $hashed_password, $admin_id);
        if($stmt->execute()) {
             $_SESSION['success_message'] = "Password berhasil diubah.";
        } else {
            $_SESSION['error_message'] = "Gagal mengubah password.";
        }
    }
}

// Logika untuk simpan profil bisnis
if ($action == 'save_business_profile') {
    $nama_bisnis = sanitize($_POST['nama_bisnis']);
    $alamat = sanitize($_POST['alamat']);
    $telepon = sanitize($_POST['telepon']);
    $email = sanitize($_POST['email']);
    
    // Logika untuk menyimpan/update logo
    $logo_path_for_db = $conn->query("SELECT logo_url FROM BusinessProfile WHERE id=1")->fetch_assoc()['logo_url'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "../../assets/uploads/";
        $file_name = "logo_" . time() . '_' . basename($_FILES["logo"]["name"]);
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_dir . $file_name)) {
            $logo_path_for_db = $file_name;
        }
    }

    $sql = "INSERT INTO BusinessProfile (id, nama_bisnis, alamat, telepon, email, logo_url) VALUES (1, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE nama_bisnis=VALUES(nama_bisnis), alamat=VALUES(alamat), telepon=VALUES(telepon), email=VALUES(email), logo_url=VALUES(logo_url)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nama_bisnis, $alamat, $telepon, $email, $logo_path_for_db);

    if($stmt->execute()){
        $_SESSION['success_message'] = "Profil bisnis berhasil disimpan.";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan profil bisnis: " . $stmt->error;
    }
}

header("Location: ../pengaturan.php");
exit();
?>