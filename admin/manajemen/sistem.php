<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);
$smtp_settings = $conn->query("SELECT * FROM SmtpSettings WHERE id = 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Manajemen Sistem</title></head>
<body>
<?php include '../../includes/header.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <h1 class="h2 pt-3 pb-2 mb-3 border-bottom">Manajemen Sistem</h1>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Pengaturan Email (SMTP)</div>
                <div class="card-body">
                    <form action="../actions/proses_sistem.php" method="POST">
                        <input type="hidden" name="action" value="save_smtp">
                        <div class="mb-3"><label class="form-label">Server SMTP</label><input type="text" class="form-control" name="server" value="<?= htmlspecialchars($smtp_settings['server'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label">Port</label><input type="number" class="form-control" name="port" value="<?= htmlspecialchars($smtp_settings['port'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" name="username" value="<?= htmlspecialchars($smtp_settings['username'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" placeholder="Isi untuk mengubah password"></div>
                        <div class="mb-3"><label class="form-label">Keamanan</label><select class="form-select" name="security"><option value="tls" <?= ($smtp_settings['security'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= ($smtp_settings['security'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option></select></div>
                        <div class="mb-3"><label class="form-label">Nama Pengirim</label><input type="text" class="form-control" name="sender_name" value="<?= htmlspecialchars($smtp_settings['sender_name'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label">Email Pengirim</label><input type="email" class="form-control" name="sender_email" value="<?= htmlspecialchars($smtp_settings['sender_email'] ?? '') ?>" required></div>
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan SMTP</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include '../../includes/footer.php'; ?>
</body>
</html>