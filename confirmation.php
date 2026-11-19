<?php
declare(strict_types=1);

// Booking confirmation, shown after a successful mock checkout (via a redirect, so
// refreshing it never re-books). Reads the finished booking from the DB and shows
// it as a ticket - the same Ticket object the confirmation email was built from.
//
// findTicket() only returns a booking that belongs to the logged-in customer, so
// changing the id in the URL shows nothing.
require_once __DIR__ . '/includes/bootstrap.php';

$bookingId = isset($_GET['booking']) ? (int) $_GET['booking'] : 0;

$pdo      = Database::getInstance()->getConnection();
$bookRepo = new BookingRepository($pdo);
$auth     = new Auth($pdo);
$auth->requireLogin();
$user = $auth->currentUser();

if ($user === null || $user['role'] !== 'customer') {
    http_response_code(403);
    exit('Customer account required.');
}

$ticket = $bookRepo->findTicket($bookingId, (int) $user['id'], config_value('ticket_secret'));

// checkout.php left the result of the email attempt here. Read it once and clear
// it, so a later visit to this page does not repeat a stale message.
$mailResult = $_SESSION['ticket_mail'] ?? null;
unset($_SESSION['ticket_mail']);

$mailNotice = null;
if (is_array($mailResult)) {
    $mailNotice = !empty($mailResult['sent']) && $mailResult['to'] !== ''
        ? 'A copy has been emailed to ' . $mailResult['to'] . '.'
        : 'We could not email your ticket right now - but it is always here and on your profile page.';
}

$pageTitle   = 'Booking confirmed';
$currentPage = '';
require __DIR__ . '/templates/layout/header.php';

if ($ticket === null): ?>
    <section class="w-full max-w-[720px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl text-center">
        <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Booking not found</h1>
        <p class="mt-space-md font-body-regular text-body-regular text-on-surface-variant">We couldn't find that booking.</p>
        <a href="<?= e(url('index.php')) ?>" class="mt-space-lg inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">Back to home</a>
    </section>
<?php else:
    require __DIR__ . '/templates/sections/ticket.php';
endif;

require __DIR__ . '/templates/layout/footer.php';
