<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['Customer']);

$user_id = $_SESSION['user_id'];
$customer = getCustomerData($user_id);

// Get repair history
$sql_repairs = "SELECT sr.*, 
(SELECT status FROM ServiceProgress WHERE id_service = sr.id_service ORDER BY created_at DESC LIMIT 1) as latest_status
FROM ServiceRequest sr
WHERE sr.id_customer = ?
ORDER BY sr.tanggal_masuk DESC LIMIT 10";
$stmt_repairs = $conn->prepare($sql_repairs);
$stmt_repairs->bind_param("i", $customer['id_customer']);
$stmt_repairs->execute();
$repairs = $stmt_repairs->get_result();

// Get latest repair
$sql_latest_repair = "SELECT sr.*, sp.status as progress_status 
FROM ServiceRequest sr
LEFT JOIN (
SELECT id_service, status 
FROM ServiceProgress 
WHERE (id_service, created_at) IN (
SELECT id_service, MAX(created_at) 
FROM ServiceProgress 
GROUP BY id_service)
) sp ON sr.id_service = sp.id_service
WHERE sr.id_customer = ?
ORDER BY sr.tanggal_masuk DESC LIMIT 1";
$stmt_latest_repair = $conn->prepare($sql_latest_repair);
$stmt_latest_repair->bind_param("i", $customer['id_customer']);
$stmt_latest_repair->execute();
$latest_repair = $stmt_latest_repair->get_result()->fetch_assoc();

// Process new repair form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_repair'])) {
    $merk = sanitize($_POST['merk']);
    $model = sanitize($_POST['model']);
    $serial_no = sanitize($_POST['serial_no']);
    $kelengkapan = sanitize($_POST['kelengkapan']);
    $deskripsi = sanitize($_POST['deskripsi']);

    // Generate service code
    function generateServiceCode($conn) {
        $code = '';
        
        // Layer 1: Panggil stored procedure
        try {
            $stmt = $conn->prepare("CALL generate_service_code(?)");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Procedure Error: ".$e->getMessage());
        }
        
        // Layer 2: Generate manual jika procedure gagal
        if (empty($code)) {
            $code = 'SRV'.date('YmdHis').rand(100,999);
            error_log("Used Layer 2 Fallback: ".$code);
        }
        
        // Layer 3: Pastikan unik
        $is_unique = false;
        $retry = 0;
        
        while (!$is_unique && $retry < 3) {
            $check = $conn->prepare("SELECT id_service FROM ServiceRequest WHERE kode_service = ?");
            $check->bind_param("s", $code);
            $check->execute();
            
            if ($check->get_result()->num_rows === 0) {
                $is_unique = true;
            } else {
                $code = 'SRV'.uniqid();
                $retry++;
            }
        }
        
        return $code;
    }

    $service_code = generateServiceCode($conn);

    // Get a random sales user
    $sql_sales = "SELECT id_user FROM User WHERE role = 'Sales' ORDER BY RAND() LIMIT 1";
    $sales_result = $conn->query($sql_sales);
    $sales_id = $sales_result->fetch_assoc()['id_user'];

    // Insert service request
    $sql = "INSERT INTO ServiceRequest (id_customer, id_sales, kode_service, nama_service, merk, model, serial_no, kategori_barang, kelengkapan, deskripsi_kerusakan, tanggal_masuk) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'laptop', ?, ?, CURDATE())";
    $stmt = $conn->prepare($sql);
    $service_name = "Perbaikan " . $merk . " " . $model;
    $stmt->bind_param("iisssssss", $customer['id_customer'], $sales_id, $service_code, $service_name, $merk, $model, $serial_no, $kelengkapan, $deskripsi);

    if ($stmt->execute()) {
        $service_id = $stmt->insert_id;

        // Insert initial progress
        $sql_progress = "INSERT INTO ServiceProgress (id_service, status) VALUES (?, 'diterima_digerai')";
        $stmt_progress = $conn->prepare($sql_progress);
        $stmt_progress->bind_param("i", $service_id);
        $stmt_progress->execute();

        $_SESSION['success_message'] = "Permintaan perbaikan berhasil diajukan. Kode Service: " . $service_code;
        header("Location: repairs.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Gagal mengajukan permintaan perbaikan.";
    }
}

// Track repair
$tracked_repair = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['track_repair'])) {
    $service_code = sanitize($_POST['service_code']);

    $sql_track = "SELECT sr.*, 
(SELECT status FROM ServiceProgress WHERE id_service = sr.id_service ORDER BY created_at DESC LIMIT 1) as latest_status
FROM ServiceRequest sr
WHERE sr.kode_service = ? AND sr.id_customer = ?";
    $stmt_track = $conn->prepare($sql_track);
    $stmt_track->bind_param("si", $service_code, $customer['id_customer']);
    $stmt_track->execute();
    $tracked_repair = $stmt_track->get_result()->fetch_assoc();
}

include '../includes/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Perbaikan';
</script>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Latest Repair Status -->
<?php if ($latest_repair): ?>
    <div class="bg-white p-6 rounded-xl shadow-sm mb-8">
        <h3 class="font-bold text-lg text-slate-800 mb-6">Status Perbaikan Terkini</h3>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="w-24 h-24 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="laptop" class="w-12 h-12 text-slate-600"></i>
                </div>
                <h6 class="font-semibold text-slate-800"><?php echo $latest_repair['nama_service']; ?></h6>
                <small class="text-slate-500">ID: <?php echo $latest_repair['kode_service']; ?></small>
            </div>
            <div class="lg:col-span-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-sm text-slate-500 mb-1">Merk</p>
                        <p class="font-semibold text-slate-800"><?php echo $latest_repair['merk']; ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-sm text-slate-500 mb-1">Model</p>
                        <p class="font-semibold text-slate-800"><?php echo $latest_repair['model']; ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-sm text-slate-500 mb-1">Tanggal Masuk</p>
                        <p class="font-semibold text-slate-800"><?php echo formatDate($latest_repair['tanggal_masuk']); ?></p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-sm text-slate-500 mb-2">Deskripsi Kerusakan</p>
                    <p class="text-slate-700 bg-slate-50 p-4 rounded-lg"><?php echo $latest_repair['deskripsi_kerusakan']; ?></p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm font-semibold text-blue-800 mb-1">Catatan Teknisi</p>
                    <p class="text-blue-700">
                        <?php
                        $sql_notes = "SELECT catatan FROM ServiceProgress 
                                     WHERE id_service = ? 
                                     ORDER BY created_at DESC LIMIT 1";
                        $stmt_notes = $conn->prepare($sql_notes);
                        $stmt_notes->bind_param("i", $latest_repair['id_service']);
                        $stmt_notes->execute();
                        $notes = $stmt_notes->get_result()->fetch_assoc();
                        echo $notes ? $notes['catatan'] : 'Belum ada catatan dari teknisi';
                        ?>
                    </p>
                </div>

                <!-- Progress Steps -->
                <?php
                $status_steps = [
                    'diterima_digerai' => ['Diterima', 20],
                    'analisis_kerusakan' => ['Analisis', 40],
                    'menunggu_sparepart' => ['Menunggu Part', 60],
                    'dalam_perbaikan' => ['Perbaikan', 80],
                    'perbaikan_selesai' => ['Selesai', 100],
                    'gagal' => ['Gagal', 100],
                    'diambil_pelanggan' => ['Diambil', 100]
                ];

                $current_status = $latest_repair['progress_status'] ?? 'diterima_digerai';
                $current_progress = $status_steps[$current_status][1];
                ?>
                
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-slate-700">Progres Perbaikan</span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded-full">
                        <?php echo $status_steps[$current_status][0]; ?>
                    </span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: <?php echo $current_progress; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Two Column Layout for Track and New Repair -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Track Repair - Larger Column -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-xl shadow-sm h-full">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Lacak Perbaikan</h3>
            <form method="POST">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="service_code" placeholder="Masukkan Kode Service" 
                               class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <button type="submit" name="track_repair" 
                            class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                        Lacak
                    </button>
                </div>
            </form>

            <?php if ($tracked_repair): ?>
                <div class="mt-8 border-t border-slate-200 pt-6">
                    <h4 class="font-bold text-lg mb-4">Status Perbaikan</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <p class="text-sm text-slate-500 mb-1">Kode Service</p>
                            <p class="font-semibold text-slate-800"><?php echo $tracked_repair['kode_service']; ?></p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <p class="text-sm text-slate-500 mb-1">Tanggal Masuk</p>
                            <p class="font-semibold text-slate-800"><?php echo formatDate($tracked_repair['tanggal_masuk']); ?></p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <p class="text-sm text-slate-500 mb-1">Status</p>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo $status_steps[$tracked_repair['latest_status']][0]; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-sm text-slate-500 mb-2">Deskripsi Kerusakan</p>
                        <p class="text-slate-700 bg-slate-50 p-4 rounded-lg"><?php echo $tracked_repair['deskripsi_kerusakan']; ?></p>
                    </div>
                    
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                             style="width: <?php echo $status_steps[$tracked_repair['latest_status']][1]; ?>%"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- New Repair - Smaller Column -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-xl shadow-sm h-full">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Ajukan Perbaikan Baru</h3>
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
                    <i data-lucide="wrench" class="w-8 h-8 text-blue-600"></i>
                </div>
                <h4 class="font-semibold text-slate-800 mb-2">Perbaikan Perangkat Anda</h4>
                <p class="text-slate-500 mb-6">Ajukan permintaan perbaikan untuk perangkat Anda dengan mengisi form sederhana.</p>
                <button onclick="showRepairModal()" 
                        class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                    Ajukan Perbaikan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Repair History -->
<div class="bg-white p-6 rounded-xl shadow-sm">
    <h3 class="font-bold text-lg text-slate-800 mb-4">Riwayat Perbaikan</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-semibold">No</th>
                    <th class="px-4 py-3 font-semibold">Kode Service</th>
                    <th class="px-4 py-3 font-semibold">Nama Service</th>
                    <th class="px-4 py-3 font-semibold">Deskripsi Kerusakan</th>
                    <th class="px-4 py-3 font-semibold">Tanggal Masuk</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php 
                mysqli_data_seek($repairs, 0); 
                $counter = 1;
                while ($repair = $repairs->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 text-slate-700"><?php echo $counter++; ?></td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-slate-700"><?php echo $repair['kode_service']; ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-900"><?php echo htmlspecialchars($repair['nama_service']); ?></td>
                    <td class="px-4 py-3 text-slate-900">
                        <div class="line-clamp-2"><?php echo htmlspecialchars($repair['deskripsi_kerusakan']); ?></div>
                    </td>
                    <td class="px-4 py-3 text-slate-900"><?php echo formatDate($repair['tanggal_masuk']); ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo $status_steps[$repair['latest_status']][0]; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <button onclick="showInvoice('<?php echo $repair['kode_service']; ?>')" 
                                class="text-blue-600 hover:text-blue-800 flex items-center">
                            <i data-lucide="file-text" class="w-4 h-4 mr-1"></i>
                            Invoice
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($repairs->num_rows == 0): ?>
    <div class="text-center py-12">
        <i data-lucide="wrench" class="w-12 h-12 text-slate-400 mx-auto mb-4"></i>
        <h3 class="text-lg font-semibold text-slate-900 mb-2">Belum Ada Riwayat Perbaikan</h3>
        <p class="text-slate-500">Anda belum pernah mengajukan permintaan perbaikan.</p>
    </div>
    <?php endif; ?>
</div>

<!-- New Repair Modal -->
<div id="repairModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Formulir Perbaikan</h3>
                <form method="POST" id="repairForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Merk</label>
                            <input type="text" name="merk" required 
                                   class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Model</label>
                            <input type="text" name="model" required 
                                   class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Serial Number</label>
                            <input type="text" name="serial_no" 
                                   class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Kelengkapan</label>
                            <input type="text" name="kelengkapan" placeholder="Charger, tas, dll" 
                                   class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Deskripsi Kerusakan</label>
                        <textarea name="deskripsi" rows="4" required 
                                  class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                  placeholder="Jelaskan masalah yang dialami perangkat Anda..."></textarea>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="hideRepairModal()" 
                                class="bg-slate-200 text-slate-800 font-semibold px-6 py-3 rounded-lg hover:bg-slate-300 transition-colors">
                            Batal
                        </button>
                        <button type="submit" name="submit_repair" 
                                class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                            Ajukan Perbaikan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Show repair modal
    function showRepairModal() {
        document.getElementById('repairModal').classList.remove('hidden');
    }

    // Hide repair modal
    function hideRepairModal() {
        document.getElementById('repairModal').classList.add('hidden');
    }

    // Show invoice (placeholder function)
    function showInvoice(serviceCode) {
        alert('Menampilkan invoice untuk kode service: ' + serviceCode);
        // You can implement actual invoice display logic here
        // For example: window.open('invoice.php?code=' + serviceCode, '_blank');
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('repairModal');
        if (event.target === modal) {
            hideRepairModal();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>