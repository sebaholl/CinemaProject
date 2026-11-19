<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = Database::getInstance()->getConnection();
$auth = new Auth($pdo);

$error = null;
$identifier = '';

$returnValue = $_POST['return'] ?? $_GET['return'] ?? '';
$returnTo = url('profile.php');

if (is_string($returnValue)
    && strlen($returnValue) <= 1024
    && !preg_match('/[\x00-\x1F\x7F\\\\#]/', $returnValue)) {
    $path = explode('?', $returnValue, 2)[0];

    if (in_array($path, [
        url('profile.php'),
        url('invoice.php'),
        url('booking.php'),
        url('admin/index.php'),
    ], true)) {
        $returnTo = $returnValue;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'The form expired. Refresh the page and try again.';
    } else {
        $identifier = is_string($_POST['identifier'] ?? null)
            ? trim($_POST['identifier'])
            : '';
        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';

        if ($auth->login($identifier, $password)) {
            $user = $auth->currentUser();

            if ($user['role'] === 'admin') {
                $destination = url('admin/index.php');
            } elseif (
                explode('?', $returnTo, 2)[0] === url('admin/index.php')
            ) {
                $destination = url('profile.php');
            } else {
                $destination = $returnTo;
            }

            header('Location: ' . $destination, true, 303);
            exit;
        }

        $error = 'Wrong email or password.';
    }
}

$pageTitle = 'Log In';
$currentPage = 'login';

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/login.php';
require __DIR__ . '/templates/layout/footer.php';
