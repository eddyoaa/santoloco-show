<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
start_session();

// Seite => [Datei in pages/, benötigte Rolle]
$routes = [
    'login' => ['login', null],
    'logout' => ['logout', 'user'],
    'dashboard' => ['dashboard', 'user'],
    'me/dashboard' => ['me_dashboard', 'user'],
    'me/shifts' => ['me_shifts', 'user'],
    'me/times' => ['me_times', 'user'],
    'me/profile' => ['me_profile', 'user'],
    'admin/home' => ['admin_home', 'admin'],
    'admin/staff' => ['admin_staff', 'admin'],
    'admin/staff_edit' => ['admin_staff_edit', 'admin'],
    'admin/chips' => ['admin_chips', 'admin'],
    'admin/shifts' => ['admin_shifts', 'admin'],
    'admin/stamps' => ['admin_stamps', 'admin'],
    'admin/reports' => ['admin_reports', 'admin'],
    'admin/log' => ['admin_log', 'admin'],
];

$page = (string) ($_GET['p'] ?? 'dashboard');
if (!isset($routes[$page])) {
    redirect('dashboard');
}
[$file, $role] = $routes[$page];

if ($role === 'user') {
    require_login();
} elseif ($role === 'admin') {
    require_admin();
}
if (is_post()) {
    csrf_check();
}

$GLOBALS['current_page'] = $page;
require APP_ROOT . "/pages/$file.php";
