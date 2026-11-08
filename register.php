<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = Database::getInstance()->getConnection();
$auth = new Auth($pdo);

$loggedInUser = $auth->currentUser();
if ($loggedInUser !== null) {
    $destination = $loggedInUser['role'] === 'customer'
        ? 'profile.php'
        : 'index.php';

    header('Location: ' . url($destination), true, 303);
    exit;
}

$users = new UserRepository($pdo);
$error = null;
$success = !empty($_SESSION['registration_complete']);
unset($_SESSION['registration_complete']);

$firstName = '';
$lastName = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(403);
        $error = 'The form expired. Refresh the page and try again.';
    } else {
        $firstName = is_string($_POST['first_name'] ?? null)
            ? trim($_POST['first_name'])
            : '';
        $lastName = is_string($_POST['last_name'] ?? null)
            ? trim($_POST['last_name'])
            : '';
        $email = is_string($_POST['email'] ?? null)
            ? trim($_POST['email'])
            : '';
        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';
        $confirmation = is_string($_POST['confirm_password'] ?? null)
            ? $_POST['confirm_password']
            : '';

        if ($firstName === '' || mb_strlen($firstName) > 100 ||
            $lastName === '' || mb_strlen($lastName) > 100) {
            $error = 'Enter a first and last name, each at most 100 characters.';
        } elseif (strlen($email) > 254 ||
                  filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 12 || strlen($password) > 72) {
            $error = 'Use a password between 12 and 72 bytes.';
        } elseif ($password !== $confirmation) {
            $error = 'The passwords do not match.';
        } elseif ($users->findByEmail($email) !== null) {
            $error = 'That email address is already registered.';
        } else {
            try {
                $users->create(
                    $firstName,
                    $lastName,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT)
                );

                $_SESSION['registration_complete'] = true;
                header('Location: ' . url('register.php'), true, 303);
                exit;
            } catch (PDOException $exception) {
                // The unique email index also handles two simultaneous sign-ups.
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                    throw $exception;
                }

                $error = 'That email address is already registered.';
            }
        }
    }
}

$pageTitle = 'Register';
$currentPage = '';

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/register.php';
require __DIR__ . '/templates/layout/footer.php';