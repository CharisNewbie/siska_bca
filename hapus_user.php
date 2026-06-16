<?php
require 'config/db.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($id <= 0) {
    redirect('users.php?error=' . urlencode('ID pengguna tidak valid'));
}

// Cegah menghapus diri sendiri
if ($id === $currentUserId) {
    redirect('users.php?error=' . urlencode('Anda tidak dapat menghapus akun sendiri'));
}

try {
    // Cek user exists
    $user = getUserById($pdo, $id);
    if (!$user) {
        redirect('users.php?error=' . urlencode('Pengguna tidak ditemukan'));
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
    
    redirect('users.php?status=deleted&msg=' . urlencode('Pengguna berhasil dihapus'));
    
} catch (PDOException $e) {
    error_log('[SISKA] Hapus user error: ' . $e->getMessage());
    redirect('users.php?error=' . urlencode('Gagal menghapus pengguna'));
}