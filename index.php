<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/Router.php';
require_once __DIR__ . '/includes/HashIdHelper.php';

$router = new Router();

$router->get('/login', __DIR__ . '/modules/auth/login.php');
$router->post('/login', __DIR__ . '/modules/auth/login.php');
$router->get('/logout', __DIR__ . '/modules/auth/logout.php');

$router->get('/dashboard', __DIR__ . '/modules/dashboard/index.php');
$router->get('/home', __DIR__ . '/modules/dashboard/index.php');

$router->get('/accounts', __DIR__ . '/modules/accounts/index.php');
$router->get('/accounts/create', __DIR__ . '/modules/accounts/create.php');
$router->post('/accounts/create', __DIR__ . '/modules/accounts/create.php');
$router->get('/accounts/edit', __DIR__ . '/modules/accounts/edit.php');
$router->post('/accounts/edit', __DIR__ . '/modules/accounts/edit.php');
$router->get('/accounts/delete', __DIR__ . '/modules/accounts/delete.php');

$router->get('/users', __DIR__ . '/modules/users/index.php');
$router->get('/users/create', __DIR__ . '/modules/users/create.php');
$router->post('/users/create', __DIR__ . '/modules/users/create.php');
$router->get('/users/edit', __DIR__ . '/modules/users/edit.php');
$router->post('/users/edit', __DIR__ . '/modules/users/edit.php');
$router->get('/users/delete', __DIR__ . '/modules/users/delete.php');

$router->get('/journal', __DIR__ . '/modules/journal/index.php');
$router->get('/journal/create', __DIR__ . '/modules/journal/create.php');
$router->post('/journal/create', __DIR__ . '/modules/journal/create.php');
$router->get('/journal/edit', __DIR__ . '/modules/journal/edit.php');
$router->post('/journal/edit', __DIR__ . '/modules/journal/edit.php');
$router->get('/journal/delete', __DIR__ . '/modules/journal/delete.php');
$router->get('/journal/view', __DIR__ . '/modules/journal/view.php');
$router->get('/journal/approve', __DIR__ . '/modules/journal/approve.php');

$router->get('/cash', __DIR__ . '/modules/cash/index.php');
$router->get('/cash/create', __DIR__ . '/modules/cash/create.php');
$router->post('/cash/create', __DIR__ . '/modules/cash/create.php');
$router->get('/cash/edit', __DIR__ . '/modules/cash/edit.php');
$router->post('/cash/edit', __DIR__ . '/modules/cash/edit.php');
$router->get('/cash/delete', __DIR__ . '/modules/cash/delete.php');

$router->get('/reports/journal', __DIR__ . '/modules/reports/journal.php');
$router->get('/reports/ledger', __DIR__ . '/modules/reports/ledger.php');
$router->get('/reports/trial-balance', __DIR__ . '/modules/reports/trial_balance.php');
$router->get('/reports/cash-flow', __DIR__ . '/modules/reports/cash_flow.php');
$router->get('/reports/income-expense', __DIR__ . '/modules/reports/income_expense.php');

$router->get('/logs', __DIR__ . '/modules/logs/index.php');

$router->get('/backup', __DIR__ . '/modules/backup/index.php');
$router->post('/backup', __DIR__ . '/modules/backup/index.php');

$router->get('/', function() {
    header('Location: ' . APP_URL . '/login');
    exit;
});

$router->dispatch();
