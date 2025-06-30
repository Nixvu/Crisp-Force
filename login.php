<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isLoggedIn()) {
    header("Location: " . ($_SESSION['user_role'] == 'Admin' ? 'admin/dashboard.php' : strtolower($_SESSION['user_role']) . '/dashboard.php'));
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = login($email, $password);

    if ($result === 'success') {
        header("Location: " . ($_SESSION['user_role'] == 'Admin' ? 'admin/dashboard.php' : strtolower($_SESSION['user_role']) . '/dashboard.php'));
        exit();
    } elseif ($result === 'change_password') {
        header("Location: change_password.php");
        exit();
    } else {
        $error = 'Email atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CRISP FORCE</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F1F5F9;
            /* Sesuai dengan bg-slate-100 dari index.php */
        }
    </style>
</head>

<body class="text-slate-800">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white/90 backdrop-blur-sm shadow-sm sticky top-0 z-50">
            <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
                <a href="index.php" class="flex items-center space-x-2">
                    <img src="assets/images/Logobl.png" alt="CRISP FORCE Logo" class="h-auto w-auto">
                    <span class="text-xl font-extrabold text-slate-900">CRISP <br> FORCE</span>
                </a>
                <div class="hidden md:flex items-center space-x-8 text-sm font-semibold">
                    <a href="index.php" class="text-slate-600 hover:text-blue-600 transition">Beranda</a>
                    <a href="index.php#products" class="text-slate-600 hover:text-blue-600 transition">Katalog</a>
                    <a href="layanan.php" class="text-slate-600 hover:text-blue-600 transition">Layanan</a>
                    <a href="tentang.php" class="text-slate-600 hover:text-blue-600 transition">Tentang</a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="login.php" class="text-blue-600 font-bold text-sm">Masuk</a>
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
                    <a href="index.php" class="block text-slate-600">Beranda</a>
                    <a href="index.php#products" class="block text-slate-600">Katalog</a>
                    <a href="layanan.php" class="block text-slate-600">Layanan</a>
                    <a href="tentang.php" class="block text-slate-600">Tentang</a>
                    <div class="pt-4 border-t border-slate-200 space-y-2">
                        <a href="login.php" class="block text-blue-600 font-semibold">Masuk</a>
                        <a href="register.php" class="block bg-blue-600 text-white font-bold px-4 py-2 rounded-lg text-center">Daftar</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow container mx-auto p-6 md:p-8 flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden flex w-full max-w-5xl">
                <div class="hidden md:block w-1/2 bg-slate-100 p-12 relative">
                    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/images/branding_image.png');"></div>
                    <div class="relative z-10 flex flex-col items-center justify-center h-full bg-black bg-opacity-0 rounded-lg">
                        <a href="index.php" class="flex items-center space-x-3">
                            <img src="assets/images/logowh.png" alt="CRISP FORCE Logo" class="h-12 w-auto">
                            <span class="text-3xl font-extrabold text-white">CRISP<br>FORCE</span>
                        </a>
                    </div>
                </div>
                <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800 mb-2">Login</h2>
                        <p class="text-slate-500 mb-8">Gunakan akun anda untuk melanjutkan</p>
                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                                <span class="block sm:inline"><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>
                        <form method="POST" class="space-y-5">
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Alamat Email *</label>
                                <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="email">
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password *</label>
                                <input type="password" id="password" name="password" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password">
                                <div class="text-right mt-2">
                                    <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-800">Lupa Password?</a>
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors duration-300">
                                MASUK
                            </button>
                        </form>
                        <div class="mt-6 text-center text-sm text-slate-600">
                            Belum punya akun?
                        </div>
                        <a href="register.php" class="mt-2 block w-full bg-slate-200 text-slate-800 font-bold py-3 rounded-lg hover:bg-slate-300 transition-colors duration-300 text-center">
                            DAFTAR
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>

</html>