<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// Data untuk Tab "Semua Kampanye"
$semua_kampanye = $conn->query("SELECT c.*, u.nama_lengkap as marketing_name, a.nama_lengkap as approver_name FROM Campaign c JOIN User u ON c.id_marketing = u.id_user LEFT JOIN User a ON c.approved_by = a.id_user WHERE c.deleted_at IS NULL ORDER BY c.created_at DESC");

// Data untuk Tab "Menunggu Persetujuan"
$menunggu_persetujuan = $conn->query("SELECT c.*, u.nama_lengkap as marketing_name FROM Campaign c JOIN User u ON c.id_marketing = u.id_user WHERE c.status = 'menunggu_acc' AND c.deleted_at IS NULL ORDER BY c.created_at ASC");

// Data untuk Tab "Template"
$templates = $conn->query("SELECT t.*, u.nama_lengkap as creator_name FROM CampaignTemplate t JOIN User u ON t.created_by = u.id_user ORDER BY t.created_at DESC");

// Variabel untuk menentukan tab aktif
$active_tab = $_GET['tab'] ?? 'all';

// Helper function untuk status badge color
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'aktif':
            return 'bg-green-100 text-green-800';
        case 'selesai':
            return 'bg-blue-100 text-blue-800';
        case 'menunggu_acc':
            return 'bg-yellow-100 text-yellow-800';
        case 'ditolak':
            return 'bg-red-100 text-red-800';
        case 'draft':
            return 'bg-slate-100 text-slate-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<?php include '../../includes/header.php'; ?>

<script>
    document.getElementById('page-title').textContent = 'Manajemen Kampanye';
</script>

<div class="space-y-6">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <?= htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']); ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-slate-800">Manajemen Kampanye</h2>
        <div class="flex items-center space-x-2">
            <button onclick="openModal('templateModal'); resetTemplateForm();" class="inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-md shadow-sm text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i data-lucide="layout-template" class="w-4 h-4 mr-2"></i> Kelola Template
            </button>
            <button onclick="openModal('campaignModal')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Buat Kampanye
            </button>
        </div>
    </div>

    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button class="tab-button <?= $active_tab == 'all' ? 'active' : '' ?> group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="all">
                <i data-lucide="megaphone" class="mr-2"></i> Semua Kampanye
            </button>
            <button class="tab-button <?= $active_tab == 'approval' ? 'active' : '' ?> group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="approval">
                <i data-lucide="clock" class="mr-2"></i> Menunggu Persetujuan
                <?php if ($menunggu_persetujuan->num_rows > 0): ?>
                    <span class="ml-2 bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                        <?= $menunggu_persetujuan->num_rows ?>
                    </span>
                <?php endif; ?>
            </button>
            <button class="tab-button <?= $active_tab == 'template' ? 'active' : '' ?> group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm" data-tab="template">
                <i data-lucide="clipboard-list" class="mr-2"></i> Daftar Template
            </button>
        </nav>
    </div>

    <div>
        <div id="all-tab-content" class="tab-content <?= $active_tab == 'all' ? 'active' : 'hidden' ?>">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Daftar Semua Kampanye</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nama Kampanye</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Channel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tanggal Mulai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Dibuat Oleh</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php if ($semua_kampanye->num_rows > 0): $i = 1;
                                while ($c = $semua_kampanye->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 text-center text-sm text-slate-500"><?= $i++ ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900"><?= htmlspecialchars($c['nama_kampanye']) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= ucfirst(str_replace('_', ' ', $c['jenis_kampanye'])) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= formatDate($c['tanggal_mulai']) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($c['marketing_name']) ?></td>
                                        <td class="px-6 py-4 text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getStatusBadgeClass($c['status']) ?>"><?= ucfirst(str_replace('_', ' ', $c['status'])) ?></span></td>
                                        <td class="px-6 py-4 text-center text-sm">
                                            <button onclick='viewCampaign(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)' class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-slate-500">Belum ada kampanye yang dibuat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="approval-tab-content" class="tab-content <?= $active_tab == 'approval' ? 'active' : 'hidden' ?>">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Daftar Kampanye Menunggu Persetujuan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nama Kampanye</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Diajukan Oleh</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tanggal Diajukan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Channel</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php if ($menunggu_persetujuan->num_rows > 0): $i = 1;
                                mysqli_data_seek($menunggu_persetujuan, 0);
                                while ($a = $menunggu_persetujuan->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 text-center text-sm text-slate-500"><?= $i++ ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900"><?= htmlspecialchars($a['nama_kampanye']) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($a['marketing_name']) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= formatDate($a['created_at']) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= ucfirst(str_replace('_', ' ', $a['jenis_kampanye'])) ?></td>
                                        <td class="px-6 py-4 text-center text-sm">
                                            <button onclick='reviewCampaign(<?= htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8') ?>)' class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                                                <i data-lucide="search-check" class="w-4 h-4 mr-2"></i> Review
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-slate-500">Tidak ada kampanye yang menunggu persetujuan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="template-tab-content" class="tab-content <?= $active_tab == 'template' ? 'active' : 'hidden' ?>">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-700">Daftar Template Kampanye</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nama Template</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Subjek</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Dibuat Oleh</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php if ($templates->num_rows > 0): $i = 1;
                                mysqli_data_seek($templates, 0);
                                while ($temp = $templates->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 text-center text-sm text-slate-500"><?= $i++ ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900"><?= htmlspecialchars($temp['nama_template']) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($temp['subjek']) ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($temp['creator_name']) ?></td>
                                        <td class="px-6 py-4 text-center text-sm">
                                            <div class="flex justify-center space-x-2">
                                                <button onclick='editTemplate(<?= htmlspecialchars(json_encode($temp), ENT_QUOTES, 'UTF-8') ?>)' class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-yellow-500 hover:bg-yellow-600"><i data-lucide="file-pen-line" class="w-4 h-4"></i></button>
                                                <a href="../actions/proses_template_kampanye.php?action=delete&id=<?= $temp['id_template'] ?>" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700" onclick="return confirm('Yakin hapus template ini?')"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-slate-500">Belum ada template yang dibuat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed inset-0 overflow-y-auto hidden" id="campaignModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form action="../actions/proses_kampanye.php" method="POST" id="campaignForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-slate-900" id="campaignModalLabel">Buat Kampanye Baru</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Gunakan Template (Opsional)</label>
                            <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" onchange="applyTemplate(this.value)">
                                <option value="">Pilih Template</option>
                                <?php mysqli_data_seek($templates, 0);
                                while ($temp = $templates->fetch_assoc()): ?>
                                    <option value="<?= $temp['id_template'] ?>"><?= htmlspecialchars($temp['nama_template']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Nama Kampanye</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="nama_kampanye" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Subjek</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" id="campaign_subjek" name="subjek"></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi/Konten</label><textarea class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" id="campaign_deskripsi" name="deskripsi" rows="4" required></textarea></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-slate-700 mb-1">Jenis Kampanye</label><select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="jenis_kampanye">
                                    <option value="internal_platform">Platform Internal</option>
                                    <option value="email">Email</option>
                                    <option value="semua">Semua</option>
                                </select></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-1">Target Segmentasi</label><select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="target_segmentasi">
                                    <option value="semua">Semua</option>
                                    <option value="baru">Baru</option>
                                    <option value="loyal">Loyal</option>
                                    <option value="perusahaan">Perusahaan</option>
                                </select></div>
                        </div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai</label><input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" name="tanggal_mulai" required></div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('campaignModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="fixed inset-0 overflow-y-auto hidden" id="templateModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="templateForm" action="../actions/proses_template_kampanye.php" method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-slate-900" id="templateModalLabel">Buat Template Baru</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="action" id="template_action" value="add">
                        <input type="hidden" name="id_template" id="id_template">
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Nama Template</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" id="nama_template" name="nama_template" required></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Subjek</label><input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" id="subjek_template" name="subjek"></div>
                        <div><label class="block text-sm font-medium text-slate-700 mb-1">Konten</label><textarea class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm" id="konten_template" name="konten" rows="5" required></textarea></div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('templateModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="fixed inset-0 overflow-y-auto hidden" id="approvalModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-xl leading-6 font-bold text-slate-900" id="approvalModalTitle">Detail Kampanye</h3>
                <div class="mt-4 border-t border-b border-slate-200 divide-y divide-slate-200">
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">Nama Kampanye</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2" id="review_nama_kampanye"></dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">Subjek</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2" id="review_subjek"></dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">Konten / Deskripsi</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2 whitespace-pre-wrap" id="review_deskripsi"></dd>
                    </div>
                </div>

                <form id="approvalForm" action="../actions/proses_kampanye.php" method="POST" class="mt-4">
                    <input type="hidden" name="id_campaign" id="review_id_campaign">
                    <div id="rejection-reason-field" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alasan Penolakan (Wajib diisi jika menolak)</label>
                        <textarea name="rejection_reason" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></textarea>
                    </div>
                </form>
            </div>
            <div id="approvalModalActions" class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="approvalForm" name="action" value="approve" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">Setujui</button>
                <button type="submit" form="approvalForm" name="action" value="reject" class="w-full mt-3 sm:mt-0 inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Tolak</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('approvalModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<style>
    /* Style untuk tab agar konsisten dengan halaman Perbaikan */
    .tab-button {
        border-color: transparent;
        color: #64748b;
        /* text-slate-500 */
    }

    .tab-button:hover {
        border-color: #cbd5e1;
        /* border-slate-300 */
        color: #334155;
        /* text-slate-700 */
    }

    .tab-button.active {
        border-color: #3b82f6;
        /* border-blue-500 */
        color: #2563eb;
        /* text-blue-600 */
    }

    .tab-button .lucide {
        color: #94a3b8;
        /* text-slate-400 */
    }

    .tab-button:hover .lucide,
    .tab-button.active .lucide {
        color: #2563eb;
        /* text-blue-600 */
    }
</style>

<script>
    lucide.createIcons();

    // --- Modal Handling ---
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // --- Tab Handling ---
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            history.pushState(null, null, `?tab=${tabId}`); // Update URL

            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            tabContents.forEach(content => {
                content.id === `${tabId}-tab-content` ? content.classList.remove('hidden') : content.classList.add('hidden');
            });
            lucide.createIcons();
        });
    });

    // --- Campaign Review Logic ---
    function viewCampaign(campaign) {
        document.getElementById('review_nama_kampanye').innerText = campaign.nama_kampanye;
        document.getElementById('review_subjek').innerText = campaign.subjek || '-';
        document.getElementById('review_deskripsi').innerText = campaign.deskripsi || '-';

        // Hide action form and buttons for view-only mode
        document.getElementById('approvalForm').classList.add('hidden');
        document.getElementById('approvalModalActions').classList.add('hidden');
        document.getElementById('approvalModalTitle').innerText = "Detail Kampanye";
        openModal('approvalModal');
    }

    function reviewCampaign(campaign) {
        viewCampaign(campaign); // Reuse view logic to populate data

        document.getElementById('review_id_campaign').value = campaign.id_campaign;
        document.getElementById('approvalForm').classList.remove('hidden');
        document.getElementById('approvalModalActions').classList.remove('hidden');
        document.getElementById('approvalModalTitle').innerText = "Review & Persetujuan Kampanye";

        // Logic for reject button
        const rejectBtn = document.querySelector('#approvalModalActions button[value="reject"]');
        const reasonField = document.getElementById('rejection-reason-field');
        rejectBtn.onclick = (e) => {
            reasonField.classList.remove('hidden');
            const reasonInput = reasonField.querySelector('textarea');
            if (e.isTrusted && reasonInput.value.trim() === '') {
                e.preventDefault();
                reasonInput.focus();
                alert('Mohon isi alasan penolakan.');
            }
        };
    }


    // --- Template Logic ---
    function resetTemplateForm() {
        document.getElementById('templateForm').reset();
        document.getElementById('templateModalLabel').innerText = 'Buat Template Baru';
        document.getElementById('template_action').value = 'add';
        document.getElementById('id_template').value = '';
    }

    function editTemplate(template) {
        resetTemplateForm();
        document.getElementById('templateModalLabel').innerText = 'Edit Template';
        document.getElementById('template_action').value = 'edit';
        document.getElementById('id_template').value = template.id_template;
        document.getElementById('nama_template').value = template.nama_template;
        document.getElementById('subjek_template').value = template.subjek;
        document.getElementById('konten_template').value = template.konten;
        openModal('templateModal');
    }

    function applyTemplate(templateId) {
        if (!templateId) {
            document.getElementById('campaign_subjek').value = '';
            document.getElementById('campaign_deskripsi').value = '';
            return;
        }
        fetch(`../actions/proses_template_kampanye.php?action=get_content&id=${templateId}`)
            .then(response => {
                if (!response.ok) throw new Error('Template not found');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    document.getElementById('campaign_subjek').value = data.subjek;
                    document.getElementById('campaign_deskripsi').value = data.konten;
                }
            })
            .catch(err => alert('Gagal memuat template: ' + err.message));
    }
</script>