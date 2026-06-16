<?php
require 'config/db.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = getSuratKuasaById($pdo, $id);

if (!$data) {
    redirect('index.php?error=' . urlencode('Data tidak ditemukan'));
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
        <a href="javascript:void(0)" onclick="confirmDelete(<?= $data['id'] ?>, '<?= e($data['nama_pemilik']) ?>')" 
           class="btn-danger-ghost" style="padding:.5rem 1rem;font-size:.875rem;">
            <i class="bi bi-trash"></i> Hapus
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- Status Banner -->
<div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem;padding:1rem 1.25rem;background:#fff;border-radius:var(--radius-lg);border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);flex-wrap:wrap;">
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

<!-- Grid 2 Kolom: Pemilik & Penerima - SAMA UKURAN -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

    <!-- ─── Kolom Kiri: Pemilik Rekening ─────────────────────────── -->
    <div>
        <div class="card-siska">
            <div class="card-header">
                <h5 style="color:var(--bca-blue);">
                    <i class="bi bi-person-badge-fill"></i>
                    Data Pemilik Rekening
                </h5>
            </div>
            <div class="card-body">
                <!-- Row 1: No Rekening & NIK (2 kolom) -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:0.75rem;">
                    <div>
                        <label class="form-label">No. Rekening</label>
                        <div class="detail-value-box">
                            <?= e($data['nomor_rekening']) ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">NIK Pemilik</label>
                        <div class="detail-value-box">
                            <?= e($data['nik_pemilik'] ?: '—') ?>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Nama Pemilik (1 kolom penuh) -->
                <div style="margin-bottom:0.75rem;">
                    <label class="form-label">Nama Lengkap</label>
                    <div class="detail-value-box" style="font-weight:600;font-size:1.05rem;">
                        <?= e($data['nama_pemilik']) ?>
                    </div>
                </div>

                <!-- Row 3: Alamat (1 kolom penuh) -->
                <div style="margin-bottom:0.75rem;">
                    <label class="form-label">Alamat</label>
                    <div class="detail-value-box" style="min-height:70px;line-height:1.6;">
                        <?= e($data['alamat_pemilik']) ?>
                    </div>
                </div>

                <!-- Row 4: Telepon (1 kolom) -->
                <div style="margin-bottom:0.75rem;">
                    <label class="form-label">No. Telepon</label>
                    <div class="detail-value-box">
                        <?= e($data['telepon_pemilik'] ?: '—') ?>
                    </div>
                </div>

                <!-- Row 5: Foto & TTD (2 kolom simetris) -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:0.5rem;">
                    <div>
                        <label class="form-label">Foto Pemilik</label>
                        <div class="photo-box-detail" onclick="openImageModal('<?= imgPath($data['foto_pemilik']) ?>', 'Foto Pemilik — <?= e($data['nama_pemilik']) ?>')">
                            <?php $fp = imgPath($data['foto_pemilik']); ?>
                            <?php if ($fp): ?>
                            <img src="<?= $fp ?>" alt="Foto Pemilik">
                            <div class="photo-overlay">
                                <i class="bi bi-zoom-in"></i> Klik perbesar
                            </div>
                            <?php else: ?>
                            <div class="photo-box-empty">
                                <i class="bi bi-image"></i>
                                <span>Tidak ada foto</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Tanda Tangan</label>
                        <div class="photo-box-detail" onclick="openImageModal('<?= imgPath($data['ttd_pemilik']) ?>', 'Tanda Tangan — <?= e($data['nama_pemilik']) ?>')">
                            <?php $tp = imgPath($data['ttd_pemilik']); ?>
                            <?php if ($tp): ?>
                            <img src="<?= $tp ?>" alt="TTD Pemilik" style="object-fit:contain;padding:0.5rem;background:#fff;">
                            <div class="photo-overlay">
                                <i class="bi bi-zoom-in"></i> Klik perbesar
                            </div>
                            <?php else: ?>
                            <div class="photo-box-empty">
                                <i class="bi bi-pen"></i>
                                <span>Tidak ada TTD</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Kolom Kanan: Penerima Kuasa ──────────────────────────── -->
    <div>
        <div class="card-siska">
            <div class="card-header">
                <h5 style="color:var(--info);">
                    <i class="bi bi-person-vcard-fill"></i>
                    Data Penerima Kuasa
                </h5>
            </div>
            <div class="card-body">
                <!-- Row 1: Nama & NIK Penerima (2 kolom) -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:0.75rem;">
                    <div>
                        <label class="form-label">Nama Penerima</label>
                        <div class="detail-value-box" style="font-weight:600;font-size:1.05rem;">
                            <?= e($data['nama_penerima']) ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">NIK Penerima</label>
                        <div class="detail-value-box">
                            <?= e($data['nik_penerima'] ?: '—') ?>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Jabatan & Telepon (2 kolom) -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:0.75rem;">
                    <div>
                        <label class="form-label">Jabatan / Hubungan</label>
                        <div class="detail-value-box">
                            <?= e($data['jabatan_penerima'] ?: '—') ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">No. Telepon</label>
                        <div class="detail-value-box">
                            <?= e($data['telepon_penerima'] ?: '—') ?>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Alamat Penerima (1 kolom penuh) -->
                <div style="margin-bottom:0.75rem;">
                    <label class="form-label">Alamat</label>
                    <div class="detail-value-box" style="min-height:70px;line-height:1.6;">
                        <?= e($data['alamat_penerima'] ?: '—') ?>
                    </div>
                </div>

                <!-- Row 4: Foto & TTD Penerima (2 kolom simetris) - SAMA DENGAN PEMILIK -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:0.5rem;">
                    <div>
                        <label class="form-label">Foto Penerima</label>
                        <div class="photo-box-detail" onclick="openImageModal('<?= imgPath($data['foto_penerima']) ?>', 'Foto Penerima — <?= e($data['nama_penerima']) ?>')">
                            <?php $fp2 = imgPath($data['foto_penerima']); ?>
                            <?php if ($fp2): ?>
                            <img src="<?= $fp2 ?>" alt="Foto Penerima">
                            <div class="photo-overlay">
                                <i class="bi bi-zoom-in"></i> Klik perbesar
                            </div>
                            <?php else: ?>
                            <div class="photo-box-empty">
                                <i class="bi bi-image"></i>
                                <span>Tidak ada foto</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Tanda Tangan</label>
                        <div class="photo-box-detail" onclick="openImageModal('<?= imgPath($data['ttd_penerima']) ?>', 'Tanda Tangan — <?= e($data['nama_penerima']) ?>')">
                            <?php $tp2 = imgPath($data['ttd_penerima']); ?>
                            <?php if ($tp2): ?>
                            <img src="<?= $tp2 ?>" alt="TTD Penerima" style="object-fit:contain;padding:0.5rem;background:#fff;">
                            <div class="photo-overlay">
                                <i class="bi bi-zoom-in"></i> Klik perbesar
                            </div>
                            <?php else: ?>
                            <div class="photo-box-empty">
                                <i class="bi bi-pen"></i>
                                <span>Tidak ada TTD</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── INFORMASI KUASA - FULL WIDTH PERSEGI PANJANG ──────────────────── -->
<div class="card-siska" style="margin-top:1.25rem;">
    <div class="card-header">
        <h5 style="color:var(--success);">
            <i class="bi bi-file-earmark-lock2-fill"></i>
            Informasi Kuasa
        </h5>
    </div>
    <div class="card-body">
        <!-- Grid 4 Kolom untuk informasi penting -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div>
                <label class="form-label">Jenis Kuasa</label>
                <div class="detail-value-box" style="padding:0.4rem 0.875rem;">
                    <span class="badge-jenis <?= strtolower($data['jenis_kuasa']) ?>" style="font-size:.8rem;display:inline-flex;align-items:center;gap:0.3rem;">
                        <?= $data['jenis_kuasa'] === 'SETORAN'
                            ? '<i class="bi bi-arrow-down-circle"></i> SETORAN'
                            : '<i class="bi bi-arrow-up-circle"></i> TARIKAN' ?>
                    </span>
                </div>
            </div>
            <div>
                <label class="form-label">Status</label>
                <div class="detail-value-box" style="padding:0.4rem 0.875rem;">
                    <span class="badge-status <?= $data['status'] ?>" style="font-size:.8rem;display:inline-flex;align-items:center;gap:0.3rem;">
                        <i class="bi bi-circle-fill" style="font-size:.4rem;"></i>
                        <?= strtoupper($data['status']) ?>
                    </span>
                </div>
            </div>
            <div>
                <label class="form-label">Limit Transaksi</label>
                <div class="detail-value-box" style="font-family:'DM Mono',monospace;font-weight:700;color:var(--bca-blue);font-size:1.05rem;">
                    <?= formatRupiah($data['limit_transaksi']) ?>
                </div>
            </div>
            <div>
                <label class="form-label">Masa Berlaku</label>
                <div class="detail-value-box">
                    <?= $data['masa_berlaku'] ? formatTanggal($data['masa_berlaku']) : '—' ?>
                </div>
            </div>
        </div>

        <!-- Keterangan (jika ada) - Full Width -->
        <?php if ($data['keterangan']): ?>
        <div style="margin-bottom:0.75rem;">
            <label class="form-label">Keterangan</label>
            <div class="detail-value-box" style="min-height:50px;line-height:1.6;">
                <?= e($data['keterangan']) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informasi Pembuat - Full Width -->
        <div style="margin-top:0.5rem;padding-top:0.75rem;border-top:1px solid var(--gray-100);font-size:.82rem;color:var(--gray-500);">
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                <span>
                    <i class="bi bi-person-check me-1"></i>
                    Diinput oleh: <strong><?= e($data['dibuat_nama'] ?: $data['dibuat_username']) ?></strong>
                </span>
                <span>
                    <i class="bi bi-clock me-1"></i>
                    Dibuat: <?= formatTanggal($data['created_at'], true) ?> WIB
                </span>
                <?php if ($data['updated_at'] && $data['updated_at'] != $data['created_at']): ?>
                <span>
                    <i class="bi bi-pencil-square me-1"></i>
                    Diperbarui: <?= formatTanggal($data['updated_at'], true) ?> WIB
                </span>
                <?php endif; ?>
                <span>
                    <i class="bi bi-file-text me-1"></i>
                    Nomor Surat: <strong><?= e($data['nomor_surat'] ?: '—') ?></strong>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ─── IMAGE MODAL ──────────────────────────────────────────────────────────── -->
<div id="imageModal" class="image-modal" style="display:none;">
    <div class="image-modal-content">
        <div class="image-modal-header">
            <span id="imageModalTitle">Preview Gambar</span>
            <button class="image-modal-close" onclick="closeImageModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="image-modal-body">
            <img id="imageModalImage" src="" alt="Preview">
        </div>
        <div class="image-modal-footer">
            <button class="btn-ghost" onclick="closeImageModal()">Tutup</button>
            <a id="imageModalDownload" href="#" download class="btn-bca" style="text-decoration:none;">
                <i class="bi bi-download"></i> Download
            </a>
        </div>
    </div>
</div>

<style>
/* ─── Detail Value Box ────────────────────────────────────────────────────── */
.detail-value-box {
    background: var(--gray-50);
    border-radius: var(--radius-md);
    border: 1px solid var(--gray-200);
    padding: 0.6rem 0.875rem;
    color: var(--gray-800);
    word-break: break-word;
    min-height: 44px;
    display: flex;
    align-items: center;
}

/* ─── Photo Box Detail ────────────────────────────────────────────────────── */
.photo-box-detail {
    width: 100%;
    height: 160px;
    background: #f8fafc;
    border-radius: var(--radius-md);
    border: 1px solid var(--gray-200);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
}

.photo-box-detail:hover {
    border-color: var(--bca-blue);
    box-shadow: 0 4px 12px rgba(0,96,175,.15);
}

.photo-box-detail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.photo-box-detail:hover img {
    transform: scale(1.05);
}

.photo-box-detail .photo-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    color: #fff;
    font-size: .8rem;
    font-weight: 500;
    opacity: 0;
    transition: opacity 0.3s ease;
    backdrop-filter: blur(2px);
}

.photo-box-detail:hover .photo-overlay {
    opacity: 1;
}

.photo-box-detail .photo-overlay i {
    font-size: 1.1rem;
}

.photo-box-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--gray-400);
    gap: .5rem;
    padding: 1rem;
}

.photo-box-empty i {
    font-size: 2.5rem;
    opacity: 0.5;
}

.photo-box-empty span {
    font-size: .8rem;
}

/* ─── Form Label ────────────────────────────────────────────────────────────── */
.form-label {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 0.3rem;
}

/* ─── Image Modal ──────────────────────────────────────────────────────────── */
.image-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.85);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease;
}

.image-modal-content {
    background: #fff;
    border-radius: var(--radius-xl);
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
    animation: slideUp 0.3s ease;
}

.image-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.image-modal-header span {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gray-800);
}

.image-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--gray-400);
    cursor: pointer;
    padding: .25rem .5rem;
    border-radius: var(--radius-sm);
    transition: all 0.3s ease;
    line-height: 1;
}

.image-modal-close:hover {
    background: #FEE2E2;
    color: var(--danger);
}

.image-modal-body {
    flex: 1;
    padding: 1.5rem;
    overflow: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    min-height: 300px;
    max-height: 65vh;
}

.image-modal-body img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: var(--radius-md);
}

.image-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    padding: 0.75rem 1.5rem;
    border-top: 1px solid var(--gray-200);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* ─── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .photo-box-detail {
        height: 140px;
    }
}

@media (max-width: 768px) {
    .photo-box-detail {
        height: 130px;
    }
    
    .image-modal {
        padding: 1rem;
    }
    
    .image-modal-content {
        max-width: 100%;
        border-radius: var(--radius-lg);
    }
    
    .image-modal-body {
        min-height: 200px;
        max-height: 50vh;
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .photo-box-detail {
        height: 110px;
    }
    
    .photo-box-detail .photo-overlay {
        font-size: .7rem;
    }
    
    .photo-box-detail .photo-overlay i {
        font-size: .9rem;
    }
    
    .image-modal-body {
        min-height: 150px;
        max-height: 40vh;
    }
}
</style>

<?php
$extraScripts = <<<JS
<script>
// ─── Image Modal Functions ──────────────────────────────────────────────────
function openImageModal(imageUrl, title) {
    if (!imageUrl) {
        alert('Gambar tidak tersedia.');
        return;
    }
    
    const modal = document.getElementById('imageModal');
    const img = document.getElementById('imageModalImage');
    const titleEl = document.getElementById('imageModalTitle');
    const downloadLink = document.getElementById('imageModalDownload');
    
    img.src = imageUrl;
    titleEl.textContent = title || 'Preview Gambar';
    downloadLink.href = imageUrl;
    
    // Extract filename untuk download
    const filename = imageUrl.split('/').pop();
    downloadLink.download = filename || 'gambar.jpg';
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Tutup modal dengan Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});

// Tutup modal jika klik di luar content
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

// ─── Confirm Delete ──────────────────────────────────────────────────────────
function confirmDelete(id, nama) {
    if (confirm('Hapus surat kuasa atas nama "' + nama + '"?')) {
        window.location.href = 'hapus.php?id=' + id;
    }
}
</script>
JS;

require 'includes/footer.php';
?>