<?php
declare(strict_types=1);

// Tests the Mailtrap HTTP API client without touching the network. The client
// takes an optional "transport" closure; the tests pass a fake one that records
// what would have been sent and returns a canned HTTP response. That lets us
// check the request we build and every way a send can fail.
//
// Run from the project root:   php tests/mailtrap_api_test.php

require_once __DIR__ . '/../src/MailtrapApiClient.php';
require_once __DIR__ . '/../src/Mailer.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) { $passed++; echo "PASS  {$name}\n"; }
    else     { $failed++; echo "FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . "\n"; }
}

function throwsWith(callable $fn): string
{
    try { $fn(); return ''; } catch (Throwable $e) { return $e->getMessage(); }
}

$goodConfig = ['endpoint' => 'https://sandbox.api.mailtrap.io/api/send/12345', 'token' => 'tok_abc'];

/** A fake transport that always answers with the given status and body. */
function fakeTransport(int $status, string $body, array &$captured, string $error = ''): Closure
{
    return static function (string $url, string $json, array $headers) use ($status, $body, &$captured, $error): array {
        $captured = ['url' => $url, 'json' => $json, 'headers' => $headers];
        return ['status' => $status, 'body' => $body, 'error' => $error];
    };
}

echo "--- Configuration guards ---------------------------------\n";

check('missing endpoint is rejected',
    str_contains(throwsWith(static fn() => new MailtrapApiClient(['token' => 't'])), 'endpoint'));

check('missing token is rejected',
    str_contains(throwsWith(static fn() => new MailtrapApiClient(['endpoint' => 'https://a.test/x'])), 'token'));

// The token travels in a header, so http:// would expose it.
check('plain http endpoint is rejected',
    str_contains(throwsWith(static fn() => new MailtrapApiClient(
        ['endpoint' => 'http://a.test/x', 'token' => 't'])), 'https'));

echo "\n--- The request we build ---------------------------------\n";

$captured = [];
$client = new MailtrapApiClient($goodConfig, fakeTransport(200, '{"success":true,"message_ids":["x"]}', $captured));

$ok = $client->send(
    'noreply@lumencinema.test', 'Lumen Cinema',
    'clifton@example.test', 'Clifton Benjamin',
    'Your tickets for Avengers: Doomsday',
    '<p>Ticket LUM-00042-7F3A</p>',
    'Ticket LUM-00042-7F3A'
);

check('a 2xx with success:true is a send', $ok === true);
check('posted to the configured endpoint', ($captured['url'] ?? '') === $goodConfig['endpoint']);
check('token is sent as the Api-Token header',
    in_array('Api-Token: tok_abc', $captured['headers'] ?? [], true));
check('content type is json',
    in_array('Content-Type: application/json', $captured['headers'] ?? [], true));

$payload = json_decode($captured['json'] ?? '', true);
check('payload is valid json', is_array($payload));
check('from address is right',  ($payload['from']['email'] ?? '') === 'noreply@lumencinema.test');
check('from name is right',     ($payload['from']['name'] ?? '') === 'Lumen Cinema');
check('recipient is a list',    isset($payload['to'][0]['email']));
check('recipient address is right', ($payload['to'][0]['email'] ?? '') === 'clifton@example.test');
check('subject is right',       ($payload['subject'] ?? '') === 'Your tickets for Avengers: Doomsday');
check('html body is sent',      str_contains($payload['html'] ?? '', 'LUM-00042-7F3A'));
check('text body is sent',      str_contains($payload['text'] ?? '', 'LUM-00042-7F3A'));

echo "\n--- Failures must be explained, not swallowed ------------\n";

$c = [];
check('a bad token says so clearly',
    str_contains(
        throwsWith(static fn() => (new MailtrapApiClient($goodConfig,
            fakeTransport(401, '{"success":false,"errors":["Unauthorized"]}', $c)))
            ->send('a@b.test','A','c@d.test','C','s','<p>h</p>','t')),
        'token'
    ));

$c = [];
check('a 422 shows the reason Mailtrap gave',
    str_contains(
        throwsWith(static fn() => (new MailtrapApiClient($goodConfig,
            fakeTransport(422, '{"success":false,"errors":["\'to\' address is invalid"]}', $c)))
            ->send('a@b.test','A','c@d.test','C','s','<p>h</p>','t')),
        "'to' address is invalid"
    ));

$c = [];
check('a 2xx with success:false is still a failure',
    str_contains(
        throwsWith(static fn() => (new MailtrapApiClient($goodConfig,
            fakeTransport(200, '{"success":false,"errors":["inbox is full"]}', $c)))
            ->send('a@b.test','A','c@d.test','C','s','<p>h</p>','t')),
        'inbox is full'
    ));

$c = [];
check('a network error is reported',
    str_contains(
        throwsWith(static fn() => (new MailtrapApiClient($goodConfig,
            fakeTransport(0, '', $c, 'Could not resolve host')))
            ->send('a@b.test','A','c@d.test','C','s','<p>h</p>','t')),
        'Could not resolve host'
    ));

$c = [];
check('a 500 with no json still raises',
    str_contains(
        throwsWith(static fn() => (new MailtrapApiClient($goodConfig,
            fakeTransport(500, 'Internal Server Error', $c)))
            ->send('a@b.test','A','c@d.test','C','s','<p>h</p>','t')),
        '500'
    ));

echo "\n--- Mailer wiring ----------------------------------------\n";

check('api is an accepted driver',
    throwsWith(static fn() => new Mailer([
        'driver' => 'api', 'from_email' => 'a@b.test',
        'api' => ['endpoint' => 'https://a.test/x', 'token' => 't'],
    ])) === '');

// Mailer still validates the recipient before any driver runs.
$m = new Mailer([
    'driver' => 'api', 'from_email' => 'a@b.test',
    'api' => ['endpoint' => 'https://a.test/x', 'token' => 't'],
]);
check('api driver still rejects a bad recipient',
    str_contains(throwsWith(static fn() => $m->send('not-an-email', 'X', 'S', '<p>h</p>', 't')), 'invalid'));

check('api driver still rejects CRLF in the address',
    str_contains(throwsWith(static fn() => $m->send("a@b.test\r\nBcc: evil@c.test", 'X', 'S', '<p>h</p>', 't')), 'invalid'));

echo "---------------\n";
printf("%d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
