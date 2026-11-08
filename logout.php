<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Logout requires POST.');
}

if (!csrf_check()) {
    http_response_code(403);
    exit('Invalid form token.');
}

$pdo = Database::getInstance()->getConnection();
$auth = new Auth($pdo);
$auth->logout();

header('Location: ' . url('login.php'), true, 303);
exit;