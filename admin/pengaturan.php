<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verify admin role
checkRole(['Admin']);

// Initialize data arrays
$admin_data = [];
$profile = [];
$admin_stats = [
    'total_users' => 0,
    'total_transactions' => 0,
    'total_revenue' => 0
];

try {
    // Get admin profile data
    $admin_id = $_SESSION['user_id'];
    $admin_data = getUserData($admin_id);
    
    if (!$admin_data) {
        throw new Exception("Admin data not found");
    }

    // Get business profile
    $stmt = $conn->prepare("SELECT * FROM BusinessProfile WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc() ?: [];
    $stmt->close();

    // Get statistics - using separate queries for better error handling
    // 1. Total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM User");
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_stats['total_users'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // 2. Total transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Transaction");
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_stats['total_transactions'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // 3. Total revenue
    $stmt = $conn->prepare("SELECT SUM(total_harga) as total FROM Transaction");
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

} catch (Exception $e) {
    error_log("Error in pengaturan.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Terjadi kesalahan saat memuat data. Silakan coba lagi.";
}

include '../includes/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Pengaturan Admin';
</script>

<div class="space-y-8">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Account Information Section -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full mr-4">
                <i data-lucide="user-cog" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-slate-800">Informasi Admin</h3>
                <p class="text-slate-500">Detail akun dan statistik administrator</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Total Pengguna</p>
                        <p class="font-semibold text-slate-800"><?= number_format($admin_stats['total_users']) ?></p>
                    </div>
                    <i data-lucide="users" class="w-8 h-8 text-slate-400"></i>
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Total Transaksi</p>
                        <p class="font-semibold text-slate-800"><?= number_format($admin_stats['total_transactions']) ?></p>
                    </div>
                    <i data-lucide="shopping-cart" class="w-8 h-8 text-slate-400"></i>
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Total Pendapatan</p>
                        <p class="font-semibold text-slate-800"><?= formatCurrency($admin_stats['total_revenue']) ?></p>
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
                    <p class="text-blue-700"><?= formatDate($admin_data['created_at'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile and Business Settings -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Admin Profile Section -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <div class="flex items-center mb-6">
                <div class="bg-purple-100 text-purple-600 p-3 rounded-full mr-4">
                    <i data-lucide="user" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Profil Admin</h3>
                    <p class="text-slate-500">Kelola informasi profil Anda</p>
                </div>
            </div>

            <form action="actions/proses_pengaturan.php" method="POST">
                <input type="hidden" name="action" value="update_my_profile">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($admin_data['nama_lengkap'] ?? '') ?>" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($admin_data['email'] ?? '') ?>" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. HP</label>
                        <input type="text" name="no_hp" value="<?= htmlspecialchars($admin_data['no_hp'] ?? '') ?>" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Profil</label>
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center">
                                <?php if (!empty($admin_data['foto_profil'])): ?>
                                    <img src="/Crisp-Force/assets/uploads/<?= htmlspecialchars($admin_data['foto_profil']) ?>" class="w-full h-full rounded-full object-cover">
                                <?php else: ?>
                                    <i data-lucide="user" class="w-8 h-8 text-slate-500"></i>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="foto_profil" accept="image/*" 
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" 
                        class="bg-purple-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                        <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Business Profile Section -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <div class="flex items-center mb-6">
                <div class="bg-green-100 text-green-600 p-3 rounded-full mr-4">
                    <i data-lucide="building-2" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Profil Bisnis</h3>
                    <p class="text-slate-500">Kelola informasi dan identitas perusahaan</p>
                </div>
            </div>

            <form action="actions/proses_pengaturan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_business_profile">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Bisnis</label>
                        <input type="text" name="nama_bisnis" value="<?= htmlspecialchars($profile['nama_bisnis'] ?? '') ?>" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Logo Bisnis</label>
                        <div class="flex items-center space-x-4">
                            <?php if(!empty($profile['logo_url'])): ?>
                                <img src="/Crisp-Force/assets/uploads/<?= htmlspecialchars($profile['logo_url']) ?>" class="w-16 h-16 object-contain">
                            <?php else: ?>
                                <div class="w-16 h-16 bg-slate-200 rounded-lg flex items-center justify-center">
                                    <i data-lucide="image" class="w-8 h-8 text-slate-500"></i>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" accept="image/*" 
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat</label>
                    <textarea name="alamat" rows="3" 
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($profile['alamat'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Telepon</label>
                        <input type="text" name="telepon" value="<?= htmlspecialchars($profile['telepon'] ?? '') ?>" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email Bisnis</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" 
                        class="bg-green-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center">
                        <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                        Simpan Profil Bisnis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Change Section -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-red-100 text-red-600 p-3 rounded-full mr-4">
                <i data-lucide="lock" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-slate-800">Keamanan Akun</h3>
                <p class="text-slate-500">Ubah password untuk meningkatkan keamanan</p>
            </div>
        </div>

        <form action="actions/proses_pengaturan.php" method="POST">
            <input type="hidden" name="action" value="change_my_password">
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password Saat Ini</label>
                    <input type="password" name="current_password" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
            </div>

            <div class="mt-6">
                <button type="submit" 
                    class="bg-red-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-red-700 transition-colors flex items-center">
                    <i data-lucide="shield" class="w-5 h-5 mr-2"></i>
                    Ubah Password
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