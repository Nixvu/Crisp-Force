<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// Fetch all products that are not soft-deleted
$products = $conn->query("SELECT p.*, u.nama_lengkap as creator_name 
                          FROM Product p 
                          JOIN User u ON p.created_by = u.id_user
                          WHERE p.deleted_at IS NULL 
                          ORDER BY p.created_at DESC");
?>

<?php include '../../includes/header.php'; ?>

<script>
    document.getElementById('page-title').textContent = 'Manajemen Produk';
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
        <h2 class="text-2xl font-bold text-slate-800">Manajemen Produk</h2>
        <button onclick="showProductModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Produk
        </button>
    </div>

    <!-- Product Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Search and Filter -->
        <div class="p-4 flex justify-between items-center bg-slate-50">
            <div class="relative w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                </div>
                <input type="text" id="product-search" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Cari produk...">
            </div>
            <div class="flex items-center space-x-2">
                <label for="category-filter" class="text-sm text-slate-600">Filter:</label>
                <select id="category-filter" class="px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Semua Kategori</option>
                    <option value="laptop">Laptop</option>
                    <option value="aksesoris">Aksesoris</option>
                    <option value="komputer">Komputer</option>
                    <option value="penyimpanan">Penyimpanan</option>
                    <option value="peripheral">Peripheral</option>
                    <option value="sparepart">Sparepart</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">No</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Kode</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nama Produk</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Kategori</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Stok</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Harga</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200" id="product-table-body">
                    <?php if ($products->num_rows > 0): $counter = 1; ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50" data-category="<?= htmlspecialchars($product['category']) ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?= $counter++; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900"><?= htmlspecialchars($product['kode_product']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900"><?= htmlspecialchars($product['nama_product']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getCategoryBadgeClass($product['category']) ?>">
                                        <?= ucfirst(htmlspecialchars($product['category'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 <?= $product['stok'] < 5 ? 'text-red-600 font-semibold' : '' ?>">
                                    <?= $product['stok'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">
                                    <?= formatCurrency($product['harga']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <div class="flex space-x-2">
                                        <button onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                            Edit
                                        </button>
                                        <a href="../actions/proses_produk.php?action=delete&id=<?= $product['id_product']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="return confirm('Anda yakin ingin menghapus produk ini?')">
                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-slate-500">Tidak ada data produk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="fixed inset-0 overflow-y-auto hidden" id="productModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900" id="productModalTitle">Tambah Produk Baru</h3>
                        <div class="mt-4">
                            <form action="../actions/proses_produk.php" method="POST" enctype="multipart/form-data" id="productForm">
                                <input type="hidden" name="action" id="productAction" value="add">
                                <input type="hidden" name="id_product" id="productId">
                                
                                <div class="grid grid-cols-1 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Produk</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="kode_product" name="kode_product" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Produk</label>
                                        <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="nama_product" name="nama_product" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                                        <textarea class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="description" name="description" rows="2"></textarea>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                                        <select class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="category" name="category" required>
                                            <option value="laptop">Laptop</option>
                                            <option value="aksesoris">Aksesoris</option>
                                            <option value="komputer">Komputer</option>
                                            <option value="penyimpanan">Penyimpanan</option>
                                            <option value="peripheral">Peripheral</option>
                                            <option value="sparepart">Sparepart</option>
                                            <option value="lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Stok</label>
                                        <input type="number" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="stok" name="stok" min="0" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Harga</label>
                                        <input type="number" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="harga" name="harga" min="0" step="1000" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Gambar Produk</label>
                                        <input type="file" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="gambar" name="gambar">
                                        <p class="mt-1 text-xs text-slate-500">Format: JPG, PNG (Max 2MB)</p>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="productForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Simpan
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('productModal')">
                    Batal
                </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Show product modal
    function showProductModal() {
        document.getElementById('productModalTitle').textContent = 'Tambah Produk Baru';
        document.getElementById('productAction').value = 'add';
        document.getElementById('productId').value = '';
        document.getElementById('productForm').reset();
        document.getElementById('productModal').classList.remove('hidden');
    }

    // Edit product
    function editProduct(product) {
        document.getElementById('productModalTitle').textContent = 'Edit Produk';
        document.getElementById('productAction').value = 'edit';
        document.getElementById('productId').value = product.id_product;
        document.getElementById('kode_product').value = product.kode_product;
        document.getElementById('nama_product').value = product.nama_product;
        document.getElementById('description').value = product.description;
        document.getElementById('category').value = product.category;
        document.getElementById('stok').value = product.stok;
        document.getElementById('harga').value = product.harga;
        
        document.getElementById('productModal').classList.remove('hidden');
    }

    // Close modal
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Search functionality
    document.getElementById('product-search').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#product-table-body tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Category filter
    document.getElementById('category-filter').addEventListener('change', function(e) {
        const category = e.target.value;
        const rows = document.querySelectorAll('#product-table-body tr');
        
        rows.forEach(row => {
            if (category === '') {
                row.style.display = '';
            } else {
                const rowCategory = row.getAttribute('data-category');
                row.style.display = rowCategory === category ? '' : 'none';
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
</script>

<?php include '../../includes/footer.php'; ?>

<?php
function getCategoryBadgeClass($category) {
    switch ($category) {
        case 'laptop': return 'bg-blue-100 text-blue-800';
        case 'aksesoris': return 'bg-purple-100 text-purple-800';
        case 'komputer': return 'bg-green-100 text-green-800';
        case 'penyimpanan': return 'bg-yellow-100 text-yellow-800';
        case 'peripheral': return 'bg-indigo-100 text-indigo-800';
        case 'sparepart': return 'bg-pink-100 text-pink-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?>