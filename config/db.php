<?php
/**
 * SISKA BCA - Konfigurasi Database & Helper Functions
 * VERSI: 2.0 (Tanpa CSRF & Audit Log)
 */

// ─── Konfigurasi Environment ────────────────────────────────────────────────
define('ENVIRONMENT', 'development');
define('DB_HOST', 'localhost');
define('DB_PORT', 8889);
define('DB_NAME', 'siska_bca');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// ─── Konfigurasi Aplikasi ────────────────────────────────────────────────────
define('APP_NAME', 'SISKA BCA ASEMKA');
define('APP_VERSION', '2.0.0');
define('APP_TZ', 'Asia/Jakarta');
define('APP_CABANG', 'KCU Asemka');
define('APP_URL', 'http://localhost:8888/siska-bca');

// ─── Konfigurasi Upload ──────────────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// ─── Konfigurasi Session ─────────────────────────────────────────────────────
define('SESSION_LIFETIME', 28800); // 8 jam
define('PASSWORD_MIN_LENGTH', 6);

// ─── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set(APP_TZ);

// ─── Error Reporting ─────────────────────────────────────────────────────────
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ─── Session Management ──────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_start();
}

// ─── Database Connection ─────────────────────────────────────────────────────
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', 
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('[SISKA] DB Error: ' . $e->getMessage());
    die('Maaf, terjadi masalah koneksi database. Silakan coba lagi nanti.');
}

// ════════════════════════════════════════════════════════════════════════════
//  FUNGSI-FUNGSI CORE (Tanpa CSRF & Audit Log)
// ════════════════════════════════════════════════════════════════════════════

// ─── AUTHENTICATION ──────────────────────────────────────────────────────────

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Require login - redirect ke login jika belum login
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('login.php?error=' . urlencode('Silakan login terlebih dahulu'));
    }
}

/**
 * Require admin - redirect ke dashboard jika bukan admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php?error=' . urlencode('Akses ditolak. Anda bukan administrator.'));
    }
}

/**
 * Ambil data user yang sedang login
 */
function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'nama'     => $_SESSION['nama'] ?? '',
        'role'     => $_SESSION['role'] ?? 'user',
    ];
}

// ─── HELPERS ──────────────────────────────────────────────────────────────────

/**
 * Escape HTML untuk keamanan
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format Rupiah
 */
function formatRupiah($angka): string {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format Tanggal Indonesia
 */
function formatTanggal(?string $date, bool $withTime = false): string {
    if (!$date) return '-';
    
    $t = strtotime($date);
    if (!$t) return '-';
    
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $r = date('d', $t) . ' ' . $months[(int)date('m', $t)] . ' ' . date('Y', $t);
    if ($withTime) {
        $r .= ', ' . date('H:i', $t) . ' WIB';
    }
    return $r;
}

/**
 * Redirect ke URL tertentu
 */
function redirect(string $url, int $code = 302): never {
    header("Location: $url", true, $code);
    exit;
}

/**
 * Generate nomor surat otomatis
 */
function generateNomorSurat(PDO $pdo): string {
    $year = date('Y');
    $month = date('m');
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM surat_kuasa 
            WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
        ");
        $stmt->execute([$year, $month]);
        $count = (int)$stmt->fetchColumn() + 1;
        
        return sprintf('SKU/%s/%s/%03d', $year, $month, $count);
    } catch (Exception $e) {
        return sprintf('SKU/%s/%s/%03d', $year, $month, rand(1, 999));
    }
}

// ─── UPLOAD ───────────────────────────────────────────────────────────────────

/**
 * Upload file dengan validasi
 */
function uploadFile(array $file, string $prefix = ''): array {
    // Cek error upload
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (max ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
            UPLOAD_ERR_EXTENSION => 'Upload diblokir oleh ekstensi PHP',
        ];
        $errorMsg = $messages[$file['error']] ?? 'Upload gagal (kode: ' . $file['error'] . ')';
        return ['ok' => false, 'message' => $errorMsg];
    }

    // Cek ukuran
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['ok' => false, 'message' => 'File terlalu besar. Maksimal ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB'];
    }

    if ($file['size'] <= 0) {
        return ['ok' => false, 'message' => 'File kosong atau rusak'];
    }

    // Cek ekstensi
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'heif', 'pdf'];
    
    if (!in_array($ext, $allowed)) {
        return ['ok' => false, 'message' => 'Format file tidak diizinkan. Gunakan: ' . implode(', ', $allowed)];
    }

    // Buat folder upload jika belum ada
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0777, true)) {
            return ['ok' => false, 'message' => 'Gagal membuat folder upload'];
        }
    }

    // Generate nama file unik
    $filename = $prefix . '_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 10) . '.' . $ext;
    $targetPath = UPLOAD_DIR . $filename;

    // Pindahkan file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['ok' => false, 'message' => 'Gagal menyimpan file ke server'];
    }

    return [
        'ok' => true,
        'filename' => $filename,
        'original_name' => $file['name'],
        'size' => $file['size'],
        'ext' => $ext
    ];
}

/**
 * Hapus file upload
 */
function deleteUploadedFile(?string $filename): bool {
    if (empty($filename)) return true;
    
    $path = UPLOAD_DIR . $filename;
    if (file_exists($path)) {
        return @unlink($path);
    }
    return true;
}

/**
 * Dapatkan path gambar untuk ditampilkan
 */
function imgPath(?string $filename): ?string {
    if (empty($filename)) return null;
    
    $path = UPLOAD_DIR . $filename;
    if (file_exists($path)) {
        return 'assets/uploads/' . $filename;
    }
    return null;
}

// ─── DATABASE HELPERS ────────────────────────────────────────────────────────

/**
 * Cek apakah username sudah terdaftar
 */
function isUsernameExists(PDO $pdo, string $username, ?int $excludeId = null): bool {
    $sql = "SELECT id FROM users WHERE username = ?";
    $params = [$username];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool)$stmt->fetch();
}

/**
 * Get user by ID
 */
function getUserById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get surat kuasa by ID
 */
function getSuratKuasaById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("
        SELECT sk.*, u.username as dibuat_username, u.nama_lengkap as dibuat_nama
        FROM surat_kuasa sk
        LEFT JOIN users u ON sk.dibuat_oleh = u.id
        WHERE sk.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all users
 */
function getAllUsers(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, username ASC");
    return $stmt->fetchAll();
}

/**
 * Get all surat kuasa dengan filter
 */
function getSuratKuasa(PDO $pdo, string $search = '', string $filter = ''): array {
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(nomor_rekening LIKE ? OR nama_pemilik LIKE ? OR nama_penerima LIKE ? OR nomor_surat LIKE ?)";
        $term = "%$search%";
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    if ($filter === 'setoran' || $filter === 'tarikan') {
        $where[] = "jenis_kuasa = ?";
        $params[] = strtoupper($filter);
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT * FROM surat_kuasa $whereSql ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}