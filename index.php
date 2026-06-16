<?php
require 'config/db.php';
requireLogin();

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

// ─── Query statistik ───────────────────────────────────────────────────────
$statsAll    = (int) $pdo->query("SELECT COUNT(*) FROM surat_kuasa")->fetchColumn();
$statsAktif  = (int) $pdo->query("SELECT COUNT(*) FROM surat_kuasa WHERE status = 'aktif'")->fetchColumn();
$statsBulan  = (int) $pdo->query("SELECT COUNT(*) FROM surat_kuasa WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();
$totalUsers  = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// ─── Query data utama + pencarian ─────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? ''; // 'setoran' | 'tarikan' | ''

try {
    $rows = getSuratKuasa($pdo, $search, $filter);
} catch (PDOException $e) {
    error_log('[SISKA] Dashboard query error: ' . $e->getMessage());
    $rows = [];
}

// ─── Render ─────────────────────────────────────────────────────────────────
require 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1>Dashboard Surat Kuasa</h1>
        <p>Monitoring dan pengelolaan otorisasi nasabah secara real-time</p>
    </div>
    <?php if (isAdmin()): ?>
    <a href="tambah.php" class="btn-bca">
        <i class="bi bi-plus-lg"></i> Input Surat Baru
    </a>
    <?php endif; ?>
</div>

<!-- Statistik -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div>
            <div class="stat-label">Total Surat</div>
            <div class="stat-value"><?= $statsAll ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="stat-label">Status Aktif</div>
            <div class="stat-value"><?= $statsAktif ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="bi bi-calendar-event-fill"></i></div>
        <div>
            <div class="stat-label">Bulan Ini</div>
            <div class="stat-value"><?= $statsBulan ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="stat-label">Pengguna</div>
            <div class="stat-value"><?= $totalUsers ?></div>
        </div>
    </div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <form class="search-wrap" method="GET" style="flex:1;">
        <?php if ($filter): ?>
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <?php endif; ?>
        <i class="bi bi-search"></i>
        <input
            type="text"
            name="q"
            class="search-input"
            placeholder="Cari no. rekening, pemilik, penerima..."
            value="<?= e($search) ?>"
        >
    </form>

    <!-- Filter Jenis -->
    <div style="display:flex;gap:.4rem;flex-shrink:0;">
        <a href="index.php<?= $search ? '?q='.urlencode($search) : '' ?>"
           class="btn-ghost <?= $filter === '' ? 'active' : '' ?>">
            Semua
        </a>
        <a href="index.php?filter=setoran<?= $search ? '&q='.urlencode($search) : '' ?>"
           class="btn-ghost"
           style="<?= $filter === 'setoran' ? 'background:#DCFCE7;color:#15803D;border-color:#BBF7D0;' : '' ?>">
            Setoran
        </a>
        <a href="index.php?filter=tarikan<?= $search ? '&q='.urlencode($search) : '' ?>"
           class="btn-ghost"
           style="<?= $filter === 'tarikan' ? 'background:#DBEAFE;color:#1D4ED8;border-color:#BFDBFE;' : '' ?>">
            Tarikan
        </a>
    </div>

    <?php if ($search || $filter): ?>
    <a href="index.php" class="btn-ghost">
        <i class="bi bi-x"></i> Reset
    </a>
    <?php endif; ?>
</div>

<!-- Jumlah hasil -->
<?php if ($search || $filter): ?>
<p style="font-size:.82rem;color:var(--gray-500);margin-bottom:1rem;">
    Menampilkan <strong><?= count($rows) ?></strong> hasil
    <?= $search ? 'untuk "<strong>' . e($search) . '</strong>"' : '' ?>
</p>
<?php endif; ?>

<!-- Grid Surat Kuasa -->
<?php if (empty($rows)): ?>
<div class="empty-state">
    <i class="bi bi-folder-x"></i>
    <h4>
        <?= $search ? 'Data tidak ditemukan' : 'Belum ada surat kuasa' ?>
    </h4>
    <p>
        <?= $search
            ? 'Coba kata kunci lain atau reset filter.'
            : (isAdmin() ? 'Klik "Input Surat Baru" untuk menambahkan data pertama.' : 'Hubungi administrator untuk menambah data.') ?>
    </p>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;">
    <?php foreach ($rows as $row): ?>
    <div class="sk-card">
        <div class="sk-card-body">
            <!-- Head -->
            <div class="sk-card-head">
                <span class="badge-jenis <?= strtolower($row['jenis_kuasa']) ?>">
                    <?= $row['jenis_kuasa'] === 'SETORAN'
                        ? '<i class="bi bi-arrow-down-circle"></i> SETORAN'
                        : '<i class="bi bi-arrow-up-circle"></i> TARIKAN' ?>
                </span>
                <span class="badge-status <?= $row['status'] ?>">
                    <i class="bi bi-circle-fill" style="font-size:.45rem;"></i>
                    <?= strtoupper($row['status']) ?>
                </span>
            </div>

            <!-- Penerima -->
            <div style="margin-bottom:.75rem;">
                <div class="sk-meta-label">Penerima Kuasa</div>
                <div style="font-size:1rem;font-weight:700;color:var(--gray-900);">
                    <?= e($row['nama_penerima']) ?>
                </div>
                <?php if ($row['jabatan_penerima']): ?>
                <div style="font-size:.78rem;color:var(--gray-500);"><?= e($row['jabatan_penerima']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Info Box -->
            <div class="sk-card-info">
                <div class="sk-card-info-row">
                    <div>
                        <div class="sk-meta-label">Pemilik Rekening</div>
                        <div class="sk-meta-value"><?= e($row['nama_pemilik']) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div class="sk-meta-label">No. Rekening</div>
                        <div class="sk-meta-value" style="font-family:'DM Mono',monospace;"><?= e($row['nomor_rekening']) ?></div>
                    </div>
                </div>
                <div class="sk-card-info-row">
                    <span class="sk-meta-label">Limit Transaksi</span>
                    <span class="sk-limit"><?= formatRupiah($row['limit_transaksi']) ?></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="sk-card-actions">
                <a href="detail.php?id=<?= $row['id'] ?>" class="btn-bca-outline" style="flex:1;justify-content:center;padding:.45rem .75rem;font-size:.82rem;">
                    <i class="bi bi-eye"></i> Detail
                </a>
                <?php if (isAdmin()): ?>
                <a href="tambah.php?edit=<?= $row['id'] ?>" class="btn-ghost" style="padding:.45rem .75rem;font-size:.82rem;">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="javascript:void(0)" onclick="confirmDelete(<?= $row['id'] ?>, '<?= e($row['nama_pemilik']) ?>')"
                   class="btn-danger-ghost" style="padding:.45rem .75rem;font-size:.82rem;">
                    <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
            </div>

            <!-- Tanggal -->
            <div style="margin-top:.75rem;font-size:.72rem;color:var(--gray-400);">
                <i class="bi bi-clock me-1"></i><?= formatTanggal($row['created_at']) ?>
                <?php if ($row['nomor_surat']): ?>
                &bull; <?= e($row['nomor_surat']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$extraScripts = <<<'JS'
<script>
function confirmDelete(id, nama) {
    if (confirm('Hapus surat kuasa atas nama "' + nama + '"?')) {
        window.location.href = 'hapus.php?id=' + id;
    }
}
</script>
JS;

require 'includes/footer.php';
?>