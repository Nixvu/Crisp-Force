<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// Mengambil data pengaturan
$profile = $conn->query("SELECT * FROM BusinessProfile WHERE id = 1")->fetch_assoc();
$smtp = $conn->query("SELECT * FROM SmtpSettings WHERE id = 1")->fetch_assoc();
?>

<?php include '../../includes/header.php'; ?>

<script>
    document.getElementById('page-title').textContent = 'Manajemen Sistem';
</script>

<div class="space-y-6">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
            <div class="flex items-center"><i data-lucide="check-circle" class="w-5 h-5 mr-2"></i><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
            <div class="flex items-center"><i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
        </div>
    <?php endif; ?>

    <h2 class="text-2xl font-bold text-slate-800">Manajemen Sistem</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <form action="../actions/proses_sistem.php" method="POST">
                    <input type="hidden" name="action" value="save_smtp">
                    <div class="border-b border-slate-200 pb-4 mb-6">
                        <h3 class="text-lg font-semibold leading-6 text-slate-900">Pengaturan Email (SMTP)</h3>
                        <p class="mt-1 text-sm text-slate-500">Konfigurasi server email untuk mengirim kampanye dan notifikasi sistem.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700">Server SMTP</label><input type="text" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="server" value="<?= htmlspecialchars($smtp['server'] ?? '') ?>" required></div>
                        <div><label class="block text-sm font-medium text-slate-700">Port</label><input type="number" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="port" value="<?= htmlspecialchars($smtp['port'] ?? '587') ?>" required></div>
                        <div><label class="block text-sm font-medium text-slate-700">Keamanan</label><select class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="security"><option value="tls" <?= ($smtp['security'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= ($smtp['security'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option></select></div>
                        <div><label class="block text-sm font-medium text-slate-700">Username</label><input type="text" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="username" value="<?= htmlspecialchars($smtp['username'] ?? '') ?>" required></div>
                        <div><label class="block text-sm font-medium text-slate-700">Password</label><input type="password" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="password" placeholder="Isi untuk mengubah"></div>
                        <div class="md:col-span-2"><hr></div>
                        <div><label class="block text-sm font-medium text-slate-700">Nama Pengirim</label><input type="text" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="sender_name" value="<?= htmlspecialchars($smtp['sender_name'] ?? '') ?>" required></div>
                        <div><label class="block text-sm font-medium text-slate-700">Email Pengirim</label><input type="email" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="sender_email" value="<?= htmlspecialchars($smtp['sender_email'] ?? '') ?>" required></div>
                    </div>

                    <div class="pt-6 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                 <h3 class="text-lg font-semibold leading-6 text-slate-900">Status & Uji Coba</h3>
                 <p class="mt-1 text-sm text-slate-500 mb-4">Pastikan pengaturan SMTP Anda benar sebelum digunakan.</p>

                <div id="smtp-status-wrapper" class="p-4 rounded-md text-sm">
                    </div>
                 
                 <div class="mt-4">
                     <button id="test-smtp-btn" type="button" class="w-full inline-flex justify-center items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-md shadow-sm text-slate-700 bg-white hover:bg-slate-50">
                         <i data-lucide="send" class="w-4 h-4 mr-2"></i> Uji Koneksi SMTP
                     </button>
                 </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
lucide.createIcons();

document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('test-smtp-btn');
    const statusWrapper = document.getElementById('smtp-status-wrapper');

    // Fungsi untuk menampilkan status
    function setStatus(type, message) {
        statusWrapper.innerHTML = ''; // Kosongkan dulu
        let bgColor, textColor, icon;

        if(type === 'loading') {
            bgColor = 'bg-yellow-50'; textColor = 'text-yellow-700'; icon = 'loader-2';
        } else if (type === 'success') {
            bgColor = 'bg-green-50'; textColor = 'text-green-700'; icon = 'check-circle';
        } else if (type === 'error') {
            bgColor = 'bg-red-50'; textColor = 'text-red-700'; icon = 'alert-triangle';
        } else {
             bgColor = 'bg-slate-50'; textColor = 'text-slate-700'; icon = 'info';
        }

        statusWrapper.className = `p-4 rounded-md text-sm ${bgColor} ${textColor}`;
        const iconElement = document.createElement('i');
        iconElement.dataset.lucide = icon;
        if(type === 'loading') iconElement.classList.add('animate-spin');

        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex items-start';
        messageDiv.innerHTML = `<div class="flex-shrink-0 mr-2"></div><div class="flex-grow">${message}</div>`;
        messageDiv.querySelector('.flex-shrink-0').appendChild(iconElement);
        
        statusWrapper.appendChild(messageDiv);
        lucide.createIcons();
    }
    
    // Set status awal
    setStatus('default', 'Status koneksi belum diuji. Klik tombol di bawah untuk memulai.');

    testBtn.addEventListener('click', function() {
        setStatus('loading', 'Sedang mencoba mengirim email tes, mohon tunggu...');
        testBtn.disabled = true;

        fetch('../actions/proses_sistem.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=test_smtp'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setStatus('success', data.message);
            } else {
                setStatus('error', '<strong>Gagal terhubung.</strong><br>' + data.message);
            }
        })
        .catch(error => {
            setStatus('error', 'Terjadi kesalahan saat menghubungi server. Periksa koneksi atau log konsol.');
            console.error('Error:', error);
        })
        .finally(() => {
            testBtn.disabled = false;
        });
    });
});
</script>