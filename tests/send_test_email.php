<?php
declare(strict_types=1);

// Sends one real ticket email using whatever driver config/config.php is set to.
// Run this after pasting your Mailtrap credentials, to check them before going
// anywhere near the booking flow.
//
//   php tests/send_test_email.php                  -> uses a made-up ticket
//   php tests/send_test_email.php 16               -> uses real booking 16
//   php tests/send_test_email.php 16 you@mail.test -> ...sent to another address
//
// Exit code 0 means the server accepted the message.

require_once __DIR__ . '/../includes/bootstrap.php';

$bookingId = isset($argv[1]) ? (int) $argv[1] : 0;
$override  = $argv[2] ?? null;

$mailConfig = config('mail');
$driver     = (string) ($mailConfig['driver'] ?? '');

echo "Driver: {$driver}\n";

if ($driver === 'api') {
    $api = $mailConfig['api'] ?? [];
    printf("Endpoint: %s\n", $api['endpoint'] ?? '?');

    if (($api['token'] ?? '') === '' || str_contains((string) ($api['endpoint'] ?? ''), 'INBOX_ID')) {
        $hasLocal = is_file(__DIR__ . '/../config/local.php');

        fwrite(STDERR,
            "\nNo Mailtrap API token.\n\n"
            . ($hasLocal
                ? "config/local.php exists but does not set mail.api.token.\n"
                : "config/local.php is missing. Create it from the template:\n"
                  . "    cp config/local.example.php config/local.php\n")
            . "\nThe token goes in config/local.php (which git ignores), not in\n"
            . "config/config.php. Get it from mailtrap.io -> Email Testing ->\n"
            . "your inbox -> Integrations -> API.\n"
        );
        exit(1);
    }
}

if ($driver === 'smtp') {
    $smtp = $mailConfig['smtp'] ?? [];
    printf("Server: %s:%s (%s)\n", $smtp['host'] ?? '?', $smtp['port'] ?? '?', $smtp['encryption'] ?? '?');

    if (($smtp['username'] ?? '') === '' || ($smtp['password'] ?? '') === '') {
        fwrite(STDERR,
            "\nNo SMTP username/password. They belong in config/local.php\n"
            . "(git-ignored), under mail.smtp. Get them from mailtrap.io ->\n"
            . "Email Testing -> your inbox -> Integrations -> SMTP.\n"
        );
        exit(1);
    }
}

// Either a real booking, or a stand-in so this works on an empty database.
if ($bookingId > 0) {
    $pdo  = Database::getInstance()->getConnection();
    $repo = new BookingRepository($pdo);

    $row = $pdo->prepare('SELECT customer_id FROM Booking WHERE booking_id = :id');
    $row->execute([':id' => $bookingId]);
    $customerId = (int) $row->fetchColumn();

    $ticket = $customerId > 0
        ? $repo->findTicket($bookingId, $customerId, config_value('ticket_secret'))
        : null;

    if ($ticket === null) {
        fwrite(STDERR, "Booking {$bookingId} was not found.\n");
        exit(1);
    }
} else {
    $ticket = new Ticket(
        9999, 9999, TicketCode::forBooking(9999, config_value('ticket_secret')),
        'Test Customer', 'test@example.test', 'Avengers: Doomsday',
        'assets/img/avengers-doomsday.png', 'Hall One', gmdate('Y-m-d', time() + 86400),
        '20:00:00', '2D, Eng subs',
        [
            ['label' => 'F3', 'type' => 'standard', 'price' => 150],
            ['label' => 'F4', 'type' => 'standard', 'price' => 150],
        ],
        300, gmdate('Y-m-d H:i:s'), 'confirmed'
    );
}

$recipient = $override ?? $ticket->getCustomerEmail();
echo "Sending to: {$recipient}\n";
echo "Ticket:     {$ticket->getTicketCode()} ({$ticket->getMovieTitle()})\n\n";

$transport = new Mailer($mailConfig);

try {
    // sendConfirmation always mails the ticket's own customer, so an override
    // address goes through the Mailer directly with the same rendered bodies.
    if ($override !== null) {
        $mailer = new ReflectionClass(TicketMailer::class);
        $render = $mailer->getMethod('render');
        $render->setAccessible(true);
        $tm = new TicketMailer($transport);

        $ok = $transport->send(
            $override,
            $ticket->getCustomerName(),
            'TEST - Your tickets for ' . $ticket->getMovieTitle(),
            $render->invoke($tm, __DIR__ . '/../templates/email/ticket.php', $ticket),
            $render->invoke($tm, __DIR__ . '/../templates/email/ticket.txt.php', $ticket)
        );
    } else {
        $ok = (new TicketMailer($transport))->sendConfirmation($ticket);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "FAILED: " . $e->getMessage() . "\n");
    exit(1);
}

if (!$ok) {
    fwrite(STDERR, "FAILED: the transport reported the message was not sent.\n");
    exit(1);
}

echo "OK - message accepted.\n";

if ($driver === 'file') {
    echo "Written to: " . $transport->getLastFile() . "\n";
    echo "Read it with mailbox.php, or open the .eml file.\n";
} elseif ($driver === 'smtp' || $driver === 'api') {
    echo "Check your Mailtrap inbox at https://mailtrap.io/inboxes\n";
}
