-- Database schema for Wisata_Malang
-- Jika menggunakan Railway MySQL, jalankan hanya CREATE TABLE saja setelah database dibuat.

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(200) NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk informasi wisata (Dinaikkan ke atas karena tabel 'tiket' & 'reviews' mereferensikannya)
CREATE TABLE IF NOT EXISTS `wisata` (
    `id_wisata` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama_wisata` VARCHAR(255) NOT NULL UNIQUE,
    `deskripsi` LONGTEXT,
    `lokasi` VARCHAR(255) NOT NULL,
    `harga_tiket` INT UNSIGNED NOT NULL,
    `jam_buka` TIME,
    `jam_tutup` TIME,
    `kategori` VARCHAR(100),
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `total_review` INT UNSIGNED DEFAULT 0,
    `gambar_url` VARCHAR(500),
    `lat` DECIMAL(10, 8),
    `lon` DECIMAL(11, 8),
    `no_hp_contact` VARCHAR(20),
    `email_contact` VARCHAR(100),
    `status_aktif` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_wisata_nama` (`nama_wisata`),
    INDEX `idx_wisata_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tiket` (
    `id_tiket` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `wisata` VARCHAR(255) NOT NULL, -- Redundan dengan tabel wisata, tapi aman jika untuk snapshot nama wisata masa lalu
    `jumlah` INT UNSIGNED NOT NULL DEFAULT 1,
    `total_harga` INT UNSIGNED NOT NULL DEFAULT 0,
    `tgl_beli` DATETIME NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `kode_barcode` VARCHAR(100) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `payment_id` VARCHAR(100) DEFAULT NULL,
    INDEX `idx_tiket_user_id` (`user_id`),
    CONSTRAINT `fk_tiket_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk pembayaran
CREATE TABLE IF NOT EXISTS `payments` (
    `id_payment` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_tiket` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `jumlah` INT UNSIGNED NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `payment_status` ENUM('pending','success','failed','expired') NOT NULL DEFAULT 'pending',
    `transaction_id` VARCHAR(100) UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_payment_tiket` (`id_tiket`),
    INDEX `idx_payment_user` (`user_id`),
    CONSTRAINT `fk_payment_tiket` FOREIGN KEY (`id_tiket`) REFERENCES `tiket`(`id_tiket`) ON DELETE CASCADE,
    CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk refund
CREATE TABLE IF NOT EXISTS `refunds` (
    `id_refund` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_payment` INT UNSIGNED NOT NULL,
    `id_tiket` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `jumlah_refund` INT UNSIGNED NOT NULL,
    `alasan` TEXT,
    `status` ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
    `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `approved_date` TIMESTAMP NULL,
    `completed_date` TIMESTAMP NULL,
    INDEX `idx_refund_tiket` (`id_tiket`),
    INDEX `idx_refund_user` (`user_id`),
    CONSTRAINT `fk_refund_payment` FOREIGN KEY (`id_payment`) REFERENCES `payments`(`id_payment`) ON DELETE CASCADE,
    CONSTRAINT `fk_refund_tiket` FOREIGN KEY (`id_tiket`) REFERENCES `tiket`(`id_tiket`) ON DELETE CASCADE,
    CONSTRAINT `fk_refund_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk analytics & prediksi
CREATE TABLE IF NOT EXISTS `analytics` (
    `id_analytics` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tanggal` DATE NOT NULL,
    `total_tiket_terjual` INT UNSIGNED DEFAULT 0,
    `total_revenue` BIGINT UNSIGNED DEFAULT 0,
    `wisata_terpopuler` VARCHAR(255),
    `visitor_count` INT UNSIGNED DEFAULT 0,
    `predicted_revenue_next_day` BIGINT UNSIGNED DEFAULT 0,
    `trend` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_analytics_date` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk review/ulasan wisata
CREATE TABLE IF NOT EXISTS `reviews` (
    `id_review` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_wisata` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `rating` INT UNSIGNED CHECK (rating >= 1 AND rating <= 5),
    `ulasan` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_review_wisata` (`id_wisata`),
    INDEX `idx_review_user` (`user_id`),
    CONSTRAINT `fk_review_wisata` FOREIGN KEY (`id_wisata`) REFERENCES `wisata`(`id_wisata`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk fasilitas wisata
CREATE TABLE IF NOT EXISTS `fasilitas_wisata` (
    `id_fasilitas` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_wisata` INT UNSIGNED NOT NULL,
    `nama_fasilitas` VARCHAR(100) NOT NULL,
    `keterangan` VARCHAR(255),
    `tersedia` BOOLEAN DEFAULT TRUE,
    CONSTRAINT `fk_fasilitas_wisata` FOREIGN KEY (`id_wisata`) REFERENCES `wisata`(`id_wisata`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--- ==========================================
--- PROSES INSERT DATA (SUDAH DIPERBAIKI)
--- ==========================================

-- Insert data Jatimpark 1
INSERT INTO wisata (nama_wisata, deskripsi, lokasi, harga_tiket, jam_buka, jam_tutup, kategori, rating, gambar_url, lat, lon, no_hp_contact, email_contact) VALUES
('Jatimpark 1', 'Jatimpark 1 adalah wahana permainan keluarga terbesar di Jawa Timur yang menghadirkan lebih dari 20 permainan edukatif dan rekreatif. Cocok untuk anak-anak dan keluarga dengan berbagai atraksi seru seperti rumah hantu, labirin, flying fox, dan berbagai permainan interaktif lainnya.', 'Jl. Oro-Oro Dowo, Kota Batu, Jawa Timur', 150000, '09:00:00', '17:00:00', 'Taman Hiburan', 4.50, 'https://via.placeholder.com/jatimpark1.jpg', -7.8945, 112.3050, '0341-597711', 'info@jatimpark.com');

-- Insert data Gunung Bromo
INSERT INTO wisata (nama_wisata, deskripsi, lokasi, harga_tiket, jam_buka, jam_tutup, kategori, rating, gambar_url, lat, lon, no_hp_contact, email_contact) VALUES
('Gunung Bromo', 'Gunung Bromo adalah salah satu gunung berapi paling terkenal di Indonesia dengan ketinggian 2.392 meter. Pemandangan matahari terbit dari puncak Gunung Bromo sangat menakjubkan. Terletak di tengah Taman Nasional Bromo Tengger Semeru dengan pemandangan panorama yang spektakuler.', 'Probolinggo, Pasuruan, Jawa Timur', 120000, '04:00:00', '17:00:00', 'Gunung', 4.80, 'https://via.placeholder.com/bromo.jpg', -7.9424, 112.9526, '0325-123456', 'info@bromonationalpark.com');

-- Insert fasilitas Jatimpark 1 (Menggunakan Subquery agar ID dinamis dan anti-error)
INSERT INTO fasilitas_wisata (id_wisata, nama_fasilitas, keterangan, tersedia) VALUES
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Jatimpark 1'), 'Kantin', 'Tersedia berbagai makanan dan minuman', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Jatimpark 1'), 'Toilet', 'Toilet bersih dan nyaman', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Jatimpark 1'), 'Parkir', 'Area parkir luas dengan keamanan 24 jam', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Jatimpark 1'), 'First Aid', 'Fasilitas pertolongan pertama darurat', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Jatimpark 1'), 'Mushola', 'Tempat ibadah untuk muslim', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Jatimpark 1'), 'Tempat Duduk', 'Area istirahat dengan kursi dan meja', TRUE);

-- Insert fasilitas Gunung Bromo (Menggunakan Subquery agar ID dinamis dan anti-error)
INSERT INTO fasilitas_wisata (id_wisata, nama_fasilitas, keterangan, tersedia) VALUES
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Gunung Bromo'), 'Akomodasi', 'Homestay dan hotel tersedia di sekitar area', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Gunung Bromo'), 'Pemandu Wisata', 'Tersedia pemandu berpengalaman', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Gunung Bromo'), 'Penyewaan Kuda', 'Penyewaan kuda untuk pendakian', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Gunung Bromo'), 'Warung Makan', 'Warung makan sederhana di sekitar area', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Gunung Bromo'), 'Toilet', 'Toilet tersedia di beberapa titik', TRUE),
((SELECT id_wisata FROM wisata WHERE nama_wisata = 'Gunung Bromo'), 'Pertolongan Medis', 'Pos kesehatan di base camp', TRUE);