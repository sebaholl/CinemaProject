<?php
declare(strict_types=1);

// Tests the hand-written SMTP client against a pretend SMTP server, so the whole
// conversation is checked without needing real credentials or an internet
// connection. Proves the client speaks SMTP correctly BEFORE anyone fills in
// Mailtrap details - if a real send then fails, the credentials are the problem,
// not this code.
//
// Run from the project root:   php tests/smtp_client_test.php

require_once __DIR__ . '/../src/SmtpClient.php';
require_once __DIR__ . '/../src/Mailer.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) { $passed++; echo "PASS  {$name}\n"; }
    else     { $failed++; echo "FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . "\n"; }
}

function throws(callable $fn): ?string
{
    try { $fn(); return null; } catch (Throwable $e) { return get_class($e); }
}

echo "--- Configuration guards ---------------------------------\n";

check('missing host is rejected',
    throws(static fn() => new SmtpClient([])) === 'InvalidArgumentException');
check('bad port is rejected',
    throws(static fn() => new SmtpClient(['host' => 'a.test', 'port' => 99999])) === 'InvalidArgumentException');
check('unknown encryption is rejected',
    throws(static fn() => new SmtpClient(['host' => 'a.test', 'encryption' => 'rot13'])) === 'InvalidArgumentException');
check('a server that is not there gives a clear error',
    throws(static fn() => (new SmtpClient([
        'host' => '127.0.0.1', 'port' => 9, 'encryption' => 'none', 'timeout' => 5,
    ]))->send('a@b.test', 'c@d.test', "Subject: x\r\n\r\nbody")) === 'RuntimeException');

echo "\n--- A full conversation with a fake server ---------------\n";

$port = random_int(43000, 44999);
$out  = sys_get_temp_dir() . '/fake-smtp-' . bin2hex(random_bytes(4)) . '.json';
$php  = PHP_BINARY;

// Start the pretend server in the background and wait for it to listen.
$cmd = escapeshellarg($php) . ' ' . escapeshellarg(__DIR__ . '/helpers/fake_smtp_server.php')
     . ' ' . $port . ' ' . escapeshellarg($out) . ' > /dev/null 2>&1 &';
exec($cmd);

for ($i = 0; $i < 100 && !is_file($out . '.ready'); $i++) {
    usleep(50_000);
}
check('fake server started', is_file($out . '.ready'), 'port ' . $port);

$client = new SmtpClient([
    'host'       => '127.0.0.1',
    'port'       => $port,
    'username'   => 'inbox-user',
    'password'   => 'inbox-pass',
    'encryption' => 'none',   // the fake server speaks plain text
    'timeout'    => 10,
]);

// A message with a line that starts with a dot, to prove dot-stuffing works.
$message = "To: \"Clifton\" <clifton@example.test>\r\n"
    . "Subject: Your tickets\r\n"
    . "From: \"Lumen Cinema\" <noreply@lumencinema.test>\r\n"
    . "\r\n"
    . "Ticket LUM-00042-7F3A\r\n"
    . ".this line starts with a dot\r\n"
    . "last line\r\n";

$sent = $client->send('noreply@lumencinema.test', 'clifton@example.test', $message);
check('send() reported success', $sent === true);

for ($i = 0; $i < 100 && !is_file($out); $i++) {
    usleep(50_000);
}
check('server wrote what it received', is_file($out));

$result     = json_decode((string) @file_get_contents($out), true) ?: [];
$transcript = $result['transcript'] ?? [];
$received   = (string) ($result['message'] ?? '');

$sawCommand = static function (string $prefix) use ($transcript): bool {
    foreach ($transcript as $line) {
        if (str_starts_with((string) $line, $prefix)) { return true; }
    }
    return false;
};

check('client sent EHLO',      $sawCommand('EHLO'));
check('client sent AUTH LOGIN', $sawCommand('AUTH LOGIN'));
check('client sent MAIL FROM', $sawCommand('MAIL FROM:<noreply@lumencinema.test>'));
check('client sent RCPT TO',   $sawCommand('RCPT TO:<clifton@example.test>'));
check('client sent DATA',      $sawCommand('DATA'));
check('client sent QUIT',      $sawCommand('QUIT'));

// Credentials go over the wire base64 encoded (which is why TLS matters).
check('username was sent base64 encoded',
    in_array('AUTH-USER:' . base64_encode('inbox-user'), $transcript, true));
check('password was sent base64 encoded',
    in_array('AUTH-PASS:' . base64_encode('inbox-pass'), $transcript, true));

// The server un-stuffs the dots, so what it stored must equal what we sent.
check('subject arrived',        str_contains($received, 'Subject: Your tickets'));
check('ticket code arrived',    str_contains($received, 'LUM-00042-7F3A'));
check('dot-stuffed line arrived intact',
    str_contains($received, "\n.this line starts with a dot"), 'dot stuffing');
check('message was not cut short at the dot line',
    str_contains($received, 'last line'));

echo "\n--- The password must never leak -------------------------\n";

$log = implode("\n", $client->getLog());
check('conversation log does not contain the password',
    !str_contains($log, 'inbox-pass') && !str_contains($log, base64_encode('inbox-pass')),
    'log leaks credentials');

echo "\n--- Mailer wiring ----------------------------------------\n";

check('smtp is an accepted driver',
    throws(static fn() => new Mailer([
        'driver' => 'smtp', 'from_email' => 'a@b.test',
        'smtp' => ['host' => 'a.test'],
    ])) === null);

check('still rejects an unknown driver',
    throws(static fn() => new Mailer(['driver' => 'telepathy', 'from_email' => 'a@b.test']))
        === 'InvalidArgumentException');

@unlink($out);
@unlink($out . '.ready');

echo "---------------\n";
printf("%d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
