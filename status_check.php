<?php
/**
 * Quick Functionality Checker
 * Verify semua fitur sudah berfungsi dengan baik
 */

session_start();
require 'config.php';

$checks = [];

// 1. Database Connection
$db_test = mysqli_ping($conn);
$checks['Database Connection'] = $db_test ? '✓ OK' : '✗ FAILED';

// 2. Tables Check
$tables_required = ['users', 'tiket', 'payments', 'refunds', 'analytics', 'wisata', 'reviews', 'fasilitas_wisata'];
$all_tables_exist = true;
foreach ($tables_required as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $all_tables_exist = false;
        break;
    }
}
$checks['Database Tables'] = $all_tables_exist ? '✓ OK (8/8)' : '✗ FAILED';

// 3. File Structure
$required_files = [
    'config.php', 'index.php', 'home.php', 'login.php', 'register.php',
    'logout.php', 'dashboard_user.php', 'dashboard_admin.php',
    'wisata.php', 'wisata_info.php', 'payment_gateway.php',
    'refund_handler.php', 'api_mobile.php', 'analytics_ai.php',
    'diagnose.php', 'test_api.php'
];
$missing_files = [];
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $missing_files[] = $file;
    }
}
$checks['File Structure'] = count($missing_files) == 0 ? '✓ OK (16/16)' : '✗ MISSING: ' . implode(', ', $missing_files);

// 4. Session Support
$checks['Session Support'] = session_status() === PHP_SESSION_ACTIVE ? '✓ OK' : '✗ FAILED';

// 5. Extension: MySQLi
$checks['MySQLi Extension'] = extension_loaded('mysqli') ? '✓ OK' : '✗ MISSING';

// 6. Extension: cURL
$checks['cURL Extension'] = extension_loaded('curl') ? '✓ OK' : '✗ MISSING';

// 7. Extension: JSON
$checks['JSON Extension'] = extension_loaded('json') ? '✓ OK' : '✗ MISSING';

// 8. Wisata Sample Data
$wisata_count = $conn->query("SELECT COUNT(*) as count FROM wisata")->fetch_assoc()['count'];
$checks['Sample Data (Wisata)'] = $wisata_count >= 2 ? '✓ OK (' . $wisata_count . ' destinations)' : '⚠ LOW: ' . $wisata_count;

// 9. Facilities Data
$facilities_count = $conn->query("SELECT COUNT(*) as count FROM fasilitas_wisata")->fetch_assoc()['count'];
$checks['Facilities Data'] = $facilities_count >= 1 ? '✓ OK (' . $facilities_count . ' items)' : '✗ MISSING';

// 10. API Endpoints Callable
$api_endpoints = ['wisata_info.php', 'payment_gateway.php', 'analytics_ai.php', 'api_mobile.php'];
$api_callable = 0;
foreach ($api_endpoints as $endpoint) {
    if (file_exists($endpoint)) {
        $api_callable++;
    }
}
$checks['API Endpoints'] = $api_callable == count($api_endpoints) ? '✓ OK (4/4)' : '⚠ ' . $api_callable . '/' . count($api_endpoints);

// Calculate overall status
$success_count = 0;
foreach ($checks as $check_name => $result) {
    if (strpos($result, '✓') === 0) {
        $success_count++;
    }
}
$total_checks = count($checks);
$pass_percentage = round(($success_count / $total_checks) * 100);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ System Health Check</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .status-circle {
            display: inline-block;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 20px 0;
            background: #f0f0f0;
        }
        
        .status-circle.pass {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .status-circle.warning {
            background: #ffe0b2;
            color: #f57f17;
        }
        
        .status-circle.fail {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .percentage {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .check-item {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #ddd;
        }
        
        .check-item.success {
            border-left-color: #4caf50;
            background: #f1f8f4;
        }
        
        .check-item.warning {
            border-left-color: #ff9800;
            background: #fff3e0;
        }
        
        .check-item.error {
            border-left-color: #f44336;
            background: #ffebee;
        }
        
        .check-item-label {
            font-weight: bold;
            color: #333;
        }
        
        .check-item-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
        }
        
        .check-item-status.success {
            background: #4caf50;
            color: white;
        }
        
        .check-item-status.warning {
            background: #ff9800;
            color: white;
        }
        
        .check-item-status.error {
            background: #f44336;
            color: white;
        }
        
        .action-buttons {
            margin-top: 20px;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #764ba2;
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        
        .legend {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .legend-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
        }
        
        .legend-icon {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .legend-icon.success {
            background: #4caf50;
        }
        
        .legend-icon.warning {
            background: #ff9800;
        }
        
        .legend-icon.error {
            background: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ System Health Check</h1>
            <p>Verifikasi status dan fungsi semua komponen</p>
            
            <div class="status-circle <?php echo $pass_percentage == 100 ? 'pass' : ($pass_percentage >= 80 ? 'warning' : 'fail'); ?>">
                <?php echo $pass_percentage; ?>%
            </div>
            
            <p class="percentage">
                <?php echo $success_count . '/' . $total_checks; ?> Checks Passed
            </p>
            
            <p style="color: #666; margin-top: 10px;">
                <?php 
                if ($pass_percentage == 100) {
                    echo '🎉 Semua sistem berfungsi normal! Siap digunakan.';
                } elseif ($pass_percentage >= 80) {
                    echo '⚠️ Sistem sebagian berfungsi. Ada beberapa warning.';
                } else {
                    echo '❌ Ada masalah kritis yang perlu diperbaiki.';
                }
                ?>
            </p>
        </div>
        
        <div>
            <?php foreach ($checks as $check_name => $result): ?>
                <?php
                $status_class = 'error';
                if (strpos($result, '✓') === 0) {
                    $status_class = 'success';
                } elseif (strpos($result, '⚠') === 0) {
                    $status_class = 'warning';
                }
                ?>
                <div class="check-item <?php echo $status_class; ?>">
                    <span class="check-item-label"><?php echo $check_name; ?></span>
                    <span class="check-item-status <?php echo $status_class; ?>">
                        <?php echo $result; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="legend">
            <div class="legend-item">
                <span class="legend-icon success"></span>
                <span>✓ OK - Berfungsi Normal</span>
            </div>
            <div class="legend-item">
                <span class="legend-icon warning"></span>
                <span>⚠ WARNING - Perlu Perhatian</span>
            </div>
            <div class="legend-item">
                <span class="legend-icon error"></span>
                <span>✗ ERROR - Ada Masalah</span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="home.php" class="btn btn-primary">🏠 Go to Home</a>
            <a href="test_api.php" class="btn btn-secondary">🧪 Test API</a>
            <a href="diagnose.php" class="btn btn-secondary">🔍 Full Diagnostics</a>
        </div>
    </div>
</body>
</html>
