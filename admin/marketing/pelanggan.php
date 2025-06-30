<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// Query diperbarui untuk mengambil u.role
$pelanggan = $conn->query("SELECT u.id_user, u.nama_lengkap, u.email, u.no_hp, u.role, c.total_transaksi, c.total_pengeluaran, c.segmentasi, c.origin
                           FROM Customer c
                           JOIN User u ON c.id_user = u.id_user
                           WHERE u.deleted_at IS NULL
                           ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan</title>
</head>
<body>

<?php include '../../includes/header.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manajemen Pelanggan</h1>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
            <i class="bi bi-plus-lg"></i> Tambah Pelanggan
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr><th>Nama</th><th>Email</th><th>Total Transaksi</th><th>Total Belanja</th><th>Segmentasi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while($p = $pelanggan->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= $p['total_transaksi'] ?></td>
                    <td><?= formatCurrency($p['total_pengeluaran']) ?></td>
                    <td><?= ucfirst($p['segmentasi']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick='editUser(<?= json_encode($p) ?>)'>Edit</button>
                        <a href="../actions/proses_pengguna.php?action=delete_user&id=<?= $p['id_user'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin nonaktifkan user ini?')">Nonaktifkan</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Tambah Pelanggan Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form action="../actions/proses_pengguna.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="add_customer">
            <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
            <div class="mb-3"><label class="form-label">No. HP</label><input type="text" class="form-control" name="no_hp" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan</button></div>
      </form>
    </div>
  </div>
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
