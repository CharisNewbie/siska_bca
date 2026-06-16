<?php
/**
 * SISKA BCA - Konfigurasi Database & Helper Functions
 * Sistem Informasi Surat Kuasa
 * Version: 2.1.0
 */

// ─── Environment ──────────────────────────────────────────────────────────
define('ENVIRONMENT', 'development');

// ─── Konfigurasi Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    8889);
define('DB_NAME',    'siska_bca');
define('DB_USER',    'root');
define('DB_PASS',    'root');
define('DB_CHARSET', 'utf8mb4');

// ─── Konfigurasi Aplikasi ─────────────────────────────────────────────────
define('APP_NAME',    'SISKA BCA ASEMKA');
define('APP_VERSION', '1.0.0');
define('APP_TZ',      'Asia/Jakarta');
define('APP_CABANG',  'KCU Asemka');
define('APP_URL',     'http://localhost:8888/siska-bca');

// ─── Konfigurasi Upload ───────────────────────────────────────────────────
define('UPLOAD_DIR',       __DIR__ . '/../assets/uploads/');
define('UPLOAD_MAX_SIZE',  10 * 1024 * 1024); // 10 MB

// ─── Konfigurasi Keamanan ─────────────────────────────────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('SESSION_LIFETIME',   28800);
define('PASSWORD_MIN_LENGTH', 8);

// ─── Timezone ─────────────────────────────────────────────────────────────
date_default_timezone_set(APP_TZ);

// ─── Error Reporting ──────────────────────────────────────────────────────
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ─── Session Security ─────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_secure', 0);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ─── PDO Connection ────────────────────────────────────────────────────────
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        PDO::ATTR_PERSISTENT         => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    error_log('[SISKA] DB Error: ' . $e->getMessage());
    
    if (ENVIRONMENT === 'development') {
        die(renderError('Koneksi database gagal: ' . $e->getMessage()));
    } else {
        http_response_code(503);
        die(renderError('Layanan sedang tidak tersedia.'));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  SECURITY & AUTHENTICATION HELPERS
// ═══════════════════════════════════════════════════════════════════════════

function isLoggedIn(): bool {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        logoutUser();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php?status=error&msg=Silakan+login+terlebih+dahulu');
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php?status=error&msg=Akses+ditolak');
    }
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id']    ?? null,
        'username' => $_SESSION['username']   ?? '',
        'nama'     => $_SESSION['nama']       ?? '',
        'role'     => $_SESSION['role']       ?? 'user',
        'email'    => $_SESSION['email']      ?? '',
    ];
}

function logoutUser(): void {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        try { 
            auditLog($pdo, 'logout', null, null, 'User logged out'); 
        } catch (Exception $e) {
            error_log('[SISKA] Logout audit error: ' . $e->getMessage());
        }
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

// ═══════════════════════════════════════════════════════════════════════════
//  INPUT / OUTPUT HELPERS
// ═══════════════════════════════════════════════════════════════════════════

function sanitize(string $data): string {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRupiah(int|float|string $angka): string {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function formatTanggal(?string $date, bool $withTime = false): string {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    if ($timestamp === false) return '-';
    
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $result = date('d', $timestamp) . ' ' . $months[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
    if ($withTime) $result .= ', ' . date('H:i', $timestamp) . ' WIB';
    return $result;
}

function redirect(string $url, int $statusCode = 302): never {
    header("Location: $url", true, $statusCode);
    exit;
}

function generateNomorSurat(PDO $pdo): string {
    $year = date('Y');
    $month = date('m');
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM surat_kuasa WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?");
        $stmt->execute([$year, $month]);
        $seq = (int) $stmt->fetchColumn() + 1;
        return sprintf('SKU/%s/%s/%03d', $year, $month, $seq);
    } catch (PDOException $e) {
        return sprintf('SKU/%s/%s/%03d', $year, $month, rand(1, 999));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  FILE UPLOAD HELPER - VERSI SEDERHANA YANG PASTI JALAN
// ═══════════════════════════════════════════════════════════════════════════

function uploadFile(array $file, string $prefix = ''): array {
    
    // 1. Cek error upload
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'message' => 'Parameter upload tidak valid.'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (melebihi MAX_FILE_SIZE).',
            UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diupload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh ekstensi PHP.',
        ];
        $msg = $errors[$file['error']] ?? 'Error upload tidak diketahui (kode: ' . $file['error'] . ')';
        return ['ok' => false, 'message' => $msg];
    }
    
    // 2. Cek apakah file benar-benar ada
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'message' => 'File tidak valid atau bukan file upload.'];
    }
    
    // 3. Cek ukuran file
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['ok' => false, 'message' => 'Ukuran file maksimal 10 MB.'];
    }
    
    if ($file['size'] <= 0) {
        return ['ok' => false, 'message' => 'File kosong (ukuran 0 byte).'];
    }
    
    // 4. Ambil ekstensi dari nama file asli
    $originalName = basename($file['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Jika tidak ada ekstensi, coba deteksi dari MIME
    if (empty($ext)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $ext = match(true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'bmp') => 'bmp',
            str_contains($mime, 'svg') => 'svg',
            default => 'jpg',
        };
    }
    
    // 5. Daftar ekstensi yang diizinkan (semua format gambar)
    $allowedExt = ['jpg', 'jpeg', 'jpe', 'jfif', 'pjpeg', 'png', 'apng', 'gif', 'webp', 'bmp', 'dib', 'svg', 'svgz', 'tiff', 'tif', 'ico', 'cur', 'heic', 'heif', 'avif', 'pdf'];
    
    if (!in_array($ext, $allowedExt)) {
        return ['ok' => false, 'message' => 'Format file tidak diizinkan: .' . $ext . '. Gunakan JPG, PNG, GIF, WebP, BMP, SVG, HEIC, atau PDF.'];
    }
    
    // 6. Bersihkan ekstensi untuk penyimpanan
    $saveExt = match($ext) {
        'jpeg', 'jpe', 'jfif', 'pjpeg' => 'jpg',
        'apng' => 'png',
        'dib' => 'bmp',
        'svgz' => 'svg',
        'tif' => 'tiff',
        'cur' => 'ico',
        'heif' => 'heic',
        default => $ext,
    };
    
    // 7. Buat nama file unik
    $filename = ($prefix ? $prefix . '_' : '') . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 10) . '.' . $saveExt;
    $target = UPLOAD_DIR . $filename;
    
    // 8. Pastikan direktori upload ada dan writable
    if (!is_dir(UPLOAD_DIR)) {
        if (!@mkdir(UPLOAD_DIR, 0777, true)) {
            error_log('[SISKA] Gagal membuat direktori: ' . UPLOAD_DIR);
            return ['ok' => false, 'message' => 'Gagal membuat direktori upload. Cek permission.'];
        }
    }
    
    if (!is_writable(UPLOAD_DIR)) {
        @chmod(UPLOAD_DIR, 0777);
        if (!is_writable(UPLOAD_DIR)) {
            error_log('[SISKA] Direktori tidak writable: ' . UPLOAD_DIR);
            return ['ok' => false, 'message' => 'Direktori upload tidak writable. Cek permission folder assets/uploads/'];
        }
    }
    
    // 9. Pindahkan file
    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        $moveError = error_get_last();
        error_log('[SISKA] Gagal move_uploaded_file: ' . ($moveError['message'] ?? 'Unknown') . ' | From: ' . $file['tmp_name'] . ' | To: ' . $target);
        return ['ok' => false, 'message' => 'Gagal menyimpan file. Error: ' . ($moveError['message'] ?? 'Unknown')];
    }
    
    // 10. Set permission file
    @chmod($target, 0644);
    
    // 11. Verifikasi file tersimpan
    if (!file_exists($target) || filesize($target) === 0) {
        error_log('[SISKA] File tersimpan tapi kosong/hilang: ' . $target);
        return ['ok' => false, 'message' => 'File gagal tersimpan dengan benar.'];
    }
    
    return [
        'ok' => true,
        'filename' => $filename,
        'size' => filesize($target),
        'original_name' => $originalName,
        'message' => 'Upload berhasil.'
    ];
}

function deleteUploadedFile(?string $filename): void {
    if (!$filename) return;
    $path = UPLOAD_DIR . $filename;
    if (file_exists($path) && is_file($path)) {
        @unlink($path);
    }
}

function imgPath(?string $filename): ?string {
    if (!$filename) return null;
    $path = UPLOAD_DIR . $filename;
    return file_exists($path) ? 'assets/uploads/' . $filename : null;
}

// ═══════════════════════════════════════════════════════════════════════════
//  AUDIT LOG - FUNGSI UTAMA UNTUK MENCATAT SEMUA AKTIVITAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Mencatat aktivitas user ke database
 * 
 * @param PDO $pdo Koneksi database
 * @param string $aksi Jenis aksi (login, logout, tambah, edit, hapus, dll)
 * @param string|null $tabel Nama tabel yang terlibat
 * @param int|null $recordId ID record yang terlibat
 * @param string|null $keterangan Deskripsi aktivitas
 * @param array|null $dataLama Data sebelum perubahan
 * @param array|null $dataBaru Data setelah perubahan
 */
function auditLog(
    PDO $pdo, 
    string $aksi, 
    ?string $tabel = null, 
    ?int $recordId = null, 
    ?string $keterangan = null, 
    ?array $dataLama = null, 
    ?array $dataBaru = null
): void {
    try {
        // Ambil data user dari session
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'system';
        
        // Ambil IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 
                     $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                     $_SERVER['HTTP_CLIENT_IP'] ?? 
                     '127.0.0.1';
        
        // Ambil User Agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Insert ke database
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (
                user_id, 
                username, 
                aksi, 
                tabel, 
                record_id, 
                data_lama, 
                data_baru, 
                keterangan, 
                ip_address, 
                user_agent,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $stmt->execute([
            $userId,
            $username,
            $aksi,
            $tabel,
            $recordId,
            $dataLama ? json_encode($dataLama, JSON_UNESCAPED_UNICODE) : null,
            $dataBaru ? json_encode($dataBaru, JSON_UNESCAPED_UNICODE) : null,
            $keterangan,
            $ipAddress,
            $userAgent
        ]);
        
    } catch (PDOException $e) {
        error_log('[SISKA] Audit Error: ' . $e->getMessage());
        error_log('[SISKA] Audit Data: ' . json_encode([
            'user_id' => $userId ?? null,
            'username' => $username ?? 'system',
            'aksi' => $aksi,
            'tabel' => $tabel,
            'record_id' => $recordId,
            'keterangan' => $keterangan
        ]));
    }
}

/**
 * Wrapper function untuk memudahkan pemanggilan audit log
 */
function logActivity(
    string $aksi, 
    ?string $tabel = null, 
    ?int $recordId = null, 
    ?string $keterangan = null, 
    ?array $dataLama = null, 
    ?array $dataBaru = null
): void {
    global $pdo;
    auditLog($pdo, $aksi, $tabel, $recordId, $keterangan, $dataLama, $dataBaru);
}

// ═══════════════════════════════════════════════════════════════════════════
//  UTILITY FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════

function renderError(string $msg, string $title = 'Error'): string {
    return "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>$title — " . APP_NAME . "</title><link href='https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap' rel='stylesheet'><link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'DM Sans',sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center}.error-box{background:#fff;padding:3rem;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);text-align:center;max-width:500px;width:90%}.error-icon{font-size:3rem;color:#0060AF;margin-bottom:1rem}h1{color:#1E293B;margin-bottom:.5rem;font-size:1.5rem}p{color:#64748B;margin-bottom:1.5rem;line-height:1.6}.btn-back{display:inline-flex;align-items:center;gap:.5rem;background:#0060AF;color:#fff;padding:.75rem 1.5rem;border-radius:10px;text-decoration:none;font-weight:600;transition:all .2s}.btn-back:hover{background:#004887;transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,96,175,.3)}</style></head><body><div class='error-box'><div class='error-icon'><i class='bi bi-exclamation-triangle'></i></div><h1>" . e($title) . "</h1><p>$msg</p><a href='javascript:history.back()' class='btn-back'><i class='bi bi-arrow-left'></i> Kembali</a></div></body></html>";
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die(renderError('Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.', 'CSRF Error'));
    }
}