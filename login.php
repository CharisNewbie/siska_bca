<?php
require 'config/db.php';

// Sudah login? Redirect ke dashboard
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'NIP dan password tidak boleh kosong.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, username, nama_lengkap, password, role, status
                 FROM users WHERE username = ? LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
                // Regenerate session ID untuk keamanan
                session_regenerate_id(true);

                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama_lengkap'] ?? $user['username'];
                $_SESSION['role']     = $user['role'];

                // Update last login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                    ->execute([$user['id']]);

                redirect('index.php');
            } else {
                $error = 'NIP atau password salah, atau akun tidak aktif.';
                // Delay untuk mencegah brute force
                sleep(1);
            }
        } catch (PDOException $e) {
            error_log('[SISKA] Login Error: ' . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Main Styles -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Login Styles -->
    <link href="assets/css/login.css" rel="stylesheet">
</head>
<body>

<div class="login-box">

    <!-- Logo -->
    <div class="login-logo">
        <img src="assets/img/logo-bca-login.png" alt="BCA">
    </div>

    <!-- Header -->
    <div class="login-header">
        <h1><?= APP_NAME ?></h1>
        <p>Sistem Informasi Surat Kuasa</p>
        <span class="badge-cabang"><?= APP_CABANG ?></span>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="alert-error">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= e($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" novalidate>
        <div class="form-group">
            <label class="form-label" for="username">
                NIP<span class="required">*</span>
            </label>
            <div class="input-group">
                <i class="bi bi-person input-icon"></i>
                <input
                    type="text"
                    class="form-control"
                    id="username"
                    name="username"
                    placeholder="Masukkan NIP Anda"
                    value="<?= e($_POST['username'] ?? '') ?>"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">
                Password <span class="required">*</span>
            </label>
            <div class="input-group">
                <i class="bi bi-lock input-icon"></i>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >
                <button type="button" id="togglePass" class="toggle-btn" tabindex="-1">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i>
            Masuk
        </button>
    </form>

    <div class="login-footer">
        <div class="version">
            <span>●</span> <?= APP_NAME ?> v<?= APP_VERSION ?> <span>●</span>
        </div>
    </div>

</div>

<script>
    // Toggle password visibility
    const toggleBtn = document.getElementById('togglePass');
    const passInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const isText = passInput.type === 'text';
            passInput.type = isText ? 'password' : 'text';
            eyeIcon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }

    // Auto-focus username
    document.getElementById('username').focus();
</script>

</body>
</html>