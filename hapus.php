<?php
require 'config/db.php';
requireAdmin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    redirect('index.php?error=' . urlencode('ID tidak valid'));
}

try {
    // Ambil info file sebelum dihapus
    $row = getSuratKuasaById($pdo, $id);

    if (!$row) {
        redirect('index.php?error=' . urlencode('Data tidak ditemukan'));
    }

    // Hapus record
    $pdo->prepare("DELETE FROM surat_kuasa WHERE id = ?")->execute([$id]);

    // Hapus file upload
    foreach (['foto_pemilik', 'ttd_pemilik', 'foto_penerima', 'ttd_penerima'] as $field) {
        deleteUploadedFile($row[$field] ?? null);
    }

    redirect('index.php?status=deleted');

} catch (PDOException $e) {
    error_log('[SISKA] Hapus error: ' . $e->getMessage());
    redirect('index.php?error=' . urlencode('Gagal menghapus data'));
}