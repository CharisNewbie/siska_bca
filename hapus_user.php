<?php
require 'config/db.php';
requireAdmin();
verifyCsrf();

// Terima dari POST (form submit) atau GET (redirect lama)
$id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
} else {
    $id = (int) ($_GET['id'] ?? 0);
}

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($id <= 0) {
    redirect('users.php?status=error&msg=ID+pengguna+tidak+valid');
}

// Cegah menghapus diri sendiri
if ($id === $currentUserId) {
    redirect('users.php?status=error&msg=Anda+tidak+dapat+menghapus+akun+sendiri');
}

try {
    // Cek user exists
    $stmt = $pdo->prepare("SELECT id, username, nama_lengkap FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('users.php?status=error&msg=Pengguna+tidak+ditemukan');
    }
    
    // Cek apakah user memiliki surat kuasa
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM surat_kuasa WHERE dibuat_oleh = ?");
    $stmt->execute([$id]);
    $suratCount = (int) $stmt->fetchColumn();
    
    if ($suratCount > 0) {
        // Set dibuat_oleh ke NULL untuk surat yang dibuat user ini
        $pdo->prepare("UPDATE surat_kuasa SET dibuat_oleh = NULL WHERE dibuat_oleh = ?")->execute([$id]);
    }
    
    // Hapus user
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    
    // Audit log
    auditLog(
        $pdo, 
        'hapus_user', 
        'users', 
        $id, 
        "User dihapus: {$user['username']} ({$user['nama_lengkap']})"
    );
    
    redirect('users.php?status=deleted&msg=Pengguna+berhasil+dihapus');
    
} catch (PDOException $e) {
    error_log('[SISKA] Hapus user error: ' . $e->getMessage());
    redirect('users.php?status=error&msg=Gagal+menghapus+pengguna');
}