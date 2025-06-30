-- Menonaktifkan pengecekan foreign key untuk memastikan impor berjalan lancar
SET FOREIGN_KEY_CHECKS=0;

-- Membuat database jika belum ada
CREATE DATABASE IF NOT EXISTS crisp_force;
USE crisp_force;

-- ================================================================
-- STRUKTUR TABEL UTAMA
-- ================================================================

-- 1. User Table
CREATE TABLE `User` (
    `id_user` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `no_hp` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `update_password` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    `role` ENUM('Admin', 'Customer', 'Sales', 'Marketing') NOT NULL
);

-- 2. Customer Table
CREATE TABLE `Customer` (
    `id_customer` INT AUTO_INCREMENT PRIMARY KEY,
    `id_user` INT NOT NULL,
    `segmentasi` ENUM('baru', 'perusahaan', 'loyal') DEFAULT 'baru',
    `alamat_pengiriman` TEXT NULL,
    `origin` ENUM('platform', 'manual_input') NOT NULL DEFAULT 'platform' COMMENT 'Sumber data customer',
    `total_transaksi` INT DEFAULT 0,
    `total_pengeluaran` DECIMAL(15,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_user`) REFERENCES `User`(`id_user`) ON DELETE CASCADE
);

-- 3. Product Table
CREATE TABLE `Product` (
    `id_product` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_product` VARCHAR(20) NOT NULL UNIQUE,
    `nama_product` VARCHAR(100) NOT NULL,
    `model` VARCHAR(50),
    `description` TEXT,
    `category` ENUM('laptop', 'aksesoris', 'komputer', 'penyimpanan', 'peripheral', 'sparepart', 'lainnya') NOT NULL,
    `stok` INT NOT NULL DEFAULT 0,
    `harga` DECIMAL(15,2) NOT NULL,
    `gambar_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT NOT NULL,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`created_by`) REFERENCES `User`(`id_user`)
);

-- 4. Service Request Table
CREATE TABLE `ServiceRequest` (
    `id_service` INT AUTO_INCREMENT PRIMARY KEY,
    `id_customer` INT NOT NULL,
    `id_sales` INT NOT NULL,
    `id_teknisi` INT NULL,
    `kode_service` VARCHAR(20) NOT NULL UNIQUE,
    `nama_service` VARCHAR(100) NOT NULL,
    `merk` VARCHAR(50),
    `model` VARCHAR(50),
    `serial_no` VARCHAR(50),
    `kategori_barang` ENUM('komputer', 'laptop', 'lainnya') NOT NULL,
    `kelengkapan` TEXT,
    `deskripsi_kerusakan` TEXT NOT NULL,
    `tanggal_masuk` DATE NOT NULL,
    `tanggal_estimasi` DATE,
    `status` ENUM('pending', 'diterima', 'ditolak') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`id_customer`) REFERENCES `Customer`(`id_customer`),
    FOREIGN KEY (`id_sales`) REFERENCES `User`(`id_user`),
    FOREIGN KEY (`id_teknisi`) REFERENCES `User`(`id_user`) ON DELETE SET NULL
);

-- 5. Service Progress Table
CREATE TABLE `ServiceProgress` (
    `id_progress` INT AUTO_INCREMENT PRIMARY KEY,
    `id_service` INT NOT NULL,
    `status` ENUM('diterima_digerai', 'analisis_kerusakan', 'menunggu_sparepart', 'dalam_perbaikan', 'perbaikan_selesai', 'gagal', 'diambil_pelanggan') NOT NULL,
    `catatan` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_service`) REFERENCES `ServiceRequest`(`id_service`) ON DELETE CASCADE
);

-- 6. Service Sparepart Table
CREATE TABLE `ServiceSparepart` (
    `id_sparepart_used` INT AUTO_INCREMENT PRIMARY KEY,
    `id_service` INT NOT NULL,
    `id_product` INT NOT NULL,
    `jumlah` INT NOT NULL,
    `harga_saat_pemasangan` DECIMAL(12, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_service`) REFERENCES `ServiceRequest`(`id_service`) ON DELETE CASCADE,
    FOREIGN KEY (`id_product`) REFERENCES `Product`(`id_product`) ON DELETE RESTRICT
);

-- 7. Transaction Table
CREATE TABLE `Transaction` (
    `id_transaction` INT AUTO_INCREMENT PRIMARY KEY,
    `id_customer` INT NULL,
    `guest_name` VARCHAR(100) NULL COMMENT 'Untuk transaksi offline/tanpa akun',
    `id_sales` INT NOT NULL,
    `jenis_transaksi` ENUM('barang', 'service') NOT NULL,
    `tanggal_transaksi` DATE NOT NULL,
    `total_harga` DECIMAL(15,2) NOT NULL,
    `discount` DECIMAL(15,2) DEFAULT 0,
    `ppn_pajak` DECIMAL(15,2) DEFAULT 0,
    `status_pembayaran` ENUM('lunas', 'belum_bayar') DEFAULT 'belum_bayar',
    `metode_bayar` ENUM('tunai', 'transfer') NOT NULL,
    `metode_pengambilan` ENUM('ambil_sendiri', 'diantar') NOT NULL,
    `status_pengiriman` ENUM('menunggu', 'diproses', 'dikirim', 'selesai') DEFAULT 'menunggu',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`id_customer`) REFERENCES `Customer`(`id_customer`),
    FOREIGN KEY (`id_sales`) REFERENCES `User`(`id_user`)
);

-- 8. Transaction Product Detail Table
CREATE TABLE `TransactionProductDetail` (
    `id_detail` INT AUTO_INCREMENT PRIMARY KEY,
    `id_transaction` INT NOT NULL,
    `id_product` INT NOT NULL,
    `qty` INT NOT NULL,
    `harga_satuan` DECIMAL(15,2) NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_transaction`) REFERENCES `Transaction`(`id_transaction`) ON DELETE CASCADE,
    FOREIGN KEY (`id_product`) REFERENCES `Product`(`id_product`)
);

-- 9. Transaction Service Detail Table
CREATE TABLE `TransactionServiceDetail` (
    `id_detail` INT AUTO_INCREMENT PRIMARY KEY,
    `id_transaction` INT NOT NULL,
    `id_service` INT NOT NULL,
    `biaya_service` DECIMAL(15,2) NOT NULL,
    `biaya_sparepart` DECIMAL(15,2) DEFAULT 0,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_transaction`) REFERENCES `Transaction`(`id_transaction`) ON DELETE CASCADE,
    FOREIGN KEY (`id_service`) REFERENCES `ServiceRequest`(`id_service`) ON DELETE CASCADE
);

-- 10. Campaign Table
CREATE TABLE `Campaign` (
    `id_campaign` INT AUTO_INCREMENT PRIMARY KEY,
    `id_marketing` INT NOT NULL,
    `nama_kampanye` VARCHAR(100) NOT NULL,
    `subjek` VARCHAR(255),
    `jenis_kampanye` ENUM('email', 'internal_platform', 'semua') NOT NULL,
    `target_segmentasi` ENUM('baru', 'perusahaan', 'loyal', 'semua') NOT NULL,
    `deskripsi` TEXT,
    `tautan_produk` VARCHAR(255),
    `kode_promo` VARCHAR(20),
    `tanggal_mulai` DATE NOT NULL,
    `tanggal_selesai` DATE,
    `status` ENUM('draft', 'menunggu_acc', 'aktif', 'ditolak', 'selesai') DEFAULT 'draft',
    `rejection_reason` TEXT NULL COMMENT 'Alasan penolakan dari Admin',
    `approved_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`id_marketing`) REFERENCES `User`(`id_user`),
    FOREIGN KEY (`approved_by`) REFERENCES `User`(`id_user`) ON DELETE SET NULL
);

-- 11. Campaign Email Target Table
CREATE TABLE `CampaignEmailTarget` (
    `id_target` INT AUTO_INCREMENT PRIMARY KEY,
    `id_campaign` INT NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `status` ENUM('menunggu', 'terkirim', 'gagal') DEFAULT 'menunggu',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_campaign`) REFERENCES `Campaign`(`id_campaign`) ON DELETE CASCADE
);

-- 12. Campaign Tracking Table
CREATE TABLE `CampaignTracking` (
    `id_tracking` INT AUTO_INCREMENT PRIMARY KEY,
    `id_campaign` INT NOT NULL,
    `id_customer` INT,
    `platform` ENUM('email', 'web', 'mobile') NOT NULL,
    `action` ENUM('dibuka', 'diklik', 'terkirim') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_campaign`) REFERENCES `Campaign`(`id_campaign`) ON DELETE CASCADE,
    FOREIGN KEY (`id_customer`) REFERENCES `Customer`(`id_customer`) ON DELETE SET NULL
);

-- 13. Activity Log Table
CREATE TABLE `ActivityLog` (
  `id_log` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `User`(`id_user`) ON DELETE SET NULL
);

-- 14. Campaign Template Table
CREATE TABLE `CampaignTemplate` (
  `id_template` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_template` VARCHAR(100) NOT NULL,
  `subjek` VARCHAR(255),
  `konten` TEXT NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `User`(`id_user`)
);

-- 15. Business Profile Table
CREATE TABLE `BusinessProfile` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `nama_bisnis` VARCHAR(100) NOT NULL,
  `logo_url` VARCHAR(255) NULL,
  `alamat` TEXT NULL,
  `telepon` VARCHAR(20) NULL,
  `email` VARCHAR(100) NULL,
  `website` VARCHAR(100) NULL,
  `jam_operasional` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 16. SMTP Settings Table
CREATE TABLE `SmtpSettings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `server` VARCHAR(100) NOT NULL,
  `port` INT NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `security` ENUM('ssl', 'tls', 'none') DEFAULT 'tls',
  `sender_name` VARCHAR(100) NOT NULL,
  `sender_email` VARCHAR(100) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- ================================================================
-- PROSEDUR, TRIGGER, DAN VIEW (DENGAN SINTAKS YANG DIPERBAIKI)
-- ================================================================

-- Generate unique service code
DROP PROCEDURE IF EXISTS generate_service_code;
DELIMITER //
CREATE PROCEDURE generate_service_code(OUT service_code VARCHAR(20))
BEGIN
    DECLARE new_code VARCHAR(20);
    SET new_code = CONCAT('SRV', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    WHILE EXISTS (SELECT 1 FROM ServiceRequest WHERE kode_service = new_code) DO
        SET new_code = CONCAT('SRV', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    END WHILE;
    SET service_code = new_code;
END//
DELIMITER ;

-- Trigger untuk update customer transaction stats
DROP TRIGGER IF EXISTS after_transaction_insert;
DELIMITER //
CREATE TRIGGER after_transaction_insert
AFTER INSERT ON Transaction
FOR EACH ROW
BEGIN
    IF NEW.id_customer IS NOT NULL THEN
        UPDATE Customer 
        SET total_transaksi = total_transaksi + 1,
            total_pengeluaran = total_pengeluaran + NEW.total_harga
        WHERE id_customer = NEW.id_customer;
    END IF;
END//
DELIMITER ;

-- Trigger untuk update sparepart stock
DROP TRIGGER IF EXISTS after_sparepart_used;
DELIMITER //
CREATE TRIGGER after_sparepart_used
AFTER INSERT ON ServiceSparepart
FOR EACH ROW
BEGIN
    UPDATE Product 
    SET stok = stok - NEW.jumlah 
    WHERE id_product = NEW.id_product;
END//
DELIMITER ;

-- View for customer service tracking
DROP VIEW IF EXISTS CustomerServiceTracking;
CREATE VIEW CustomerServiceTracking AS
SELECT 
    sr.id_service,
    sr.kode_service,
    c.id_customer,
    u.nama_lengkap AS customer_name,
    sr.nama_service,
    sr.status AS request_status,
    sp.status AS progress_status,
    sp.created_at AS last_update
FROM ServiceRequest sr
JOIN Customer c ON sr.id_customer = c.id_customer
JOIN User u ON c.id_user = u.id_user
LEFT JOIN (
    SELECT id_service, status, created_at
    FROM ServiceProgress
    WHERE (id_service, created_at) IN (
        SELECT id_service, MAX(created_at)
        FROM ServiceProgress
        GROUP BY id_service
    )
) sp ON sr.id_service = sp.id_service;

-- ================================================================
-- INDEXES
-- ================================================================

CREATE INDEX idx_customer_user ON Customer(id_user);
CREATE INDEX idx_product_created ON Product(created_by);
CREATE INDEX idx_service_customer ON ServiceRequest(id_customer);
CREATE INDEX idx_service_sales ON ServiceRequest(id_sales);
CREATE INDEX idx_transaction_customer ON Transaction(id_customer);
CREATE INDEX idx_transaction_sales ON Transaction(id_sales);
CREATE INDEX idx_campaign_marketing ON Campaign(id_marketing);

-- Mengaktifkan kembali pengecekan foreign key setelah impor selesai
SET FOREIGN_KEY_CHECKS=1;