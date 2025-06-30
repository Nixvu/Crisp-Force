<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - CRISP FORCE</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F1F5F9; /* slate-100 */
            color: #1E293B; /* slate-800 */
        }
    </style>
</head>
<body class="bg-slate-100">

    <!-- =========== HEADER / NAVIGATION (Sama seperti halaman lain) =========== -->
    <header class="bg-white/90 backdrop-blur-sm shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="flex items-center space-x-2"><i data-lucide="zap" class="w-7 h-7 text-blue-600"></i><span class="text-xl font-extrabold text-slate-900">CRISP FORCE</span></a>
            <div class="hidden md:flex items-center space-x-8 text-sm font-semibold">
                <a href="index.php" class="text-slate-600 hover:text-blue-600 transition">Beranda</a>
                <a href="#" class="text-slate-600 hover:text-blue-600 transition">Katalog</a>
                <a href="layanan.php" class="text-slate-600 hover:text-blue-600 transition">Layanan</a>
                <a href="tentang.php" class="text-blue-600">Tentang</a>
            </div>
            <div class="hidden md:flex items-center space-x-4">
                <a href="#" class="text-slate-600 hover:text-blue-600 font-bold text-sm">Masuk</a>
                <a href="#" class="bg-slate-800 text-white font-bold px-5 py-2.5 rounded-lg hover:bg-slate-900 transition text-sm shadow-lg shadow-slate-500/20">Daftar</a>
            </div>
            <div class="md:hidden"><button><i data-lucide="menu" class="w-6 h-6"></i></button></div>
        </nav>
    </header>

    <main>
        <!-- =========== HERO SECTION =========== -->
        <section class="relative h-[50vh] w-full flex items-center justify-center text-center">
            <div class="absolute inset-0">
                <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=2070&auto=format&fit=crop" 
                     alt="Tim sedang berdiskusi di kantor" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-slate-900/60"></div>
            </div>
            <div class="relative z-10 text-white px-6">
                <h1 class="text-4xl md:text-5xl font-extrabold">Tentang CRISP FORCE</h1>
                <p class="mt-4 text-lg md:text-xl max-w-2xl mx-auto text-slate-200">
                    Misi kami adalah menjadi partner teknologi terpercaya Anda, menyediakan produk berkualitas dan layanan yang handal.
                </p>
            </div>
        </section>

        <!-- =========== OUR STORY SECTION =========== -->
        <section class="py-20 lg:py-24">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div class="order-2 lg:order-1">
                        <h2 class="text-sm font-bold uppercase tracking-widest text-blue-600">KISAH KAMI</h2>
                        <p class="mt-3 text-3xl md:text-4xl font-bold text-slate-900 mb-6">Berawal dari Gairah Teknologi</p>
                        <div class="text-slate-600 space-y-4">
                            <p>CRISP FORCE didirikan pada tahun 2020 oleh sekelompok sahabat yang memiliki gairah yang sama terhadap dunia teknologi dan komputer. Kami memulai dari sebuah workshop kecil dengan satu mimpi besar: menciptakan sebuah tempat di mana setiap orang bisa mendapatkan akses ke produk teknologi terbaik dan layanan perbaikan yang jujur dan transparan.</p>
                            <p>Kami melihat banyak orang kesulitan mencari produk original dan layanan service yang bisa dipercaya. Dari sanalah, kami bertekad untuk membangun CRISP FORCE sebagai jawaban atas masalah tersebut. Kami percaya bahwa teknologi harusnya memudahkan, bukan menyulitkan hidup.</p>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2 rounded-xl overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1521737852567-6949f3f9f2b5?q=80&w=2070&auto=format&fit=crop" alt="Tim pendiri CRISP FORCE" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </section>
        
        <!-- =========== OUR VALUES SECTION =========== -->
        <section class="py-20 lg:py-24 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-blue-600">PRINSIP KAMI</h2>
                    <p class="mt-3 text-3xl md:text-4xl font-bold text-slate-900">Nilai yang Kami Pegang Teguh</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Value Card 1 -->
                    <div class="text-center p-8">
                        <div class="bg-blue-100 text-blue-600 rounded-full p-4 w-16 h-16 mx-auto mb-5 flex items-center justify-center"><i data-lucide="gem" class="w-8 h-8"></i></div>
                        <h3 class="text-xl font-bold mb-2">Kualitas</h3>
                        <p class="text-slate-500 text-sm">Kami hanya menyediakan produk dan layanan dengan standar kualitas tertinggi. Kepuasan Anda adalah prioritas kami.</p>
                    </div>
                    <!-- Value Card 2 -->
                    <div class="text-center p-8">
                        <div class="bg-blue-100 text-blue-600 rounded-full p-4 w-16 h-16 mx-auto mb-5 flex items-center justify-center"><i data-lucide="handshake" class="w-8 h-8"></i></div>
                        <h3 class="text-xl font-bold mb-2">Integritas</h3>
                        <p class="text-slate-500 text-sm">Kejujuran dan transparansi adalah fondasi dari setiap interaksi kami dengan pelanggan.</p>
                    </div>
                    <!-- Value Card 3 -->
                    <div class="text-center p-8">
                        <div class="bg-blue-100 text-blue-600 rounded-full p-4 w-16 h-16 mx-auto mb-5 flex items-center justify-center"><i data-lucide="lightbulb" class="w-8 h-8"></i></div>
                        <h3 class="text-xl font-bold mb-2">Inovasi</h3>
                        <p class="text-slate-500 text-sm">Kami terus belajar dan beradaptasi untuk selalu memberikan solusi teknologi yang terkini dan terbaik.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========== MEET THE TEAM SECTION =========== -->
        <section class="py-20 lg:py-24">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-blue-600">TIM KAMI</h2>
                    <p class="mt-3 text-3xl md:text-4xl font-bold text-slate-900">Wajah di Balik CRISP FORCE</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Team Member 1 -->
                    <div class="text-center">
                        <img src="https://i.pravatar.cc/300?u=ceo" alt="CEO" class="w-32 h-32 rounded-full mx-auto mb-4 shadow-lg">
                        <h4 class="font-bold text-lg">Andi Wijaya</h4>
                        <p class="text-sm text-blue-600 font-semibold">Founder & CEO</p>
                    </div>
                     <!-- Team Member 2 -->
                    <div class="text-center">
                        <img src="https://i.pravatar.cc/300?u=cto" alt="CTO" class="w-32 h-32 rounded-full mx-auto mb-4 shadow-lg">
                        <h4 class="font-bold text-lg">Budi Santoso</h4>
                        <p class="text-sm text-blue-600 font-semibold">Head of Technology</p>
                    </div>
                     <!-- Team Member 3 -->
                    <div class="text-center">
                        <img src="https://i.pravatar.cc/300?u=marketing" alt="Marketing Head" class="w-32 h-32 rounded-full mx-auto mb-4 shadow-lg">
                        <h4 class="font-bold text-lg">Citra Ayu</h4>
                        <p class="text-sm text-blue-600 font-semibold">Marketing Manager</p>
                    </div>
                     <!-- Team Member 4 -->
                    <div class="text-center">
                        <img src="https://i.pravatar.cc/300?u=service" alt="Service Head" class="w-32 h-32 rounded-full mx-auto mb-4 shadow-lg">
                        <h4 class="font-bold text-lg">Rian Ardiansyah</h4>
                        <p class="text-sm text-blue-600 font-semibold">Lead Technician</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- =========== JOIN US SECTION =========== -->
        <section class="container mx-auto px-6 pb-20 lg:pb-24">
            <div class="bg-white p-12 md:p-16 rounded-2xl text-center shadow-lg border border-slate-200">
                 <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-4">Bergabunglah dengan Tim Kami</h2>
                 <p class="text-lg text-slate-600 max-w-2xl mx-auto mb-8">Kami selalu mencari talenta-talenta terbaik yang memiliki gairah di dunia teknologi. Lihat posisi yang tersedia dan mari berkembang bersama kami.</p>
                 <a href="#" class="bg-slate-800 text-white font-bold px-8 py-4 rounded-lg hover:bg-slate-900 transition duration-300 text-lg">
                    Lihat Karir
                </a>
            </div>
        </section>
    </main>

    <!-- =========== FOOTER (Sama seperti halaman lain) =========== -->
    <footer class="bg-slate-800 text-slate-400">
        <div class="container mx-auto px-6 py-16"><div class="grid grid-cols-1 md:grid-cols-4 gap-12 text-sm"><div><a href="#" class="flex items-center space-x-2 mb-4"><i data-lucide="zap" class="w-7 h-7 text-blue-400"></i><span class="text-xl font-extrabold text-white">CRISP FORCE</span></a><p class="pr-8">Solusi lengkap untuk semua kebutuhan teknologi dan komputer Anda.</p></div><div><h4 class="font-bold text-white mb-4 uppercase">Navigasi</h4><ul class="space-y-3"><li class="hover:text-white transition"><a href="#">Beranda</a></li><li class="hover:text-white transition"><a href="#">Katalog</a></li><li class="hover:text-white transition"><a href="#">Layanan</a></li><li class="hover:text-white transition"><a href="#">Tentang</a></li></ul></div><div><h4 class="font-bold text-white mb-4 uppercase">Layanan</h4><ul class="space-y-3"><li class="hover:text-white transition"><a href="#">Service Laptop</a></li><li class="hover:text-white transition"><a href="#">Service PC</a></li><li class="hover:text-white transition"><a href="#">Upgrade Hardware</a></li></ul></div><div><h4 class="font-bold text-white mb-4 uppercase">Kontak</h4><ul class="space-y-3"><li class="flex items-start"><i data-lucide="map-pin" class="w-4 h-4 mr-3 mt-1 flex-shrink-0"></i><span>Jl. Teknologi No. 123, Jakarta</span></li><li class="flex items-start"><i data-lucide="phone" class="w-4 h-4 mr-3 mt-1 flex-shrink-0"></i><span>(021) 1234-5678</span></li></ul></div></div></div>
        <div class="bg-slate-900 py-4"><div class="container mx-auto px-6 text-center text-xs"><p>&copy; 2025 CRISP FORCE. All Rights Reserved.</p></div></div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
