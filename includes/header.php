<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';

// Get current page URL
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRISP FORCE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F1F5F9;
        }

        .dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .dropdown-content.show {
            max-height: 500px;
        }

        .dropdown-toggle i[data-lucide="chevron-down"] {
            transition: transform 0.3s ease;
        }

        .sidebar-link.active {
            background-color: #334155 !important;
            color: white !important;
            font-weight: 600;
        }

        .sidebar-submenu-link.active {
            color: white !important;
            font-weight: 500;
        }

        .dropdown-toggle.active {
            background-color: #1e293b !important;
            color: white !important;
        }
    </style>
</head>

<body class="text-slate-800">
    <div class="flex h-screen bg-white">
        <aside id="sidebar-nav" class="w-64 flex-shrink-0 bg-slate-800 text-slate-300 flex-col p-4 hidden md:flex">
            <div class="h-20 flex items-center justify-center mb-6">
                <a href="/" class="flex items-center space-x-2">
                    <i data-lucide="zap" class="w-7 h-7 text-blue-400"></i>
                    <span class="text-xl font-extrabold text-white">CRISP FORCE</span>
                </a>
            </div>

            <nav class="flex-1 flex flex-col space-y-1 text-sm">
                <?php if ($userRole == 'Customer'): ?>
                    <!-- Menu Customer -->
                    <a href="/customer/dashboard.php"
                        class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="/customer/products.php"
                        class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= ($currentPage == 'products.php') ? 'active' : '' ?>">
                        <i data-lucide="shopping-basket" class="w-5 h-5"></i>
                        <span>Produk</span>
                    </a>
                    <a href="/customer/repairs.php"
                        class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= ($currentPage == 'repairs.php') ? 'active' : '' ?>">
                        <i data-lucide="tool-case" class="w-5 h-5"></i>
                        <span>Perbaikan</span>
                    </a>
                    <a href="/customer/transactions.php"
                        class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= ($currentPage == 'transactions.php') ? 'active' : '' ?>">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                        <span>Transaksi</span>
                    </a>

                <?php elseif ($userRole == 'Admin'): ?>
                    <!-- Menu Admin -->
                    <a href="/admin/dashboard.php"
                        class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Dropdown Sales -->
                    <div class="dropdown-group">
                        <button class="dropdown-toggle w-full flex items-center justify-between space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= (in_array($currentPage, ['transaksi.php', 'produk.php', 'perbaikan.php', 'transaksi_baru.php'])) ? 'active' : '' ?>">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                                <span>Sales</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                        </button>
                        <div class="dropdown-content pl-7 space-y-1 <?= (in_array($currentPage, ['transaksi.php', 'produk.php', 'perbaikan.php', 'transaksi_baru.php'])) ? 'show' : '' ?>">
                            <a href="/admin/sales/transaksi.php"
                                class="sidebar-submenu-link block px-4 py-2 rounded-lg hover:text-white hover:bg-slate-700/50 <?= ($currentPage == 'transaksi.php' || $currentPage == 'transaksi_baru.php') ? 'active' : '' ?>">Transaksi</a>
                            <a href="/admin/sales/produk.php"
                                class="sidebar-submenu-link block px-4 py-2 rounded-lg hover:text-white hover:bg-slate-700/50 <?= ($currentPage == 'produk.php') ? 'active' : '' ?>">Produk</a>
                            <a href="/admin/sales/perbaikan.php"
                                class="sidebar-submenu-link block px-4 py-2 rounded-lg hover:text-white hover:bg-slate-700/50 <?= ($currentPage == 'perbaikan.php') ? 'active' : '' ?>">Perbaikan</a>
                        </div>
                    </div>

                    <!-- Dropdown Marketing -->
                    <div class="dropdown-group">
                        <button class="dropdown-toggle w-full flex items-center justify-between space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= (in_array($currentPage, ['kampanye.php', 'pelanggan.php'])) ? 'active' : '' ?>">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="megaphone" class="w-5 h-5"></i>
                                <span>Marketing</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                        </button>
                        <div class="dropdown-content pl-7 space-y-1 <?= (in_array($currentPage, ['kampanye.php', 'pelanggan.php'])) ? 'show' : '' ?>">
                            <a href="/admin/marketing/kampanye.php"
                                class="sidebar-submenu-link block px-4 py-2 rounded-lg hover:text-white hover:bg-slate-700/50 <?= ($currentPage == 'kampanye.php') ? 'active' : '' ?>">Kampanye</a>
                            <a href="/admin/marketing/pelanggan.php"
                                class="sidebar-submenu-link block px-4 py-2 rounded-lg hover:text-white hover:bg-slate-700/50 <?= ($currentPage == 'pelanggan.php') ? 'active' : '' ?>">Pelanggan</a>
                        </div>
                    </div>

                    <!-- Dropdown Manajemen -->
                    <div class="dropdown-group">
                        <button class="dropdown-toggle w-full flex items-center justify-between space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= (in_array($currentPage, ['pengguna.php', 'sistem.php'])) ? 'active' : '' ?>">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="briefcase-business" class="w-5 h-5"></i>
                                <span>Manajemen</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                        </button>
                        <div class="dropdown-content pl-7 space-y-1 <?= (in_array($currentPage, ['pengguna.php', 'sistem.php'])) ? 'show' : '' ?>">
                            <a href="/admin/manajemen/pengguna.php"
                                class="sidebar-submenu-link block px-4 py-2 rounded-lg hover:text-white hover:bg-slate-700/50 <?= ($currentPage == 'pengguna.php') ? 'active' : '' ?>">Pengguna</a>
                            <a href="/admin/manajemen/sistem.php"
                                class="sidebar-submenu-link block px-4 py-2 rounded-lg hover:text-white hover:bg-slate-700/50 <?= ($currentPage == 'sistem.php') ? 'active' : '' ?>">Sistem</a>
                        </div>
                    </div>

                <?php elseif ($userRole == 'Sales'): ?>
                    <!-- Menu Sales (to be implemented) -->
                <?php elseif ($userRole == 'Marketing'): ?>
                    <!-- Menu Marketing (to be implemented) -->
                <?php endif; ?>

                <!-- Account Settings -->
                <div class="mt-auto">
                    <?php
                    $settings_path = "/customer/pengaturan.php";
                    if ($userRole == 'Admin') $settings_path = "/admin/pengaturan.php";
                    ?>
                    <a href="<?= $settings_path ?>"
                        class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors <?= ($currentPage == 'pengaturan.php') ? 'active' : '' ?>">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        <span>Pengaturan</span>
                    </a>
                    <a href="/logout.php"
                        class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col">
            <header class="h-20 bg-white flex items-center justify-between px-8 border-b border-slate-200 flex-shrink-0">
                <div class="flex-1 flex justify-end items-center space-x-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Hi, <?php echo htmlspecialchars($userName); ?></p>
                        <p class="text-sm font-semibold text-slate-400"><?php echo htmlspecialchars($userRole); ?></p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-600">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <div id="main-content" class="flex-1 p-6 md:p-8 bg-slate-100 overflow-y-auto">