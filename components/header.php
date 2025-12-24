<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/functions.php';

$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentModule = '';
$pathParts = explode('/', $_SERVER['PHP_SELF']);
foreach ($pathParts as $part) {
    if (in_array($part, ['dashboard', 'users', 'accounts', 'journal', 'cash', 'reports', 'logs'])) {
        $currentModule = $part;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Finacore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo APP_URL; ?>/assets/css/style.css?v=1.0.1">
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script> 
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isMobile = window.innerWidth <= 1024;

            if (!isMobile) {
                const lenis = new Lenis({
                    duration: 1.2,
                    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
                    direction: 'vertical',
                    gestureDirection: 'vertical',
                    smooth: true,
                    mouseMultiplier: 1,
                    smoothTouch: false,
                    touchMultiplier: 2,
                });

                function raf(time) {
                    lenis.raf(time);
                    requestAnimationFrame(raf);
                }

                requestAnimationFrame(raf);
            }

            const sidebarWrapper = document.getElementById('sidebarNav');
            const sidebarContent = document.querySelector('.sidebar-nav-content');

            if (sidebarWrapper && sidebarContent) {
                const sidebarLenis = new Lenis({
                    wrapper: sidebarWrapper,
                    content: sidebarContent,
                    duration: 1.2,
                    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
                    direction: 'vertical',
                    gestureDirection: 'vertical',
                    smooth: true,
                    mouseMultiplier: 1,
                    smoothTouch: false,
                    touchMultiplier: 2,
                });

                function rafSidebar(time) {
                    sidebarLenis.raf(time);
                    requestAnimationFrame(rafSidebar);
                }

                requestAnimationFrame(rafSidebar);
            }
        });
    </script>
</head>
<body class="preload">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            if (window.innerWidth > 1024 && localStorage.getItem('sidebarCollapsed') === 'true') {
                document.documentElement.classList.add('sidebar-is-collapsed');
            }

            document.body.classList.remove('preload');
        })();
    </script>
    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="sidebar-brand-text">Finacore</span>
            </div>

            <nav class="sidebar-nav" id="sidebarNav" data-lenis-prevent>
                <div class="sidebar-nav-content">
                    <div class="nav-section">
                        <div class="nav-section-title">Menu Utama</div>
                        <a href="<?php echo APP_URL; ?>/dashboard" class="nav-item <?php echo $currentModule === 'dashboard' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-home"></i></span>
                            <span class="nav-item-text">Dashboard</span>
                        </a>
                    </div>

                    <?php if (hasPermission('accounts_view')): ?>
                    <div class="nav-section">
                        <div class="nav-section-title">Master Data</div>
                        <a href="<?php echo APP_URL; ?>/accounts" class="nav-item <?php echo $currentModule === 'accounts' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-list-alt"></i></span>
                            <span class="nav-item-text">Chart of Accounts</span>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasAnyPermission(['journal_view', 'cash_view'])): ?>
                    <div class="nav-section">
                        <div class="nav-section-title">Transaksi</div>
                        <?php if (hasPermission('journal_view')): ?>
                        <a href="<?php echo APP_URL; ?>/journal" class="nav-item <?php echo $currentModule === 'journal' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-book"></i></span>
                            <span class="nav-item-text">Jurnal Umum</span>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('cash_view')): ?>
                        <a href="<?php echo APP_URL; ?>/cash" class="nav-item <?php echo $currentModule === 'cash' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-money-bill-wave"></i></span>
                            <span class="nav-item-text">Kas Masuk/Keluar</span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (hasPermission('reports_view')): ?>
                    <div class="nav-section">
                        <div class="nav-section-title">Laporan</div>
                        <a href="<?php echo APP_URL; ?>/reports/journal" class="nav-item <?php echo $currentPage === 'journal' && $currentModule === 'reports' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-file-alt"></i></span>
                            <span class="nav-item-text">Laporan Jurnal</span>
                        </a>
                        <a href="<?php echo APP_URL; ?>/reports/ledger" class="nav-item <?php echo $currentPage === 'ledger' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-book-open"></i></span>
                            <span class="nav-item-text">Buku Besar</span>
                        </a>
                        <a href="<?php echo APP_URL; ?>/reports/trial-balance" class="nav-item <?php echo $currentPage === 'trial_balance' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-balance-scale"></i></span>
                            <span class="nav-item-text">Neraca Saldo</span>
                        </a>
                        <a href="<?php echo APP_URL; ?>/reports/cash-flow" class="nav-item <?php echo $currentPage === 'cash_flow' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-exchange-alt"></i></span>
                            <span class="nav-item-text">Arus Kas</span>
                        </a>
                        <a href="<?php echo APP_URL; ?>/reports/income-expense" class="nav-item <?php echo $currentPage === 'income_expense' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-chart-pie"></i></span>
                            <span class="nav-item-text">Pendapatan & Beban</span>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasAnyPermission(['users_view', 'logs_view'])): ?>
                    <div class="nav-section">
                        <div class="nav-section-title">Pengaturan</div>
                        <?php if (hasPermission('users_view')): ?>
                        <a href="<?php echo APP_URL; ?>/users" class="nav-item <?php echo $currentModule === 'users' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-users"></i></span>
                            <span class="nav-item-text">Kelola Pengguna</span>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('logs_view')): ?>
                        <a href="<?php echo APP_URL; ?>/logs" class="nav-item <?php echo $currentModule === 'logs' ? 'active' : ''; ?>">
                            <span class="nav-item-icon"><i class="fas fa-history"></i></span>
                            <span class="nav-item-text">Activity Log</span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($user['role_name']); ?></div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button class="header-btn" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="page-title"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                        <?php if (isset($breadcrumb)): ?>
                        <div class="breadcrumb">
                            <a href="<?php echo APP_URL; ?>/dashboard">Home</a>
                            <?php foreach ($breadcrumb as $item): ?>
                            <span class="breadcrumb-separator">/</span>
                            <?php if (isset($item['url'])): ?>
                            <a href="<?php echo $item['url']; ?>"><?php echo $item['title']; ?></a>
                            <?php else: ?>
                            <span><?php echo $item['title']; ?></span>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="header-right">
                    <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                        <i class="fas fa-sun icon-sun"></i>
                        <i class="fas fa-moon icon-moon"></i>
                    </button>
                    <button class="header-btn" title="Notifikasi">
                        <i class="fas fa-bell"></i>
                    </button>
                    <a href="<?php echo APP_URL; ?>/logout" class="header-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <?php 
                $flash = getFlash();
                if ($flash): 
                ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showToast('<?php echo addslashes($flash['message']); ?>', '<?php echo $flash['type'] === 'danger' ? 'error' : $flash['type']; ?>');
                    });
                </script>
                <?php endif; ?>
