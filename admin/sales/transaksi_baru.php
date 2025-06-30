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
$customers = $conn->query("SELECT c.id_customer, u.nama_lengkap FROM Customer c JOIN User u ON c.id_user = u.id_user WHERE u.deleted_at IS NULL ORDER BY u.nama_lengkap");
$products = $conn->query("SELECT id_product, nama_product, harga, stok FROM Product WHERE deleted_at IS NULL AND stok > 0 ORDER BY nama_product");
$sales_users = $conn->query("SELECT id_user, nama_lengkap FROM User WHERE role = 'Sales' AND deleted_at IS NULL ORDER BY nama_lengkap");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen Transaksi</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                        <i class="bi bi-plus-lg"></i> Buat Transaksi
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th><th>Customer</th><th>Jenis</th><th>Tanggal</th><th>Total</th><th>Status Bayar</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions && $transactions->num_rows > 0): ?>
                            <?php while($trx = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td>TRX-<?= str_pad($trx['id_transaction'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($trx['customer_name']); ?></td>
                                <td><span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($trx['jenis_transaksi'])); ?></span></td>
                                <td><?= formatDate($trx['tanggal_transaksi']); ?></td>
                                <td><?= formatCurrency($trx['total_harga']); ?></td>
                                <td><span class="badge bg-<?= $trx['status_pembayaran'] == 'lunas' ? 'success' : 'warning' ?>"><?= ucfirst($trx['status_pembayaran']) ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewInvoice(<?= $trx['id_transaction']; ?>)"><i class="bi bi-eye"></i></button>
                                    <a href="../actions/proses_transaksi.php?action=delete&id=<?= $trx['id_transaction']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus transaksi ini?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">Tidak ada data transaksi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Invoice Detail Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="invoiceModalLabel">Rincian Transaksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="invoiceModalBody">
        <div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" onclick="printInvoice()"><i class="bi bi-printer"></i> Cetak</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addTransactionModalLabel">Buat Transaksi Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="../actions/proses_transaksi.php" method="POST" id="addTransactionForm">
        <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label class="form-label">Jenis Transaksi</label>
                <select class="form-select" name="jenis_transaksi" id="jenis_transaksi_selector" required>
                    <option value="barang" selected>Produk</option>
                    <option value="service">Service</option>
                </select>
            </div>

            <!-- Product Fields -->
            <div id="product-fields">
                <div class="mb-3"><label class="form-label">Customer</label><input class="form-control" list="customerOptions" name="customer_produk" placeholder="Ketik untuk mencari customer atau isi nama baru..."></div>
                <datalist id="customerOptions">
                    <?php mysqli_data_seek($customers, 0); while($c = $customers->fetch_assoc()) echo "<option value='{$c['id_customer']}-{$c['nama_lengkap']}'>"; ?>
                </datalist>
                <div id="product-items"></div>
                <button type="button" class="btn btn-sm btn-outline-success mt-2" id="addProductItemBtn">+ Tambah Item</button>
            </div>

            <!-- Service Fields -->
            <div id="service-fields" style="display: none;">
                <div class="mb-3"><label class="form-label">Customer</label><input class="form-control" list="customerOptions" name="customer_service" placeholder="Ketik untuk mencari customer..."></div>
                <div class="mb-3"><label class="form-label">Nama Service</label><input type="text" class="form-control" name="nama_service" placeholder="Contoh: Perbaikan Laptop Acer Aspire 5"></div>
                <div class="mb-3"><label class="form-label">Deskripsi Kerusakan</label><textarea class="form-control" name="deskripsi_kerusakan" rows="2"></textarea></div>
                <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Biaya Service</label><input type="number" class="form-control" name="biaya_service" value="0"></div><div class="col-md-6 mb-3"><label class="form-label">Biaya Sparepart</label><input type="number" class="form-control" name="biaya_sparepart" value="0"></div></div>
            </div>
            
            <hr>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Metode Pembayaran</label><select class="form-select" name="metode_bayar" required><option value="tunai">Tunai</option><option value="transfer">Transfer Bank</option></select></div><div class="col-md-6 mb-3"><label class="form-label">Status Pembayaran</label><select class="form-select" name="status_pembayaran" required><option value="lunas">Lunas</option><option value="belum_bayar">Belum Bayar</option></select></div></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Self-invoking function to prevent global scope pollution
(function() {
    // Ensure the DOM is fully loaded before running scripts
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- Logic for Add Transaction Modal ---
        const selector = document.getElementById('jenis_transaksi_selector');
        const productFields = document.getElementById('product-fields');
        const serviceFields = document.getElementById('service-fields');
        
        if(selector) {
            selector.addEventListener('change', function() {
                const isService = this.value === 'service';
                serviceFields.style.display = isService ? 'block' : 'none';
                productFields.style.display = isService ? 'none' : 'block';
            });
        }

        let productIndex = 0;
        const addProductBtn = document.getElementById('addProductItemBtn');
        const productItemsContainer = document.getElementById('product-items');

        function addProductItem() {
            const newItem = document.createElement('div');
            newItem.classList.add('row', 'product-item', 'mb-2', 'align-items-end');
            newItem.innerHTML = `
                <div class="col-md-6"><label class="form-label small">Produk</label><select class="form-select form-select-sm" name="products[${productIndex}][id]"><?php mysqli_data_seek($products, 0); while($p = $products->fetch_assoc()) echo "<option value='{$p['id_product']}'>{$p['nama_product']}</option>"; ?></select></div>
                <div class="col-md-3"><label class="form-label small">Qty</label><input type="number" class="form-control form-control-sm" name="products[${productIndex}][qty]" value="1" min="1"></div>
                <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.product-item').remove()">Hapus</button></div>
            `;
            productItemsContainer.appendChild(newItem);
            productIndex++;
        }

        if(addProductBtn) {
            addProductBtn.addEventListener('click', addProductItem);
        }
        // Add the first item automatically
        addProductItem();
    });

    // --- Logic for Invoice Modal ---
    let invoiceModalInstance = null;
    // Function to initialize and get the modal instance
    function getInvoiceModal() {
        if (!invoiceModalInstance) {
            const modalElement = document.getElementById('invoiceModal');
            if(modalElement) {
                invoiceModalInstance = new bootstrap.Modal(modalElement);
            }
        }
        return invoiceModalInstance;
    }

    // Make viewInvoice globally accessible
    window.viewInvoice = function(transactionId) {
        const modal = getInvoiceModal();
        if(!modal) {
            console.error('Invoice modal element not found.');
            alert('Error: Komponen modal tidak ditemukan.');
            return;
        }
        
        const modalBody = document.getElementById('invoiceModalBody');
        modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Memuat data...</p></div>';
        modal.show();

        fetch(`../actions/get_invoice_details.php?id=${transactionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Gagal memuat data invoice. Server merespons dengan status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => { 
                modalBody.innerHTML = html; 
            })
            .catch(error => {
                console.error('Fetch error:', error);
                modalBody.innerHTML = `<div class="alert alert-danger m-0"><strong>Error:</strong> ${error.message}</div>`;
            });
    }

    // Make printInvoice globally accessible
    window.printInvoice = function() {
        const invoiceContent = document.getElementById('invoiceModalBody').innerHTML;
        const printWindow = window.open('', '', 'height=800,width=900');
        printWindow.document.write('<html><head><title>Cetak Invoice</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write('<style>body { padding: 20px; } @media print { .no-print, .modal-footer, .btn { display: none !important; } }</style>');
        printWindow.document.write('</head><body>' + invoiceContent + '</body></html>');
        printWindow.document.close();
        printWindow.addEventListener('load', function() {
            printWindow.focus();
            printWindow.print();
        });
    }
})();
</script>
</body>
</html>
