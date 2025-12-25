<?php
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

$error = '';
$lockoutTime = isLoginLocked();

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($lockoutTime) {
        $minutes = ceil($lockoutTime / 60);
        $_SESSION['login_error'] = "Terlalu banyak percobaan gagal. Coba lagi dalam $minutes menit.";
        header('Location: ' . APP_URL . '/login');
        exit;
    }
    
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['login_error'] = 'Invalid security token. Please refresh and try again.';
        header('Location: ' . APP_URL . '/login');
        exit;
    }
    
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Username dan password harus diisi';
        $_SESSION['login_username'] = $username;
        header('Location: ' . APP_URL . '/login');
        exit;
    } elseif (login($username, $password)) {
        header('Location: ' . APP_URL . '/dashboard');
        exit;
    } else {
        $remaining = getRemainingAttempts();
        if ($remaining > 0) {
            $_SESSION['login_error'] = "Username atau password salah. Sisa percobaan: $remaining";
        } else {
            $_SESSION['login_error'] = 'Terlalu banyak percobaan gagal. Coba lagi dalam 5 menit.';
        }
        $_SESSION['login_username'] = $username;
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}

$savedUsername = $_SESSION['login_username'] ?? '';
unset($_SESSION['login_username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finacore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css?v=1.0.5">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo APP_URL; ?>/assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo APP_URL; ?>/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo APP_URL; ?>/assets/images/favicon-16x16.png">
    <link rel="manifest" href="<?php echo APP_URL; ?>/assets/images/site.webmanifest">
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">

    <style>

        html, body {
            height: auto;
            min-height: 100%;
            overflow-y: auto;
        }

        .login-page {
            min-height: 100vh;
        }

        @media (min-width: 769px) and (min-height: 701px) {
            html, body {
                height: 100%;
                overflow: hidden;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 40px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        .fade-in-up {
            animation-name: fadeInUp;
            animation-duration: 0.8s;
            animation-fill-mode: both;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card fade-in-up">
            <div class="login-header">
                <div class="login-logo">
                    <img src="<?php echo APP_URL; ?>/assets/images/main_logo.png" alt="Finacore Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 12px;">
                </div>
                <h1 class="login-title">Finacore</h1>
                <p class="login-subtitle">Sistem Informasi Akuntansi</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="username" class="form-control input-with-icon" 
                                   placeholder="Masukkan username" 
                                   value="<?php echo htmlspecialchars($savedUsername); ?>"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="passwordInput" class="form-control input-with-icon" 
                                   placeholder="Masukkan password"
                                   style="padding-right: 44px;"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Finacore v1.0.1</p>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
