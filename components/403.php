<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | Finacore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container" style="text-align: center;">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo" style="background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);">
                    <i class="fas fa-ban"></i>
                </div>
                <h1 class="login-title">403</h1>
                <p class="login-subtitle">Akses Ditolak</p>
            </div>
            <div class="login-body">
                <p style="color: var(--gray-600); margin-bottom: 24px;">
                    Anda tidak memiliki izin untuk mengakses halaman ini.
                </p>
                <a href="<?php echo APP_URL; ?>/modules/dashboard/" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-home"></i>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
