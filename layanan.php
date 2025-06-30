<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$tracked_repair = null;
$progress_history = [];
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['track_service'])) {
    $service_code = sanitize($_POST['service_code']);

    if (empty($service_code)) {
        $error_message = 'Harap masukkan ID Service.';
    } else {
        // Query untuk mendapatkan detail perbaikan dasar
        $sql_track = "SELECT sr.id_service, sr.kode_service, sr.nama_service, sr.tanggal_masuk, u.nama_lengkap as customer_name
                      FROM ServiceRequest sr
                      LEFT JOIN Customer c ON sr.id_customer = c.id_customer
                      LEFT JOIN User u ON c.id_user = u.id_user
                      WHERE sr.kode_service = ?";
        $stmt_track = $conn->prepare($sql_track);
        $stmt_track->bind_param("s", $service_code);
        $stmt_track->execute();
        $tracked_repair = $stmt_track->get_result()->fetch_assoc();

        if ($tracked_repair) {
            // Jika ditemukan, ambil riwayat progresnya
            $sql_history = "SELECT status, catatan, created_at FROM ServiceProgress WHERE id_service = ? ORDER BY created_at ASC";
            $stmt_history = $conn->prepare($sql_history);
            $stmt_history->bind_param("i", $tracked_repair['id_service']);
            $stmt_history->execute();
            $progress_history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $error_message = 'ID Service tidak ditemukan. Pastikan Anda memasukkan ID yang benar.';
        }
    }
}

// Definisikan langkah-langkah progres untuk pemetaan
$status_map = [
    'diterima_digerai' => ['Diterima', 'check', 1],
    'analisis_kerusakan' => ['Diagnosa', 'search', 2],
    'menunggu_sparepart' => ['Menunggu Part', 'clock', 3],
    'dalam_perbaikan' => ['Perbaikan', 'wrench', 3],
    'perbaikan_selesai' => ['Selesai', 'package-check', 4],
    'gagal' => ['Gagal', 'x-circle', 4],
    'diambil_pelanggan' => ['Sudah Diambil', 'package-check', 4]
];

?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan & Lacak Service - CRISP FORCE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F1F5F9;
            color: #1E293B;
        }
    </style>
</head>

<body class="bg-slate-100">

    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-sm shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2">
                <i data-lucide="zap" class="w-7 h-7 text-blue-600"></i>
                <span class="text-xl font-extrabold text-slate-900">CRISP FORCE</span>
            </a>
            <div class="hidden md:flex items-center space-x-8 text-sm font-semibold">
                <a href="index.php" class="text-slate-600 hover:text-blue-600 transition">Beranda</a>
                <a href="index.php#products" class="text-slate-600 hover:text-blue-600 transition">Katalog</a>
                <a href="layanan.php" class="text-blue-600">Layanan</a>
                <a href="tentang.php" class="text-slate-600 hover:text-blue-600 transition">Tentang</a>
            </div>
            <div class="hidden md:flex items-center space-x-4">
                <a href="login.php" class="text-slate-600 hover:text-blue-600 font-bold text-sm">Masuk</a>
                <a href="register.php" class="bg-blue-600 text-white font-bold px-5 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm shadow-lg shadow-blue-500/20">Daftar</a>
            </div>
            <div class="md:hidden">
                <button id="mobile-menu-btn">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="bg-gradient-to-br from-blue-600 to-purple-600 text-white">
            <div class="container mx-auto px-6 py-16 md:py-20 text-center">
                <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-4">Pusat Layanan & Dukungan Teknis</h1>
                <p class="mt-4 text-lg text-blue-100 max-w-2xl mx-auto">Lacak status perbaikan perangkat Anda atau temukan layanan yang tepat untuk semua masalah teknologi Anda.</p>
            </div>
        </section>

        <!-- Track Service Section -->
        <section class="py-16 lg:py-20 -mt-12">
            <div class="container mx-auto px-6">
                <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-xl p-8">
                    <div class="text-center mb-8">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="search" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800 mb-2">Lacak Status Service Anda</h2>
                        <p class="text-slate-500">Masukkan ID Service Anda untuk melihat progres perbaikan secara real-time.</p>
                    </div>

                    <form method="POST" action="layanan.php#hasil-lacak">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" name="service_code" placeholder="Contoh: SRV..."
                                class="w-full px-5 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="<?= htmlspecialchars($_POST['service_code'] ?? '') ?>">
                            <button type="submit" name="track_service"
                                class="bg-blue-600 text-white font-bold px-8 py-3 rounded-lg hover:bg-blue-700 transition w-full sm:w-auto">
                                <span class="flex items-center justify-center">
                                    <i data-lucide="search" class="w-5 h-5 mr-2"></i> Lacak Service
                                </span>
                            </button>
                        </div>
                    </form>

                    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['track_service'])): ?>
                        <div id="hasil-lacak" class="mt-10 border-t border-slate-200 pt-8">
                            <?php if ($tracked_repair && !empty($progress_history)): ?>
                                <?php $last_status_key = end($progress_history)['status']; ?>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                                    <div class="flex items-center mb-4">
                                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600 mr-3"></i>
                                        <h3 class="font-bold text-lg text-green-800">Service Ditemukan!</h3>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="text-green-600 font-semibold">ID Service:</span>
                                            <p class="font-bold"><?= htmlspecialchars($tracked_repair['kode_service']) ?></p>
                                        </div>
                                        <div>
                                            <span class="text-green-600 font-semibold">Perangkat:</span>
                                            <p class="font-bold"><?= htmlspecialchars($tracked_repair['nama_service']) ?></p>
                                        </div>
                                        <div>
                                            <span class="text-green-600 font-semibold">Status Terkini:</span>
                                            <p class="font-bold"><?= $status_map[$last_status_key][0] ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress Timeline -->
                                <div class="mb-8">
                                    <h4 class="font-bold text-lg text-slate-800 mb-6">Progres Perbaikan</h4>
                                    <div class="grid grid-cols-4 text-center text-xs font-medium text-slate-500">
                                        <?php
                                        $current_step = $status_map[$last_status_key][2];
                                        foreach ([1 => 'Diterima', 2 => 'Diagnosa', 3 => 'Perbaikan', 4 => 'Selesai'] as $step => $label):
                                            $state = 'pending';
                                            if ($step < $current_step) $state = 'completed';
                                            if ($step == $current_step) $state = 'active';
                                            $icon = ($state == 'completed') ? 'check' : ($step == 2 ? 'search' : ($step == 3 ? 'wrench' : 'package-check'));
                                        ?>
                                            <div class="progress-step <?= $state ?> relative">
                                                <div class="progress-step-circle">
                                                    <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                                                </div>
                                                <p class="mt-3 <?= ($state == 'active') ? 'font-bold text-blue-600' : '' ?>"><?= $label ?></p>
                                                <?php if ($step < 4): ?>
                                                    <div class="progress-step-line <?= ($step < $current_step) ? 'active' : '' ?>"></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Progress History -->
                                <div class="bg-slate-50 rounded-lg p-6">
                                    <h4 class="font-semibold text-slate-800 mb-4">Riwayat Progres:</h4>
                                    <div class="space-y-4">
                                        <?php foreach (array_reverse($progress_history) as $progress): ?>
                                            <div class="flex items-start bg-white p-4 rounded-lg">
                                                <div class="bg-blue-100 text-blue-600 rounded-full p-2 mr-4 flex-shrink-0">
                                                    <i data-lucide="<?= $status_map[$progress['status']][1] ?>" class="w-4 h-4"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <p class="font-semibold text-slate-800"><?= $status_map[$progress['status']][0] ?></p>
                                                        <span class="text-slate-400 text-sm"><?= formatDate($progress['created_at']) ?></span>
                                                    </div>
                                                    <p class="text-slate-600"><?= !empty($progress['catatan']) ? htmlspecialchars($progress['catatan']) : 'Status diperbarui oleh sistem.' ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div class="text-center bg-red-50 border border-red-200 text-red-700 p-6 rounded-lg">
                                    <i data-lucide="alert-circle" class="w-12 h-12 text-red-400 mx-auto mb-4"></i>
                                    <h3 class="font-bold text-lg mb-2">Service Tidak Ditemukan</h3>
                                    <p><?= $error_message ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="py-16 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Layanan Perbaikan Kami</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Solusi lengkap untuk semua kebutuhan perbaikan teknologi Anda dengan layanan profesional dan terpercaya.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="card-hover bg-slate-50 p-8 rounded-2xl text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="laptop" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Perbaikan Laptop</h3>
                        <p class="text-slate-600 mb-6">Service profesional untuk semua jenis kerusakan laptop dengan garansi dan teknisi berpengalaman.</p>
                        <ul class="text-sm text-slate-500 space-y-2">
                            <li>• Perbaikan hardware & software</li>
                            <li>• Upgrade RAM & SSD</li>
                            <li>• Cleaning & maintenance</li>
                            <li>• Recovery data</li>
                        </ul>
                    </div>

                    <div class="card-hover bg-slate-50 p-8 rounded-2xl text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="smartphone" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Perbaikan Smartphone</h3>
                        <p class="text-slate-600 mb-6">Solusi cepat untuk masalah smartphone dengan teknisi berpengalaman dan spare part original.</p>
                        <ul class="text-sm text-slate-500 space-y-2">
                            <li>• Ganti LCD & touchscreen</li>
                            <li>• Perbaikan charging port</li>
                            <li>• Software troubleshooting</li>
                            <li>• Water damage repair</li>
                        </ul>
                    </div>

                    <div class="card-hover bg-slate-50 p-8 rounded-2xl text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="monitor" class="w-8 h-8 text-purple-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Perbaikan PC Desktop</h3>
                        <p class="text-slate-600 mb-6">Maintenance dan perbaikan komputer desktop untuk performa optimal dan produktivitas maksimal.</p>
                        <ul class="text-sm text-slate-500 space-y-2">
                            <li>• Troubleshooting hardware</li>
                            <li>• Install OS & software</li>
                            <li>• Upgrade komponen</li>
                            <li>• Network setup</li>
                        </ul>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <a href="login.php" class="bg-blue-600 text-white font-bold px-8 py-4 rounded-lg hover:bg-blue-700 transition shadow-lg">
                        Ajukan Perbaikan Sekarang
                    </a>
                </div>
            </div>
        </section>

        <!-- Why Choose Us Section -->
        <section class="py-16 bg-slate-100">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Mengapa Memilih Layanan Kami?</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Kami berkomitmen memberikan pelayanan terbaik dengan standar kualitas tinggi.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="shield-check" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Garansi Resmi</h3>
                        <p class="text-slate-600 text-sm">Semua perbaikan dilengkapi garansi untuk memberikan ketenangan pikiran.</p>
                    </div>

                    <div class="text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="clock" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Proses Cepat</h3>
                        <p class="text-slate-600 text-sm">Estimasi waktu perbaikan yang akurat dan proses yang efisien.</p>
                    </div>

                    <div class="text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="users" class="w-8 h-8 text-purple-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Teknisi Ahli</h3>
                        <p class="text-slate-600 text-sm">Tim teknisi berpengalaman dan bersertifikat internasional.</p>
                    </div>

                    <div class="text-center">
                        <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="dollar-sign" class="w-8 h-8 text-orange-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Harga Transparan</h3>
                        <p class="text-slate-600 text-sm">Estimasi biaya yang jelas tanpa biaya tersembunyi.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-800 text-slate-400">
        <div class="container mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <i data-lucide="zap" class="w-6 h-6 text-blue-400"></i>
                        <span class="text-lg font-bold text-white">CRISP FORCE</span>
                    </div>
                    <p class="text-slate-400 mb-4">Solusi teknologi terdepan untuk masa depan yang lebih baik.</p>
                </div>

                <div>
                    <h3 class="font-bold text-white mb-4">Layanan</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Perbaikan Laptop</a></li>
                        <li><a href="#" class="hover:text-white transition">Perbaikan HP</a></li>
                        <li><a href="#" class="hover:text-white transition">Perbaikan PC</a></li>
                        <li><a href="#" class="hover:text-white transition">Konsultasi IT</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-white mb-4">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Lacak Service</a></li>
                        <li><a href="#" class="hover:text-white transition">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition">Panduan</a></li>
                        <li><a href="#" class="hover:text-white transition">Kontak</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-white mb-4">Kontak</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                            Jl. Teknologi No. 123, Jakarta
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                            (021) 1234-5678
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="mail" class="w-4 h-4 mr-2"></i>
                            info@crispforce.com
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-700 mt-8 pt-8 text-center">
                <p>&copy; 2024 CRISP FORCE. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu?.classList.toggle('hidden');
        });
    </script>
</body>

</html>