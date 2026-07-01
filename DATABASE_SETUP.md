# 📊 Database Setup - Tiket Wisata Malang

## Quick Start - Import Database

### Option 1: Gunakan file SQL siap pakai

#### Di phpMyAdmin:
1. Buka http://localhost/phpmyadmin
2. Klik **"Import"**
3. Pilih file `tiket_malang_backup.sql` atau `db_init.sql`
4. Klik **"Go"**
5. Database `tiket_malang` akan terbuat dengan semua tabel dan data

#### Di Terminal/Command Prompt:

```bash
# Gunakan db_init.sql (schema + data Jatimpark & Bromo)
mysql -u root < db_init.sql

# ATAU gunakan tiket_malang_backup.sql (complete backup)
mysql -u root < tiket_malang_backup.sql
```

#### Di XAMPP MySQL:

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root < db_init.sql
```

---

## Database Schema

### 🔐 Tabel Users
```sql
CREATE TABLE users (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nama VARCHAR(200) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

### 🎫 Tabel Tiket
```sql
CREATE TABLE tiket (
  id_tiket INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  wisata VARCHAR(255) NOT NULL,
  jumlah INT UNSIGNED DEFAULT 1,
  total_harga INT UNSIGNED DEFAULT 0,
  tgl_beli DATETIME NOT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  kode_barcode VARCHAR(100) NOT NULL,
  payment_method VARCHAR(50),
  payment_id VARCHAR(100),
  FOREIGN KEY (user_id) REFERENCES users(id)
)
```

### 💳 Tabel Payments
```sql
CREATE TABLE payments (
  id_payment INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_tiket INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  jumlah INT UNSIGNED NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  payment_status ENUM('pending','success','failed','expired') DEFAULT 'pending',
  transaction_id VARCHAR(100) UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_tiket) REFERENCES tiket(id_tiket),
  FOREIGN KEY (user_id) REFERENCES users(id)
)
```

### ↩️ Tabel Refunds
```sql
CREATE TABLE refunds (
  id_refund INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_payment INT UNSIGNED NOT NULL,
  id_tiket INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  jumlah_refund INT UNSIGNED NOT NULL,
  alasan TEXT,
  status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
  request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_date TIMESTAMP NULL,
  completed_date TIMESTAMP NULL,
  FOREIGN KEY (id_payment) REFERENCES payments(id_payment),
  FOREIGN KEY (id_tiket) REFERENCES tiket(id_tiket),
  FOREIGN KEY (user_id) REFERENCES users(id)
)
```

### 📊 Tabel Analytics
```sql
CREATE TABLE analytics (
  id_analytics INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tanggal DATE NOT NULL UNIQUE,
  total_tiket_terjual INT UNSIGNED DEFAULT 0,
  total_revenue BIGINT UNSIGNED DEFAULT 0,
  wisata_terpopuler VARCHAR(255),
  visitor_count INT UNSIGNED DEFAULT 0,
  predicted_revenue_next_day BIGINT UNSIGNED DEFAULT 0,
  trend VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

### 🏖️ Tabel Wisata
```sql
CREATE TABLE wisata (
  id_wisata INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nama_wisata VARCHAR(255) NOT NULL UNIQUE,
  deskripsi LONGTEXT,
  lokasi VARCHAR(255) NOT NULL,
  harga_tiket INT UNSIGNED NOT NULL,
  jam_buka TIME,
  jam_tutup TIME,
  kategori VARCHAR(100),
  rating DECIMAL(3,2) DEFAULT 0,
  total_review INT UNSIGNED DEFAULT 0,
  gambar_url VARCHAR(500),
  lat DECIMAL(10,8),
  lon DECIMAL(11,8),
  no_hp_contact VARCHAR(20),
  email_contact VARCHAR(100),
  status_aktif BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### ⭐ Tabel Reviews
```sql
CREATE TABLE reviews (
  id_review INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_wisata INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating INT UNSIGNED CHECK (rating BETWEEN 1 AND 5),
  ulasan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_wisata) REFERENCES wisata(id_wisata),
  FOREIGN KEY (user_id) REFERENCES users(id)
)
```

### 🎪 Tabel Fasilitas Wisata
```sql
CREATE TABLE fasilitas_wisata (
  id_fasilitas INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  id_wisata INT UNSIGNED NOT NULL,
  nama_fasilitas VARCHAR(100) NOT NULL,
  keterangan VARCHAR(255),
  tersedia BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (id_wisata) REFERENCES wisata(id_wisata)
)
```

---

## Sample Data

### Wisata yang Sudah Tersedia:

| ID | Nama | Harga | Rating | Lokasi |
|----|------|-------|--------|--------|
| 1 | Jatimpark 1 | Rp 150.000 | 4.5/5 | Kota Batu |
| 2 | Gunung Bromo | Rp 120.000 | 4.8/5 | Probolinggo |

### Fasilitas Jatimpark 1:
- Kantin
- Toilet
- Parkir
- First Aid
- Mushola
- Tempat Duduk

### Fasilitas Gunung Bromo:
- Akomodasi
- Pemandu Wisata
- Penyewaan Kuda
- Warung Makan
- Toilet
- Pertolongan Medis

---

## Konfigurasi Database

### Environment Variables (`.env`)

```env
# Database Connection
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=tiket_malang
DB_PORT=3306

# Midtrans Payment Gateway
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_ENVIRONMENT=sandbox
```

### config.php

File `config.php` sudah dikonfigurasi untuk membaca dari environment variables:

```php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: 'tiket_malang';
$port = getenv('DB_PORT') ?: 3306;
```

---

## Verifikasi Database

Setelah import, verifikasi dengan:

```bash
# Masuk ke MySQL
mysql -u root

# Pilih database
USE tiket_malang;

# Lihat semua tabel
SHOW TABLES;

# Lihat data wisata
SELECT * FROM wisata;

# Lihat fasilitas
SELECT * FROM fasilitas_wisata;

# Lihat struktur tabel
DESCRIBE users;
DESCRIBE tiket;
DESCRIBE payments;
DESCRIBE refunds;
DESCRIBE analytics;
DESCRIBE wisata;
DESCRIBE reviews;
DESCRIBE fasilitas_wisata;
```

---

## Migration & Backup

### Membuat Backup:

```bash
mysqldump -u root tiket_malang > tiket_malang_backup.sql
```

### Restore dari Backup:

```bash
mysql -u root tiket_malang < tiket_malang_backup.sql
```

### Export untuk Production:

```bash
# Include struktur dan data
mysqldump -u root --single-transaction --routines --triggers tiket_malang > tiket_malang_prod.sql

# Hanya struktur
mysqldump -u root --no-data tiket_malang > tiket_malang_structure.sql
```

---

## Troubleshooting

### Error: "Database doesn't exist"

```bash
# Create database dulu
mysql -u root -e "CREATE DATABASE tiket_malang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Kemudian import
mysql -u root tiket_malang < db_init.sql
```

### Error: "Foreign key constraint is incorrectly formed"

```bash
# Disable foreign key checks saat import
mysql -u root tiket_malang -e "SET FOREIGN_KEY_CHECKS=0;"
mysql -u root tiket_malang < db_init.sql
mysql -u root tiket_malang -e "SET FOREIGN_KEY_CHECKS=1;"
```

### Access phpMyAdmin Database:

1. Buka: http://localhost/phpmyadmin
2. Username: `root`
3. Password: (kosong/blank)
4. Select database: `tiket_malang`

---

## Files Included

- `db_init.sql` - Schema + Sample data (Jatimpark & Bromo)
- `tiket_malang_backup.sql` - Complete database backup
- `DATABASE_SETUP.md` - This file (documentation)
- `config.php` - Database configuration
- `wisata_info.php` - API endpoints untuk wisata

---

## Support

Untuk bantuan lebih lanjut, hubungi tim development atau lihat dokumentasi di `API_DOCUMENTATION.md`
