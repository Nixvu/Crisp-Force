<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi</title>
</head>
<body>

<?php include '../../includes/header.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manajemen Transaksi</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
                <i class="bi bi-plus-lg"></i>
                Buat Transaksi
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
     <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Jenis</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while($trx = $transactions->fetch_assoc()): ?>
                    <tr>
                        <td>TRX-<?= str_pad($trx['id_transaction'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($trx['customer_name']); ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($trx['jenis_transaksi'])); ?></span></td>
                        <td><?= formatDate($trx['tanggal_transaksi']); ?></td>
                        <td><?= formatCurrency($trx['total_harga']); ?></td>
                        <td><span class="badge bg-<?= $trx['status_pembayaran'] == 'lunas' ? 'success' : 'warning' ?>"><?= ucfirst($trx['status_pembayaran']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewInvoice(<?= $trx['id_transaction']; ?>)">View</button>
                            <a href="../actions/proses_transaksi.php?action=delete&id=<?= $trx['id_transaction']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus transaksi ini?')">Hapus</a>
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

<!-- [BARU] Invoice Detail Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="invoiceModalLabel">Rincian Transaksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="invoiceModalBody">
        <p class="text-center">Memuat data...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" onclick="printInvoice()">Cetak</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk Buat Transaksi Baru -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="transactionModalLabel">Buat Transaksi Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="../actions/proses_transaksi.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="add">
            
            <ul class="nav nav-tabs" id="myTab" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="product-tab" data-bs-toggle="tab" data-bs-target="#product" type="button" role="tab">Transaksi Produk</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab">Transaksi Service</button>
              </li>
            </ul>
            
            <div class="tab-content" id="myTabContent">
              <div class="tab-pane fade show active" id="product" role="tabpanel">
                <div class="p-3 border border-top-0">
                    <input type="hidden" name="jenis_transaksi_produk" value="barang">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <input class="form-control" list="customerOptions" name="customer_produk" placeholder="Ketik untuk mencari customer terdaftar atau isi nama baru...">
                        <datalist id="customerOptions">
                            <?php mysqli_data_seek($customers, 0); while($c = $customers->fetch_assoc()) echo "<option value='{$c['id_customer']}-{$c['nama_lengkap']}'>"; ?>
                        </datalist>
                    </div>
                    <div id="product-items">
                        <div class="row product-item mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Produk</label>
                                <select class="form-select" name="products[0][id]" onchange="updatePrice(this)">
                                    <option selected disabled>Pilih Produk</option>
                                    <?php mysqli_data_seek($products, 0); while($p = $products->fetch_assoc()) echo "<option value='{$p['id_product']}' data-price='{$p['harga']}'>{$p['nama_product']} (Stok: {$p['stok']})</option>"; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Qty</label>
                                <input type="number" class="form-control" name="products[0][qty]" value="1" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Satuan</label>
                                <input type="text" class="form-control price-display" readonly>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addProductItem()">+ Tambah Item</button>
                </div>
              </div>
              <div class="tab-pane fade" id="service" role="tabpanel">
                <div class="p-3 border border-top-0">
                    <input type="hidden" name="jenis_transaksi_service" value="service">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <input class="form-control" list="customerOptions" name="customer_service" placeholder="Ketik untuk mencari customer terdaftar atau isi nama baru...">
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Service</label><input type="text" class="form-control" name="nama_service" placeholder="Contoh: Perbaikan Laptop Acer Aspire 5"></div>
                    <div class="mb-3"><label class="form-label">Deskripsi Kerusakan</label><textarea class="form-control" name="deskripsi_kerusakan" rows="2"></textarea></div>
                     <div class="mb-3"><label class="form-label">Teknisi Penanggung Jawab</label><select class="form-select" name="id_teknisi"><?php mysqli_data_seek($teknisi, 0); while($t = $teknisi->fetch_assoc()) echo "<option value='{$t['id_user']}'>{$t['nama_lengkap']}</option>"; ?></select></div>
                    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Biaya Service</label><input type="number" class="form-control" name="biaya_service" value="0"></div><div class="col-md-6 mb-3"><label class="form-label">Biaya Sparepart</label><input type="number" class="form-control" name="biaya_sparepart" value="0"></div></div>
                </div>
              </div>
            </div>
            <hr>
            <div class="row p-3"><div class="col-md-6 mb-3"><label class="form-label">Metode Pembayaran</label><select class="form-select" name="metode_bayar" required><option value="tunai">Tunai</option><option value="transfer">Transfer Bank</option></select></div><div class="col-md-6 mb-3"><label class="form-label">Status Pembayaran</label><select class="form-select" name="status_pembayaran" required><option value="lunas">Lunas</option><option value="belum_bayar">Belum Bayar</option></select></div></div>
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
<script>
    let productIndex = 1;
    function addProductItem() {
        const container = document.getElementById('product-items');
        const newItem = document.createElement('div');
        newItem.classList.add('row', 'product-item', 'mb-2');
        newItem.innerHTML = `
            <div class="col-md-6">
                <select class="form-select" name="products[${productIndex}][id]" onchange="updatePrice(this)">
                    <option selected disabled>Pilih Produk</option>
                    <?php mysqli_data_seek($products, 0); while($p = $products->fetch_assoc()) echo "<option value='{$p['id_product']}' data-price='{$p['harga']}'>{$p['nama_product']} (Stok: {$p['stok']})</option>"; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="number" class="form-control" name="products[${productIndex}][qty]" value="1" min="1"></div>
            <div class="col-md-3"><input type="text" class="form-control price-display" readonly></div>
            <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-danger" onclick="removeProductItem(this)">X</button></div>
        `;
        container.appendChild(newItem);
        productIndex++;
    }
    function removeProductItem(button) { button.closest('.product-item').remove(); }
    function updatePrice(selectElement) {
        const price = selectElement.options[selectElement.selectedIndex].getAttribute('data-price');
        const priceDisplay = selectElement.closest('.product-item').querySelector('.price-display');
        priceDisplay.value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
    }

    // [DIUBAH] Fungsi untuk menampilkan modal invoice dengan AJAX
    function viewInvoice(transactionId) {
        const modalBody = document.getElementById('invoiceModalBody');
        modalBody.innerHTML = '<p class="text-center">Memuat data...</p>';
        var myModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
        myModal.show();
        fetch(`../actions/get_invoice_details.php?id=${transactionId}`)
            .then(response => response.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(error => { modalBody.innerHTML = '<p class="text-center text-danger">Gagal memuat data.</p>'; });
    }

    function printInvoice() {
        const invoiceContent = document.getElementById('invoiceModalBody').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Cetak Invoice</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write('</head><body style="padding: 20px;">');
        printWindow.document.write(invoiceContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); }, 500);
    }
</script>
</body>
</html>