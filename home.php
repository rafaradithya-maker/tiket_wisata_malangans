<?php
require 'config.php';

// Cek session
$user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get user info if logged in
$user_info = null;
if ($user) {
    $query = "SELECT nama, username, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Wisata Malang - Pesan Tiket Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        
        .navbar {
            background: white;
            color: #333;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .navbar-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .navbar a, .navbar button {
            color: #333;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
        }
        
        .navbar a:hover {
            background: #f0f0f0;
            color: #667eea;
        }
        
        .navbar .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .navbar .btn-primary:hover {
            background: #764ba2;
        }
        
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }
        
        .hero h2 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin: 5px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #667eea;
            color: white;
            border: 2px solid white;
        }
        
        .btn-secondary:hover {
            background: transparent;
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            padding: 60px 20px;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.8;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 40px 20px;
        }
        
        .menu-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #667eea;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .menu-item:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .menu-item i {
            font-size: 2rem;
            display: block;
            margin-bottom: 10px;
        }
        
        .menu-item span {
            display: block;
            font-weight: bold;
            font-size: 13px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 15px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        
        .section-title {
            text-align: center;
            margin: 40px 0 20px 0;
            padding: 20px 0;
            border-top: 2px solid #e0e0e0;
        }
        
        .section-title h2 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 30px 20px;
            margin-top: 60px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">🎫 Tiket Wisata Malang</div>
            <div class="navbar-links">
                <a href="wisata.php">🏖️ Wisata</a>
                <a href="diagnose.php">🔍 Status Sistem</a>
                
                <?php if ($user): ?>
                    <div class="user-info">
                        👤 <?php echo $user_info['nama']; ?>
                    </div>
                    <a href="<?php echo $role === 'admin' ? 'dashboard_admin.php' : 'dashboard_user.php'; ?>" class="btn btn-primary">Dashboard</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>🏞️ Jelajahi Keindahan Wisata Malang</h2>
            <p>Pesan tiket wisata favorit Anda dengan mudah, aman, dan terpercaya</p>
            <div>
                <a href="wisata.php" class="btn btn-primary">Lihat Semua Wisata</a>
                <?php if (!$user): ?>
                    <a href="login.php" class="btn btn-secondary">Mulai Pesan Sekarang</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Fitur Utama -->
    <section class="container">
        <div class="section-title">
            <h2>✨ Fitur Utama</h2>
            <p>Nikmati kemudahan dalam memesan tiket wisata</p>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Destinasi Lengkap</h3>
                <p>Pilih dari berbagai wisata menarik di Jawa Timur dengan informasi lengkap dan ulasan pengunjung</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-credit-card"></i>
                <h3>Pembayaran Mudah</h3>
                <p>Berbagai metode pembayaran dengan sistem keamanan terpercaya (Bank Transfer, E-Wallet, Kartu Kredit)</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-undo"></i>
                <h3>Refund Jaminan</h3>
                <p>Proses pengembalian dana yang transparan dan cepat jika ada perubahan rencana</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-mobile-alt"></i>
                <h3>Akses Multi-Platform</h3>
                <p>Akses dari website maupun aplikasi mobile (Android/iOS) kapan saja, di mana saja</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-star"></i>
                <h3>Rating & Review</h3>
                <p>Baca pengalaman pengunjung lain dan berikan review untuk wisata yang Anda kunjungi</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h3>Analytics Real-time</h3>
                <p>Dapatkan insight dan prediksi tren wisata dengan teknologi AI</p>
            </div>
        </div>
    </section>

    <!-- Menu Navigasi -->
    <?php if ($user): ?>
    <section class="container">
        <div class="section-title">
            <h2>📋 Menu Cepat</h2>
        </div>
        
        <div class="menu-grid">
            <a href="wisata.php" class="menu-item">
                <i class="fas fa-map"></i>
                <span>Lihat Wisata</span>
            </a>
            
            <a href="dashboard_user.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="pesan.php" class="menu-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Pesan Tiket</span>
            </a>
            
            <a href="riwayat.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Riwayat</span>
            </a>
            
            <?php if ($role === 'admin'): ?>
            <a href="dashboard_admin.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Admin Panel</span>
            </a>
            <?php endif; ?>
            
            <a href="diagnose.php" class="menu-item">
                <i class="fas fa-stethoscope"></i>
                <span>Status Sistem</span>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2026 Tiket Wisata Malang. All rights reserved.</p>
            <p>Hubungi kami: info@tiketwisatamalang.com | +62 341 5977 11</p>
        </div>
    </footer>
</body>
</html>
