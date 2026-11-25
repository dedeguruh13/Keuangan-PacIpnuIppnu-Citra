-- Database Sistem Keuangan PAC IPNU IPPNU Cikarang Utara
-- Import lewat phpMyAdmin atau: mysql -u root -p < database.sql

CREATE DATABASE IF NOT EXISTS keuangan_pac CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE keuangan_pac;

CREATE TABLE IF NOT EXISTS admin (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  nama          VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaksi (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal       DATE NOT NULL,
  jenis         ENUM('masuk','keluar') NOT NULL,
  metode        ENUM('cash','qris') NOT NULL,
  nominal       BIGINT UNSIGNED NOT NULL,
  nama_pembayar VARCHAR(100) NULL,
  keterangan    VARCHAR(255) NOT NULL,
  created_by    INT UNSIGNED NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tanggal (tanggal),
  INDEX idx_jenis_metode (jenis, metode),
  CONSTRAINT fk_transaksi_admin FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Akun admin awal  ->  username: admin   password: admin123
-- WAJIB diganti lewat menu "Akun" setelah login pertama.
INSERT INTO admin (username, nama, password_hash) VALUES
('admin', 'Admin Bendahara', '$2y$10$hUk51lEGIhBfDXuClFUGIeI8oRRpHHtHAiYUGlguwhqm62bwv4Uju');
