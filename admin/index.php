<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$dashboard = new AdminDashboardRepository($pdo);
$statistics = $dashboard->statistics();
$recentBookings = $dashboard->recentBookings();

$flash = admin_take_flash();
$pageTitle = 'Admin dashboard';
$adminPage = 'dashboard';

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/dashboard.php';
require __DIR__ . '/../templates/admin/footer.php';