<?php
require 'config/db.php';
requireAdmin();

$pageTitle  = 'Kelola Pengguna';
$activeMenu = 'users';
$me         = currentUser();
$errors     = [];
$success    = '';

// ─── Pesan dari redirect ─────────────────────────────────────────────────
$statusMsg = $_GET['status'] ?? '';
$msgText = $_GET['msg'] ?? '';

if ($statusMsg === 'success') {
    $success = 'Pengguna baru berhasil ditambahkan.';
} elseif ($statusMsg === 'deleted') {
    $success = $msgText ?: 'Pengguna berhasil dihapus.';
} elseif ($statusMsg === 'error') {
    $errors['_global'] = $msgText ?: 'Terjadi kesalahan.';
}

// ─── Proses POST: Tambah User ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'tambah') {
    $username    = trim($_POST['username'] ?? '');
    $nama        = trim($_POST['nama_lengkap'] ?? '');
    $password    = $_POST['password'] ?? '';
    $role        = $_POST['role'] ?? 'user';
    $status      = $_POST['status'] ?? 'active';

    // Validasi
    if ($username === '') $errors['username'] = 'NIP/Username wajib diisi.';
    if ($password === '') $errors['password'] = 'Password wajib diisi.';
    elseif (strlen($password) < 6) $errors['password'] = 'Password minimal 6 karakter.';
    if (!in_array($role, ['admin', 'user'])) $errors['role'] = 'Pilih role yang valid.';
    if (!in_array($status, ['active', 'inactive'])) $errors['status'] = 'Status tidak valid.';

    if (empty($errors)) {
        try {
            // Cek duplikat
            if (isUsernameExists($pdo, $username)) {
                $errors['username'] = "Username '$username' sudah terdaftar.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, nama_lengkap, password, role, status) VALUES (?,?,?,?,?)"
                );
                $stmt->execute([$username, $nama, $hash, $role, $status]);

                redirect('users.php?status=success');
            }
        } catch (PDOException $e) {
            error_log('[SISKA] Tambah user error: ' . $e->getMessage());
            $errors['_global'] = 'Terjadi kesalahan database. Silakan coba lagi.';
        }
    }
}

// ─── Query users ─────────────────────────────────────────────────────────
$users = getAllUsers($pdo);

require 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Kelola Pengguna</h1>
        <p>Manajemen akses login pegawai kantor cabang</p>
    </div>
    <button class="btn-bca" onclick="openModalTambah()">
        <i class="bi bi-person-plus-fill"></i> Tambah Pengguna
    </button>
</div>

<!-- Alert Messages -->
<?php if ($success): ?>
<div class="alert alert-success alert-auto-hide">
    <i class="bi bi-check-circle-fill"></i> <?= e($success) ?>
</div>
<?php endif; ?>

<?php if (!empty($errors['_global'])): ?>
<div class="alert alert-error">
    <i class="bi bi-exclamation-circle-fill"></i> <?= e($errors['_global']) ?>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="card-siska">
    <div class="card-header">
        <h5><i class="bi bi-people-fill"></i> Daftar Pengguna</h5>
        <span style="font-size:.8rem;color:var(--gray-400);"><?= count($users) ?> pengguna terdaftar</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="table-siska">
                <thead>
                    <tr>
                        <th>Username / NIP</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Login Terakhir</th>
                        <th>Dibuat</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:2rem;color:var(--gray-400);">
                            <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            Belum ada pengguna terdaftar
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.6rem;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--bca-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">
                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-900);"><?= e($u['username']) ?></div>
                                    <?php if ($u['id'] === (int)$me['id']): ?>
                                    <div style="font-size:.65rem;color:var(--success);font-weight:600;">ANDA</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u['nama_lengkap'] ?: '—') ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                            <span style="background:#DBEAFE;color:#1D4ED8;padding:.25rem .6rem;border-radius:4px;font-size:.72rem;font-weight:700;">
                                ADMIN
                            </span>
                            <?php else: ?>
                            <span style="background:var(--gray-100);color:var(--gray-600);padding:.25rem .6rem;border-radius:4px;font-size:.72rem;font-weight:700;">
                                USER
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                            <span class="badge-status status-active"><i class="bi bi-circle-fill" style="font-size:.45rem;"></i> Aktif</span>
                            <?php else: ?>
                            <span class="badge-status status-inactive"><i class="bi bi-circle-fill" style="font-size:.45rem;"></i> Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.8rem;color:var(--gray-500);">
                            <?= $u['last_login'] ? formatTanggal($u['last_login'], true) : '—' ?>
                        </td>
                        <td style="font-size:.8rem;color:var(--gray-500);">
                            <?= formatTanggal($u['created_at']) ?>
                        </td>
                        <td style="text-align:right;">
                            <?php if ($u['id'] !== (int)$me['id']): ?>
                            <a href="javascript:void(0)" onclick="confirmHapusUser(<?= $u['id'] ?>, '<?= e($u['username']) ?>')"
                               class="btn-danger-ghost" title="Hapus Pengguna">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php else: ?>
                            <span style="font-size:.75rem;color:var(--gray-400);" title="Akun Anda">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── Modal Tambah Pengguna ─────────────────────────────────────────── -->
<div id="modalTambahUser" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Tambah Pengguna Baru</h4>
            <button onclick="closeModalTambah()" class="modal-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <?php if (!empty($errors) && ($_POST['_action'] ?? '') === 'tambah'): ?>
        <div class="alert alert-error" style="margin-bottom:1rem;">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= e($errors['_global'] ?? 'Periksa kembali input Anda.') ?>
        </div>
        <script>document.addEventListener('DOMContentLoaded', function() { openModalTambah(); });</script>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="_action" value="tambah">

            <div class="form-group">
                <label class="form-label">NIP / Username <span class="required">*</span></label>
                <input type="text" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                       value="<?= e($_POST['username'] ?? '') ?>" placeholder="Contoh: 6601234" required autocomplete="off">
                <?php if (isset($errors['username'])): ?>
                <div class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?= e($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control"
                       value="<?= e($_POST['nama_lengkap'] ?? '') ?>" placeholder="Nama lengkap pegawai">
            </div>

            <div class="form-group">
                <label class="form-label">Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                       value="" placeholder="Min. 6 karakter" required autocomplete="new-password"
                       id="passwordInput">
                <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><i class="bi bi-exclamation-circle"></i> <?= e($errors['password']) ?></div>
                <?php endif; ?>
                <div style="font-size:.75rem;color:var(--warning);margin-top:.3rem;">
                    <i class="bi bi-exclamation-triangle"></i>
                    Informasikan password ke pengguna agar segera diganti.
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Role / Hak Akses</label>
                    <select name="role" class="form-select">
                        <option value="user" <?= ($_POST['role'] ?? '') === 'user' ? 'selected' : '' ?>>User (Lihat Saja)</option>
                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (Full Akses)</option>
                    </select>
                    <?php if (isset($errors['role'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= e($errors['role']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($_POST['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                    <?php if (isset($errors['status'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= e($errors['status']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;margin-top:1.5rem;justify-content:flex-end;">
                <button type="button" onclick="closeModalTambah()" class="btn-ghost">Batal</button>
                <button type="submit" class="btn-bca">
                    <i class="bi bi-person-check-fill"></i> Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScripts = <<<JS
<script>
// Fungsi konfirmasi hapus user
function confirmHapusUser(id, nama) {
    if (confirm('Hapus pengguna "' + nama + '"? Semua data terkait akan dihapus.')) {
        window.location.href = 'hapus_user.php?id=' + id;
    }
}

// Modal functions
function openModalTambah() {
    document.getElementById('modalTambahUser').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModalTambah() {
    document.getElementById('modalTambahUser').style.display = 'none';
    document.body.style.overflow = '';
}

// Tutup modal jika klik overlay
document.getElementById('modalTambahUser').addEventListener('click', function(e) {
    if (e.target === this) closeModalTambah();
});

// Tutup modal dengan Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModalTambah();
});
</script>
JS;

require 'includes/footer.php';
?>