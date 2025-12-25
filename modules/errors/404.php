<?php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan - Finacore</title>
    
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css?v=1.0.6">
    
    <link rel="icon" type="image/png" href="<?php echo APP_URL; ?>/assets/images/favicon-32x32.png">
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/images/favicon-16x16.png">

    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-primary);
            padding: 24px;
        }

        .error-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .error-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 32px;
            animation: floatLogo 6s ease-in-out infinite;
        }
        
        .error-code {
            font-size: 80px;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .error-text {
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .error-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .error-bg-shape {
            position: absolute;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            opacity: 0.05;
            border-radius: 50%;
            z-index: 0;
        }

        .shape-1 { width: 300px; height: 300px; top: -100px; right: -100px; }
        .shape-2 { width: 200px; height: 200px; bottom: -50px; left: -50px; }
        
        .error-content {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body data-theme="dark">
    <div class="error-page">
        <div class="error-card">
            <div class="error-bg-shape shape-1"></div>
            <div class="error-bg-shape shape-2"></div>
            
            <div class="error-content">
                <div class="error-logo">
                    <img src="<?php echo APP_URL; ?>/assets/images/main_logo.png" alt="Finacore Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                
                <div class="error-code">404</div>
                <h1 class="error-title">Halaman Tidak Ditemukan</h1>
                <p class="error-text">
                    Maaf, halaman yang Anda cari tidak tersedia. Mungkin URL salah atau halaman sudah dipindahkan.
                </p>
                
                <div class="error-actions">
                    <a href="<?php echo APP_URL; ?>/dashboard" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div style="position: absolute; bottom: 24px; color: var(--text-tertiary); font-size: 13px;">
            &copy; <?php echo date('Y'); ?> Finacore by Keneth Langit Baranduda
        </div>
    </div>
</body>
</html>
