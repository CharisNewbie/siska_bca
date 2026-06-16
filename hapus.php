<?php
require 'config/db.php';
requireAdmin();

// DEBUG: Log semua data yang masuk
error_log('[HAPUS] GET: ' . print_r($_GET, true));
error_log('[HAPUS] POST: ' . print_r($_POST, true));
error_log('[HAPUS] SESSION CSRF: ' . ($_SESSION['csrf_token'] ?? 'NULL'));

// Validasi CSRF
verifyCsrf();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    redirect('index.php?status=error&msg=ID+tidak+valid');
}

try {
    // Ambil info file sebelum dihapus
    $stmt = $pdo->prepare("SELECT foto_pemilik, ttd_pemilik, foto_penerima, ttd_penerima FROM surat_kuasa WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        redirect('index.php?status=error&msg=Data+tidak+ditemukan');
    }

    // Hapus record
    $pdo->prepare("DELETE FROM surat_kuasa WHERE id = ?")->execute([$id]);

    // Hapus file upload
    foreach (['foto_pemilik', 'ttd_pemilik', 'foto_penerima', 'ttd_penerima'] as $field) {
        deleteUploadedFile($row[$field] ?? null);
    }

    auditLog($pdo, 'hapus', 'surat_kuasa', $id);
    redirect('index.php?status=deleted');

} catch (PDOException $e) {
    error_log('[SISKA] Hapus error: ' . $e->getMessage());
    redirect('index.php?status=error&msg=Gagal+menghapus+data');
}
?>