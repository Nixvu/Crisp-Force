<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get featured products
$sql_products = "SELECT * FROM Product WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 6";
$featured_products = $conn->query($sql_products);

// Get active campaigns
$sql_campaigns = "SELECT * FROM Campaign WHERE status = 'aktif' ORDER BY tanggal_mulai DESC LIMIT 3";
$campaigns = $conn->query($sql_campaigns);
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRISP FORCE - Solusi Teknologi Terdepan</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F1F5F9;
            color: #1E293B;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
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
                <a href="index.php" class="text-blue-600">Beranda</a>
                <a href="#products" class="text-slate-600 hover:text-blue-600 transition">Katalog</a>
                <a href="layanan.php" class="text-slate-600 hover:text-blue-600 transition">Layanan</a>
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

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-slate-200">
            <div class="px-6 py-4 space-y-4">
                <a href="index.php" class="block text-blue-600 font-semibold">Beranda</a>
                <a href="#products" class="block text-slate-600">Katalog</a>
                <a href="layanan.php" class="block text-slate-600">Layanan</a>
                <a href="tentang.php" class="block text-slate-600">Tentang</a>
                <div class="pt-4 border-t border-slate-200 space-y-2">
                    <a href="login.php" class="block text-slate-600 font-semibold">Masuk</a>
                    <a href="register.php" class="block bg-blue-600 text-white font-bold px-4 py-2 rounded-lg text-center">Daftar</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero-gradient text-white">
            <div class="container mx-auto px-6 py-20 md:py-32">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-6">
                            Solusi Teknologi <span class="text-yellow-300">Terdepan</span> untuk Masa Depan
                        </h1>
                        <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                            Kami menyediakan produk teknologi berkualitas tinggi dan layanan perbaikan profesional untuk semua kebutuhan digital Anda.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#products" class="bg-white text-blue-600 font-bold px-8 py-4 rounded-lg hover:bg-blue-50 transition text-center shadow-lg">
                                Lihat Produk
                            </a>
                            <a href="layanan.php" class="border-2 border-white text-white font-bold px-8 py-4 rounded-lg hover:bg-white hover:text-blue-600 transition text-center">
                                Layanan Kami
                            </a>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="animate-float">
                            <i data-lucide="laptop" class="w-64 h-64 text-blue-200 mx-auto"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Mengapa Memilih Kami?</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Kami berkomitmen memberikan pelayanan terbaik dengan teknologi terdepan dan tim profesional berpengalaman.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="card-hover bg-slate-50 p-8 rounded-2xl text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="shield-check" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Kualitas Terjamin</h3>
                        <p class="text-slate-600">Semua produk dan layanan kami telah melalui kontrol kualitas ketat untuk memastikan kepuasan pelanggan.</p>
                    </div>

                    <div class="card-hover bg-slate-50 p-8 rounded-2xl text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="users" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Tim Profesional</h3>
                        <p class="text-slate-600">Teknisi berpengalaman dan bersertifikat siap membantu menyelesaikan masalah teknologi Anda.</p>
                    </div>

                    <div class="card-hover bg-slate-50 p-8 rounded-2xl text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="clock" class="w-8 h-8 text-purple-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Layanan Cepat</h3>
                        <p class="text-slate-600">Proses perbaikan yang efisien dan pengiriman produk yang cepat untuk memenuhi kebutuhan mendesak Anda.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products" class="py-20 bg-slate-100">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Produk Unggulan</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Temukan berbagai produk teknologi terbaru dengan kualitas terbaik dan harga kompetitif.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php while ($product = $featured_products->fetch_assoc()): ?>
                    <div class="card-hover bg-white rounded-2xl shadow-sm overflow-hidden">
                        <div class="h-48 bg-slate-200 overflow-hidden">
                            <img src="<?php echo $product['gambar_url'] ? 'assets/uploads/' . htmlspecialchars($product['gambar_url']) : 'assets/images/product-placeholder.png'; ?>" 
                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                        </div>
                        <div class="p-6">
                            <div class="mb-3">
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full">
                                    <?php echo strtoupper(htmlspecialchars($product['category'])); ?>
                                </span>
                            </div>
                            <h3 class="font-bold text-lg text-slate-900 mb-2"><?php echo htmlspecialchars($product['nama_product']); ?></h3>
                            <div class="flex items-center mb-3">
                                <div class="flex text-yellow-400 mr-2">
                                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                    <i data-lucide="star" class="w-4 h-4 fill-current opacity-50"></i>
                                </div>
                                <span class="text-slate-500 text-sm">(4.5)</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <h4 class="text-xl font-bold text-blue-600"><?php echo formatCurrency($product['harga']); ?></h4>
                                <span class="text-sm text-slate-500">Stok: <?php echo $product['stok']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="text-center mt-12">
                    <a href="login.php" class="bg-blue-600 text-white font-bold px-8 py-4 rounded-lg hover:bg-blue-700 transition shadow-lg">
                        Lihat Semua Produk
                    </a>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Layanan Kami</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Solusi lengkap untuk semua kebutuhan teknologi Anda dengan layanan profesional dan terpercaya.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="card-hover text-center p-6">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="wrench" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Perbaikan Laptop</h3>
                        <p class="text-slate-600 text-sm">Service profesional untuk semua jenis kerusakan laptop dengan garansi.</p>
                    </div>

                    <div class="card-hover text-center p-6">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="smartphone" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Perbaikan HP</h3>
                        <p class="text-slate-600 text-sm">Solusi cepat untuk masalah smartphone dengan teknisi berpengalaman.</p>
                    </div>

                    <div class="card-hover text-center p-6">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="monitor" class="w-8 h-8 text-purple-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Perbaikan PC</h3>
                        <p class="text-slate-600 text-sm">Maintenance dan perbaikan komputer desktop untuk performa optimal.</p>
                    </div>

                    <div class="card-hover text-center p-6">
                        <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="settings" class="w-8 h-8 text-orange-600"></i>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">Konsultasi IT</h3>
                        <p class="text-slate-600 text-sm">Konsultasi teknologi untuk kebutuhan bisnis dan personal.</p>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <a href="layanan.php" class="bg-slate-800 text-white font-bold px-8 py-4 rounded-lg hover:bg-slate-900 transition">
                        Lihat Semua Layanan
                    </a>
                </div>
            </div>
        </section>

        <!-- Campaigns Section -->
        <?php if ($campaigns->num_rows > 0): ?>
        <section class="py-20 bg-slate-100">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Promo & Penawaran</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Jangan lewatkan penawaran menarik dan promo spesial untuk pelanggan setia kami.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <?php while ($campaign = $campaigns->fetch_assoc()): ?>
                    <div class="card-hover bg-gradient-to-br from-blue-600 to-purple-600 text-white p-8 rounded-2xl">
                        <div class="mb-4">
                            <i data-lucide="gift" class="w-12 h-12 text-blue-200"></i>
                        </div>
                        <h3 class="font-bold text-xl mb-4"><?php echo htmlspecialchars($campaign['nama_kampanye']); ?></h3>
                        <p class="text-blue-100 mb-6"><?php echo htmlspecialchars($campaign['deskripsi']); ?></p>
                        <?php if ($campaign['kode_promo']): ?>
                        <div class="bg-white/20 backdrop-blur-sm p-3 rounded-lg">
                            <span class="text-sm">Kode Promo:</span>
                            <span class="font-bold text-lg block"><?php echo htmlspecialchars($campaign['kode_promo']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CTA Section -->
        <section class="py-20 bg-slate-800 text-white">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold mb-4">Siap Memulai?</h2>
                <p class="text-xl text-slate-300 mb-8 max-w-2xl mx-auto">
                    Bergabunglah dengan ribuan pelanggan yang telah mempercayai layanan kami untuk solusi teknologi terbaik.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="register.php" class="bg-blue-600 text-white font-bold px-8 py-4 rounded-lg hover:bg-blue-700 transition shadow-lg">
                        Daftar Sekarang
                    </a>
                    <a href="layanan.php" class="border-2 border-white text-white font-bold px-8 py-4 rounded-lg hover:bg-white hover:text-slate-800 transition">
                        Lacak Service
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400">
        <div class="container mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <i data-lucide="zap" class="w-6 h-6 text-blue-400"></i>
                        <span class="text-lg font-bold text-white">CRISP FORCE</span>
                    </div>
                    <p class="text-slate-400 mb-4">Solusi teknologi terdepan untuk masa depan yang lebih baik.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-slate-400 hover:text-white transition">
                            <i data-lucide="facebook" class="w-5 h-5"></i>
                        </a>
                        <a href="#" class="text-slate-400 hover:text-white transition">
                            <i data-lucide="twitter" class="w-5 h-5"></i>
                        </a>
                        <a href="#" class="text-slate-400 hover:text-white transition">
                            <i data-lucide="instagram" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-white mb-4">Produk</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Laptop</a></li>
                        <li><a href="#" class="hover:text-white transition">Smartphone</a></li>
                        <li><a href="#" class="hover:text-white transition">Aksesoris</a></li>
                        <li><a href="#" class="hover:text-white transition">Komponen</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-white mb-4">Layanan</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Perbaikan</a></li>
                        <li><a href="#" class="hover:text-white transition">Konsultasi</a></li>
                        <li><a href="#" class="hover:text-white transition">Maintenance</a></li>
                        <li><a href="#" class="hover:text-white transition">Support</a></li>
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

            <div class="border-t border-slate-800 mt-8 pt-8 text-center">
                <p>&copy; 2024 CRISP FORCE. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>

</html>

