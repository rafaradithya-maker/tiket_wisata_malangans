<?php
/**
 * Tampilan Halaman Informasi Wisata
 */

session_start();

require 'config.php';
require 'wisata_info.php';

$wisata_manager = new WisataManager($conn);

// Get action
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$searchQuery = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['kategori'] ?? '');

if ($action === 'search' && $searchQuery !== '') {
    $all_wisata = $wisata_manager->search_wisata($searchQuery);
} elseif ($action === 'filter_kategori' && $categoryFilter !== '') {
    $all_wisata = $wisata_manager->filter_by_kategori($categoryFilter);
} elseif ($action === 'top') {
    $all_wisata = $wisata_manager->get_top_wisata();
} elseif ($action === 'trending') {
    $all_wisata = $wisata_manager->get_trending_wisata();
} else {
    $all_wisata = $wisata_manager->get_all_wisata();
}

$show_list = $action !== 'detail' || !$id;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Wisata - Tiket Malang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #050b18;
            color: white;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        header {
            background: rgba(255, 255, 255, 0.06);
            padding: 20px;
            border-radius: 1.5rem;
            margin-bottom: 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
            border-color: rgba(59, 130, 246, 0.35);
        }

        .text-accent { color: #3b82f6; }
        .bg-primary { background-color: #3b82f6 !important; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .stars {
            color: #ffc107;
        }

        .filter-section {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .filter-section input,
        .filter-section select {
            padding: 10px 15px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            color: white;
            min-width: 220px;
        }

        .filter-section input::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .filter-section button {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
        }

        .filter-section button:hover {
            background: #2563eb;
        }

        .wisata-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .wisata-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.18);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .wisata-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .wisata-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #3b82f6 0%, #9333ea 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }

        .wisata-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wisata-content {
            padding: 20px;
        }

        .wisata-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }

        .wisata-location {
            font-size: 0.92rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .wisata-price {
            font-size: 1rem;
            color: #60a5fa;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .wisata-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .view-detail-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
        }

        .view-detail-btn:hover {
            background: #2563eb;
        }

        .detail-section {
            background: rgba(255,255,255,0.05);
            padding: 30px;
            border-radius: 1.5rem;
            margin-bottom: 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.18);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .detail-header {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .detail-section h3,
        .detail-info h2,
        .wisata-name,
        .review-author,
        .stat-value {
            color: white;
        }

        .detail-section p,
        .detail-info-value,
        .review-date,
        .review-text,
        .form-group label {
            color: rgba(255,255,255,0.78);
        }

        .detail-section .section-divider {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.12);
        }

        .detail-info-item {
            display: flex;
            margin-bottom: 15px;
            gap: 15px;
        }

        .detail-info-label {
            font-weight: 700;
            color: #3b82f6;
            min-width: 120px;
        }

        .detail-info-value {
            color: rgba(255,255,255,0.78);
        }

        .reviews-section h3,
        .fasilitas-section h3 {
            color: white;
            margin-bottom: 15px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }

        .fasilitas-item {
            background: rgba(255,255,255,0.06);
            padding: 14px 16px;
            border-radius: 14px;
            border-left: 4px solid #3b82f6;
        }

        .review-item {
            background: rgba(255,255,255,0.06);
            padding: 18px;
            border-radius: 14px;
            margin-bottom: 15px;
            border-left: 4px solid #facc15;
        }

        .back-btn {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            padding: 12px 20px;
            background: rgba(17,24,39,0.9);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .detail-image {
            flex: 1;
            min-width: 300px;
            height: 400px;
            background: linear-gradient(135deg, #3b82f6 0%, #9333ea 100%);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }

        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-info {
            flex: 1;
            min-width: 300px;
        }

        .detail-info h2 {
            font-size: 2rem;
            color: white;
            margin-bottom: 15px;
        }

        .detail-info-item {
            display: flex;
            margin-bottom: 15px;
            gap: 15px;
        }

        .detail-info-label {
            font-weight: 700;
            color: #3b82f6;
            min-width: 120px;
        }

        .detail-info-value {
            color: rgba(255,255,255,0.78);
        }

        .fasilitas-section {
            margin-top: 30px;
        }

        .fasilitas-section h3,
        .reviews-section h3 {
            color: white;
            margin-bottom: 15px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }

        .fasilitas-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .fasilitas-item {
            background: rgba(255,255,255,0.06);
            padding: 14px 16px;
            border-radius: 14px;
            border-left: 4px solid #3b82f6;
        }

        .review-item {
            background: rgba(255,255,255,0.06);
            padding: 18px;
            border-radius: 14px;
            margin-bottom: 15px;
            border-left: 4px solid #facc15;
        }

        .review-author {
            font-weight: 700;
            color: white;
        }

        .review-date,
        .review-text,
        .detail-info-value,
        .form-group label {
            color: rgba(255,255,255,0.75);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            color: white;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn,
        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.2s ease;
        }

        .submit-btn {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .submit-btn:hover,
        .view-detail-btn:hover,
        .back-btn:hover {
            transform: translateY(-2px);
        }

        .back-btn {
            background: #111827;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 1.5rem;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.18);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #3b82f6;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 1.25rem;
            border: 1px solid rgba(255,255,255,0.12);
        }

        .empty-state h2 {
            color: rgba(255,255,255,0.8);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: rgba(255,255,255,0.65);
        }
    </style>
</head>
<body class="min-h-screen">
    <div id="stars-container" class="fixed inset-0 pointer-events-none">
        <?php for ($i = 0; $i < 40; $i++): ?>
            <div class="star absolute bg-white rounded-full opacity-40" 
                 style="left:<?=rand(0,100)?>%; width:2px; height:2px; top:<?=rand(0,100)?>%; --duration:<?=rand(10,25)?>s;"></div>
        <?php endfor; ?>
    </div>

    </div>

        <?php if ($show_list): ?>
            <!-- List Wisata -->
            <div class="detail-section">
                    <div class="filter-section">
                        <input type="text" id="searchInput" placeholder="Cari wisata..." value="<?php echo htmlspecialchars($searchQuery); ?>" />
                        <select id="categorySelect">
                            <option value="">Semua Kategori</option>
                            <?php 
                            $categories = $wisata_manager->get_categories();
                            foreach ($categories as $cat): 
                            ?>
                            <option value="<?php echo htmlspecialchars($cat['kategori']); ?>" <?php echo $categoryFilter === $cat['kategori'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="searchWisata()">Cari</button>
                        <button type="button" onclick="showTopWisata()">Rating Tertinggi</button>
                        <button type="button" onclick="showTrendingWisata()">Trending</button>
                    </div>
                </div>
            <div class="wisata-grid">
                <?php if (empty($all_wisata)): ?>
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <h2>Tidak ada wisata yang ditemukan</h2>
                        <p>Coba lagi dengan filter berbeda</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_wisata as $w): ?>
                    <div class="wisata-card" onclick="window.location.href='?action=detail&id=<?php echo $w['id_wisata']; ?>'">
                        <div class="wisata-image">
                            <?php if ($w['gambar_url']): ?>
                                <img src="<?php echo htmlspecialchars(resolve_wisata_image_url($w['gambar_url'])); ?>" alt="<?php echo htmlspecialchars($w['nama_wisata']); ?>">
                            <?php else: ?>
                                📸 <?php echo htmlspecialchars($w['nama_wisata']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="wisata-content">
                            <div class="wisata-name"><?php echo $w['nama_wisata']; ?></div>
                            <div class="wisata-location">📍 <?php echo substr($w['lokasi'], 0, 30); ?>...</div>
                            <div class="wisata-price">Rp <?php echo number_format($w['harga_tiket'], 0, ',', '.'); ?></div>
                            <div class="wisata-rating">
                                <span class="stars">★★★★★</span>
                                <span><?php echo number_format($w['rating'], 1); ?> (<?php echo $w['total_review']; ?> review)</span>
                            </div>
                            <div class="flex gap-2" style="margin-top: 15px;">
                                <a class="view-detail-btn" href="?action=detail&id=<?php echo $w['id_wisata']; ?>">Lihat Detail</a>
                                <a class="view-detail-btn" style="background:#34d399;" href="pesan.php?wisata=<?php echo urlencode($w['nama_wisata']); ?>">Pesan Tiket</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif (!$show_list && $action === 'detail' && $id): ?>
            <!-- Detail Wisata -->
            <a href="?" class="back-btn">← Kembali</a>

            <?php
            $wisata = $wisata_manager->get_wisata_detail($id);
            
            if (!$wisata):
            ?>
            <div class="empty-state">
                <h2>Wisata tidak ditemukan</h2>
                <p><a href="?" class="back-btn">Kembali ke Daftar Wisata</a></p>
            </div>
            <?php else: ?>

            <div class="detail-section">
                <div class="detail-header">
                    <div class="detail-image">
                        <?php if ($wisata['gambar_url']): ?>
                            <img src="<?php echo htmlspecialchars(resolve_wisata_image_url($wisata['gambar_url'])); ?>" alt="<?php echo htmlspecialchars($wisata['nama_wisata']); ?>">
                        <?php else: ?>
                            📸
                        <?php endif; ?>
                    </div>
                    <div class="detail-info">
                        <h2><?php echo $wisata['nama_wisata']; ?></h2>
                        
                        <div class="detail-info-item">
                            <span class="detail-info-label">Lokasi:</span>
                            <span class="detail-info-value">📍 <?php echo $wisata['lokasi']; ?></span>
                        </div>

                        <div class="detail-info-item">
                            <span class="detail-info-label">Harga Tiket:</span>
                            <span class="detail-info-value">Rp <?php echo number_format($wisata['harga_tiket'], 0, ',', '.'); ?></span>
                        </div>

                        <div class="detail-info-item">
                            <span class="detail-info-label">Jam Operasional:</span>
                            <span class="detail-info-value">
                                <?php echo ($wisata['jam_buka'] ?? '09:00'); ?> - <?php echo ($wisata['jam_tutup'] ?? '17:00'); ?>
                            </span>
                        </div>

                        <div class="detail-info-item">
                            <span class="detail-info-label">Kategori:</span>
                            <span class="detail-info-value"><?php echo $wisata['kategori']; ?></span>
                        </div>

                        <div class="detail-info-item">
                            <span class="detail-info-label">Rating:</span>
                            <span class="detail-info-value">
                                ⭐ <?php echo number_format($wisata['rating'], 1); ?>/5 
                                (<?php echo $wisata['total_review'] ?? 0; ?> review)
                            </span>
                        </div>

                        <div class="detail-info-item">
                            <span class="detail-info-label">Kontak:</span>
                            <span class="detail-info-value">
                                📱 <?php echo $wisata['no_hp_contact'] ?? 'N/A'; ?> 
                                | 📧 <?php echo $wisata['email_contact'] ?? 'N/A'; ?>
                            </span>
                        </div>

                        <a class="submit-btn" href="pesan.php?wisata=<?php echo urlencode($wisata['nama_wisata']); ?>">
                            🎫 Pesan Tiket Sekarang
                        </a>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="section-divider">
                    <h3>📝 Tentang Wisata Ini</h3>
                    <p style="line-height: 1.8;">
                        <?php echo nl2br($wisata['deskripsi'] ?? 'Tidak ada deskripsi'); ?>
                    </p>
                </div>

                <!-- Fasilitas -->
                <?php if (!empty($wisata['fasilitas'])): ?>
                <div class="fasilitas-section">
                    <h3>🎪 Fasilitas Tersedia</h3>
                    <div class="fasilitas-list">
                        <?php foreach ($wisata['fasilitas'] as $f): ?>
                        <div class="fasilitas-item">
                            <strong><?php echo $f['nama_fasilitas']; ?></strong>
                            <p style="font-size: 13px; color: #999; margin-top: 5px;">
                                <?php echo $f['keterangan']; ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reviews -->
                <div class="reviews-section">
                    <h3>💬 Ulasan Pengunjung</h3>

                    <?php if (!empty($wisata['reviews'])): ?>
                    <div>
                        <?php foreach ($wisata['reviews'] as $r): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-author"><?php echo $r['nama']; ?></span>
                                <span class="review-date"><?php echo date('d M Y', strtotime($r['created_at'])); ?></span>
                            </div>
                            <div class="review-rating">
                                <?php echo str_repeat('⭐', $r['rating']); ?> <?php echo $r['rating']; ?>/5
                            </div>
                            <div class="review-text"><?php echo $r['ulasan']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="color: #999;">Belum ada review untuk wisata ini</p>
                    <?php endif; ?>

                    <!-- Form Tambah Review -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="add-review-form">
                        <h4>Tambahkan Review Anda</h4>
                        <form onsubmit="submitReview(event, <?php echo $id; ?>)">
                            <div class="form-group">
                                <label>Rating</label>
                                <select name="rating" required>
                                    <option value="">Pilih Rating</option>
                                    <option value="5">⭐⭐⭐⭐⭐ Sangat Bagus</option>
                                    <option value="4">⭐⭐⭐⭐ Bagus</option>
                                    <option value="3">⭐⭐⭐ Cukup</option>
                                    <option value="2">⭐⭐ Kurang</option>
                                    <option value="1">⭐ Sangat Kurang</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ulasan Anda</label>
                                <textarea name="ulasan" placeholder="Bagikan pengalaman Anda di wisata ini..." required></textarea>
                            </div>
                            <button type="submit" class="submit-btn">Kirim Review</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <p style="color: #999; margin-top: 20px;">
                        <a href="login.php">Login</a> untuk menambahkan review
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        function searchWisata() {
            const search = document.getElementById('searchInput').value.trim();
            const category = document.getElementById('categorySelect').value;
            let url = 'wisata.php?';

            if (search && category) {
                url += `action=search&q=${encodeURIComponent(search)}&kategori=${encodeURIComponent(category)}`;
            } else if (search) {
                url += `action=search&q=${encodeURIComponent(search)}`;
            } else if (category) {
                url += `action=filter_kategori&kategori=${encodeURIComponent(category)}`;
            } else {
                url += 'action=list';
            }

            window.location.href = url;
        }

        function showTopWisata() {
            window.location.href = '?action=top';
        }

        function showTrendingWisata() {
            window.location.href = '?action=trending';
        }

        function submitReview(e, wisataId) {
            e.preventDefault();
            const form = e.target;
            const rating = form.rating.value;
            const ulasan = form.ulasan.value;

            fetch('wisata_info.php?action=add_review', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_wisata: wisataId,
                    rating: rating,
                    ulasan: ulasan
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status) {
                    alert('Review berhasil ditambahkan!');
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
