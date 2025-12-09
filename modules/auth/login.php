<?php
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/modules/dashboard/');
    exit;
}

$error = '';


if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Username dan password harus diisi';
        $_SESSION['login_username'] = $username;
        header('Location: ' . APP_URL . '/modules/auth/login.php');
        exit;
    } elseif (login($username, $password)) {
        header('Location: ' . APP_URL . '/modules/dashboard/');
        exit;
    } else {
        $_SESSION['login_error'] = 'Username atau password salah';
        $_SESSION['login_username'] = $username;
        header('Location: ' . APP_URL . '/modules/auth/login.php');
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
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
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
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-chart-line"></i>
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
                <p>&copy; <?php echo date('Y'); ?> Finacore v1.0.0</p>
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
