<?php
declare(strict_types=1);

// Vanilla-PHP test harness for the ticket email (no framework, no database).
// It builds a Ticket in memory and checks the three parts that are easy to get
// wrong: the derived ticket code, the message Mailer puts together, and whether
// both templates actually contain the facts the customer needs.
//
// Run from the project root:   php tests/ticket_email_test.php
// Exit code is 0 when every case passes, 1 if any case fails.

require_once __DIR__ . '/../src/TicketCode.php';
require_once __DIR__ . '/../src/QrCode.php';
require_once __DIR__ . '/../src/Ticket.php';
require_once __DIR__ . '/../src/Mailer.php';
require_once __DIR__ . '/../src/TicketMailer.php';

// The templates call e(), url_abs() and SITE_NAME, which normally come from
// bootstrap. Define just those, so the test stays independent of the web stack.
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('url')) {
    function url(string $path = ''): string { return '/CinemaProject/' . ltrim($path, '/'); }
}
if (!function_exists('url_abs')) {
    function url_abs(string $path = ''): string
    {
        return preg_match('#^https?://#i', $path) === 1 ? $path : 'http://localhost' . url($path);
    }
}
if (!defined('SITE_NAME')) { define('SITE_NAME', 'Lumen Cinema'); }

// --- Tiny assertion runner -------------------------------------------------
$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "PASS  {$name}\n";
    } else {
        $failed++;
        echo "FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . "\n";
    }
}

/** Run a callable and report which exception class (if any) came out. */
function throws(callable $fn): ?string
{
    try {
        $fn();
        return null;
    } catch (Throwable $e) {
        return get_class($e);
    }
}

// --- A ticket to work with -------------------------------------------------
$secret = 'test-secret';

$ticket = new Ticket(
    bookingId: 42,
    invoiceId: 12,
    ticketCode: TicketCode::forBooking(42, $secret),
    customerName: 'Alice Andersen',
    customerEmail: 'alice@example.test',
    movieTitle: 'Spider-Man: Brand New Day',
    posterUrl: 'assets/img/spiderman-brand-new-day.png',
    hallName: 'Screen 1',
    screeningDate: '2026-11-20',
    screeningTime: '20:30:00',
    format: '2D, Eng subs',
    seats: [
        ['label' => 'F3', 'type' => 'standard', 'price' => 150],
        ['label' => 'F4', 'type' => 'standard', 'price' => 150],
        ['label' => 'G5', 'type' => 'luxury',   'price' => 185],
    ],
    total: 485,
    bookedAt: '2026-11-19 20:28:00',
    status: 'confirmed',
);

$spool = sys_get_temp_dir() . '/cinema-mail-test-' . bin2hex(random_bytes(4));
$fileConfig = [
    'driver'     => 'file',
    'from_email' => 'noreply@lumencinema.test',
    'from_name'  => 'Lumen Cinema',
    'spool_dir'  => $spool,
];

echo "--- Ticket code ------------------------------------------\n";

// 1. The format is the one printed on the ticket and encoded in the QR.
check(
    'code looks like LUM-00042-XXXX',
    preg_match('/^LUM-00042-[0-9A-F]{4}$/', $ticket->getTicketCode()) === 1,
    $ticket->getTicketCode()
);

// 2. Same booking + same secret must always give the same code, or a ticket
//    emailed yesterday would not match the one shown today.
check(
    'code is stable for the same booking',
    TicketCode::forBooking(42, $secret) === TicketCode::forBooking(42, $secret)
);

// 3. The check digits depend on the secret - that is the whole point of them.
check(
    'code changes with the secret',
    TicketCode::forBooking(42, $secret) !== TicketCode::forBooking(42, 'other-secret')
);

// 4. Different bookings get different codes.
check(
    'different bookings get different codes',
    TicketCode::forBooking(42, $secret) !== TicketCode::forBooking(43, $secret)
);

// 5. matches() accepts the right code and rejects a guessed one.
check('matches() accepts the real code', TicketCode::matches($ticket->getTicketCode(), 42, $secret));
check('matches() rejects a wrong code', !TicketCode::matches('LUM-00042-0000', 42, $secret)
    || $ticket->getTicketCode() === 'LUM-00042-0000');

echo "\n--- Ticket formatting ------------------------------------\n";

check('seat count is the number of seats', $ticket->getSeatCount() === 3);
check('seat list is comma separated', $ticket->getSeatList() === 'F3, F4, G5', $ticket->getSeatList());
check('row list is de-duplicated', $ticket->getRowList() === 'F, G', $ticket->getRowList());
check('time drops the seconds', $ticket->getScreeningTime() === '20:30', $ticket->getScreeningTime());
check('date label is readable', $ticket->getDateLabel() === 'Fri, 20 Nov 2026', $ticket->getDateLabel());
check('purchase time is readable', $ticket->getBookedAtLabel() === '19.11.2026 at 20:28', $ticket->getBookedAtLabel());

// A cancelled booking has its seats deleted by the DB trigger, so an empty seat
// list must read as "Released", not as an empty line.
$cancelled = new Ticket(
    43, null, 'LUM-00043-ABCD', 'Bob B', 'bob@example.test', 'Dune: Part Three',
    null, 'Screen 2', '2026-12-01', '18:00:00', '2D', [], 0, '2026-11-19 10:00:00', 'cancelled'
);
check('cancelled booking says Released', $cancelled->getSeatList() === 'Released', $cancelled->getSeatList());

echo "\n--- QR code ----------------------------------------------\n";

$qr = QrCode::imageUrl($ticket->getTicketCode());
check('QR url carries the ticket code', str_contains($qr, 'data=LUM-00042'), $qr);
check('QR url is https', str_starts_with($qr, 'https://'), $qr);
check('QR url uses a plain & separator (templates escape it)', !str_contains($qr, '&amp;'), $qr);
check('empty QR data is rejected', throws(static fn() => QrCode::imageUrl('  ')) === 'InvalidArgumentException');

echo "\n--- Mailer: refusing bad input ---------------------------\n";

$mailer = new Mailer($fileConfig);

// 6. A malformed recipient must never reach the transport.
check(
    'malformed address is rejected',
    throws(static fn() => $mailer->send('not-an-email', 'X', 'S', '<p>h</p>', 't'))
        === 'InvalidArgumentException'
);

// 7. Email header injection: a newline in an address could otherwise start a
//    header of the attacker's choosing (Bcc, Reply-To, ...).
check(
    'address containing CRLF is rejected',
    throws(static fn() => $mailer->send("alice@example.test\r\nBcc: evil@example.test", 'X', 'S', '<p>h</p>', 't'))
        === 'InvalidArgumentException'
);

// 8. A newline in the NAME must be stripped rather than breaking the header.
$mailer->send("bob@example.test", "Bob\r\nBcc: evil@example.test", 'Subject here', '<p>h</p>', 'text');
$injected = (string) file_get_contents((string) $mailer->getLastFile());
// The payload survives as TEXT inside the quoted display name - that is fine and
// harmless. What must not exist is a LINE that starts a header of its own, so
// that is what we check for.
check(
    'CRLF in the display name does not forge a header',
    preg_match('/^Bcc:/mi', $injected) !== 1
);
check(
    'the injected name stays on the To line',
    // \r? because mail headers end with CRLF, so $ sits after the carriage return.
    preg_match('/^To: .*Bcc: evil@example\\.test.* <bob@example\\.test>\\r?$/m', $injected) === 1
);

// 9. A bad from address is caught when the Mailer is built, not at send time.
check(
    'invalid from address is rejected',
    throws(static fn() => new Mailer(['driver' => 'file', 'from_email' => 'nope', 'spool_dir' => $spool]))
        === 'InvalidArgumentException'
);
check(
    'unknown driver is rejected',
    throws(static fn() => new Mailer(['driver' => 'carrier-pigeon', 'from_email' => 'a@b.test']))
        === 'InvalidArgumentException'
);

echo "\n--- The real message -------------------------------------\n";

// Keep the Mailer so we can ask it exactly which file it wrote. Picking "the
// newest file in the folder" would be flaky: the checks above wrote messages of
// their own into the same spool in the same second.
$ticketMailTransport = new Mailer($fileConfig);
$sent = (new TicketMailer($ticketMailTransport))->sendConfirmation($ticket);
check('sendConfirmation reports success', $sent === true);

$written = (string) $ticketMailTransport->getLastFile();
check('an .eml file was written', $written !== '' && is_file($written), $written);

$raw = (string) file_get_contents($written);

check('message is addressed to the customer', str_contains($raw, 'alice@example.test'));
check('message has a From header', str_contains($raw, 'From: "Lumen Cinema"'));
check('message is multipart/alternative', str_contains($raw, 'multipart/alternative'));
check('message has a text part', str_contains($raw, 'Content-Type: text/plain'));
check('message has an html part', str_contains($raw, 'Content-Type: text/html'));

// Decode both parts and check the facts actually made it in. This is what would
// break silently if a template variable were renamed.
preg_match_all(
    '/Content-Type:\s*text\/(plain|html).*?base64\s*\R\R(.*?)\R--/s',
    $raw,
    $parts,
    PREG_SET_ORDER
);
check('both parts could be decoded', count($parts) === 2, 'found ' . count($parts));

$decoded = [];
foreach ($parts as $part) {
    $decoded[$part[1]] = (string) base64_decode(preg_replace('/\s+/', '', $part[2]) ?? '', true);
}

foreach (['plain', 'html'] as $type) {
    $body = $decoded[$type] ?? '';
    check("{$type} part has the ticket code", str_contains($body, $ticket->getTicketCode()));
    // stripos: the plain-text version prints the title in capitals.
    check("{$type} part has the movie title", stripos($body, 'Brand New Day') !== false);
    check("{$type} part has the hall", str_contains($body, 'Screen 1'));
    check("{$type} part has the seats", str_contains($body, 'F3'));
    check("{$type} part has the total", str_contains($body, '485'));
    check("{$type} part has the time", str_contains($body, '20:30'));
}

// The QR is a remote image, which many clients block. The code must therefore
// also be readable as text in the HTML part, not only inside the <img>.
$htmlWithoutImages = preg_replace('/<img[^>]*>/', '', $decoded['html'] ?? '') ?? '';
check(
    'html ticket code survives blocked images',
    str_contains($htmlWithoutImages, $ticket->getTicketCode())
);

// Absolute links: a path-only href does not resolve inside a mail client.
check('html links are absolute', str_contains($decoded['html'] ?? '', 'http://localhost/CinemaProject/invoice.php'));

// --- Clean up --------------------------------------------------------------
foreach (glob($spool . '/*.eml') ?: [] as $file) { @unlink($file); }
@rmdir($spool);

echo "---------------\n";
printf("%d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
