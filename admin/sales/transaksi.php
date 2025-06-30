<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin', 'Sales']);

// Fetch all transactions
$query = "SELECT 
            t.id_transaction, t.jenis_transaksi, t.tanggal_transaksi, t.total_harga, t.status_pembayaran, t.metode_bayar,
            COALESCE(u.nama_lengkap, t.guest_name) as customer_name,
            s.nama_lengkap as sales_name
          FROM Transaction t
          LEFT JOIN Customer c ON t.id_customer = c.id_customer
          LEFT JOIN User u ON c.id_user = u.id_user
          JOIN User s ON t.id_sales = s.id_user
          WHERE t.deleted_at IS NULL
          ORDER BY t.created_at DESC";
$transactions = $conn->query($query);

// Data untuk form
$customers = $conn->query("SELECT c.id_customer, u.nama_lengkap FROM Customer c JOIN User u ON c.id_user = u.id_user ORDER BY u.nama_lengkap");
$products = $conn->query("SELECT id_product, nama_product, harga, stok FROM Product WHERE deleted_at IS NULL AND stok > 0 ORDER BY nama_product");
$teknisi = $conn->query("SELECT id_user, nama_lengkap FROM User WHERE role IN ('Admin', 'Sales', 'Marketing') AND deleted_at IS NULL ORDER BY nama_lengkap");
?>

<?php include '../../includes/header.php'; ?>

<script>
    document.getElementById('page-title').textContent = 'Manajemen Transaksi';
</script>

<div class="space-y-6">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header with Add Button -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-slate-800">Manajemen Transaksi</h2>
        <button onclick="showTransactionModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Buat Transaksi
        </button>
    </div>

    <!-- Transaction Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 flex justify-between items-center bg-slate-50">
            <div class="relative w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                </div>
                <input type="text" id="transaction-search" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Cari transaksi...">
            </div>
            <div class="flex items-center space-x-2">
                <label for="status-filter" class="text-sm text-slate-600">Filter:</label>
                <select id="status-filter" class="px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="lunas">Lunas</option>
                    <option value="belum_bayar">Belum Bayar</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Jenis</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200" id="transaction-table-body">
                    <?php if ($transactions->num_rows > 0): ?>
                        <?php while ($trx = $transactions->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50" data-status="<?= $trx['status_pembayaran'] ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">TRX-<?= str_pad($trx['id_transaction'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900"><?= htmlspecialchars($trx['customer_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $trx['jenis_transaksi'] === 'barang' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                        <?= ucfirst(htmlspecialchars($trx['jenis_transaksi'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= formatDate($trx['tanggal_transaksi']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900"><?= formatCurrency($trx['total_harga']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $trx['status_pembayaran'] == 'lunas' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= ucfirst($trx['status_pembayaran']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <div class="flex space-x-2">
                                        <button onclick="viewInvoice(<?= $trx['id_transaction'] ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i data-lucide="eye" class="w-3 h-3 mr-1"></i> Lihat
                                        </button>
                                        <a href="../actions/proses_transaksi.php?action=delete&id=<?= $trx['id_transaction'] ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="return confirm('Anda yakin ingin menghapus transaksi ini?')">
                                            <i data-lucide="trash-2" class="w-3 h-3 mr-1"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-slate-500">Tidak ada data transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transaction Modal -->
<div class="fixed inset-0 overflow-y-auto hidden" id="transactionModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900" id="transactionModalTitle">Buat Transaksi Baru</h3>
                        <div class="mt-4">
                            <form action="../actions/proses_transaksi.php" method="POST" id="transactionForm">
                                <input type="hidden" name="action" value="add">

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Transaksi</label>
                                    <div class="flex space-x-2">
                                        <button type="button" id="productTabBtn" class="flex-1 py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="switchTab('product')">
                                            Produk
                                        </button>
                                        <button type="button" id="serviceTabBtn" class="flex-1 py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" onclick="switchTab('service')">
                                            Service
                                        </button>
                                    </div>
                                </div>

                                <!-- Product Fields -->
                                <div id="product-fields">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
                                        <input class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" list="customerOptions" name="customer_produk" placeholder="Ketik untuk mencari customer atau isi nama baru...">
                                        <datalist id="customerOptions">
                                            <?php mysqli_data_seek($customers, 0);
                                            while ($c = $customers->fetch_assoc()) echo "<option value='{$c['id_customer']}-{$c['nama_lengkap']}'>"; ?>
                                        </datalist>
                                    </div>

                                    <div id="product-items" class="space-y-3">
                                        <!-- Product items will be added here -->
                                    </div>

                                    <button type="button" class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" id="addProductItemBtn">
                                        <i data-lucide="plus" class="w-3 h-3 mr-1"></i> Tambah Item
                                    </button>
                                </div>

                                <!-- Service Fields -->
                                <div id="service-fields" class="hidden space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
                                        <input class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" list="customerOptions" name="customer_service" placeholder="Ketik untuk mencari customer...">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Service</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="nama_service" placeholder="Contoh: Perbaikan Laptop Acer Aspire 5">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Kerusakan</label>
                                        <textarea class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="deskripsi_kerusakan" rows="2"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Teknisi Penanggung Jawab</label>
                                        <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="id_teknisi">
                                            <?php mysqli_data_seek($teknisi, 0);
                                            while ($t = $teknisi->fetch_assoc()) echo "<option value='{$t['id_user']}'>{$t['nama_lengkap']}</option>"; ?>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Biaya Service</label>
                                            <input type="number" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="biaya_service" value="0">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Biaya Sparepart</label>
                                            <input type="number" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="biaya_sparepart" value="0">
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Metode Pembayaran</label>
                                        <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="metode_bayar" required>
                                            <option value="tunai">Tunai</option>
                                            <option value="transfer">Transfer Bank</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Status Pembayaran</label>
                                        <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="status_pembayaran" required>
                                            <option value="lunas">Lunas</option>
                                            <option value="belum_bayar">Belum Bayar</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="transactionForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Simpan Transaksi
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('transactionModal')">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Modal -->
<div class="fixed inset-0 overflow-y-auto hidden" id="invoiceModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900">Rincian Transaksi</h3>
                        <div class="mt-4" id="invoiceModalBody">
                            <div class="text-center p-4">
                                <div class="spinner-border text-blue-500" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">Memuat data invoice...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" onclick="printInvoice()">
                    <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Cetak
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('invoiceModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Show transaction modal
    function showTransactionModal() {
        document.getElementById('transactionModalTitle').textContent = 'Buat Transaksi Baru';
        document.getElementById('transactionForm').reset();
        document.getElementById('product-items').innerHTML = '';
        addProductItem(); // Add first item by default
        switchTab('product'); // Reset to product tab
        document.getElementById('transactionModal').classList.remove('hidden');
    }

    // Close modal
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Switch between tabs
    function switchTab(tab) {
        
        const isProduct = tab === 'product';
        document.getElementById('product-fields').classList.toggle('hidden', !isProduct);
        document.getElementById('service-fields').classList.toggle('hidden', isProduct);

        // Update tab button styles
        document.getElementById('productTabBtn').classList.toggle('bg-blue-600', isProduct);
        document.getElementById('productTabBtn').classList.toggle('bg-blue-500', !isProduct);
        document.getElementById('serviceTabBtn').classList.toggle('bg-purple-600', !isProduct);
        document.getElementById('serviceTabBtn').classList.toggle('bg-purple-500', isProduct);
        
    }

    // Add product item
    let productIndex = 0;

    function addProductItem() {
        const container = document.getElementById('product-items');
        const newItem = document.createElement('div');
        newItem.classList.add('grid', 'grid-cols-12', 'gap-3', 'items-end', 'product-item');
        newItem.innerHTML = `
            <div class="col-span-6">
                <label class="block text-xs font-medium text-slate-700 mb-1">Produk</label>
                <select class="w-full px-2 py-1 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" name="products[${productIndex}][id]" onchange="updatePrice(this)">
                    <option selected disabled>Pilih Produk</option>
                    <?php mysqli_data_seek($products, 0);
                    while ($p = $products->fetch_assoc()) echo "<option value='{$p['id_product']}' data-price='{$p['harga']}'>{$p['nama_product']} (Stok: {$p['stok']})</option>"; ?>
                </select>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-slate-700 mb-1">Qty</label>
                <input type="number" class="w-full px-2 py-1 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" name="products[${productIndex}][qty]" value="1" min="1">
            </div>
            <div class="col-span-3">
                <label class="block text-xs font-medium text-slate-700 mb-1">Harga</label>
                <input type="text" class="w-full px-2 py-1 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm price-display" readonly>
            </div>
            <div class="col-span-1">
                <button type="button" class="w-full px-2 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="this.closest('.product-item').remove()">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        `;
        container.appendChild(newItem);
        lucide.createIcons(); // Refresh icons for new element
        productIndex++;
    }

    // Update price display
    function updatePrice(selectElement) {
        const price = selectElement.options[selectElement.selectedIndex]?.getAttribute('data-price');
        const priceDisplay = selectElement.closest('.product-item').querySelector('.price-display');
        if (price && priceDisplay) {
            priceDisplay.value = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(price);
        }
    }

    // View invoice
    function viewInvoice(transactionId) {
        const modalBody = document.getElementById('invoiceModalBody');
        modalBody.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-blue-500" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 text-sm text-slate-500">Memuat data invoice...</p>
            </div>
        `;

        document.getElementById('invoiceModal').classList.remove('hidden');

        fetch(`../actions/get_invoice_details.php?id=${transactionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal memuat data invoice');
                }
                return response.text();
            })
            .then(html => {
                modalBody.innerHTML = html;
                lucide.createIcons(); // Refresh icons in the loaded content
            })
            .catch(error => {
                modalBody.innerHTML = `
                    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                            ${error.message}
                        </div>
                    </div>
                `;
                lucide.createIcons();
            });
    }

    // Print invoice
    function printInvoice() {
        const invoiceContent = document.getElementById('invoiceModalBody').innerHTML;
        const printWindow = window.open('', '_blank', 'height=800,width=900');

        printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak Invoice</title>
            <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
            <script src="https://unpkg.com/lucide@latest">
<\/script>
<style>
    @page {
        size: auto;
        margin: 5mm;
    }

    @media print {
        body {
            padding: 20px;
        }

        .no-print {
            display: none !important;
        }
    }
</style>
</head>

<body class="p-6">
    ${invoiceContent}
</body>

</html>
`);

printWindow.document.close();

// Tunggu sampai konten selesai dimuat sebelum mencetak
printWindow.onload = function() {
printWindow.focus();
printWindow.print();
};
}

// Search functionality
document.getElementById('transaction-search').addEventListener('input', function(e) {
const searchTerm = e.target.value.toLowerCase();
const rows = document.querySelectorAll('#transaction-table-body tr');

rows.forEach(row => {
const text = row.textContent.toLowerCase();
row.style.display = text.includes(searchTerm) ? '' : 'none';
});
});

// Status filter
document.getElementById('status-filter').addEventListener('change', function(e) {
const status = e.target.value;
const rows = document.querySelectorAll('#transaction-table-body tr');

rows.forEach(row => {
if (status === '') {
row.style.display = '';
} else {
const rowStatus = row.getAttribute('data-status');
row.style.display = rowStatus === status ? '' : 'none';
}
});
});

// Close modal when clicking outside
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
modal.addEventListener('click', function(e) {
if (e.target === this) {
this.classList.add('hidden');
}
});
});

// Initialize add product button
document.getElementById('addProductItemBtn').addEventListener('click', addProductItem);
</script>

<?php include '../../includes/footer.php'; ?>