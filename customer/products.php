<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRole(['Customer']);

$user_id = $_SESSION['user_id'];
$customer = getCustomerData($user_id);

// Get products
$sql_products = "SELECT * FROM Product WHERE deleted_at IS NULL ORDER BY created_at DESC";
$result_products = $conn->query($sql_products);
$all_products = [];
while ($row = $result_products->fetch_assoc()) {
    $all_products[] = $row;
}

// Get active campaigns
$sql_campaigns = "SELECT * FROM Campaign 
                 WHERE status = 'aktif' 
                 AND (target_segmentasi = ? OR target_segmentasi = 'semua')
                 ORDER BY tanggal_mulai DESC LIMIT 1";
$stmt_campaigns = $conn->prepare($sql_campaigns);
$segmentasi = $customer['segmentasi'] ?? 'umum';
$stmt_campaigns->bind_param("s", $segmentasi);
$stmt_campaigns->execute();
$featured_campaign = $stmt_campaigns->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Katalog Produk';
</script>

<!-- Featured Campaign Banner -->
<?php if ($featured_campaign): ?>
<div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 rounded-xl shadow-lg mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between">
        <div class="md:w-2/3">
            <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($featured_campaign['nama_kampanye']); ?></h3>
            <p class="text-blue-100 mb-4"><?php echo htmlspecialchars($featured_campaign['deskripsi']); ?></p>
            <?php if ($featured_campaign['kode_promo']): ?>
            <div class="flex items-center">
                <span class="text-blue-100 mr-2">Gunakan kode:</span>
                <span class="bg-white text-blue-600 font-bold px-3 py-1 rounded-lg"><?php echo htmlspecialchars($featured_campaign['kode_promo']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="md:w-1/3 text-center mt-4 md:mt-0">
            <i data-lucide="gift" class="w-16 h-16 text-blue-200 mx-auto"></i>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Products Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($all_products as $product): ?>
    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden group">
        <div class="h-48 bg-slate-100 overflow-hidden">
            <img src="<?php echo $product['gambar_url'] ? '../assets/uploads/' . htmlspecialchars($product['gambar_url']) : '../assets/images/product-placeholder.png'; ?>" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        </div>
        <div class="p-5">
            <div class="mb-2">
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full">
                    <?php echo strtoupper(htmlspecialchars($product['category'])); ?>
                </span>
            </div>
            <h5 class="font-bold text-lg text-slate-800 mb-2 line-clamp-2"><?php echo htmlspecialchars($product['nama_product']); ?></h5>
            <div class="flex items-center mb-2">
                <div class="flex text-yellow-400 mr-2">
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current opacity-50"></i>
                </div>
                <span class="text-slate-500 text-sm">(4.5)</span>
            </div>
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xl font-bold text-blue-600"><?php echo formatCurrency($product['harga']); ?></h4>
                <span class="text-sm text-slate-500">Stok: <?php echo $product['stok']; ?></span>
            </div>
        </div>
        <div class="px-5 pb-5">
            <button class="w-full bg-slate-800 text-white font-semibold py-3 rounded-lg hover:bg-slate-900 transition-colors duration-300" 
                    onclick="openProductModal(<?php echo $product['id_product']; ?>)">
                Lihat Detail
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Product Detail Modal -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-slate-200">
            <h5 class="text-xl font-bold text-slate-800" id="modalProductName">Detail Produk</h5>
            <button onclick="closeProductModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[70vh]" id="modalProductContent">
            <!-- Content will be loaded here -->
        </div>
        <div class="flex items-center justify-end space-x-3 p-6 border-t border-slate-200">
            <button onclick="closeProductModal()" class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium">Tutup</button>
            <button onclick="openBuyModal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                Beli Sekarang
            </button>
        </div>
    </div>
</div>

<!-- Buy Modal -->
<div id="buyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="flex items-center justify-between p-6 border-b border-slate-200">
            <h5 class="text-xl font-bold text-slate-800" id="buyModalTitle">Beli Produk</h5>
            <button onclick="closeBuyModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6">
            <div id="alert-placeholder" class="mb-4"></div>
            <form id="buyForm">
                <input type="hidden" name="product_id" id="buyProductId">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Jumlah</label>
                    <input type="number" name="quantity" id="quantityInput" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" value="1" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Metode Pembayaran</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" name="payment_method" required>
                        <option value="transfer">Transfer Bank</option>
                        <option value="tunai">Tunai</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Metode Pengambilan</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 delivery-method" name="delivery_method" required>
                        <option value="ambil_sendiri">Ambil Sendiri di Gerai</option>
                        <option value="diantar">Diantar ke Alamat</option>
                    </select>
                </div>
                
                <div class="address-fields mb-4" style="<?php echo ($customer["alamat_pengiriman"] && $delivery_method !== "diantar") ? "display: none;" : "display: block;"; ?>">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Pengiriman</label>
                    <textarea class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" name="shipping_address" rows="3"><?php echo htmlspecialchars($customer["alamat_pengiriman"] ?? ""); ?></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-blue-800 font-semibold">Total Pembayaran: <span class="total-payment" id="totalPayment">Rp 0</span></p>
                </div>
            </form>
        </div>
        <div class="flex items-center justify-end space-x-3 p-6 border-t border-slate-200">
            <button onclick="closeBuyModal()" class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium">Batal</button>
            <button onclick="processOrder()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">Proses Pembelian</button>
        </div>
    </div>
</div>

<script>
    let currentProduct = null;
    let products = <?php echo json_encode($all_products); ?>;

    function formatCurrency(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function openProductModal(productId) {
        currentProduct = products.find(p => p.id_product == productId);
        if (!currentProduct) return;

        document.getElementById('modalProductName').textContent = currentProduct.nama_product;
        
        const content = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <img src="${currentProduct.gambar_url ? '../assets/uploads/' + currentProduct.gambar_url : '../assets/images/product-placeholder.png'}" 
                         class="w-full rounded-lg">
                </div>
                <div>
                    <h4 class="text-2xl font-bold text-blue-600 mb-2">${formatCurrency(currentProduct.harga)}</h4>
                    <p class="text-slate-600 mb-4">Stok: ${currentProduct.stok}</p>
                    
                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400 mr-2">
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i>
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i>
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i>
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i>
                            <i data-lucide="star" class="w-5 h-5 fill-current opacity-50"></i>
                        </div>
                        <span class="text-slate-500">4.5 (120 ulasan)</span>
                    </div>
                    
                    <h5 class="font-bold text-lg mb-2">Deskripsi Produk</h5>
                    <p class="text-slate-700 mb-4">${currentProduct.description || 'Tidak ada deskripsi produk.'}</p>
                    
                    <div class="mb-3">
                        <span class="font-semibold">Kategori:</span> 
                        <span class="bg-slate-100 text-slate-800 px-2 py-1 rounded text-sm">${currentProduct.category.charAt(0).toUpperCase() + currentProduct.category.slice(1)}</span>
                    </div>
                    
                    <div class="mb-3">
                        <span class="font-semibold">Model:</span> ${currentProduct.model || '-'}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('modalProductContent').innerHTML = content;
        document.getElementById('productModal').classList.remove('hidden');
        document.getElementById('productModal').classList.add('flex');
        
        // Re-initialize Lucide icons
        lucide.createIcons();
    }

    function closeProductModal() {
        document.getElementById('productModal').classList.add('hidden');
        document.getElementById('productModal').classList.remove('flex');
    }

    function openBuyModal() {
        if (!currentProduct) return;
        
        document.getElementById('buyModalTitle').textContent = `Beli ${currentProduct.nama_product}`;
        document.getElementById('buyProductId').value = currentProduct.id_product;
        document.getElementById('quantityInput').max = currentProduct.stok;
        document.getElementById('totalPayment').textContent = formatCurrency(currentProduct.harga);
        
        closeProductModal();
        document.getElementById('buyModal').classList.remove('hidden');
        document.getElementById('buyModal').classList.add('flex');
    }

    function closeBuyModal() {
        document.getElementById('buyModal').classList.add('hidden');
        document.getElementById('buyModal').classList.remove('flex');
        document.getElementById('alert-placeholder').innerHTML = '';
    }

    // Update total payment on quantity change
    document.getElementById('quantityInput').addEventListener('input', function() {
        if (!currentProduct) return;
        const quantity = parseInt(this.value, 10);
        if (quantity > 0) {
            document.getElementById('totalPayment').textContent = formatCurrency(currentProduct.harga * quantity);
        }
    });

    // Show/hide address fields based on delivery method
    document.querySelector('.delivery-method').addEventListener('change', function() {
        const addressFields = document.querySelector('.address-fields');
        addressFields.style.display = this.value === 'diantar' ? 'block' : 'none';
    });

    function processOrder() {
        const form = document.getElementById('buyForm');
        const formData = new FormData(form);
        const alertPlaceholder = document.getElementById('alert-placeholder');
        
        // Basic validation
        if (formData.get('delivery_method') === 'diantar' && !formData.get('shipping_address').trim()) {
            alertPlaceholder.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg">Alamat pengiriman harus diisi untuk metode pengantaran.</div>';
            return;
        }

        // Show loading
        alertPlaceholder.innerHTML = '<div class="bg-blue-50 border border-blue-200 text-blue-700 p-3 rounded-lg">Memproses pembelian...</div>';

        fetch('process_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alertPlaceholder.innerHTML = '<div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg">' + data.message + '</div>';
                setTimeout(() => {
                    closeBuyModal();
                    location.reload();
                }, 2000);
            } else {
                alertPlaceholder.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg">' + data.message + '</div>';
            }
        })
        .catch(error => {
            alertPlaceholder.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg">Terjadi kesalahan. Silakan coba lagi.</div>';
        });
    }

    // Initialize Lucide icons
    lucide.createIcons();
</script>

<?php include '../includes/footer.php'; ?>