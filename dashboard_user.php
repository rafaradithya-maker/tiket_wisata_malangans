<?php
session_start();
require_once 'config.php';

// Fungsi konversi rupiah jika belum ada di config.php
if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$db_error = false;

if (!table_exists($conn, 'payments')) {
    $create_payments = "CREATE TABLE IF NOT EXISTS payments (
        id_payment INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_tiket INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        jumlah INT UNSIGNED NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_status ENUM('pending','success','failed','expired') NOT NULL DEFAULT 'pending',
        transaction_id VARCHAR(100) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_payments);
}

$query_tiket = null;
$result_tiket = false;
if (table_exists($conn, 'tiket')) {
    $tiket_columns = [
        'id_tiket', 'user_id', 'wisata', 'jumlah', 'total_harga', 'tgl_beli', 'status', 'kode_barcode'
    ];

    try {
        $query_tiket = "SELECT " . implode(', ', $tiket_columns) . ", status AS payment_status
                        FROM tiket
                        WHERE user_id = '" . mysqli_real_escape_string($conn, $user_id) . "'
                        ORDER BY id_tiket DESC LIMIT 5";
        $result_tiket = mysqli_query($conn, $query_tiket);
    } catch (mysqli_sql_exception $e) {
        $result_tiket = false;
        $db_error = true;
        $db_error_message = $e->getMessage();
    }
}

$query_wisata = null;
$result_wisata = false;
if (table_exists($conn, 'wisata')) {
    $query_wisata = "SELECT w.id_wisata, w.nama_wisata, w.deskripsi, w.lokasi, w.harga_tiket, w.jam_buka, w.jam_tutup, w.status_aktif,
                            0 AS total_reviews, 0 AS avg_rating, 0 AS total_facilities
                     FROM wisata w
                     WHERE w.status_aktif = 1
                     ORDER BY w.id_wisata";

    if (table_exists($conn, 'reviews') && table_exists($conn, 'fasilitas_wisata')) {
    $query_wisata = "SELECT w.id_wisata, w.nama_wisata, w.deskripsi, w.lokasi, w.harga_tiket, w.jam_buka, w.jam_tutup, w.status_aktif,
                        COUNT(r.id_review) AS total_reviews,
                        COALESCE(AVG(r.rating), 0) AS avg_rating,
                        COUNT(DISTINCT f.id_fasilitas) AS total_facilities
                    FROM wisata w
                    LEFT JOIN reviews r ON w.id_wisata = r.id_wisata
                    LEFT JOIN fasilitas_wisata f ON w.id_wisata = f.id_wisata
                    WHERE w.status_aktif = 1
                    GROUP BY w.id_wisata
                    ORDER BY w.id_wisata";
    }
    $result_wisata = mysqli_query($conn, $query_wisata);
}

$query_pending = null;
$result_pending = false;
if (table_exists($conn, 'tiket')) {
    $pending_columns = [
        'id_tiket', 'user_id', 'wisata', 'jumlah', 'total_harga', 'tgl_beli', 'status', 'kode_barcode'
    ];

    try {
        $query_pending = "SELECT " . implode(', ', $pending_columns) . ", status AS payment_status
                          FROM tiket
                          WHERE user_id = '" . mysqli_real_escape_string($conn, $user_id) . "' AND status = 'pending'
                          ORDER BY tgl_beli DESC";
        $result_pending = mysqli_query($conn, $query_pending);
    } catch (mysqli_sql_exception $e) {
        $result_pending = false;
        $db_error = true;
        $db_error_message = $e->getMessage();
    }
}

$tickets_data = [];
if ($result_tiket) {
    while ($row = mysqli_fetch_assoc($result_tiket)) {
        $tickets_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wisata_Malang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style_stars.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #050b18;
            color: white;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-5px);
        }
        .text-accent { color: #3b82f6; }
        .bg-primary { background-color: #3b82f6 !important; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .nav-tabs .nav-link {
            border-bottom: 2px solid transparent !important;
            color: rgba(255, 255, 255, 0.6) !important;
        }
        .nav-tabs .nav-link.active {
            border-bottom-color: #3b82f6 !important;
            color: white !important;
        }
        .nav-tabs .nav-link:hover {
            color: white !important;
        }
    </style>
</head>
<body class="min-h-screen">

    <div id="stars-container" class="fixed inset-0 pointer-events-none">
        <?php for ($i = 0; $i < 40; $i++): $size = rand(1, 2); ?>
            <div class="star absolute bg-white rounded-full opacity-40"
                style="left:<?=rand(0,100)?>%; width:<?=$size?>px; height:<?=$size?>px; top:<?=rand(0,100)?>%; --duration:<?=rand(20,40)?>s;"></div>
        <?php endfor; ?>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark border-bottom border-white/10 mb-12 relative z-10 bg-black/20 backdrop-blur-sm">
        <div class="container py-2">
            <a class="navbar-brand fw-extrabold tracking-tighter text-2xl" href="#">
                MALANG<span class="text-accent">ANS</span>
            </a>
            <div class="ms-auto text-white text-sm flex items-center gap-3">
                <span class="opacity-70">Halo,</span> 
                <span class="font-bold text-blue-400"><?= isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User'; ?></span>
                <div class="h-4 w-[1px] bg-white/20 mx-2"></div>
                <a href="logout.php" class="text-red-400 hover:text-red-300 font-bold no-underline transition-colors">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container relative z-10">

        <?php if ($db_error): ?>
        <div class="mb-8 p-4 bg-red-500/20 border-2 border-red-500/30 text-red-200 rounded-2xl">
            <strong class="text-white">⚠️ Sistem Database Belum Siap:</strong> Hubungan data bermasalah.
        </div>
        <?php endif; ?>

        <?php 
        if ($result_pending && mysqli_num_rows($result_pending) > 0):
            $pending_count = mysqli_num_rows($result_pending);
        ?>
        <div class="mb-8 glass-card p-6 border-yellow-500/30 border-2">
            <h5 class="font-bold text-xl text-yellow-400 mb-4 flex items-center gap-2">
                <span>⚠️ Pembayaran Tertunda</span>
                <span class="bg-yellow-500/20 text-yellow-400 px-3 py-1 rounded-full text-sm font-bold"><?= $pending_count; ?></span>
            </h5>
            <div class="row g-3">
                <?php 
                mysqli_data_seek($result_pending, 0);
                while ($pending = mysqli_fetch_assoc($result_pending)): 
                ?>
                <div class="col-md-6">
                    <div class="glass-card p-4 border-yellow-500/20">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h6 class="font-bold text-yellow-400"><?= $pending['wisata']; ?></h6>
                                <small class="text-gray-400"><?= $pending['jumlah']; ?> Tiket • <?= rupiah($pending['total_harga']); ?></small>
                            </div>
                            <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-[10px] font-bold rounded">MENUNGGU</span>
                        </div>
                        <a href="payment_gateway.php?action=initiate&tiket_id=<?= $pending['id_tiket']; ?>" class="btn btn-sm bg-yellow-600 border-0 w-100 font-bold text-white rounded-lg hover:bg-yellow-700">
                            💳 Bayar Sekarang
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-7 mb-8">
                <h4 class="font-black text-2xl mb-8 flex items-center gap-3">
                    <span class="w-2 h-8 bg-blue-500 rounded-full"></span>
                    Destinasi Wisata Populer
                </h4>
                <div class="row g-4">
                    <?php 
                    if ($result_wisata):
                        mysqli_data_seek($result_wisata, 0);
                        while ($wisata = mysqli_fetch_assoc($result_wisata)): 
                    ?>
                    <div class="col-md-6">
                        <div class="glass-card h-100 p-6 position-relative overflow-hidden">
                            <div class="flex justify-between items-start mb-4">
                                <div class="bg-blue-500/10 w-12 h-12 rounded-xl flex items-center justify-center text-2xl">
                                    <?= $wisata['id_wisata'] == 1 ? '🎡' : '🌋'; ?>
                                </div>
                                <div class="text-right">
                                    <div class="text-yellow-400 text-sm font-bold flex items-center gap-1">
                                        ⭐ <?= number_format($wisata['avg_rating'], 1); ?>
                                    </div>
                                    <small class="text-gray-400"><?= $wisata['total_reviews']; ?> ulasan</small>
                                </div>
                            </div>
                            
                            <h5 class="font-bold text-xl mb-2"><?= $wisata['nama_wisata']; ?></h5>
                            <p class="text-sm text-gray-400 leading-relaxed mb-4 line-clamp-2"><?= $wisata['deskripsi']; ?></p>
                            
                            <div class="text-xs text-gray-500 mb-4 space-y-1">
                                <div>📍 <?= $wisata['lokasi']; ?></div>
                                <div>🕐 <?= substr($wisata['jam_buka'], 0, 5); ?> - <?= substr($wisata['jam_tutup'], 0, 5); ?> WIB</div>
                                <div>🏢 <?= $wisata['total_facilities']; ?> Fasilitas</div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-6 pt-4 border-t border-white/5">
                                <div>
                                    <small class="text-gray-500">Harga Per Tiket</small>
                                    <div class="text-blue-400 font-black text-lg"><?= rupiah($wisata['harga_tiket']); ?></div>
                                </div>
                                <a href="wisata.php?id=<?= $wisata['id_wisata']; ?>" class="btn btn-sm bg-primary border-0 rounded-pill px-3 py-2 font-bold text-white transition-all hover:scale-105 shadow-lg shadow-blue-500/20">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile; 
                    endif;
                    ?>
                </div>
                
                <div class="text-center mt-6">
                    <a href="wisata.php" class="btn btn-outline-primary rounded-pill px-5 font-bold">
                        👉 Jelajahi Semua Wisata →
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <h4 class="font-black text-2xl mb-8 flex items-center gap-3">
                    <span class="w-2 h-8 bg-blue-500 rounded-full"></span>
                    Tiket & Pembayaran
                </h4>
                
                <ul class="nav nav-tabs mb-4 border-bottom border-white/10 bg-transparent" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-white bg-transparent border-0" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-tickets" type="button" role="tab">
                            ✅ Aktif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white bg-transparent border-0" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-tickets" type="button" role="tab">
                            📜 Riwayat
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active glass-card p-6" id="active-tickets" role="tabpanel">
                        <?php 
                        $active_count = 0;
                        if (!empty($tickets_data)) {
                            foreach ($tickets_data as $row) {
                                if ($row['status'] === 'confirmed' || $row['status'] === 'lunas') {
                                    $active_count++;
                                }
                            }
                        }
                        
                        if ($active_count > 0):
                            foreach ($tickets_data as $row):
                                if ($row['status'] === 'confirmed' || $row['status'] === 'lunas'):
                        ?>
                        <div class="mb-4 p-4 bg-white/5 rounded-xl border border-green-500/20">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h6 class="mb-1 font-bold text-lg text-green-400">✓ <?= $row['wisata']; ?></h6>
                                    <small class="text-gray-500">
                                        <?= $row['jumlah']; ?> Tiket • <?= date('d M Y', strtotime($row['tgl_beli'])); ?>
                                    </small>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-green-500/20 text-green-400 border border-green-500/20">
                                    LUNAS
                                </span>
                            </div>
                            <div class="bg-white p-3 rounded-xl inline-block shadow-lg w-full mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($row['kode_barcode']); ?>" alt="QR" class="w-24 h-24 mx-auto">
                            </div>
                            <small class="text-gray-400 block text-center">Kode: <?= substr($row['kode_barcode'], 0, 15); ?>...</small>
                        </div>
                        <?php 
                                endif;
                            endforeach;
                        else:
                        ?>
                        <div class="text-center py-8 flex flex-col items-center">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center text-3xl mb-4">🎫</div>
                            <p class="text-gray-500 text-sm font-medium">Tidak ada tiket aktif</p>
                            <a href="wisata.php" class="text-blue-400 text-xs mt-2 hover:text-blue-300">Pesan tiket sekarang →</a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade glass-card p-6" id="history-tickets" role="tabpanel">
                        <?php 
                        $history_count = 0;
                        if (!empty($tickets_data)) {
                            foreach ($tickets_data as $row):
                                if ($row['status'] !== 'confirmed' && $row['status'] !== 'lunas'):
                                    $history_count++;
                        ?>
                        <div class="mb-3 p-4 bg-white/5 rounded-xl border border-white/10">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h6 class="mb-1 font-bold text-sm"><?= $row['wisata']; ?></h6>
                                    <small class="text-gray-500"><?= $row['jumlah']; ?> Tiket</small>
                                </div>
                                <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter bg-yellow-500/20 text-yellow-400">
                                    <?= $row['status']; ?>
                                </span>
                            </div>
                            <?php if ($row['status'] == 'pending'): ?>
                            <a href="payment_gateway.php?action=initiate&tiket_id=<?= $row['id_tiket']; ?>" class="btn btn-sm btn-warning w-100 mt-2 font-bold">Lanjutkan Pembayaran</a>
                            <?php endif; ?>
                        </div>
                        <?php 
                                endif;
                            endforeach;
                        }
                        
                        if ($history_count == 0):
                        ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-sm">Tidak ada riwayat</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-6 p-4 glass-card border-blue-500/30">
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div>
                            <small class="text-gray-500">Total Tiket</small>
                            <div class="text-2xl font-black text-blue-400"><?= count($tickets_data); ?></div>
                        </div>
                        <div>
                            <small class="text-gray-500">Total Harga</small>
                            <div class="text-lg font-black text-blue-400">
                                <?php 
                                $total = 0;
                                if (!empty($tickets_data)) {
                                    foreach ($tickets_data as $t) $total += $t['total_harga'];
                                }
                                echo rupiah($total);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="riwayat.php" class="btn btn-outline-primary btn-sm rounded-pill font-bold">
                        Lihat Semua Riwayat →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-12 mt-12 opacity-40">
        <small class="uppercase tracking-[0.3em] text-[10px] font-bold">&copy; 2026 Wisata Malang — Rafarel Adzka Radithya</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>