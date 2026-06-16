<?php
/**
 * SISKA BCA - Audit Log Viewer
 */
require 'config/db.php';
requireAdmin();

$pageTitle  = 'Log Aktivitas';
$activeMenu = 'audit';

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filter
$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($filterUser !== '') {
    $where[] = "(u.username LIKE ? OR u.nama_lengkap LIKE ?)";
    $params[] = "%$filterUser%";
    $params[] = "%$filterUser%";
}

if ($filterAction !== '') {
    $where[] = "al.aksi = ?";
    $params[] = $filterAction;
}

if ($dateFrom !== '') {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total records
try {
    $countSql = "SELECT COUNT(*) FROM audit_log al LEFT JOIN users u ON al.user_id = u.id $whereSql";
    $totalStmt = $pdo->prepare($countSql);
    $totalStmt->execute($params);
    $totalRecords = (int) $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
} catch (PDOException $e) {
    error_log("Error counting audit logs: " . $e->getMessage());
    $totalRecords = 0;
    $totalPages = 0;
}

// Get records
try {
    $sql = "
        SELECT al.*, u.nama_lengkap, u.username 
        FROM audit_log al 
        LEFT JOIN users u ON al.user_id = u.id 
        $whereSql 
        ORDER BY al.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    // Gabungkan semua parameter
    $allParams = array_merge($params, [$perPage, $offset]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($allParams);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: cek jika tidak ada data
    if (empty($logs) && $totalRecords > 0) {
        error_log("Audit log mismatch: Total=$totalRecords but no data fetched");
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($allParams, true));
    }
} catch (PDOException $e) {
    error_log("Error fetching audit logs: " . $e->getMessage());
    $logs = [];
}

// ─── Helper: Konversi nama aksi ke label yang readable ──────────────────
function getActionLabel(string $aksi): string {
    return match($aksi) {
        'login'           => 'Login',
        'logout'          => 'Logout',
        'login_gagal'     => 'Login Gagal',
        'tambah'          => 'Tambah Surat Kuasa',
        'edit'            => 'Edit Surat Kuasa',
        'hapus'           => 'Hapus Surat Kuasa',
        'tambah_user'     => 'Tambah User',
        'hapus_user'      => 'Hapus User',
        'edit_user'       => 'Edit User',
        'ubah_password'   => 'Ubah Password',
        'export_data'     => 'Export Data',
        'cetak'           => 'Cetak Dokumen',
        'upload'          => 'Upload File',
        default           => ucwords(str_replace('_', ' ', $aksi)),
    };
}

/**
 * Get badge class untuk aksi
 */
function getActionBadgeClass(string $aksi): string {
    return match($aksi) {
        'login'           => 'badge-status status-active',
        'logout'          => 'badge-status status-inactive',
        'login_gagal'     => 'badge-status status-expired',
        'tambah', 'tambah_user' => 'badge-jenis badge-setoran',
        'edit', 'edit_user'     => 'badge-jenis badge-tarikan',
        'hapus', 'hapus_user'   => 'badge-status status-expired',
        default           => 'badge-status',
    };
}

/**
 * Get icon untuk aksi
 */
function getActionIcon(string $aksi): string {
    return match($aksi) {
        'login'           => 'bi bi-box-arrow-in-right',
        'logout'          => 'bi bi-box-arrow-right',
        'login_gagal'     => 'bi bi-shield-exclamation',
        'tambah', 'tambah_user' => 'bi bi-plus-circle',
        'edit', 'edit_user'     => 'bi bi-pencil',
        'hapus', 'hapus_user'   => 'bi bi-trash',
        'ubah_password'   => 'bi bi-key',
        'export_data'     => 'bi bi-download',
        'cetak'           => 'bi bi-printer',
        'upload'          => 'bi bi-upload',
        default           => 'bi bi-activity',
    };
}

require 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Log Aktivitas</h1>
        <p>Catatan seluruh aktivitas dalam sistem</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn-ghost">
            <i class="bi bi-printer"></i> Cetak
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card-siska mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Cari User</label>
                <input type="text" name="user" class="form-control" 
                       value="<?= e($filterUser) ?>" 
                       placeholder="Username atau nama...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Jenis Aksi</label>
                <select name="action" class="form-select">
                    <option value="">Semua Aksi</option>
                    <option value="login" <?= $filterAction === 'login' ? 'selected' : '' ?>>Login</option>
                    <option value="logout" <?= $filterAction === 'logout' ? 'selected' : '' ?>>Logout</option>
                    <option value="login_gagal" <?= $filterAction === 'login_gagal' ? 'selected' : '' ?>>Login Gagal</option>
                    <option value="tambah" <?= $filterAction === 'tambah' ? 'selected' : '' ?>>Tambah Surat Kuasa</option>
                    <option value="edit" <?= $filterAction === 'edit' ? 'selected' : '' ?>>Edit Surat Kuasa</option>
                    <option value="hapus" <?= $filterAction === 'hapus' ? 'selected' : '' ?>>Hapus Surat Kuasa</option>
                    <option value="tambah_user" <?= $filterAction === 'tambah_user' ? 'selected' : '' ?>>Tambah User</option>
                    <option value="hapus_user" <?= $filterAction === 'hapus_user' ? 'selected' : '' ?>>Hapus User</option>
                    <option value="edit_user" <?= $filterAction === 'edit_user' ? 'selected' : '' ?>>Edit User</option>
                    <option value="ubah_password" <?= $filterAction === 'ubah_password' ? 'selected' : '' ?>>Ubah Password</option>
                    <option value="export_data" <?= $filterAction === 'export_data' ? 'selected' : '' ?>>Export Data</option>
                    <option value="cetak" <?= $filterAction === 'cetak' ? 'selected' : '' ?>>Cetak Dokumen</option>
                    <option value="upload" <?= $filterAction === 'upload' ? 'selected' : '' ?>>Upload File</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-bca">
                    <i class="bi bi-funnel-fill"></i> Filter
                </button>
                <?php if ($filterUser || $filterAction || $dateFrom || $dateTo): ?>
                <a href="audit_log.php" class="btn-ghost">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Info Ringkasan -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-journal-text"></i></div>
        <div>
            <div class="stat-label">Total Aktivitas</div>
            <div class="stat-value"><?= number_format($totalRecords) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-box-arrow-in-right"></i></div>
        <div>
            <div class="stat-label">Login Hari Ini</div>
            <div class="stat-value">
                <?php
                try {
                    $stmtLogin = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE aksi = 'login' AND DATE(created_at) = CURDATE()");
                    $stmtLogin->execute();
                    echo number_format((int) $stmtLogin->fetchColumn());
                } catch (Exception $e) {
                    echo '0';
                }
                ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="bi bi-plus-circle"></i></div>
        <div>
            <div class="stat-label">Surat Dibuat Hari Ini</div>
            <div class="stat-value">
                <?php
                try {
                    $stmtTambah = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE aksi = 'tambah' AND DATE(created_at) = CURDATE()");
                    $stmtTambah->execute();
                    echo number_format((int) $stmtTambah->fetchColumn());
                } catch (Exception $e) {
                    echo '0';
                }
                ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="bi bi-people"></i></div>
        <div>
            <div class="stat-label">User Aktif Hari Ini</div>
            <div class="stat-value">
                <?php
                try {
                    $stmtUser = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM audit_log WHERE DATE(created_at) = CURDATE() AND user_id IS NOT NULL");
                    $stmtUser->execute();
                    echo number_format((int) $stmtUser->fetchColumn());
                } catch (Exception $e) {
                    echo '0';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Log Table -->
<div class="card-siska">
    <div class="card-header">
        <h5><i class="bi bi-journal-text"></i> Daftar Aktivitas</h5>
        <span class="text-muted small">
            Menampilkan <?= count($logs) ?> dari <?= number_format($totalRecords) ?> records
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-siska">
                <thead>
                    <tr>
                        <th style="width: 160px;">Waktu</th>
                        <th>User</th>
                        <th style="width: 180px;">Aksi</th>
                        <th>Target</th>
                        <th>Keterangan</th>
                        <th style="width: 130px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem;">
                            <div class="empty-state">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <h4>Tidak ada data log</h4>
                                <p>
                                    <?php if ($filterUser || $filterAction || $dateFrom || $dateTo): ?>
                                    Tidak ada aktivitas yang sesuai dengan filter. 
                                    <a href="audit_log.php" class="text-decoration-none">Reset filter</a>
                                    <?php else: ?>
                                    Sistem akan mencatat aktivitas secara otomatis.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space: nowrap;">
                            <div style="font-weight: 500;">
                                <?= date('d/m/Y', strtotime($log['created_at'])) ?>
                            </div>
                            <small class="text-muted">
                                <?= date('H:i:s', strtotime($log['created_at'])) ?> WIB
                            </small>
                        </td>
                        <td>
                            <?php if (!empty($log['username'])): ?>
                            <div style="display: flex; align-items: center; gap: .5rem;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--bca-blue); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700;">
                                    <?= strtoupper(substr($log['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600;"><?= e($log['username']) ?></div>
                                    <?php if (!empty($log['nama_lengkap'])): ?>
                                    <small class="text-muted"><?= e($log['nama_lengkap']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div style="display: flex; align-items: center; gap: .5rem;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--gray-300); color: var(--gray-500); display: flex; align-items: center; justify-content: center; font-size: .7rem;">
                                    <i class="bi bi-gear"></i>
                                </div>
                                <span class="text-muted">System</span>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $label = getActionLabel($log['aksi']);
                            $badgeClass = getActionBadgeClass($log['aksi']);
                            $icon = getActionIcon($log['aksi']);
                            ?>
                            <span class="<?= $badgeClass ?>" style="display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap;">
                                <i class="<?= $icon ?>"></i>
                                <?= e($label) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($log['tabel']): ?>
                            <span class="text-muted small" style="text-transform: uppercase; letter-spacing: .5px;">
                                <?= e($log['tabel']) ?>
                            </span>
                            <?php if ($log['record_id']): ?>
                            <span class="badge bg-light text-dark ms-1" style="font-family: 'DM Mono', monospace; font-size: .7rem;">
                                #<?= $log['record_id'] ?>
                            </span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= e($log['keterangan'] ?? '—') ?></small>
                            <?php if (!empty($log['data_lama']) || !empty($log['data_baru'])): ?>
                            <br>
                            <button class="btn btn-sm btn-link p-0 text-decoration-none" 
                                    onclick="viewDetail(<?= htmlspecialchars(json_encode($log, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                    style="font-size: .7rem;">
                                <i class="bi bi-eye"></i> Lihat Detail
                            </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code style="font-size: .75rem; background: var(--gray-100); padding: .15rem .4rem; border-radius: 4px;">
                                <?= e($log['ip_address'] ?? '—') ?>
                            </code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php 
        $queryParams = $_GET;
        unset($queryParams['page']);
        $baseQuery = http_build_query($queryParams);
        $baseUrl = 'audit_log.php' . ($baseQuery ? '?' . $baseQuery . '&' : '?');
        
        // Previous
        $prevDisabled = $page <= 1;
        ?>
        <li class="page-item <?= $prevDisabled ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $prevDisabled ? '#' : $baseUrl . 'page=' . ($page - 1) ?>" 
               tabindex="<?= $prevDisabled ? '-1' : '' ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        
        <?php
        // Tentukan range halaman yang ditampilkan
        $range = 2;
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);
        
        // First page
        if ($start > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= $baseUrl ?>page=1">1</a>
        </li>
        <?php if ($start > 2): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= $baseUrl ?>page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        
        <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item">
            <a class="page-link" href="<?= $baseUrl ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
        </li>
        <?php endif; ?>
        
        <?php
        // Next
        $nextDisabled = $page >= $totalPages;
        ?>
        <li class="page-item <?= $nextDisabled ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $nextDisabled ? '#' : $baseUrl . 'page=' . ($page + 1) ?>"
               tabindex="<?= $nextDisabled ? '-1' : '' ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php 
$extraScripts = <<<JS
<script>
// View detail log (data lama & baru)
function viewDetail(log) {
    let html = '<div class="text-start">';
    
    html += '<h6 class="mb-3">Detail Log Aktivitas</h6>';
    
    html += '<table class="table table-sm table-bordered">';
    html += '<tr><th style="width: 120px;">Waktu</th><td>' + formatDateTime(log.created_at) + '</td></tr>';
    html += '<tr><th>User</th><td>' + (log.username || 'System') + '</td></tr>';
    html += '<tr><th>Aksi</th><td>' + getActionLabel(log.aksi) + '</td></tr>';
    html += '<tr><th>Tabel</th><td>' + (log.tabel || '-') + '</td></tr>';
    html += '<tr><th>Record ID</th><td>' + (log.record_id || '-') + '</td></tr>';
    html += '<tr><th>IP Address</th><td>' + (log.ip_address || '-') + '</td></tr>';
    html += '</table>';
    
    if (log.keterangan) {
        html += '<h6 class="mt-3">Keterangan</h6>';
        html += '<p class="text-muted">' + escapeHtml(log.keterangan) + '</p>';
    }
    
    if (log.data_lama) {
        html += '<h6 class="mt-3">Data Lama</h6>';
        html += '<pre class="bg-light p-2 rounded" style="font-size: .75rem; max-height: 200px; overflow-y: auto;">' + 
                JSON.stringify(JSON.parse(log.data_lama), null, 2) + '</pre>';
    }
    
    if (log.data_baru) {
        html += '<h6 class="mt-3">Data Baru</h6>';
        html += '<pre class="bg-light p-2 rounded" style="font-size: .75rem; max-height: 200px; overflow-y: auto;">' + 
                JSON.stringify(JSON.parse(log.data_baru), null, 2) + '</pre>';
    }
    
    html += '</div>';
    
    Swal.fire({
        title: 'Detail Log',
        html: html,
        width: '700px',
        showCloseButton: true,
        showConfirmButton: false,
        padding: '1.5rem',
    });
}

// Format datetime
function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + 
           ', ' + String(d.getHours()).padStart(2, '0') + ':' + 
           String(d.getMinutes()).padStart(2, '0') + ' WIB';
}

// Get action label
function getActionLabel(aksi) {
    const labels = {
        'login': 'Login',
        'logout': 'Logout',
        'login_gagal': 'Login Gagal',
        'tambah': 'Tambah Surat Kuasa',
        'edit': 'Edit Surat Kuasa',
        'hapus': 'Hapus Surat Kuasa',
        'tambah_user': 'Tambah User',
        'hapus_user': 'Hapus User',
        'edit_user': 'Edit User',
        'ubah_password': 'Ubah Password',
        'export_data': 'Export Data',
        'cetak': 'Cetak Dokumen',
        'upload': 'Upload File',
    };
    return labels[aksi] || aksi.replace(/_/g, ' ').replace(/\\b\\w/g, l => l.toUpperCase());
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}
</script>
JS;

require 'includes/footer.php'; 
?>