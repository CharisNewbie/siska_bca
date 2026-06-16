<?php
/**
 * SISKA BCA - Logout Handler
 */
require 'config/db.php';

// Catat aktivitas logout SEBELUM session dihapus
if (isLoggedIn()) {
    try {
        auditLog(
            $pdo, 
            'logout', 
            null, 
            null, 
            'User logout - ' . ($_SESSION['username'] ?? 'Unknown')
        );
    } catch (Exception $e) {
        error_log('[SISKA] Logout audit error: ' . $e->getMessage());
    }
}

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login
redirect('login.php?status=info&msg=Anda+telah+keluar+dari+sistem.');