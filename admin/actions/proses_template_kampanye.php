<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

$action = $_REQUEST['action'] ?? '';

// ACTION: Add Template
if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_template = sanitize($_POST['nama_template']);
    $subjek = sanitize($_POST['subjek']);
    $konten = sanitize($_POST['konten']);
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO CampaignTemplate (nama_template, subjek, konten, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nama_template, $subjek, $konten, $created_by);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Template baru berhasil disimpan!";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan template: " . $stmt->error;
    }
    header("Location: ../marketing/kampanye.php?tab=template");
    exit();
}

// ACTION: Edit Template
if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_template = (int)$_POST['id_template'];
    $nama_template = sanitize($_POST['nama_template']);
    $subjek = sanitize($_POST['subjek']);
    $konten = sanitize($_POST['konten']);

    $stmt = $conn->prepare("UPDATE CampaignTemplate SET nama_template = ?, subjek = ?, konten = ? WHERE id_template = ?");
    $stmt->bind_param("sssi", $nama_template, $subjek, $konten, $id_template);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Template berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui template: " . $stmt->error;
    }
    header("Location: ../marketing/kampanye.php?tab=template");
    exit();
}

// ACTION: Delete Template
if ($action == 'delete' && isset($_GET['id'])) {
    $id_template = (int)$_GET['id'];

    // Sebaiknya gunakan soft delete jika ada kolom deleted_at
    $stmt = $conn->prepare("DELETE FROM CampaignTemplate WHERE id_template = ?");
    $stmt->bind_param("i", $id_template);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Template berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus template.";
    }
    header("Location: ../marketing/kampanye.php?tab=template");
    exit();
}

// ACTION: Get Template Content (for AJAX)
if ($action == 'get_content' && isset($_GET['id'])) {
    $id_template = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT subjek, konten FROM CampaignTemplate WHERE id_template = ?");
    $stmt->bind_param("i", $id_template);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Template not found']);
    }
    exit();
}
?>