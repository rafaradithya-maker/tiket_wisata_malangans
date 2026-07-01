<?php
/**
 * Sistem Diagnosa Tiket Wisata Malang
 * Membantu mengidentifikasi masalah pada sistem
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnosa Sistem - Tiket Wisata Malang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .diagnostic-section {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .diagnostic-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-left: 4px solid #ddd;
            border-radius: 4px;
        }
        
        .status-item.success {
            border-left-color: #4caf50;
            background: #f1f8f4;
        }
        
        .status-item.warning {
            border-left-color: #ff9800;
            background: #fff3e0;
        }
        
        .status-item.error {
            border-left-color: #f44336;
            background: #ffebee;
        }
        
        .status-label {
            font-weight: bold;
            color: #333;
        }
        
        .status-value {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }
        
        .status-value.success {
            background: #4caf50;
            color: white;
        }
        
        .status-value.warning {
            background: #ff9800;
            color: white;
        }
        
        .status-value.error {
            background: #f44336;
            color: white;
        }
        
        .test-button {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .test-button:hover {
            background: #764ba2;
        }
        
        .code-block {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .link-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: transform 0.2s ease;
        }
        
        .link-card:hover {
            transform: translateY(-3px);
        }
        
        .link-card h3 {
            margin-bottom: 10px;
        }
        
        .link-card p {
            font-size: 13px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnostik Sistem Tiket Wisata Malang</h1>
            <p>Periksa status dan konfigurasi aplikasi</p>
        </div>
        
        <!-- PHP Environment -->
        <div class="diagnostic-section">
            <h2>🖥️ Lingkungan Server</h2>
            
            <div class="status-item success">
                <span class="status-label">PHP Version</span>
                <span class="status-value success"><?php echo phpversion(); ?></span>
            </div>
            
            <div class="status-item <?php echo extension_loaded('mysqli') ? 'success' : 'error'; ?>">
                <span class="status-label">MySQLi Extension</span>
                <span class="status-value <?php echo extension_loaded('mysqli') ? 'success' : 'error'; ?>">
                    <?php echo extension_loaded('mysqli') ? '✓ Installed' : '✗ Missing'; ?>
                </span>
            </div>
            
            <div class="status-item <?php echo extension_loaded('curl') ? 'success' : 'error'; ?>">
                <span class="status-label">cURL Extension</span>
                <span class="status-value <?php echo extension_loaded('curl') ? 'success' : 'error'; ?>">
                    <?php echo extension_loaded('curl') ? '✓ Installed' : '✗ Missing'; ?>
                </span>
            </div>
            
            <div class="status-item <?php echo extension_loaded('json') ? 'success' : 'error'; ?>">
                <span class="status-label">JSON Extension</span>
                <span class="status-value <?php echo extension_loaded('json') ? 'success' : 'error'; ?>">
                    <?php echo extension_loaded('json') ? '✓ Installed' : '✗ Missing'; ?>
                </span>
            </div>
            
            <div class="status-item success">
                <span class="status-label">Server Software</span>
                <span class="status-value success"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
            </div>
            
            <div class="status-item success">
                <span class="status-label">Current Directory</span>
                <span class="status-value success"><?php echo __DIR__; ?></span>
            </div>
        </div>
        
        <!-- Database Connection -->
        <div class="diagnostic-section">
            <h2>🗄️ Koneksi Database</h2>
            
            <?php
            $db_host = 'localhost';
            $db_user = 'root';
            $db_pass = '';
            $db_name = 'tiket_malang';
            
            $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
            $db_connected = $conn && mysqli_ping($conn);
            ?>
            
            <div class="status-item <?php echo $db_connected ? 'success' : 'error'; ?>">
                <span class="status-label">MySQL Connection</span>
                <span class="status-value <?php echo $db_connected ? 'success' : 'error'; ?>">
                    <?php echo $db_connected ? '✓ Connected' : '✗ Failed'; ?>
                </span>
            </div>
            
            <?php if ($db_connected): ?>
                <div class="code-block">
                    Host: <?php echo $db_host; ?><br>
                    Database: <?php echo $db_name; ?><br>
                    MySQL Version: <?php echo mysqli_get_server_info($conn); ?>
                </div>
                
                <div class="status-item success">
                    <span class="status-label">Database</span>
                    <span class="status-value success"><?php echo $db_name; ?> ✓</span>
                </div>
                
                <?php
                $tables_query = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?";
                $stmt = $conn->prepare($tables_query);
                $stmt->bind_param('s', $db_name);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $table_count = $result['count'];
                ?>
                
                <div class="status-item success">
                    <span class="status-label">Total Tables</span>
                    <span class="status-value success"><?php echo $table_count; ?> tables</span>
                </div>
                
                <?php
                $tables = ['users', 'tiket', 'payments', 'refunds', 'analytics', 'wisata', 'reviews', 'fasilitas_wisata'];
                $missing_tables = [];
                
                foreach ($tables as $table) {
                    $check = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($check->num_rows == 0) {
                        $missing_tables[] = $table;
                    }
                }
                ?>
                
                <div class="status-item <?php echo empty($missing_tables) ? 'success' : 'warning'; ?>">
                    <span class="status-label">Required Tables</span>
                    <span class="status-value <?php echo empty($missing_tables) ? 'success' : 'warning'; ?>">
                        <?php 
                        if (empty($missing_tables)) {
                            echo '✓ All present';
                        } else {
                            echo '⚠ Missing: ' . implode(', ', $missing_tables);
                        }
                        ?>
                    </span>
                </div>
                
            <?php else: ?>
                <div class="status-item error">
                    <span class="status-label">Error</span>
                    <span class="status-value error">
                        <?php echo mysqli_connect_error(); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Files Check -->
        <div class="diagnostic-section">
            <h2>📄 Cek File</h2>
            
            <?php
            $required_files = [
                'config.php' => 'Konfigurasi database',
                'index.php' => 'Halaman utama',
                'login.php' => 'Login',
                'register.php' => 'Registrasi',
                'dashboard_user.php' => 'Dashboard User',
                'dashboard_admin.php' => 'Dashboard Admin',
                'wisata.php' => 'Informasi Wisata',
                'wisata_info.php' => 'API Wisata',
                'payment_gateway.php' => 'Payment Gateway',
                'refund_handler.php' => 'Refund Handler',
                'api_mobile.php' => 'Mobile API',
                'analytics_ai.php' => 'Analytics AI'
            ];
            
            foreach ($required_files as $file => $description) {
                $exists = file_exists($file);
                ?>
                <div class="status-item <?php echo $exists ? 'success' : 'error'; ?>">
                    <span class="status-label"><?php echo $description; ?> (<?php echo $file; ?>)</span>
                    <span class="status-value <?php echo $exists ? 'success' : 'error'; ?>">
                        <?php echo $exists ? '✓ Ada' : '✗ Tidak ada'; ?>
                    </span>
                </div>
                <?php
            }
            ?>
        </div>
        
        <!-- Links -->
        <div class="diagnostic-section">
            <h2>🔗 Akses Aplikasi</h2>
            
            <div class="links-grid">
                <a href="index.php" class="link-card">
                    <h3>🏠 Beranda</h3>
                    <p>Halaman utama aplikasi</p>
                </a>
                
                <a href="wisata.php" class="link-card">
                    <h3>🏖️ Wisata</h3>
                    <p>Lihat semua wisata</p>
                </a>
                
                <a href="login.php" class="link-card">
                    <h3>🔐 Login</h3>
                    <p>Masuk akun</p>
                </a>
                
                <a href="register.php" class="link-card">
                    <h3>📝 Register</h3>
                    <p>Buat akun baru</p>
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard_user.php" class="link-card">
                    <h3>👤 Dashboard</h3>
                    <p>Dashboard user</p>
                </a>
                <?php endif; ?>
                
                <a href="http://localhost/phpmyadmin" class="link-card" target="_blank">
                    <h3>💾 phpMyAdmin</h3>
                    <p>Kelola database</p>
                </a>
            </div>
        </div>
        
        <!-- API Test -->
        <div class="diagnostic-section">
            <h2>🧪 Test API</h2>
            
            <button class="test-button" onclick="testAPI('wisata_info.php?action=all')">Test Wisata API</button>
            <button class="test-button" onclick="testAPI('analytics_ai.php?action=stats')">Test Analytics API</button>
            <button class="test-button" onclick="testAPI('api_mobile.php')">Test Mobile API</button>
            
            <div id="api-result"></div>
        </div>
        
        <!-- Info -->
        <div class="diagnostic-section">
            <h2>ℹ️ Informasi</h2>
            <p>
                ✅ Jika semua status di atas menunjukkan ✓ (hijau), maka aplikasi siap digunakan<br>
                ⚠️ Jika ada status ⚠ (kuning), periksa konfigurasi<br>
                ✗ Jika ada status ✗ (merah), ada masalah yang perlu diperbaiki
            </p>
        </div>
    </div>
    
    <script>
        function testAPI(endpoint) {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = '<div class="status-item warning"><span class="status-label">Loading...</span></div>';
            
            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = '<div class="code-block">' + 
                        JSON.stringify(data, null, 2) + 
                        '</div>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="status-item error"><span class="status-label">Error: ' + error.message + '</span></div>';
                });
        }
    </script>
</body>
</html>
