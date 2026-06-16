<?php
/**
 * SISKA BCA - Header Template
 * Include di setiap halaman setelah logika PHP
 *
 * Variabel yang diharapkan ada:
 *   $pageTitle   - Judul halaman (wajib)
 *   $activeMenu  - Nama menu aktif
 *   $extraHead   - Additional head content (optional)
 */
$user        = currentUser();
$pageTitle   = $pageTitle   ?? APP_NAME;
$activeMenu  = $activeMenu  ?? '';
$userInitial = strtoupper(substr($user['username'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_NAME ?> - Sistem Informasi Surat Kuasa <?= APP_CABANG ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?> <?= APP_CABANG ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- SISKA Custom CSS -->
    <link href="assets/css/style.css?v=<?= APP_VERSION ?>" rel="stylesheet">

    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>

<!-- ─── Sidebar ─────────────────────────────────────────────────────────── -->
<aside class="siska-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="assets/img/logo-bca.png" alt="BCA" height="32">
            <div class="sidebar-brand">
                <span class="brand-name">SISKA</span>
                <span class="brand-sub"><?= APP_CABANG ?></span>
            </div>
        </div>
        <button class="sidebar-toggle-btn d-lg-none" id="sidebarClose" aria-label="Tutup sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu Utama</div>
        <a href="index.php" class="nav-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <?php if (isAdmin()): ?>
        <a href="tambah.php" class="nav-item <?= $activeMenu === 'tambah' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-plus-fill"></i>
            <span>Input Surat Kuasa</span>
        </a>

        <div class="nav-section-label">Administrasi</div>
        <a href="users.php" class="nav-item <?= $activeMenu === 'users' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Kelola Pengguna</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-avatar"><?= $userInitial ?></div>
            <div class="user-meta">
                <span class="user-name"><?= e($user['nama'] ?: $user['username']) ?></span>
                <span class="user-role"><?= $user['role'] === 'admin' ? 'Administrator' : 'Teller' ?></span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout" title="Keluar">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</aside>

<!-- ─── Overlay (mobile) ─────────────────────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ─── Main Content Wrapper ─────────────────────────────────────────────── -->
<div class="siska-main" id="mainContent">

    <!-- Topbar -->
    <header class="siska-topbar">
        <button class="topbar-toggle d-lg-none" id="sidebarOpen" aria-label="Buka sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="topbar-breadcrumb">
            <i class="bi bi-chevron-right text-muted"></i>
            <?= e($pageTitle) ?>
        </div>
        <div class="topbar-right">
            <span class="topbar-date">
                <i class="bi bi-calendar3 me-1"></i>
                <?= date('d M Y') ?>
            </span>
        </div>
    </header>

    <!-- Page Content -->
    <main class="siska-content">