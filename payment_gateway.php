<?php
/**
 * Payment Gateway Integration (Midtrans/Bank API)
 * Support untuk Midtrans, Bank Transfer, E-Wallet
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Konfigurasi Midtrans
define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: 'your_server_key');
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: 'your_client_key');
define('MIDTRANS_ENVIRONMENT', getenv('MIDTRANS_ENVIRONMENT') ?: 'sandbox');

class PaymentGateway {
    private $conn;
    private $serverKey;
    private $clientKey;
    private $environment;

    public function __construct($connection) {
        $this->conn = $connection;
        $this->serverKey = MIDTRANS_SERVER_KEY;
        $this->clientKey = MIDTRANS_CLIENT_KEY;
        $this->environment = MIDTRANS_ENVIRONMENT;
    }

    /**
     * Inisiasi transaksi pembayaran
     */
    public function initiate_payment($tiket_id, $user_id, $jumlah, $payment_method = 'midtrans') {
        // Validasi input
        if (!$tiket_id || !$user_id || !$jumlah) {
            return ['status' => false, 'message' => 'Data tidak lengkap'];
        }

        // Generate transaction ID
        $transaction_id = 'TRX-' . time() . '-' . rand(1000, 9999);

        // Insert ke tabel payments
        $query = "INSERT INTO payments (id_tiket, user_id, jumlah, payment_method, payment_status, transaction_id)
                  VALUES (?, ?, ?, ?, 'pending', ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iiiss', $tiket_id, $user_id, $jumlah, $payment_method, $transaction_id);

        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id;

            // Jika menggunakan Midtrans
            if ($payment_method === 'midtrans') {
                return $this->create_midtrans_transaction($payment_id, $transaction_id, $jumlah, $user_id);
            }

            // Jika Bank Transfer Manual
            if ($payment_method === 'bank_transfer') {
                return $this->generate_bank_account($payment_id, $transaction_id, $jumlah);
            }

            return ['status' => true, 'message' => 'Pembayaran berhasil disiapkan', 'payment_id' => $payment_id];
        }

        return ['status' => false, 'message' => 'Gagal membuat pembayaran'];
    }

    /**
     * Buat transaksi Midtrans
     */
    private function create_midtrans_transaction($payment_id, $transaction_id, $jumlah, $user_id) {
        // Get user info
        $user_query = "SELECT nama, username FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($user_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result()->fetch_assoc();

        $payload = [
            'transaction_details' => [
                'order_id' => $transaction_id,
                'gross_amount' => (int)$jumlah,
            ],
            'customer_details' => [
                'first_name' => $user_result['nama'] ?? 'Customer',
                'email' => $user_result['username'] . '@wisatamalang.com',
                'phone' => '08123456789'
            ],
            'enabled_payments' => [
                'credit_card',
                'bca',
                'bni',
                'mandiri',
                'echannel',
                'gopay',
                'ovo'
            ],
            'vt_web' => true
        ];

        // Send ke Midtrans
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://app.' . $this->environment . '.midtrans.com/snap/v1/transactions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->serverKey . ':')
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => false, 'message' => 'Gagal koneksi ke Midtrans'];
        }

        $result = json_decode($response, true);

        if (isset($result['token'])) {
            return [
                'status' => true,
                'message' => 'Silahkan lakukan pembayaran',
                'payment_id' => $payment_id,
                'token' => $result['token'],
                'redirect_url' => $result['redirect_url'] ?? null,
                'transaction_id' => $transaction_id
            ];
        }

        return ['status' => false, 'message' => 'Gagal membuat transaksi Midtrans'];
    }

    /**
     * Generate Virtual Account untuk Bank Transfer
     */
    private function generate_bank_account($payment_id, $transaction_id, $jumlah) {
        $va_number = '1234' . str_pad($payment_id, 10, '0', STR_PAD_LEFT);
        
        return [
            'status' => true,
            'message' => 'Silahkan transfer ke rekening virtual',
            'payment_id' => $payment_id,
            'payment_method' => 'bank_transfer',
            'va_number' => $va_number,
            'bank_name' => 'BCA',
            'jumlah' => $jumlah,
            'transaction_id' => $transaction_id,
            'expire_time' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ];
    }

    /**
     * Verifikasi pembayaran dari Midtrans webhook
     */
    public function verify_midtrans_notification($notification) {
        $transaction_id = $notification['transaction_id'];
        $status_code = $notification['status_code'];

        // Update payment status
        $payment_status = in_array($status_code, ['200', '201']) ? 'success' : 'failed';

        $query = "UPDATE payments SET payment_status = ? WHERE transaction_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $payment_status, $transaction_id);

        if ($stmt->execute() && $payment_status === 'success') {
            // Update tiket status
            $query2 = "UPDATE tiket SET status = 'confirmed' 
                       WHERE id_tiket = (SELECT id_tiket FROM payments WHERE transaction_id = ?)";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->bind_param('s', $transaction_id);
            $stmt2->execute();
        }

        return ['status' => true, 'message' => 'Verifikasi berhasil'];
    }

    /**
     * Check status pembayaran
     */
    public function check_payment_status($payment_id) {
        $query = "SELECT p.*, t.wisata 
                  FROM payments p
                  JOIN tiket t ON p.id_tiket = t.id_tiket
                  WHERE p.id_payment = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result ?: null;
    }

    /**
     * Get payment history
     */
    public function get_payment_history($user_id, $limit = 10) {
        $query = "SELECT p.*, t.wisata 
                  FROM payments p
                  JOIN tiket t ON p.id_tiket = t.id_tiket
                  WHERE p.user_id = ?
                  ORDER BY p.created_at DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

// API Endpoint untuk pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    if ($action === 'initiate') {
        header('Content-Type: application/json');
        $payment = new PaymentGateway($conn);
        $result = $payment->initiate_payment(
            $data['tiket_id'] ?? null,
            $_SESSION['user_id'] ?? null,
            $data['jumlah'] ?? null,
            $data['payment_method'] ?? 'midtrans'
        );
        echo json_encode($result);
        exit;
    }

    if ($action === 'confirm') {
        if (!isset($_SESSION['user_id'])) {
            if (!empty($_POST)) {
                header('Location: login.php');
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'Silakan login terlebih dahulu.']);
            }
            exit;
        }

        $user_id = (int) $_SESSION['user_id'];
        $tiket_id = (int) ($data['tiket_id'] ?? 0);
        $payment_id = (int) ($data['payment_id'] ?? 0);

        if (!$tiket_id || !$payment_id) {
            if (!empty($_POST)) {
                header('Location: dashboard_user.php?payment=failed');
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'Data pembayaran tidak lengkap.']);
            }
            exit;
        }

        $stmt = $conn->prepare("UPDATE payments SET payment_status = 'success' WHERE id_payment = ? AND id_tiket = ? AND user_id = ? AND payment_status = 'pending'");
        $stmt->bind_param('iii', $payment_id, $tiket_id, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt2 = $conn->prepare("UPDATE tiket SET status = 'lunas' WHERE id_tiket = ? AND user_id = ?");
            $stmt2->bind_param('ii', $tiket_id, $user_id);
            $stmt2->execute();
            if (!empty($_POST)) {
                header('Location: dashboard_user.php?payment=success');
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => true, 'message' => 'Pembayaran berhasil dikonfirmasi.']);
            }
            exit;
        }

        if (!empty($_POST)) {
            header('Location: dashboard_user.php?payment=failed');
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'Pembayaran tidak dapat dikonfirmasi.']);
        }
        exit;
    }
}

// Halaman dan API GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'initiate' && isset($_GET['tiket_id'])) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $tiket_id = (int) $_GET['tiket_id'];
        $user_id = (int) $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT * FROM tiket WHERE id_tiket = ? AND user_id = ?");
        $stmt->bind_param('ii', $tiket_id, $user_id);
        $stmt->execute();
        $tiket = $stmt->get_result()->fetch_assoc();

        if (!$tiket) {
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Pembayaran Tidak Ditemukan</title></head><body><p>Tiket tidak ditemukan atau Anda tidak memiliki akses.</p><p><a href="dashboard_user.php">Kembali ke Dashboard</a></p></body></html>';
            exit;
        }

        $payment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payments WHERE id_tiket = {$tiket_id} AND user_id = {$user_id} ORDER BY created_at DESC LIMIT 1"));

        if (!$payment) {
            $transaction_id = 'TRX-' . time() . '-' . rand(1000, 9999);
            mysqli_query($conn, "INSERT INTO payments (id_tiket, user_id, jumlah, payment_method, payment_status, transaction_id) VALUES ({$tiket_id}, {$user_id}, {$tiket['total_harga']}, 'bank_transfer', 'pending', '{$transaction_id}')");
            $payment_id = mysqli_insert_id($conn);
            mysqli_query($conn, "UPDATE tiket SET payment_id = '{$payment_id}' WHERE id_tiket = {$tiket_id}");
            $payment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payments WHERE id_payment = {$payment_id}"));
        }

        $status_label = $payment['payment_status'] === 'pending' ? 'Menunggu Pembayaran' : 'Terbayar';
        $button_disabled = $payment['payment_status'] !== 'pending' ? 'disabled' : '';
        $button_text = $payment['payment_status'] !== 'pending' ? 'Pembayaran Selesai' : 'Konfirmasi Pembayaran';

        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Konfirmasi Pembayaran</title><link rel="stylesheet" href="https://cdn.tailwindcss.com"><style>body{font-family:Arial,sans-serif;background:#050b18;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;} .glass{background:rgba(255,255,255,0.08);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.12);border-radius:24px;max-width:520px;width:100%;padding:30px;} .btn{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:14px 18px;border-radius:18px;border:none;font-weight:700;cursor:pointer;}.btn-primary{background:#3b82f6;color:#fff;}.btn-secondary{background:rgba(255,255,255,0.08);color:#fff;}.field{margin-bottom:18px;} .field label{display:block;font-size:0.82rem;color:#9ca3af;margin-bottom:8px;} .field span{display:block;font-size:1rem;color:#fff;}
</style></head><body><div class="glass"><h1 class="text-3xl font-bold mb-4">Konfirmasi Pembayaran</h1><div class="field"><label>Destinasi</label><span>' . htmlspecialchars($tiket['wisata']) . '</span></div><div class="field"><label>Jumlah Tiket</label><span>' . htmlspecialchars($tiket['jumlah']) . ' tiket</span></div><div class="field"><label>Total Harga</label><span>Rp ' . number_format($tiket['total_harga'], 0, ',', '.') . '</span></div><div class="field"><label>Status Pembayaran</label><span>' . $status_label . '</span></div><div class="field"><label>Metode Pembayaran</label><span>' . htmlspecialchars($payment['payment_method']) . '</span></div><form action="payment_gateway.php?action=confirm" method="POST"><input type="hidden" name="tiket_id" value="' . $tiket_id . '"><input type="hidden" name="payment_id" value="' . $payment['id_payment'] . '"><button type="submit" class="btn btn-primary" ' . $button_disabled . '>' . $button_text . '</button></form><a href="dashboard_user.php" class="btn btn-secondary mt-3">Kembali ke Dashboard</a></div></body></html>';
        exit;
    }

    header('Content-Type: application/json');
    $payment = new PaymentGateway($conn);

    if ($action === 'check' && isset($_GET['payment_id'])) {
        $result = $payment->check_payment_status($_GET['payment_id']);
        echo json_encode($result ?? ['status' => false, 'message' => 'Pembayaran tidak ditemukan']);
        exit;
    }

    if ($action === 'history') {
        $result = $payment->get_payment_history($_SESSION['user_id'] ?? 0);
        echo json_encode($result);
        exit;
    }
}
?>
