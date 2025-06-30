<?php
// PHP SCRIPT UNTUK MENGAMBIL DATA - TETAP DIPERTAHANKAN
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get featured products (6 produk)
$sql_products = "SELECT * FROM Product WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 6";
$featured_products = $conn->query($sql_products);

// Note: Query untuk campaign tidak digunakan di desain baru, namun tetap disimpan jika diperlukan.
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F8FAFC;
            /* Warna latar belakang sesuai desain */
            color: #1E293B;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.07), 0 4px 6px -4px rgb(0 0 0 / 0.07);
        }
    </style>
</head>

<body class="bg-slate-50">

    <header class="bg-white/90 backdrop-blur-sm shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2">
                <img src="assets/images/Logobl.png" alt="CRISP FORCE Logo" class="h-auto w-auto"></i>
                <span class="text-xl font-extrabold text-slate-900">CRISP <br> FORCE</span>
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
        <section class="relative bg-slate-800 text-white">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/images/hero-background.png');"></div>
            <div class="absolute inset-0 bg-slate-900/70"></div>

            <div class="relative container mx-auto px-6 py-28 md:py-40">
                <div class="max-w-3xl">
                    <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-6">
                        Solusi Lengkap Kebutuhan Teknologi Dan Komputer Anda
                    </h1>
                    <p class="text-lg text-slate-300 mb-8 leading-relaxed">
                        Produk original dengan harga kompetitif dan layanan service profesional dengan garansi resmi.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="#products" class="bg-blue-600 text-white font-bold px-8 py-3 rounded-lg hover:bg-blue-700 transition text-center shadow-lg">
                            Jelajahi Produk
                        </a>
                        <a href="layanan.php" class="border-2 border-slate-400 text-white font-bold px-8 py-3 rounded-lg hover:bg-white hover:text-slate-800 transition text-center">
                            Hubungi Kami
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">KENAPA PILIH KAMI</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Kami berkomitmen memberikan pelayanan dan produk terbaik untuk Anda.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="bg-white p-8 rounded-xl border border-slate-200 text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="package-check" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">PRODUK ORIGINAL</h3>
                        <p class="text-slate-600 text-sm">Semua produk yang kami jual dijamin original dan bergaransi resmi.</p>
                    </div>
                    <div class="bg-white p-8 rounded-xl border border-slate-200 text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="award" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">GARANSI RESMI</h3>
                        <p class="text-slate-600 text-sm">Dapatkan ketenangan dengan garansi resmi untuk setiap pembelian produk.</p>
                    </div>
                    <div class="bg-white p-8 rounded-xl border border-slate-200 text-center">
                        <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="shield-check" class="w-8 h-8 text-red-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">KUALITAS TERJAMIN</h3>
                        <p class="text-slate-600 text-sm">Produk telah melewati kontrol kualitas untuk kepuasan pelanggan.</p>
                    </div>
                    <div class="bg-white p-8 rounded-xl border border-slate-200 text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide-headphones" class="w-8 h-8 text-purple-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">LAYANAN PURNA JUAL</h3>
                        <p class="text-slate-600 text-sm">Dukungan penuh setelah pembelian untuk kenyamanan Anda.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="products" class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">PRODUK UNGGULAN KAMI</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Temukan berbagai produk teknologi terbaru dengan kualitas terbaik.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php while ($product = $featured_products->fetch_assoc()): ?>
                        <div class="bg-white rounded-xl border border-slate-200 text-center overflow-hidden card-hover">
                            <div class="h-56 bg-slate-100 overflow-hidden">
                                <img src="<?php echo $product['gambar_url'] ? 'assets/uploads/' . htmlspecialchars($product['gambar_url']) : 'assets/images/product-placeholder.png'; ?>" alt="<?php echo htmlspecialchars($product['nama_product']); ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="p-6">
                                <h3 class="font-bold text-lg text-slate-800 mb-2 truncate"><?php echo htmlspecialchars($product['nama_product']); ?></h3>
                                <h4 class="text-xl font-bold text-blue-600"><?php echo formatCurrency($product['harga']); ?></h4>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="text-center mt-16">
                    <a href="login.php" class="bg-slate-800 text-white font-bold px-8 py-3 rounded-lg hover:bg-slate-900 transition">
                        Lihat Semua Produk
                    </a>
                </div>
            </div>
        </section>

        <section class="py-20">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="rounded-xl overflow-hidden shadow-lg">
                        <img src="assets/images/service-technician.png" alt="Layanan Service Profesional" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">LAYANAN SERVICE PROFESIONAL</h2>
                        <p class="text-slate-600 mb-8">Tim teknisi berpengalaman dan bersertifikat kami siap membantu menyelesaikan semua masalah perangkat Anda dengan cepat dan efisien.</p>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-blue-600 mr-3"></i>
                                <span class="text-slate-800 font-semibold">Diagnosa Gratis & Akurat</span>
                            </li>
                            <li class="flex items-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-blue-600 mr-3"></i>
                                <span class="text-slate-800 font-semibold">Perbaikan Hardware & Software</span>
                            </li>
                            <li class="flex items-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-blue-600 mr-3"></i>
                                <span class="text-slate-800 font-semibold">Garansi Service Terpercaya</span>
                            </li>
                        </ul>
                        <a href="layanan.php" class="bg-slate-800 text-white font-bold px-8 py-3 rounded-lg hover:bg-slate-900 transition">
                            Pelajari Selengkapnya
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">APA KATA MEREKA</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">Produk yang baik didukung oleh kepuasan pelanggan setia kami.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-slate-50 border border-slate-200 p-8 rounded-xl">
                        <div class="flex text-yellow-400 mb-4">
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i>
                        </div>
                        <p class="text-slate-600 mb-6">"Pelayanannya sangat memuaskan, perbaikan laptop saya cepat selesai dan sekarang berfungsi normal kembali. Recommended!"</p>
                        <div>
                            <p class="font-bold text-slate-900">Ahmad Subarjo</p>
                            <p class="text-sm text-slate-500">Mahasiswa</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 p-8 rounded-xl">
                        <div class="flex text-yellow-400 mb-4">
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i>
                        </div>
                        <p class="text-slate-600 mb-6">"Beli laptop di sini dapat produk original, harganya juga kompetitif. Prosesnya mudah dan cepat sampai. Terima kasih Crisp Force!"</p>
                        <div>
                            <p class="font-bold text-slate-900">Siti Wulandari</p>
                            <p class="text-sm text-slate-500">Graphic Designer</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 p-8 rounded-xl">
                        <div class="flex text-yellow-400 mb-4">
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i><i data-lucide="star" class="w-5 h-5 fill-current"></i>
                        </div>
                        <p class="text-slate-600 mb-6">"Konsultasi IT dengan tim Crisp Force sangat membantu. Mereka memberikan solusi yang tepat untuk kebutuhan bisnis saya."</p>
                        <div>
                            <p class="font-bold text-slate-900">Joko Mulyono</p>
                            <p class="text-sm text-slate-500">Pengusaha UKM</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-slate-900 text-slate-400">
        <div class="container mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <img src="assets/images/logowh.png" alt="CRISP FORCE Logo" class="h-10 w-auto">
                        <span class="text-lg font-bold text-white">CRISP FORCE</span>
                    </div>
                    <p class="text-slate-400 mb-4">Solusi teknologi terdepan untuk masa depan yang lebih baik.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-slate-400 hover:text-white transition"><i data-lucide="facebook" class="w-5 h-5"></i></a>
                        <a href="#" class="text-slate-400 hover:text-white transition"><i data-lucide="twitter" class="w-5 h-5"></i></a>
                        <a href="#" class="text-slate-400 hover:text-white transition"><i data-lucide="instagram" class="w-5 h-5"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-white mb-4">Navigasi</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="#products" class="hover:text-white transition">Katalog</a></li>
                        <li><a href="layanan.php" class="hover:text-white transition">Layanan</a></li>
                        <li><a href="tentang.php" class="hover:text-white transition">Tentang</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-white mb-4">Layanan Kami</h3>
                    <ul class="space-y-2">
                        <li><a href="layanan.php" class="hover:text-white transition">Service Laptop</a></li>
                        <li><a href="layanan.php" class="hover:text-white transition">Service Komputer & PC</a></li>
                        <li><a href="layanan.php" class="hover:text-white transition">Konsultasi IT</a></li>
                        <li><a href="layanan.php" class="hover:text-white transition">Maintenance</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-white mb-4">Kontak Kami</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start"><i data-lucide="map-pin" class="w-4 h-4 mr-2 mt-1 flex-shrink-0"></i>Jl. Telekomunikasi No. 1, Bandung</li>
                        <li class="flex items-start"><i data-lucide="phone" class="w-4 h-4 mr-2 mt-1 flex-shrink-0"></i>(022) 7564-108</li>
                        <li class="flex items-start"><i data-lucide="mail" class="w-4 h-4 mr-2 mt-1 flex-shrink-0"></i>info@crispforce.com</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800 mt-8 pt-8 text-center">
                <p>&copy; <?php echo date("Y"); ?> CRISP FORCE. Semua hak dilindungi.</p>
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
            anchor.addEventListener('click', function(e) {
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