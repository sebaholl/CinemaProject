<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = Database::getInstance()->getConnection();
$auth = new Auth($pdo);
$adminUser = $auth->currentUser();

if ($adminUser === null) {
    header(
        'Location: ' . url(
            'login.php?return=' . rawurlencode(url('admin/index.php'))
        ),
        true,
        302
    );
    exit;
}

if ($adminUser['role'] !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

header('Cache-Control: private, no-store');

// Reuse these in later admin forms after a successful POST.
function admin_flash(string $message): void
{
    $_SESSION['admin_flash'] = $message;
}

function admin_take_flash(): ?string
{
    $message = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    return is_string($message) ? $message : null;
}