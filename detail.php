<?php
require 'config/db.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT sk.*, u.username as dibuat_username, u.nama_lengkap as dibuat_nama
                           FROM surat_kuasa sk
                           LEFT JOIN users u ON sk.dibuat_oleh = u.id
                           WHERE sk.id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[SISKA] Detail error: ' . $e->getMessage());
    $data = null;
}

if (!$data) {
    redirect('index.php?status=error&msg=Data+tidak+ditemukan');
}

$pageTitle  = 'Detail Surat Kuasa';
$activeMenu = 'dashboard';

require 'includes/header.php';
?>

<!-- Page header -->
<div class="page-header">
    <div class="page-header-left">
        <h1>Detail Surat Kuasa</h1>
        <p>
            <?= $data['nomor_surat'] ? e($data['nomor_surat']) . ' &bull; ' : '' ?>
            Dibuat <?= formatTanggal($data['created_at'], true) ?> WIB
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <?php if (isAdmin()): ?>
        <a href="tambah.php?edit=<?= $data['id'] ?>" class="btn-ghost">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <button onclick="confirmDelete(<?= $data['id'] ?>, '<?= e($data['nama_pemilik']) ?>')" class="btn-danger-ghost" style="padding:.5rem 1rem;font-size:.875rem;">
            <i class="bi bi-trash"></i> Hapus
        </button>
        <?php endif; ?>
        <a href="index.php" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- Status Banner -->
<div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem;padding:1rem 1.25rem;background:#fff;border-radius:var(--radius-lg);border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);">
    <span class="badge-jenis <?= strtolower($data['jenis_kuasa']) ?>">
        <?= $data['jenis_kuasa'] === 'SETORAN'
            ? '<i class="bi bi-arrow-down-circle-fill"></i> SETORAN'
            : '<i class="bi bi-arrow-up-circle-fill"></i> TARIKAN' ?>
    </span>
    <span class="badge-status <?= $data['status'] ?>">
        <i class="bi bi-circle-fill" style="font-size:.45rem;"></i>
        <?= strtoupper($data['status']) ?>
    </span>
    <div style="flex:1;"></div>
    <div style="font-family:'DM Mono',monospace;font-size:1.5rem;font-weight:700;color:var(--bca-blue);">
        <?= formatRupiah($data['limit_transaksi']) ?>
    </div>
    <div style="font-size:.75rem;color:var(--gray-400);">Limit Transaksi</div>
</div>

<!-- Grid 2 Kolom: Pemilik & Penerima -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

    <!-- ─── PEMILIK REKENING ──────────────────────────────────────────── -->
    <div class="detail-section">
        <div class="detail-section-header" style="color:var(--bca-blue);">
            <i class="bi bi-person-badge-fill"></i>
            Data Pemilik Rekening
        </div>
        <div class="detail-section-body">
            <!-- Data Pemilik -->
            <div class="detail-row">
                <div class="detail-item">
                    <div class="detail-label">Nomor Rekening</div>
                    <div class="detail-value mono" style="font-size:1.1rem;font-weight:700;color:var(--bca-blue);">
                        <?= e($data['nomor_rekening']) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">NIK</div>
                    <div class="detail-value mono"><?= e($data['nik_pemilik'] ?: '—') ?></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item" style="flex:2;">
                    <div class="detail-label">Nama Lengkap</div>
                    <div class="detail-value" style="font-size:1.1rem;font-weight:700;"><?= e($data['nama_pemilik']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Telepon</div>
                    <div class="detail-value"><?= e($data['telepon_pemilik'] ?: '—') ?></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <div class="detail-label">Alamat</div>
                    <div class="detail-value"><?= e($data['alamat_pemilik']) ?></div>
                </div>
            </div>

            <!-- Foto & TTD Pemilik - Grid 2 kolom -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;">
                <!-- Foto Pemilik -->
                <div>
                    <div class="detail-label" style="margin-bottom:.5rem;">Foto Pemilik</div>
                    <div style="width:100%;height:180px;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        <?php $fp = imgPath($data['foto_pemilik']); ?>
                        <?php if ($fp): ?>
                        <img src="<?= $fp ?>" alt="Foto Pemilik"
                             onclick="viewImg('<?= $fp ?>', 'Foto Pemilik — <?= e($data['nama_pemilik']) ?>')"
                             style="width:100%;height:100%;object-fit:cover;cursor:pointer;">
                        <?php else: ?>
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--gray-400);">
                            <i class="bi bi-image" style="font-size:2rem;"></i>
                            <span style="font-size:.75rem;margin-top:.25rem;">Tidak ada foto</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- TTD Pemilik -->
                <div>
                    <div class="detail-label" style="margin-bottom:.5rem;">Tanda Tangan</div>
                    <div style="width:100%;height:180px;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        <?php $tp = imgPath($data['ttd_pemilik']); ?>
                        <?php if ($tp): ?>
                        <img src="<?= $tp ?>" alt="TTD Pemilik"
                             onclick="viewImg('<?= $tp ?>', 'Tanda Tangan — <?= e($data['nama_pemilik']) ?>')"
                             style="width:100%;height:100%;object-fit:contain;cursor:pointer;padding:0.5rem;">
                        <?php else: ?>
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--gray-400);">
                            <i class="bi bi-pen" style="font-size:2rem;"></i>
                            <span style="font-size:.75rem;margin-top:.25rem;">Tidak ada TTD</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── PENERIMA KUASA ────────────────────────────────────────────── -->
    <div class="detail-section">
        <div class="detail-section-header" style="color:var(--info);">
            <i class="bi bi-person-vcard-fill"></i>
            Data Penerima Kuasa
        </div>
        <div class="detail-section-body">
            <!-- Data Penerima -->
            <div class="detail-row">
                <div class="detail-item" style="flex:2;">
                    <div class="detail-label">Nama Lengkap</div>
                    <div class="detail-value" style="font-size:1.1rem;font-weight:700;"><?= e($data['nama_penerima']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">NIK</div>
                    <div class="detail-value mono"><?= e($data['nik_penerima'] ?: '—') ?></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <div class="detail-label">Jabatan / Hubungan</div>
                    <div class="detail-value"><?= e($data['jabatan_penerima'] ?: '—') ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Telepon</div>
                    <div class="detail-value"><?= e($data['telepon_penerima'] ?: '—') ?></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <div class="detail-label">Alamat</div>
                    <div class="detail-value"><?= e($data['alamat_penerima'] ?: '—') ?></div>
                </div>
            </div>

            <!-- Foto & TTD Penerima - Grid 2 kolom -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;">
                <!-- Foto Penerima -->
                <div>
                    <div class="detail-label" style="margin-bottom:.5rem;">Foto Penerima</div>
                    <div style="width:100%;height:180px;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        <?php $fp2 = imgPath($data['foto_penerima']); ?>
                        <?php if ($fp2): ?>
                        <img src="<?= $fp2 ?>" alt="Foto Penerima"
                             onclick="viewImg('<?= $fp2 ?>', 'Foto Penerima — <?= e($data['nama_penerima']) ?>')"
                             style="width:100%;height:100%;object-fit:cover;cursor:pointer;">
                        <?php else: ?>
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--gray-400);">
                            <i class="bi bi-image" style="font-size:2rem;"></i>
                            <span style="font-size:.75rem;margin-top:.25rem;">Tidak ada foto</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- TTD Penerima -->
                <div>
                    <div class="detail-label" style="margin-bottom:.5rem;">Tanda Tangan</div>
                    <div style="width:100%;height:180px;background:#f8fafc;border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        <?php $tp2 = imgPath($data['ttd_penerima']); ?>
                        <?php if ($tp2): ?>
                        <img src="<?= $tp2 ?>" alt="TTD Penerima"
                             onclick="viewImg('<?= $tp2 ?>', 'Tanda Tangan — <?= e($data['nama_penerima']) ?>')"
                             style="width:100%;height:100%;object-fit:contain;cursor:pointer;padding:0.5rem;">
                        <?php else: ?>
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--gray-400);">
                            <i class="bi bi-pen" style="font-size:2rem;"></i>
                            <span style="font-size:.75rem;margin-top:.25rem;">Tidak ada TTD</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── INFORMASI KUASA ──────────────────────────────────────────────────── -->
<div class="detail-section" style="margin-top:1.25rem;">
    <div class="detail-section-header" style="color:var(--success);">
        <i class="bi bi-file-earmark-lock2-fill"></i>
        Informasi Kuasa
    </div>
    <div class="detail-section-body">
        <div class="detail-row">
            <div class="detail-item">
                <div class="detail-label">Limit Transaksi</div>
                <div class="detail-value big"><?= formatRupiah($data['limit_transaksi']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Masa Berlaku</div>
                <div class="detail-value"><?= $data['masa_berlaku'] ? formatTanggal($data['masa_berlaku']) : '—' ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Tanggal Dibuat</div>
                <div class="detail-value"><?= formatTanggal($data['created_at'], true) ?> WIB</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Terakhir Diperbarui</div>
                <div class="detail-value"><?= formatTanggal($data['updated_at'], true) ?> WIB</div>
            </div>
        </div>
        <?php if ($data['keterangan']): ?>
        <div class="detail-row" style="margin-top:.5rem;">
            <div class="detail-item">
                <div class="detail-label">Keterangan</div>
                <div class="detail-value"><?= e($data['keterangan']) ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($data['dibuat_username']): ?>
        <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--gray-100);font-size:.78rem;color:var(--gray-400);">
            <i class="bi bi-person-check me-1"></i>
            Diinput oleh: <strong><?= e($data['dibuat_nama'] ?: $data['dibuat_username']) ?></strong>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$csrf = csrfToken();
$extraScripts = <<<JS
<script>
function viewImg(src, title) {
    Swal.fire({
        title: title,
        imageUrl: src,
        imageAlt: title,
        showCloseButton: true,
        showConfirmButton: false,
        width: 'auto',
        padding: '1.5rem',
        backdrop: 'rgba(0,0,0,.85)'
    });
}

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Surat Kuasa?',
        html: `Data atas nama <strong>\${nama}</strong> akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(r => {
        if (r.isConfirmed) {
            // Gunakan encodeURIComponent untuk aman
            const csrf = encodeURIComponent('{$csrf}');
            window.location.href = 'hapus.php?id=' + id + '&_csrf=' + csrf;
        }
    });
}

// DEBUG: Tampilkan token di console (hapus setelah testing)
console.log('CSRF Token: ' + '{$csrf}');
</script>
JS;
require 'includes/footer.php';
?>