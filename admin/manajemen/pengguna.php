<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// Data untuk Tab "Akun Tim" (menambahkan no_hp)
$akun_tim = $conn->query("SELECT id_user, nama_lengkap, email, no_hp, role FROM User WHERE role IN ('Admin', 'Sales', 'Marketing') AND deleted_at IS NULL ORDER BY created_at DESC");

// Data untuk Tab "Akun Pelanggan" (menambahkan no_hp dan role)
$akun_pelanggan = $conn->query("SELECT u.id_user, u.nama_lengkap, u.email, u.no_hp, u.role, c.origin
                               FROM User u 
                               JOIN Customer c ON u.id_user = c.id_user 
                               WHERE u.role = 'Customer' AND u.deleted_at IS NULL 
                               ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna</title>
</head>
<body>

<?php include '../../includes/header.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manajemen Pengguna</h1>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="userTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tim-tab" data-bs-toggle="tab" data-bs-target="#tim" type="button" role="tab">Akun Tim</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pelanggan-tab" data-bs-toggle="tab" data-bs-target="#pelanggan" type="button" role="tab">Akun Pelanggan</button>
      </li>
    </ul>

    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade show active" id="tim" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between">
                <span>Daftar Akun Tim Internal</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#teamModal">Tambah Akun Tim</button>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php while($t = $akun_tim->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($t['email']) ?></td>
                            <td><span class="badge bg-info"><?= $t['role'] ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick='editUser(<?= json_encode($t) ?>)'>Edit</button>
                                <a href="../actions/proses_pengguna.php?action=delete_user&id=<?= $t['id_user'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin nonaktifkan user ini?')">Nonaktifkan</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
      <div class="tab-pane fade" id="pelanggan" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">Daftar Akun Pelanggan</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Nama</th><th>Email</th><th>Sumber</th><th>Aksi</th></tr></thead>
                    <tbody>
                         <?php while($p = $akun_pelanggan->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($p['origin']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick='editUser(<?= json_encode($p) ?>)'>Edit</button>
                                <a href="../actions/proses_pengguna.php?action=delete_user&id=<?= $p['id_user'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin nonaktifkan user ini?')">Nonaktifkan</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
    </div>
</main>

<!-- Add Team Modal -->
<div class="modal fade" id="teamModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Tambah Akun Tim</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form action="../actions/proses_pengguna.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="add_tim">
            <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
            <div class="mb-3"><label class="form-label">No. HP</label><input type="text" class="form-control" name="no_hp" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
            <div class="mb-3"><label class="form-label">Peran (Role)</label><select class="form-select" name="role" required><option value="Sales">Sales</option><option value="Marketing">Marketing</option><option value="Admin">Admin</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan</button></div>
      </form>
  </div></div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form action="../actions/proses_pengguna.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="id_user" id="edit_id_user">
            <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="edit_email" required></div>
            <div class="mb-3"><label class="form-label">No. HP</label><input type="text" class="form-control" name="no_hp" id="edit_no_hp" required></div>
            <div class="mb-3"><label class="form-label">Peran (Role)</label><select class="form-select" name="role" id="edit_role" required><option value="Customer">Customer</option><option value="Sales">Sales</option><option value="Marketing">Marketing</option><option value="Admin">Admin</option></select></div>
            <div class="mb-3"><label class="form-label">Password Baru (Opsional)</label><input type="password" class="form-control" name="password"><small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
function editUser(user) {
    document.getElementById('edit_id_user').value = user.id_user;
    document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_no_hp').value = user.no_hp;
    
    const roleSelect = document.getElementById('edit_role');
    roleSelect.value = user.role;

    // Disable role changes for customers
    if (user.role === 'Customer') {
        roleSelect.disabled = true;
    } else {
        roleSelect.disabled = false;
    }

    var myModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    myModal.show();
}
</script>

</body>
</html>
