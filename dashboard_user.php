<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user tickets
$query_tiket = "SELECT t.*, COALESCE(p.payment_status, 'pending') as payment_status FROM tiket t 
                LEFT JOIN payments p ON t.id_tiket = p.id_tiket 
                WHERE t.user_id = '$user_id' ORDER BY t.id_tiket DESC LIMIT 5";
$result_tiket = mysqli_query($conn, $query_tiket);

// Get all wisata with details
$query_wisata = "SELECT w.*, COUNT(r.id_review) as total_reviews, 
                 COALESCE(AVG(r.rating), 0) as avg_rating,
                 COUNT(DISTINCT f.id_fasilitas) as total_facilities
                 FROM wisata w 
                 LEFT JOIN reviews r ON w.id_wisata = r.id_wisata
                 LEFT JOIN fasilitas_wisata f ON w.id_wisata = f.id_wisata
                 WHERE w.status_aktif = 1
                 GROUP BY w.id_wisata
                 ORDER BY w.id_wisata";
$result_wisata = mysqli_query($conn, $query_wisata);

// Get pending payments
$query_pending = "SELECT t.*, p.id_payment FROM tiket t 
                  LEFT JOIN payments p ON t.id_tiket = p.id_tiket
                  WHERE t.user_id = '$user_id' AND (t.status = 'pending' OR p.payment_status = 'pending')
                  ORDER BY t.tgl_beli DESC";
$result_pending = mysqli_query($conn, $query_pending);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wisata_Malang</title>
    <!-- Menambahkan Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style_stars.css">
    <!-- Google Fonts untuk tampilan modern -->
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

    <!-- Navbar dengan Tailwind utility -->
    <nav class="navbar navbar-expand-lg navbar-dark border-bottom border-white/10 mb-12 relative z-10 bg-black/20 backdrop-blur-sm">
        <div class="container py-2">
            <a class="navbar-brand fw-extrabold tracking-tighter text-2xl" href="#">
                MALANG<span class="text-accent">ANS</span>
            </a>
            <div class="ms-auto text-white text-sm flex items-center gap-3">
                <span class="opacity-70">Halo,</span> 
                <span class="font-bold text-blue-400"><?= $_SESSION['nama']; ?></span>
                <div class="h-4 w-[1px] bg-white/20 mx-2"></div>
                <a href="logout.php" class="text-red-400 hover:text-red-300 font-bold no-underline transition-colors">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container relative z-10">
        <!-- Pending Payments Section -->
        <?php 
        $pending_count = mysqli_num_rows($result_pending);
        if ($pending_count > 0):
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
                                <small class="text-gray-400"><?= $pending['jumlah']; ?> Tiket • Rp <?= number_format($pending['total_harga'], 0, ',', '.'); ?></small>
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
            <!-- Kolom Destinasi -->
            <div class="col-lg-7 mb-8">
                <h4 class="font-black text-2xl mb-8 flex items-center gap-3">
                    <span class="w-2 h-8 bg-blue-500 rounded-full"></span>
                    Destinasi Wisata Populer
                </h4>
                <div class="row g-4">
                    <?php 
                    mysqli_data_seek($result_wisata, 0);
                    while ($wisata = mysqli_fetch_assoc($result_wisata)): 
                    ?>
                    <div class="col-md-6">
                        <div class="glass-card h-100 p-6 position-relative overflow-hidden">
                            <!-- Header dengan Icon dan Info -->
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
                            
                            <!-- Nama dan Deskripsi -->
                            <h5 class="font-bold text-xl mb-2"><?= $wisata['nama_wisata']; ?></h5>
                            <p class="text-sm text-gray-400 leading-relaxed mb-4 line-clamp-2"><?= $wisata['deskripsi']; ?></p>
                            
                            <!-- Info Fasilitas dan Jam -->
                            <div class="text-xs text-gray-500 mb-4 space-y-1">
                                <div>📍 <?= $wisata['lokasi']; ?></div>
                                <div>🕐 <?= substr($wisata['jam_buka'], 0, 5); ?> - <?= substr($wisata['jam_tutup'], 0, 5); ?> WIB</div>
                                <div>🏢 <?= $wisata['total_facilities']; ?> Fasilitas</div>
                            </div>
                            
                            <!-- Harga dan Tombol -->
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
                    <?php endwhile; ?>
                </div>
                
                <!-- Tombol Lihat Semua Wisata -->
                <div class="text-center mt-6">
                    <a href="wisata.php" class="btn btn-outline-primary rounded-pill px-5 font-bold">
                        👉 Jelajahi Semua Wisata →
                    </a>
                </div>
            </div>

            <!-- Kolom Tiket Saya -->
            <div class="col-lg-5">
                <h4 class="font-black text-2xl mb-8 flex items-center gap-3">
                    <span class="w-2 h-8 bg-blue-500 rounded-full"></span>
                    Tiket & Pembayaran
                </h4>
                
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4 border-bottom border-white/10 bg-transparent" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-white bg-transparent border-0 border-bottom border-blue-500" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-tickets" type="button" role="tab">
                            ✅ Aktif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white bg-transparent border-0" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-tickets" type="button" role="tab">
                            📜 Riwayat
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Active Tickets -->
                    <div class="tab-pane fade show active glass-card p-6" id="active-tickets" role="tabpanel">
                        <?php 
                        $active_count = 0;
                        mysqli_data_seek($result_tiket, 0);
                        $tickets_data = [];
                        while ($row = mysqli_fetch_assoc($result_tiket)) {
                            $tickets_data[] = $row;
                            if ($row['status'] == 'lunas' && $row['payment_status'] == 'success') {
                                $active_count++;
                            }
                        }
                        
                        if ($active_count > 0):
                            foreach ($tickets_data as $row):
                                if ($row['status'] == 'lunas' && $row['payment_status'] == 'success'):
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
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= $row['kode_barcode']; ?>" alt="QR" class="w-24 h-24 mx-auto">
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

                    <!-- History Tickets -->
                    <div class="tab-pane fade glass-card p-6" id="history-tickets" role="tabpanel">
                        <?php 
                        $history_count = 0;
                        foreach ($tickets_data as $row):
                            if ($row['status'] != 'lunas' || $row['payment_status'] != 'success'):
                                $history_count++;
                        ?>
                        <div class="mb-3 p-4 bg-white/5 rounded-xl border border-white/10">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h6 class="mb-1 font-bold text-sm"><?= $row['wisata']; ?></h6>
                                    <small class="text-gray-500"><?= $row['jumlah']; ?> Tiket</small>
                                </div>
                                <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter 
                                    <?= $row['payment_status'] == 'success' ? 'bg-green-500/20 text-green-400' : ($row['payment_status'] == 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') ?>">
                                    <?= ucfirst($row['payment_status']); ?>
                                </span>
                            </div>
                            <?php if ($row['payment_status'] == 'pending'): ?>
                            <a href="payment_gateway.php?action=initiate&tiket_id=<?= $row['id_tiket']; ?>" class="btn btn-sm btn-warning w-100 mt-2 font-bold">Lanjutkan Pembayaran</a>
                            <?php endif; ?>
                        </div>
                        <?php 
                            endif;
                        endforeach;
                        
                        if ($history_count == 0):
                        ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-sm">Tidak ada riwayat</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Summary Card -->
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
                                foreach ($tickets_data as $t) $total += $t['total_harga'];
                                echo rupiah($total);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View All Button -->
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

    <!-- Bootstrap JS untuk Tab -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>