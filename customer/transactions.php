<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRole(['Customer']);

$user_id = $_SESSION['user_id'];
$customer = getCustomerData($user_id);

// Get all transactions for the customer
$sql_transactions = "SELECT 
                        t.id_transaction,
                        t.jenis_transaksi,
                        t.tanggal_transaksi,
                        t.total_harga,
                        t.status_pembayaran,
                        (CASE 
                            WHEN t.jenis_transaksi = 'barang' THEN (SELECT GROUP_CONCAT(p.nama_product SEPARATOR ', ') 
                                                                    FROM TransactionProductDetail tpd 
                                                                    JOIN Product p ON tpd.id_product = p.id_product 
                                                                    WHERE tpd.id_transaction = t.id_transaction)
                            WHEN t.jenis_transaksi = 'service' THEN (SELECT sr.nama_service 
                                                                     FROM TransactionServiceDetail tsd 
                                                                     JOIN ServiceRequest sr ON tsd.id_service = sr.id_service 
                                                                     WHERE tsd.id_transaction = t.id_transaction)
                            ELSE 'N/A'
                        END) as item_details,
                        (CASE
                            WHEN t.jenis_transaksi = 'barang' THEN (SELECT SUM(tpd.qty) 
                                                                    FROM TransactionProductDetail tpd 
                                                                    WHERE tpd.id_transaction = t.id_transaction)
                            ELSE 1
                        END) as total_qty
                    FROM Transaction t
                    WHERE t.id_customer = ?
                    ORDER BY t.tanggal_transaksi DESC";
$stmt_transactions = $conn->prepare($sql_transactions);
$stmt_transactions->bind_param("i", $customer['id_customer']);
$stmt_transactions->execute();
$transactions = $stmt_transactions->get_result();

include '../includes/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Riwayat Transaksi';
</script>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h3 class="font-bold text-2xl text-slate-800">Riwayat Transaksi</h3>
        <div class="flex flex-wrap gap-3">
            <button class="border border-slate-300 font-semibold px-4 py-2 rounded-lg hover:bg-slate-50 text-sm flex items-center">
                <i data-lucide="filter" class="w-4 h-4 mr-2"></i>Filter
            </button>
            <button class="border border-slate-300 font-semibold px-4 py-2 rounded-lg hover:bg-slate-50 text-sm flex items-center">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i>Export
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" placeholder="Cari transaksi..."
                    class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent">
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-slate-500">Total:</span>
                <span class="font-semibold"><?php echo $transactions->num_rows; ?> Transaksi</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 font-semibold">No</th>
                        <th class="px-6 py-4 font-semibold">ID Transaksi</th>
                        <th class="px-6 py-4 font-semibold">Nama Barang/Perbaikan</th>
                        <th class="px-6 py-4 font-semibold">QTY</th>
                        <th class="px-6 py-4 font-semibold">Tanggal</th>
                        <th class="px-6 py-4 font-semibold">Total Harga</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php $counter = 1;
                    while ($transaction = $transactions->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-slate-900"><?= $counter++ ?></td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-slate-700">TRX-<?= str_pad($transaction['id_transaction'], 6, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p class="text-slate-900 font-medium truncate"><?= htmlspecialchars($transaction['item_details']) ?></p>
                                    <p class="text-slate-500 text-xs"><?= $transaction['jenis_transaksi'] == 'barang' ? 'Pembelian Produk' : 'Layanan Service' ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-900"><?= $transaction['total_qty'] ?></td>
                            <td class="px-6 py-4 text-slate-900"><?= formatDate($transaction['tanggal_transaksi']) ?></td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-900"><?= formatCurrency($transaction['total_harga']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $transaction['status_pembayaran'] == 'lunas' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full <?= $transaction['status_pembayaran'] == 'lunas' ? 'bg-green-400' : 'bg-yellow-400' ?>"></span>
                                    <?= $transaction['status_pembayaran'] == 'lunas' ? 'Lunas' : 'Belum Bayar' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="viewInvoice(<?= $transaction['id_transaction'] ?>)"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <i data-lucide="eye" class="w-3 h-3 mr-1"></i>
                                    Invoice
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($transactions->num_rows == 0): ?>
            <div class="text-center py-12">
                <i data-lucide="receipt" class="w-12 h-12 text-slate-400 mx-auto mb-4"></i>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Belum Ada Transaksi</h3>
                <p class="text-slate-500 mb-6">Anda belum memiliki riwayat transaksi. Mulai berbelanja sekarang!</p>
                <a href="products.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i data-lucide="shopping-cart" class="w-4 h-4 mr-2"></i>
                    Lihat Produk
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Invoice Modal -->
<div id="invoiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-slate-200">
            <h5 class="text-xl font-bold text-slate-800" id="invoiceModalLabel">Rincian Invoice</h5>
            <button onclick="closeInvoiceModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div id="invoiceModalBody" class="p-6 overflow-y-auto max-h-[70vh]">
            <div class="text-center p-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-slate-600">Memuat data...</p>
            </div>
        </div>
        <div class="flex items-center justify-end space-x-3 p-6 border-t border-slate-200">
            <button onclick="closeInvoiceModal()" class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium">Tutup</button>
            <button onclick="printInvoice()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                <i data-lucide="printer" class="w-4 h-4 mr-2 inline"></i>Cetak
            </button>
        </div>
    </div>
</div>

<script>
    function viewInvoice(transactionId) {
        const modal = document.getElementById('invoiceModal');
        const modalBody = document.getElementById('invoiceModalBody');
        const modalTitle = document.getElementById('invoiceModalLabel');

        modalTitle.textContent = `Rincian Invoice #${transactionId}`;
        modalBody.innerHTML = '<div class="text-center p-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-4 text-slate-600">Memuat data...</p></div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        fetch(`invoice.php?id=${transactionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Gagal memuat invoice. Status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(error => {
                modalBody.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">${error.message}</div>`;
            });
    }

    function closeInvoiceModal() {
        const modal = document.getElementById('invoiceModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function printInvoice() {
        const invoiceContent = document.getElementById('invoiceModalBody').innerHTML;
        const printWindow = window.open('', '', 'height=700,width=900');
        printWindow.document.write('<html><head><title>Cetak Invoice</title>');
        printWindow.document.write('<script src="https://cdn.tailwindcss.com"></' + 'script>');
        printWindow.document.write('<style>body { padding: 20px; font-family: Arial, sans-serif; } @media print { .no-print { display: none !important; } }</style>');
        printWindow.document.write('</head><body>' + invoiceContent + '</body></html>');
printWindow.document.close();
printWindow.addEventListener('load', function() {
printWindow.focus();
printWindow.print();
});
}

// Initialize Lucide icons
lucide.createIcons();
</script>

<?php include '../includes/footer.php'; ?>