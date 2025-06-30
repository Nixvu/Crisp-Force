<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// Data untuk Tab "Akun Tim"
$akun_tim = $conn->query("SELECT id_user, nama_lengkap, email, no_hp, role FROM User WHERE role IN ('Admin', 'Sales', 'Marketing') AND deleted_at IS NULL ORDER BY created_at DESC");

// Data untuk Tab "Akun Pelanggan"
$akun_pelanggan = $conn->query("SELECT u.id_user, u.nama_lengkap, u.email, u.no_hp, u.role, c.origin
                               FROM User u 
                               JOIN Customer c ON u.id_user = c.id_user 
                               WHERE u.role = 'Customer' AND u.deleted_at IS NULL 
                               ORDER BY u.created_at DESC");

// Helper function untuk Role badge color
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'Admin': return 'bg-purple-100 text-purple-800';
        case 'Sales': return 'bg-blue-100 text-blue-800';
        case 'Marketing': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?>

<?php include '../../includes/header.php'; ?>

<script>
    document.getElementById('page-title').textContent = 'Manajemen Pengguna';
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

    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-slate-800">Manajemen Pengguna</h2>
        <button onclick="openModal('addTeamModal')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i> Tambah Akun Tim
        </button>
    </div>

    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button class="tab-button active group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="tim">
                <i data-lucide="users" class="mr-2"></i> Akun Tim
            </button>
            <button class="tab-button group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="pelanggan">
                <i data-lucide="user-circle" class="mr-2"></i> Akun Pelanggan
            </button>
        </nav>
    </div>

    <div>
        <div id="tim-tab-content" class="tab-content active">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Daftar Akun Tim Internal</h3>
                    <div class="relative w-64">
                        <i data-lucide="search" class="h-4 w-4 text-slate-400 absolute top-1/2 left-3 -translate-y-1/2"></i>
                        <input type="text" id="team-search" class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md sm:text-sm" placeholder="Cari akun tim...">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Info Tim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Peran</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200" id="team-table-body">
                           <?php if ($akun_tim->num_rows > 0): $i = 1; while($t = $akun_tim->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-center text-sm text-slate-500"><?= $i++ ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars($t['nama_lengkap']) ?></div>
                                    <div class="text-sm text-slate-500"><?= htmlspecialchars($t['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getRoleBadgeClass($t['role']) ?>"><?= $t['role'] ?></span></td>
                                <td class="px-6 py-4 text-center text-sm">
                                    <div class="flex justify-center space-x-2">
                                        <button onclick='editUser(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8') ?>)' class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-yellow-500 hover:bg-yellow-600"><i data-lucide="file-pen-line" class="w-4 h-4"></i></button>
                                        <a href="../actions/proses_pengguna.php?action=delete_user&id=<?= $t['id_user'] ?>" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700" onclick="return confirm('Yakin nonaktifkan user ini?')"><i data-lucide="user-x" class="w-4 h-4"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center py-10 text-slate-500">Tidak ada data akun tim.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="pelanggan-tab-content" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                 <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Daftar Akun Pelanggan</h3>
                    <div class="relative w-64">
                        <i data-lucide="search" class="h-4 w-4 text-slate-400 absolute top-1/2 left-3 -translate-y-1/2"></i>
                        <input type="text" id="customer-search" class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md sm:text-sm" placeholder="Cari akun pelanggan...">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Info Pelanggan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sumber</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200" id="customer-table-body">
                             <?php if ($akun_pelanggan->num_rows > 0): $i = 1; while($p = $akun_pelanggan->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-center text-sm text-slate-500"><?= $i++ ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars($p['nama_lengkap']) ?></div>
                                    <div class="text-sm text-slate-500"><?= htmlspecialchars($p['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $p['origin'] == 'platform' ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-800' ?>"><?= ucfirst(str_replace('_', ' ', $p['origin'])) ?></span></td>
                                <td class="px-6 py-4 text-center text-sm">
                                    <div class="flex justify-center space-x-2">
                                        <button onclick='editUser(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)' class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-yellow-500 hover:bg-yellow-600"><i data-lucide="file-pen-line" class="w-4 h-4"></i></button>
                                        <a href="../actions/proses_pengguna.php?action=delete_user&id=<?= $p['id_user'] ?>" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700" onclick="return confirm('Yakin nonaktifkan user ini?')"><i data-lucide="user-x" class="w-4 h-4"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center py-10 text-slate-500">Tidak ada data akun pelanggan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="fixed inset-0 overflow-y-auto hidden" id="addTeamModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="../actions/proses_pengguna.php" method="POST" id="addTeamForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-slate-900">Tambah Akun Tim Baru</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="action" value="add_tim">
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="nama_lengkap" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Email</label><input type="email" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="email" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">No. HP</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="no_hp" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Password</label><input type="password" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="password" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Peran (Role)</label><select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="role" required><option value="Sales">Sales</option><option value="Marketing">Marketing</option><option value="Admin">Admin</option></select></div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('addTeamModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="fixed inset-0 overflow-y-auto hidden" id="editUserModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="../actions/proses_pengguna.php" method="POST" id="editUserForm">
                 <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-slate-900">Edit Pengguna</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="id_user" id="edit_id_user">
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="nama_lengkap" id="edit_nama_lengkap" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Email</label><input type="email" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="email" id="edit_email" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">No. HP</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="no_hp" id="edit_no_hp" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Peran (Role)</label><select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm bg-slate-50" name="role" id="edit_role" required><option value="Customer">Customer</option><option value="Sales">Sales</option><option value="Marketing">Marketing</option><option value="Admin">Admin</option></select></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Password Baru (Opsional)</label><input type="password" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="password"><small class="text-xs text-slate-500">Kosongkan jika tidak ingin mengubah password.</small></div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan Perubahan</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('editUserModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<style>
/* Style untuk tab agar konsisten */
.tab-button { border-color: transparent; color: #64748b; }
.tab-button:hover { border-color: #cbd5e1; color: #334155; }
.tab-button.active { border-color: #3b82f6; color: #2563eb; }
.tab-button .lucide { color: #94a3b8; }
.tab-button:hover .lucide, .tab-button.active .lucide { color: #2563eb; }
</style>

<script>
lucide.createIcons();

// --- Modal Handling ---
function openModal(modalId) { document.getElementById(modalId).classList.remove('hidden'); }
function closeModal(modalId) { document.getElementById(modalId).classList.add('hidden'); }

// --- Tab Handling ---
const tabButtons = document.querySelectorAll('.tab-button');
const tabContents = document.querySelectorAll('.tab-content');
tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        const tabId = button.getAttribute('data-tab');
        
        tabButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        tabContents.forEach(content => {
            content.id === `${tabId}-tab-content` ? content.classList.remove('hidden') : content.classList.add('hidden');
        });
        lucide.createIcons();
    });
});

// --- Search Functionality ---
['team', 'customer'].forEach(prefix => {
    const searchInput = document.getElementById(`${prefix}-search`);
    if (searchInput) {
        searchInput.addEventListener('input', e => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll(`#${prefix}-table-body tr`);
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

// --- Edit User Function ---
function editUser(user) {
    document.getElementById('edit_id_user').value = user.id_user;
    document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_no_hp').value = user.no_hp;
    
    const roleSelect = document.getElementById('edit_role');
    roleSelect.value = user.role;

    // Logika untuk disable role select dipertahankan
    if (user.role === 'Customer') {
        roleSelect.disabled = true;
        roleSelect.classList.add('bg-slate-200'); // Tambahkan style disabled
    } else {
        roleSelect.disabled = false;
        roleSelect.classList.remove('bg-slate-200');
    }

    openModal('editUserModal');
}
</script>