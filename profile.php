<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = Database::getInstance()->getConnection();
$auth = new Auth($pdo);
$auth->requireLogin();

$user = $auth->currentUser();

if ($user['role'] !== 'customer') {
    http_response_code(403);
    exit('Customer account required.');
}

header('Cache-Control: private, no-store');

$profiles = new ProfileRepository($pdo);
$customer = $profiles->findCustomer((int) $user['id']);

if ($customer === null) {
    http_response_code(403);
    exit('Customer account not found.');
}

$error = null;
$notice = $_SESSION['profile_notice'] ?? null;
unset($_SESSION['profile_notice']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(403);
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? null) === 'details') {
        $firstName = is_string($_POST['first_name'] ?? null)
            ? trim($_POST['first_name'])
            : '';
        $lastName = is_string($_POST['last_name'] ?? null)
            ? trim($_POST['last_name'])
            : '';
        $email = is_string($_POST['email'] ?? null)
            ? trim($_POST['email'])
            : '';

        if ($firstName === '' || mb_strlen($firstName) > 100 ||
            $lastName === '' || mb_strlen($lastName) > 100) {
            $error = 'Enter a first and last name, each at most 100 characters.';
        } elseif (strlen($email) > 254 ||
                  filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $error = 'Enter a valid email address.';
        } else {
            try {
                $profiles->updateDetails(
                    (int) $user['id'],
                    $firstName,
                    $lastName,
                    $email
                );

                $_SESSION['auth']['name'] = $firstName . ' ' . $lastName;
                $_SESSION['profile_notice'] = 'Account details updated.';

                header('Location: ' . url('profile.php'), true, 303);
                exit;
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                    throw $exception;
                }

                $error = 'That email address is already in use.';
            }
        }
    } elseif (($_POST['action'] ?? null) === 'password') {
        $current = is_string($_POST['current_password'] ?? null)
            ? $_POST['current_password']
            : '';
        $new = is_string($_POST['new_password'] ?? null)
            ? $_POST['new_password']
            : '';
        $confirmation = is_string($_POST['confirm_password'] ?? null)
            ? $_POST['confirm_password']
            : '';

        $previousHash = $profiles->findPasswordHash((int) $user['id']);

        if ($previousHash === null ||
            !password_verify($current, $previousHash)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 12 || strlen($new) > 72) {
            $error = 'Use a new password between 12 and 72 bytes.';
        } elseif ($new !== $confirmation) {
            $error = 'The new passwords do not match.';
        } elseif ($new === $current) {
            $error = 'Choose a different password.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);

            if (!$profiles->updatePassword(
                (int) $user['id'],
                $previousHash,
                $newHash
            )) {
                $error = 'Password changed elsewhere. Refresh and try again.';
            } else {
                session_regenerate_id(true);
                $_SESSION['profile_notice'] = 'Password updated.';

                header('Location: ' . url('profile.php'), true, 303);
                exit;
            }
        }
    } elseif (($_POST['action'] ?? null) === 'cancel_booking') {
        $bookingId = $_POST['booking_id'] ?? null;

        if (!is_string($bookingId) || !ctype_digit($bookingId)) {
            http_response_code(400);
            exit('Invalid booking.');
        }

        $bookRepo = new BookingRepository($pdo);
        $cancelled = $bookRepo->cancelOwnedBooking(
            (int) $bookingId,
            (int) $user['id']
        );

        if (!$cancelled) {
            http_response_code(403);
            exit('This booking cannot be cancelled.');
        }

        header('Location: ' . url('profile.php'), true, 303);
        exit;
    } else {
        http_response_code(400);
        $error = 'Unknown profile action.';
    }
}

$bookings = $profiles->findBookings((int) $user['id']);
$pageTitle = 'My profile';
$currentPage = 'profile';

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/profile.php';
require __DIR__ . '/templates/layout/footer.php';