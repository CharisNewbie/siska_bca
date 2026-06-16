<?php
require 'config/db.php';
requireAdmin();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$isEdit = $editId > 0;
$data   = [];
$errors = [];

// Load data jika mode edit
if ($isEdit) {
    $data = getSuratKuasaById($pdo, $editId);
    if (!$data) redirect('index.php');
}

// ─── Proses POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'nomor_rekening'   => trim($_POST['nomor_rekening'] ?? ''),
        'nama_pemilik'     => trim($_POST['nama_pemilik']   ?? ''),
        'nik_pemilik'      => trim($_POST['nik_pemilik']    ?? ''),
        'alamat_pemilik'   => trim($_POST['alamat_pemilik'] ?? ''),
        'telepon_pemilik'  => trim($_POST['telepon_pemilik']?? ''),
        'jenis_kuasa'      => $_POST['jenis_kuasa']         ?? '',
        'nama_penerima'    => trim($_POST['nama_penerima']  ?? ''),
        'nik_penerima'     => trim($_POST['nik_penerima']   ?? ''),
        'jabatan_penerima' => trim($_POST['jabatan_penerima']?? ''),
        'alamat_penerima'  => trim($_POST['alamat_penerima']?? ''),
        'telepon_penerima' => trim($_POST['telepon_penerima']??''),
        'limit_transaksi'  => (int) preg_replace('/\D/', '', $_POST['limit_transaksi'] ?? '0'),
        'masa_berlaku'     => $_POST['masa_berlaku']        ?: null,
        'keterangan'       => trim($_POST['keterangan']     ?? ''),
        'status'           => $_POST['status']              ?? 'aktif',
    ];

    // Validasi wajib
    $required = ['nomor_rekening', 'nama_pemilik', 'alamat_pemilik', 'jenis_kuasa', 'nama_penerima'];
    foreach ($required as $k) {
        if ($fields[$k] === '') {
            $errors[$k] = 'Field ini wajib diisi.';
        }
    }

    if (!in_array($fields['jenis_kuasa'], ['SETORAN', 'TARIKAN'])) {
        $errors['jenis_kuasa'] = 'Pilih jenis kuasa yang valid.';
    }

    // Handle uploads
    $uploadFields = ['foto_pemilik', 'ttd_pemilik', 'foto_penerima', 'ttd_penerima'];
    $uploadResults = [];

    if (empty($errors)) {
        foreach ($uploadFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $res = uploadFile($_FILES[$field], $field);
                if (!$res['ok']) {
                    $errors[$field] = $res['message'];
                } else {
                    $uploadResults[$field] = $res['filename'];
                }
            } else {
                $uploadResults[$field] = $isEdit ? ($data[$field] ?? null) : null;
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                // UPDATE
                $sql = "UPDATE surat_kuasa SET
                    nomor_rekening = ?, nama_pemilik = ?, nik_pemilik = ?, alamat_pemilik = ?,
                    telepon_pemilik = ?, jenis_kuasa = ?, nama_penerima = ?, nik_penerima = ?,
                    jabatan_penerima = ?, alamat_penerima = ?, telepon_penerima = ?,
                    limit_transaksi = ?, masa_berlaku = ?, keterangan = ?, status = ?,
                    foto_pemilik = ?, ttd_pemilik = ?, foto_penerima = ?, ttd_penerima = ?,
                    updated_at = NOW()
                    WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $fields['nomor_rekening'], $fields['nama_pemilik'], $fields['nik_pemilik'],
                    $fields['alamat_pemilik'], $fields['telepon_pemilik'], $fields['jenis_kuasa'],
                    $fields['nama_penerima'], $fields['nik_penerima'], $fields['jabatan_penerima'],
                    $fields['alamat_penerima'], $fields['telepon_penerima'], $fields['limit_transaksi'],
                    $fields['masa_berlaku'], $fields['keterangan'], $fields['status'],
                    $uploadResults['foto_pemilik'], $uploadResults['ttd_pemilik'],
                    $uploadResults['foto_penerima'], $uploadResults['ttd_penerima'],
                    $editId
                ]);
                
                redirect('detail.php?id=' . $editId . '&status=success');
            } else {
                // INSERT
                $nomorSurat = generateNomorSurat($pdo);
                $userId = $_SESSION['user_id'] ?? null;
                
                // Validasi user id
                if ($userId) {
                    $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
                    $checkUser->execute([$userId]);
                    if (!$checkUser->fetch()) {
                        $userId = null;
                    }
                }
                
                // Fallback ke admin pertama
                if (!$userId) {
                    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
                    $adminUser = $adminStmt->fetch();
                    $userId = $adminUser ? $adminUser['id'] : null;
                }
                
                $sql = "INSERT INTO surat_kuasa
                    (nomor_surat, nomor_rekening, nama_pemilik, nik_pemilik, alamat_pemilik, telepon_pemilik,
                     jenis_kuasa, nama_penerima, nik_penerima, jabatan_penerima, alamat_penerima,
                     telepon_penerima, limit_transaksi, masa_berlaku, keterangan, status,
                     foto_pemilik, ttd_pemilik, foto_penerima, ttd_penerima, dibuat_oleh)
                    VALUES (?,?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nomorSurat, $fields['nomor_rekening'], $fields['nama_pemilik'], $fields['nik_pemilik'],
                    $fields['alamat_pemilik'], $fields['telepon_pemilik'], $fields['jenis_kuasa'],
                    $fields['nama_penerima'], $fields['nik_penerima'], $fields['jabatan_penerima'],
                    $fields['alamat_penerima'], $fields['telepon_penerima'], $fields['limit_transaksi'],
                    $fields['masa_berlaku'], $fields['keterangan'], $fields['status'],
                    $uploadResults['foto_pemilik'], $uploadResults['ttd_pemilik'],
                    $uploadResults['foto_penerima'], $uploadResults['ttd_penerima'],
                    $userId
                ]);
                
                $newId = $pdo->lastInsertId();
                redirect('detail.php?id=' . $newId . '&status=success');
            }
        } catch (PDOException $e) {
            error_log('[SISKA] Tambah/Edit error: ' . $e->getMessage());
            $errors['_global'] = 'Terjadi kesalahan database. Silakan coba lagi.';
        }
    }

    // Isi ulang form dari POST jika ada error
    if (!empty($errors) && !$isEdit) {
        $data = $fields;
    }
}

// Helper: nilai form
function val(string $key, array $data = [], string $default = ''): string {
    return e($_POST[$key] ?? $data[$key] ?? $default);
}

$pageTitle  = $isEdit ? 'Edit Surat Kuasa' : 'Input Surat Kuasa';
$activeMenu = 'tambah';
require 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?= $isEdit ? 'Edit Surat Kuasa' : 'Input Surat Kuasa Baru' ?></h1>
        <p><?= $isEdit ? 'Perbarui data surat kuasa nasabah.' : 'Isi formulir untuk mendaftarkan surat kuasa baru.' ?></p>
    </div>
    <a href="<?= $isEdit ? 'detail.php?id='.$editId : 'index.php' ?>" class="btn-ghost">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<?php if (!empty($errors['_global'])): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem;">
    <i class="bi bi-exclamation-circle-fill"></i>
    <?= e($errors['_global']) ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate id="formSurat">

    <!-- Grid 2 Kolom: Pemilik & Penerima -->
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
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">No. Rekening <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="nomor_rekening" class="form-control <?= isset($errors['nomor_rekening']) ? 'is-invalid' : '' ?>"
                                   value="<?= val('nomor_rekening', $data) ?>" placeholder="Contoh: 1234567890" maxlength="30" required>
                            <?php if (isset($errors['nomor_rekening'])): ?>
                            <div class="invalid-feedback"><?= e($errors['nomor_rekening']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIK Pemilik</label>
                            <input type="text" name="nik_pemilik" class="form-control"
                                   value="<?= val('nik_pemilik', $data) ?>" placeholder="16 digit NIK" maxlength="20">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Pemilik <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="nama_pemilik" class="form-control <?= isset($errors['nama_pemilik']) ? 'is-invalid' : '' ?>"
                               value="<?= val('nama_pemilik', $data) ?>" placeholder="Nama lengkap sesuai KTP" required>
                        <?php if (isset($errors['nama_pemilik'])): ?>
                        <div class="invalid-feedback"><?= e($errors['nama_pemilik']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Pemilik <span style="color:var(--danger)">*</span></label>
                        <textarea name="alamat_pemilik" class="form-control <?= isset($errors['alamat_pemilik']) ? 'is-invalid' : '' ?>"
                                  rows="3" placeholder="Alamat lengkap sesuai KTP"><?= val('alamat_pemilik', $data) ?></textarea>
                        <?php if (isset($errors['alamat_pemilik'])): ?>
                        <div class="invalid-feedback"><?= e($errors['alamat_pemilik']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Telepon Pemilik</label>
                        <input type="text" name="telepon_pemilik" class="form-control"
                               value="<?= val('telepon_pemilik', $data) ?>" placeholder="08xx-xxxx-xxxx" maxlength="20">
                    </div>

                    <!-- Upload Foto & TTD Pemilik -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;">
                        <div class="form-group">
                            <label class="form-label">Foto Pemilik <?= !$isEdit ? '<span style="color:var(--danger)">*</span>' : '' ?></label>
                            <div class="upload-zone" id="zFotoPemilik">
                                <input type="file" name="foto_pemilik" id="inFotoPemilik"
                                       accept="image/*,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.heic,.heif,.pdf"
                                       <?= !$isEdit ? 'required' : '' ?>>
                                <div class="upload-placeholder" id="phFotoPemilik">
                                    <div class="upload-icon"><i class="bi bi-camera-fill"></i></div>
                                    <div class="upload-text">Klik atau drag foto di sini</div>
                                    <div class="upload-hint">JPG, PNG, GIF, WebP &bull; Maks 10 MB</div>
                                </div>
                                <div class="upload-preview" id="prFotoPemilik" style="<?= ($isEdit && !empty($data['foto_pemilik'])) ? 'display:block;' : 'display:none;' ?>">
                                    <?php if ($isEdit && !empty($data['foto_pemilik'])): ?>
                                        <?php $imgPath = imgPath($data['foto_pemilik']); ?>
                                        <?php if ($imgPath): ?>
                                            <img src="<?= $imgPath ?>?v=<?= time() ?>" alt="Foto Pemilik" style="display:block;">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <img src="" alt="Preview" style="display:none;">
                                    <?php endif; ?>
                                    <div class="upload-preview-overlay"><i class="bi bi-pencil"></i> Ganti Foto</div>
                                </div>
                            </div>
                            <?php if (isset($errors['foto_pemilik'])): ?>
                            <div class="invalid-feedback" style="display:block;"><?= e($errors['foto_pemilik']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanda Tangan Pemilik <?= !$isEdit ? '<span style="color:var(--danger)">*</span>' : '' ?></label>
                            <div class="upload-zone" id="zTtdPemilik">
                                <input type="file" name="ttd_pemilik" id="inTtdPemilik"
                                       accept="image/*,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.heic,.heif,.pdf"
                                       <?= !$isEdit ? 'required' : '' ?>>
                                <div class="upload-placeholder" id="phTtdPemilik">
                                    <div class="upload-icon"><i class="bi bi-pen-fill"></i></div>
                                    <div class="upload-text">Klik atau drag TTD di sini</div>
                                    <div class="upload-hint">JPG, PNG, GIF, WebP &bull; Maks 10 MB</div>
                                </div>
                                <div class="upload-preview" id="prTtdPemilik" style="<?= ($isEdit && !empty($data['ttd_pemilik'])) ? 'display:block;' : 'display:none;' ?>">
                                    <?php if ($isEdit && !empty($data['ttd_pemilik'])): ?>
                                        <?php $imgPath = imgPath($data['ttd_pemilik']); ?>
                                        <?php if ($imgPath): ?>
                                            <img src="<?= $imgPath ?>?v=<?= time() ?>" alt="TTD Pemilik" style="display:block;object-fit:contain;background:#fff;">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <img src="" alt="Preview" style="display:none;object-fit:contain;background:#fff;">
                                    <?php endif; ?>
                                    <div class="upload-preview-overlay"><i class="bi bi-pencil"></i> Ganti TTD</div>
                                </div>
                            </div>
                            <?php if (isset($errors['ttd_pemilik'])): ?>
                            <div class="invalid-feedback" style="display:block;"><?= e($errors['ttd_pemilik']) ?></div>
                            <?php endif; ?>
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
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Nama Penerima <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="nama_penerima" class="form-control <?= isset($errors['nama_penerima']) ? 'is-invalid' : '' ?>"
                                   value="<?= val('nama_penerima', $data) ?>" placeholder="Nama lengkap penerima kuasa" required>
                            <?php if (isset($errors['nama_penerima'])): ?>
                            <div class="invalid-feedback"><?= e($errors['nama_penerima']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIK Penerima</label>
                            <input type="text" name="nik_penerima" class="form-control"
                                   value="<?= val('nik_penerima', $data) ?>" placeholder="16 digit NIK" maxlength="20">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Jabatan / Hubungan</label>
                            <input type="text" name="jabatan_penerima" class="form-control"
                                   value="<?= val('jabatan_penerima', $data) ?>" placeholder="Contoh: Istri, Anak, Karyawan">
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Telepon Penerima</label>
                            <input type="text" name="telepon_penerima" class="form-control"
                                   value="<?= val('telepon_penerima', $data) ?>" placeholder="08xx-xxxx-xxxx" maxlength="20">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Penerima</label>
                        <textarea name="alamat_penerima" class="form-control" rows="3"
                                  placeholder="Alamat lengkap penerima"><?= val('alamat_penerima', $data) ?></textarea>
                    </div>

                    <!-- Upload Foto & TTD Penerima -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;">
                        <div class="form-group">
                            <label class="form-label">Foto Penerima <?= !$isEdit ? '<span style="color:var(--danger)">*</span>' : '' ?></label>
                            <div class="upload-zone" id="zFotoPenerima">
                                <input type="file" name="foto_penerima" id="inFotoPenerima"
                                       accept="image/*,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.heic,.heif,.pdf"
                                       <?= !$isEdit ? 'required' : '' ?>>
                                <div class="upload-placeholder" id="phFotoPenerima">
                                    <div class="upload-icon"><i class="bi bi-camera-fill"></i></div>
                                    <div class="upload-text">Klik atau drag foto di sini</div>
                                    <div class="upload-hint">JPG, PNG, GIF, WebP &bull; Maks 10 MB</div>
                                </div>
                                <div class="upload-preview" id="prFotoPenerima" style="<?= ($isEdit && !empty($data['foto_penerima'])) ? 'display:block;' : 'display:none;' ?>">
                                    <?php if ($isEdit && !empty($data['foto_penerima'])): ?>
                                        <?php $imgPath = imgPath($data['foto_penerima']); ?>
                                        <?php if ($imgPath): ?>
                                            <img src="<?= $imgPath ?>?v=<?= time() ?>" alt="Foto Penerima" style="display:block;">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <img src="" alt="Preview" style="display:none;">
                                    <?php endif; ?>
                                    <div class="upload-preview-overlay"><i class="bi bi-pencil"></i> Ganti Foto</div>
                                </div>
                            </div>
                            <?php if (isset($errors['foto_penerima'])): ?>
                            <div class="invalid-feedback" style="display:block;"><?= e($errors['foto_penerima']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanda Tangan Penerima <?= !$isEdit ? '<span style="color:var(--danger)">*</span>' : '' ?></label>
                            <div class="upload-zone" id="zTtdPenerima">
                                <input type="file" name="ttd_penerima" id="inTtdPenerima"
                                       accept="image/*,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.heic,.heif,.pdf"
                                       <?= !$isEdit ? 'required' : '' ?>>
                                <div class="upload-placeholder" id="phTtdPenerima">
                                    <div class="upload-icon"><i class="bi bi-pen-fill"></i></div>
                                    <div class="upload-text">Klik atau drag TTD di sini</div>
                                    <div class="upload-hint">JPG, PNG, GIF, WebP &bull; Maks 10 MB</div>
                                </div>
                                <div class="upload-preview" id="prTtdPenerima" style="<?= ($isEdit && !empty($data['ttd_penerima'])) ? 'display:block;' : 'display:none;' ?>">
                                    <?php if ($isEdit && !empty($data['ttd_penerima'])): ?>
                                        <?php $imgPath = imgPath($data['ttd_penerima']); ?>
                                        <?php if ($imgPath): ?>
                                            <img src="<?= $imgPath ?>?v=<?= time() ?>" alt="TTD Penerima" style="display:block;object-fit:contain;background:#fff;">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <img src="" alt="Preview" style="display:none;object-fit:contain;background:#fff;">
                                    <?php endif; ?>
                                    <div class="upload-preview-overlay"><i class="bi bi-pencil"></i> Ganti TTD</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── INFORMASI KUASA - FULL WIDTH DI BAWAH ────────────────────────── -->
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
                <div class="form-group">
                    <label class="form-label">Jenis Kuasa <span style="color:var(--danger)">*</span></label>
                    <select name="jenis_kuasa" class="form-select <?= isset($errors['jenis_kuasa']) ? 'is-invalid' : '' ?>" required>
                        <option value="" disabled <?= !($data['jenis_kuasa'] ?? '') ? 'selected' : '' ?>>Pilih jenis...</option>
                        <option value="SETORAN" <?= ($data['jenis_kuasa'] ?? '') === 'SETORAN' ? 'selected' : '' ?>>SETORAN (Giro)</option>
                        <option value="TARIKAN" <?= ($data['jenis_kuasa'] ?? '') === 'TARIKAN' ? 'selected' : '' ?>>TARIKAN (Tabungan)</option>
                    </select>
                    <?php if (isset($errors['jenis_kuasa'])): ?>
                    <div class="invalid-feedback"><?= e($errors['jenis_kuasa']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['aktif', 'nonaktif', 'kadaluarsa'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($data['status'] ?? 'aktif') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Limit Transaksi (Rp)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="limit_transaksi" id="limitInput" class="form-control" data-rupiah
                               value="<?= number_format((float)($data['limit_transaksi'] ?? 0), 0, ',', '.') ?>" placeholder="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Masa Berlaku</label>
                    <input type="date" name="masa_berlaku" class="form-control"
                           value="<?= e($data['masa_berlaku'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- Keterangan - Full Width -->
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"><?= val('keterangan', $data) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--gray-200);">
        <a href="<?= $isEdit ? 'detail.php?id='.$editId : 'index.php' ?>" class="btn-ghost">Batal</a>
        <button type="submit" class="btn-bca" id="btnSubmit">
            <i class="bi bi-save-fill"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Data' ?>
        </button>
    </div>

</form>

<script>
// ─── SIMPLE UPLOAD PREVIEW ────────────────────────────────────────────────
function setupUpload(inputId, previewId, phId, zoneId, isTtd) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const ph      = document.getElementById(phId);
    const zone    = document.getElementById(zoneId);
    if (!input || !preview || !ph) return;

    // Saat file dipilih
    input.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        // Validasi ukuran (10 MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('File terlalu besar! Maksimal 10 MB.');
            this.value = '';
            return;
        }

        // Preview gambar
        if (file.type.match(/^image\//)) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = preview.querySelector('img');
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if (isTtd) {
                        img.style.objectFit = 'contain';
                        img.style.background = '#fff';
                    }
                }
                preview.style.display = 'block';
                ph.style.display = 'none';
                if (zone) zone.classList.add('has-file');
            };
            reader.readAsDataURL(file);
        } else {
            // File non-gambar (PDF dll)
            ph.innerHTML = '<div class="upload-icon"><i class="bi bi-file-earmark-check-fill"></i></div>' +
                          '<div class="upload-text" style="color:green;">File: ' + file.name + '</div>' +
                          '<div class="upload-hint">' + (file.size / 1024).toFixed(1) + ' KB</div>';
            preview.style.display = 'none';
            if (zone) zone.classList.add('has-file');
        }
    });

    // Drag & drop
    if (zone) {
        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', function() { zone.classList.remove('drag-over'); });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }
}

// Setup semua upload field
setupUpload('inFotoPemilik', 'prFotoPemilik', 'phFotoPemilik', 'zFotoPemilik', false);
setupUpload('inTtdPemilik', 'prTtdPemilik', 'phTtdPemilik', 'zTtdPemilik', true);
setupUpload('inFotoPenerima', 'prFotoPenerima', 'phFotoPenerima', 'zFotoPenerima', false);
setupUpload('inTtdPenerima', 'prTtdPenerima', 'phTtdPenerima', 'zTtdPenerima', true);

// Rupiah format
document.querySelectorAll('[data-rupiah]').forEach(function(el) {
    el.addEventListener('input', function() {
        var raw = this.value.replace(/\D/g, '');
        this.value = raw ? Number(raw).toLocaleString('id-ID') : '';
    });
    el.addEventListener('focus', function() { this.select(); });
});

// Prevent double submit
document.getElementById('formSurat').addEventListener('submit', function() {
    var btn = document.getElementById('btnSubmit');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Menyimpan...';
    }
});
</script>

<?php require 'includes/footer.php'; ?>