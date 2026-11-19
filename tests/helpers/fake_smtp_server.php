<?php
declare(strict_types=1);

// A pretend SMTP server, used only by tests/smtp_client_test.php.
// It accepts one connection, plays its part in the conversation, and writes
// whatever message it received to a file so the test can check it.
//
// Usage: php tests/helpers/fake_smtp_server.php <port> <output-file>

$port = (int) ($argv[1] ?? 0);
$out  = (string) ($argv[2] ?? '');

if ($port < 1 || $out === '') {
    fwrite(STDERR, "usage: fake_smtp_server.php <port> <output-file>\n");
    exit(1);
}

$server = @stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);
if ($server === false) {
    fwrite(STDERR, "could not listen on {$port}: {$errstr}\n");
    exit(1);
}

// Tell the test we are ready.
file_put_contents($out . '.ready', 'ok');

$client = @stream_socket_accept($server, 10);
if ($client === false) {
    exit(1);
}
stream_set_timeout($client, 10);

$say = static function (string $line) use ($client): void {
    fwrite($client, $line . "\r\n");
};

$transcript = [];
$message    = '';
$inData     = false;

$say('220 fake.smtp.test ESMTP ready');

while (($line = fgets($client, 2048)) !== false) {
    if ($inData) {
        if (rtrim($line, "\r\n") === '.') {
            $inData = false;
            $say('250 2.0.0 Ok: queued as FAKE1');
            continue;
        }
        // Undo dot-stuffing, exactly as a real server does.
        $message .= preg_replace('/^\.\./', '.', $line);
        continue;
    }

    $command = rtrim($line, "\r\n");
    $transcript[] = $command;
    $verb = strtoupper(strtok($command, ' ') ?: '');

    switch ($verb) {
        case 'EHLO':
            // Multi-line reply, so the test also covers continuation parsing.
            $say('250-fake.smtp.test says hello');
            $say('250-SIZE 10240000');
            $say('250-AUTH LOGIN PLAIN');
            $say('250 HELP');
            break;
        case 'HELO':
            $say('250 fake.smtp.test');
            break;
        case 'AUTH':
            $say('334 VXNlcm5hbWU6');            // "Username:"
            $transcript[] = 'AUTH-USER:' . rtrim((string) fgets($client, 1024), "\r\n");
            $say('334 UGFzc3dvcmQ6');            // "Password:"
            $transcript[] = 'AUTH-PASS:' . rtrim((string) fgets($client, 1024), "\r\n");
            $say('235 2.7.0 Authentication successful');
            break;
        case 'MAIL':
        case 'RCPT':
            $say('250 2.1.0 Ok');
            break;
        case 'DATA':
            $inData = true;
            $say('354 End data with <CR><LF>.<CR><LF>');
            break;
        case 'QUIT':
            $say('221 2.0.0 Bye');
            break 2;
        default:
            $say('500 5.5.2 Unrecognized command');
    }
}

file_put_contents($out, json_encode([
    'transcript' => $transcript,
    'message'    => $message,
], JSON_PRETTY_PRINT));

@fclose($client);
@fclose($server);
@unlink($out . '.ready');
