CREATE DATABASE IF NOT EXISTS siska_bca 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE siska_bca;

-- TABLE: users

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `login_attempts` INT(11) DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TABLE: surat_kuasa

CREATE TABLE IF NOT EXISTS `surat_kuasa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nomor_surat` VARCHAR(50) DEFAULT NULL,
  `nomor_rekening` VARCHAR(30) NOT NULL,
  `nama_pemilik` VARCHAR(100) NOT NULL,
  `nik_pemilik` VARCHAR(20) DEFAULT NULL,
  `alamat_pemilik` TEXT NOT NULL,
  `telepon_pemilik` VARCHAR(20) DEFAULT NULL,
  `email_pemilik` VARCHAR(100) DEFAULT NULL,
  `jenis_kuasa` ENUM('SETORAN','TARIKAN') NOT NULL,
  `nama_penerima` VARCHAR(100) NOT NULL,
  `nik_penerima` VARCHAR(20) DEFAULT NULL,
  `jabatan_penerima` VARCHAR(100) DEFAULT NULL,
  `hubungan` VARCHAR(50) DEFAULT NULL COMMENT 'Hubungan dengan pemilik rekening',
  `alamat_penerima` TEXT DEFAULT NULL,
  `telepon_penerima` VARCHAR(20) DEFAULT NULL,
  `email_penerima` VARCHAR(100) DEFAULT NULL,
  `limit_transaksi` DECIMAL(20,0) NOT NULL DEFAULT 0,
  `frekuensi_transaksi` VARCHAR(50) DEFAULT NULL COMMENT 'Harian/Mingguan/Bulanan',
  `masa_berlaku` DATE DEFAULT NULL,
  `tanggal_berakhir` DATE DEFAULT NULL,
  `tujuan_kuasa` TEXT DEFAULT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `foto_pemilik` VARCHAR(255) DEFAULT NULL,
  `ttd_pemilik` VARCHAR(255) DEFAULT NULL,
  `foto_penerima` VARCHAR(255) DEFAULT NULL,
  `ttd_penerima` VARCHAR(255) DEFAULT NULL,
  `dokumen_pendukung` VARCHAR(255) DEFAULT NULL COMMENT 'Dokumen tambahan (PDF/Image)',
  `status` ENUM('aktif','nonaktif','kadaluarsa','dicabut') NOT NULL DEFAULT 'aktif',
  `dibuat_oleh` INT(11) DEFAULT NULL,
  `diupdate_oleh` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nomor_rekening` (`nomor_rekening`),
  KEY `idx_nama_pemilik` (`nama_pemilik`),
  KEY `idx_status` (`status`),
  KEY `idx_jenis_kuasa` (`jenis_kuasa`),
  KEY `idx_masa_berlaku` (`masa_berlaku`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `ft_search` (`nama_pemilik`, `nama_penerima`, `nomor_rekening`, `nomor_surat`),
  CONSTRAINT `fk_dibuat_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_diupdate_oleh` FOREIGN KEY (`diupdate_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TABLE: audit_log

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `username` VARCHAR(50) DEFAULT NULL,
  `aksi` VARCHAR(50) NOT NULL,
  `tabel` VARCHAR(50) DEFAULT NULL,
  `record_id` INT(11) DEFAULT NULL,
  `data_lama` JSON DEFAULT NULL,
  `data_baru` JSON DEFAULT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_aksi` (`aksi`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TABLE: sessions (untuk manajemen session)

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `data` TEXT DEFAULT NULL,
  `last_activity` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `email`, `role`, `status`) VALUES
('admin', '$2y$12$Bv0zNTXVpDXgEX0b4VGfGexOxCCnVpKdFbmKQF8Wm0bCBbNJvhqH6', 'Administrator SISKA', 'admin@bca.co.id', 'admin', 'active'),
('pic01', '$2y$12$Bv0zNTXVpDXgEX0b4VGfGexOxCCnVpKdFbmKQF8Wm0bCBbNJvhqH6', 'PIC Asemka 01', 'pic01@bca.co.id', 'admin', 'active'),
('teller01', '$2y$12$Bv0zNTXVpDXgEX0b4VGfGexOxCCnVpKdFbmKQF8Wm0bCBbNJvhqH6', 'Teller 01', 'teller01@bca.co.id', 'user', 'active');
