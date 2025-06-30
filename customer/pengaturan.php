<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

checkRole(['Customer']);

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);
$customer = getCustomerData($user_id);

// Process profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);

    // Update user data
    $sql_user = "UPDATE User SET nama_lengkap = ?, email = ?, no_hp = ? WHERE id_user = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("sssi", $name, $email, $phone, $user_id);

    // Update customer address
    $sql_customer = "UPDATE Customer SET alamat_pengiriman = ? WHERE id_user = ?";
    $stmt_customer = $conn->prepare($sql_customer);
    $stmt_customer->bind_param("si", $address, $user_id);

    if ($stmt_user->execute() && $stmt_customer->execute()) {
        $_SESSION['user_name'] = $name;
        $_SESSION['success_message'] = "Profil berhasil diperbarui!";
        header("Location: pengaturan.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui profil.";
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = sanitize($_POST['current_password']);
    $new_password = sanitize($_POST['new_password']);
    $confirm_password = sanitize($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Password baru dan konfirmasi password tidak sama!";
    } elseif (!password_verify($current_password, $user['password'])) {
        $_SESSION['error_message'] = "Password saat ini salah!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE User SET password = ?, update_password = FALSE WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Password berhasil diubah!";
            header("Location: pengaturan.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Gagal mengubah password.";
        }
    }
}

include '../includes/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Pengaturan Akun';
</script>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
            <?php echo $_SESSION['success_message'];
            unset($_SESSION['success_message']); ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
            <?php echo $_SESSION['error_message'];
            unset($_SESSION['error_message']); ?>
        </div>
    </div>
<?php endif; ?>

<div class="space-y-8">
    <!-- Account Information (stays at the top) -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-green-100 text-green-600 p-3 rounded-full mr-4">
                <i data-lucide="info" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-slate-800">Informasi Akun</h3>
                <p class="text-slate-500">Detail akun dan statistik Anda</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Segmentasi Customer</p>
                        <p class="font-semibold text-slate-800 capitalize"><?php echo $customer['segmentasi']; ?></p>
                    </div>
                    <i data-lucide="users" class="w-8 h-8 text-slate-400"></i>
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Total Transaksi</p>
                        <p class="font-semibold text-slate-800"><?php echo $customer['total_transaksi']; ?></p>
                    </div>
                    <i data-lucide="shopping-cart" class="w-8 h-8 text-slate-400"></i>
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Total Pengeluaran</p>
                        <p class="font-semibold text-slate-800"><?php echo formatCurrency($customer['total_pengeluaran']); ?></p>
                    </div>
                    <i data-lucide="dollar-sign" class="w-8 h-8 text-slate-400"></i>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
                <i data-lucide="calendar" class="w-5 h-5 text-blue-600 mr-3 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-blue-800">Bergabung Sejak</p>
                    <p class="text-blue-700"><?php echo formatDate($user['created_at']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New grid layout for Profile and Password widgets -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
    <!-- Profile Settings (now on the left) -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full mr-4">
                <i data-lucide="user" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-slate-800">Profil Saya</h3>
                <p class="text-slate-500">Kelola informasi profil Anda</p>
            </div>
        </div>

        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Handphone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['no_hp']); ?>" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Profil</label>
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center">
                            <i data-lucide="user" class="w-8 h-8 text-slate-500"></i>
                        </div>
                        <input type="file" accept="image/*"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Lengkap</label>
                <textarea name="address" rows="3"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($customer['alamat_pengiriman']); ?></textarea>
            </div>

            <div class="mt-6">
                <button type="submit" name="update_profile"
                    class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Password Change (now on the right) -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-red-100 text-red-600 p-3 rounded-full mr-4">
                <i data-lucide="lock" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-slate-800">Ubah Password</h3>
                <p class="text-slate-500">Pastikan akun Anda tetap aman dengan password yang kuat</p>
            </div>
        </div>

        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password Saat Ini</label>
                    <input type="password" name="current_password" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                    <input type="password" name="new_password" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" name="change_password"
                    class="bg-red-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-red-700 transition-colors flex items-center">
                    <i data-lucide="shield" class="w-5 h-5 mr-2"></i>
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
</script>

<?php include '../includes/footer.php'; ?>