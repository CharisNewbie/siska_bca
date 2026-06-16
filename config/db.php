<?php
/**
 * SISKA BCA - Konfigurasi Database & Helper Functions
 */

define('ENVIRONMENT', 'development');
define('DB_HOST', 'localhost');
define('DB_PORT', 8889);
define('DB_NAME', 'siska_bca');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'SISKA BCA ASEMKA');
define('APP_VERSION', '1.0.0');
define('APP_TZ', 'Asia/Jakarta');
define('APP_CABANG', 'KCU Asemka');
define('APP_URL', 'http://localhost:8888/siska-bca');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('SESSION_LIFETIME', 28800);
define('PASSWORD_MIN_LENGTH', 8);

date_default_timezone_set(APP_TZ);

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('[SISKA] DB Error: ' . $e->getMessage());
    die('Koneksi database gagal');
}

// ═══════════════ SECURITY & AUTH ═══════════════
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) redirect('login.php?status=error&msg=Silakan+login');
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) redirect('index.php?status=error&msg=Akses+ditolak');
}

function currentUser(): array {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'nama' => $_SESSION['nama'] ?? '',
        'role' => $_SESSION['role'] ?? 'user',
    ];
}

function logoutUser(): void {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        try { auditLog($pdo, 'logout', null, null, 'Session expired - auto logout'); } 
        catch (Exception $e) {}
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

// ═══════════════ HELPERS ═══════════════
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka): string {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function formatTanggal(?string $date, bool $withTime = false): string {
    if (!$date) return '-';
    $t = strtotime($date);
    if (!$t) return '-';
    $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $r = date('d',$t).' '.$months[(int)date('m',$t)].' '.date('Y',$t);
    if ($withTime) $r .= ', '.date('H:i',$t).' WIB';
    return $r;
}

function redirect(string $url, int $code = 302): never {
    header("Location: $url", true, $code);
    exit;
}

function generateNomorSurat(PDO $pdo): string {
    $y = date('Y'); $m = date('m');
    try {
        $n = (int)$pdo->query("SELECT COUNT(*) FROM surat_kuasa WHERE YEAR(created_at)=$y AND MONTH(created_at)=$m")->fetchColumn() + 1;
        return sprintf('SKU/%s/%s/%03d', $y, $m, $n);
    } catch (Exception $e) {
        return sprintf('SKU/%s/%s/%03d', $y, $m, rand(1,999));
    }
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['_csrf'] ?? $_GET['_csrf'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Token keamanan tidak valid');
    }
}

// ═══════════════ AUDIT LOG ═══════════════
function auditLog(PDO $pdo, string $aksi, ?string $tabel=null, ?int $recordId=null, ?string $ket=null, ?array $old=null, ?array $new=null): void {
    try {
        $uid = $_SESSION['user_id'] ?? null;
        $uname = $_SESSION['username'] ?? 'system';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $pdo->prepare("INSERT INTO audit_log (user_id,username,aksi,tabel,record_id,data_lama,data_baru,keterangan,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())")
            ->execute([$uid,$uname,$aksi,$tabel,$recordId,$old?json_encode($old):null,$new?json_encode($new):null,$ket,$ip,$ua]);
    } catch (PDOException $e) {
        error_log('[AUDIT] Error: '.$e->getMessage());
    }
}

function logActivity(string $aksi, ?string $tabel=null, ?int $id=null, ?string $ket=null): void {
    global $pdo;
    auditLog($pdo, $aksi, $tabel, $id, $ket);
}

// ═══════════════ UPLOAD ═══════════════
function uploadFile(array $file, string $prefix = ''): array {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok'=>false, 'message'=>'Upload gagal: '.($file['error']??'unknown')];
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) return ['ok'=>false, 'message'=>'File terlalu besar (max 10MB)'];
    if ($file['size'] <= 0) return ['ok'=>false, 'message'=>'File kosong'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','bmp','svg','heic','heif','pdf'];
    if (!in_array($ext, $allowed)) return ['ok'=>false, 'message'=>'Format tidak diizinkan: .'.$ext];
    
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
    
    $fname = $prefix.'_'.date('Ymd_His').'_'.substr(md5(uniqid()),0,10).'.'.$ext;
    $target = UPLOAD_DIR.$fname;
    
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok'=>false, 'message'=>'Gagal menyimpan file'];
    }
    
    return ['ok'=>true, 'filename'=>$fname, 'original_name'=>$file['name']];
}

function deleteUploadedFile(?string $f): void {
    if ($f && file_exists(UPLOAD_DIR.$f)) @unlink(UPLOAD_DIR.$f);
}

function imgPath(?string $f): ?string {
    return ($f && file_exists(UPLOAD_DIR.$f)) ? 'assets/uploads/'.$f : null;
}