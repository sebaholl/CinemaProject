<?php
declare(strict_types=1);

// A small SMTP client written from scratch - no Composer, no PHPMailer.
//
// SMTP is a line-based conversation over a socket. We say a command, the server
// answers with a three digit code, and 2xx means "fine, carry on". The whole
// exchange for one email looks like this:
//
//   server: 220 smtp.example greeting
//   we:     EHLO localhost          -> 250 (and a list of what it supports)
//   we:     STARTTLS                -> 220, then we turn on encryption
//   we:     EHLO localhost          -> 250 (ask again, now encrypted)
//   we:     AUTH LOGIN              -> 334, then username and password, -> 235
//   we:     MAIL FROM:<sender>      -> 250
//   we:     RCPT TO:<recipient>     -> 250
//   we:     DATA                    -> 354 "go ahead"
//   we:     ...the message..., then a line with a single dot -> 250 accepted
//   we:     QUIT
//
// This class only does that conversation. Mailer builds the message it sends.
final class SmtpClient
{
    /** @var resource|null */
    private $socket = null;

    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;   // 'tls' (STARTTLS) or 'none'
    private int $timeout;
    private string $clientName;
    private bool $verifyPeer;

    /** The full conversation, for error messages and debugging. */
    private array $log = [];

    public function __construct(array $cfg)
    {
        $this->host       = (string) ($cfg['host'] ?? '');
        $this->port       = (int) ($cfg['port'] ?? 2525);
        $this->username   = (string) ($cfg['username'] ?? '');
        $this->password   = (string) ($cfg['password'] ?? '');
        $this->encryption = (string) ($cfg['encryption'] ?? 'tls');
        $this->timeout    = max(5, (int) ($cfg['timeout'] ?? 20));
        $this->clientName = (string) ($cfg['client_name'] ?? 'localhost');
        $this->verifyPeer = (bool) ($cfg['verify_peer'] ?? true);

        if ($this->host === '') {
            throw new InvalidArgumentException('SMTP host is not configured.');
        }
        if ($this->port < 1 || $this->port > 65535) {
            throw new InvalidArgumentException('SMTP port is not valid.');
        }
        if (!in_array($this->encryption, ['tls', 'none'], true)) {
            throw new InvalidArgumentException('SMTP encryption must be "tls" or "none".');
        }
    }

    /**
     * Deliver one already-built message.
     *
     * $message is the complete email: headers, a blank line, then the body.
     * Returns true when the server accepted it.
     */
    public function send(string $fromEmail, string $toEmail, string $message): bool
    {
        $this->log = [];
        $this->connect();

        try {
            $this->expect($this->readResponse(), 220, 'greeting');
            $this->ehlo();

            if ($this->encryption === 'tls') {
                $this->startTls();
            }

            if ($this->username !== '') {
                $this->authenticate();
            }

            $this->command('MAIL FROM:<' . $fromEmail . '>', 250);
            $this->command('RCPT TO:<' . $toEmail . '>', 250);
            $this->command('DATA', 354);

            // The message ends with a line containing only a dot, so any line in
            // the body that already starts with a dot has to be doubled first -
            // otherwise it would end the message early. This is "dot stuffing".
            $data = preg_replace('/^\./m', '..', $this->normaliseLineEndings($message));

            $this->write($data . "\r\n.\r\n");
            $this->expect($this->readResponse(), 250, 'message body');

            // Politeness, and it flushes the server's side cleanly. If QUIT fails
            // the mail is already accepted, so we do not treat it as an error.
            try { $this->command('QUIT', 221); } catch (RuntimeException) { /* ignore */ }

            return true;
        } finally {
            $this->close();
        }
    }

    /** The whole conversation, newest last. Useful when something goes wrong. */
    public function getLog(): array
    {
        return $this->log;
    }

    // -- the conversation ---------------------------------------------------

    private function connect(): void
    {
        $errno  = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            'tcp://' . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => [
                'verify_peer'       => $this->verifyPeer,
                'verify_peer_name'  => $this->verifyPeer,
                'SNI_enabled'       => true,
                'peer_name'         => $this->host,
            ]])
        );

        if ($socket === false) {
            throw new RuntimeException(
                "Could not reach the SMTP server {$this->host}:{$this->port} ({$errstr})"
            );
        }

        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;
    }

    private function ehlo(): void
    {
        // EHLO is the modern greeting; some old servers only know HELO.
        try {
            $this->command('EHLO ' . $this->clientName, 250);
        } catch (RuntimeException) {
            $this->command('HELO ' . $this->clientName, 250);
        }
    }

    private function startTls(): void
    {
        $this->command('STARTTLS', 220);

        $ok = @stream_socket_enable_crypto(
            $this->requireSocket(),
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($ok !== true) {
            throw new RuntimeException('Could not start TLS with the SMTP server.');
        }

        // Everything the server told us before was unencrypted, so ask again.
        $this->ehlo();
    }

    private function authenticate(): void
    {
        // AUTH LOGIN sends the username and password base64 encoded, each as its
        // own line. Base64 is NOT encryption - it is readable to anyone watching
        // the connection, which is exactly why STARTTLS runs first.
        $this->command('AUTH LOGIN', 334);
        $this->command(base64_encode($this->username), 334, sensitive: true);

        try {
            $this->command(base64_encode($this->password), 235, sensitive: true);
        } catch (RuntimeException $e) {
            // Never let the password itself end up in a log or an error page.
            throw new RuntimeException(
                'The SMTP server rejected the username or password. '
                . 'Check the credentials in config/config.php.'
            );
        }
    }

    /**
     * Send one command and check the reply code.
     * $sensitive marks a line that carries credentials, so it is not written to
     * the log and never appears in an error message.
     */
    private function command(string $line, int $expected, bool $sensitive = false): string
    {
        $this->write($line . "\r\n", $sensitive);
        $response = $this->readResponse();
        $this->expect($response, $expected, $sensitive ? 'AUTH' : $line);
        return $response;
    }

    private function write(string $data, bool $sensitive = false): void
    {
        $socket = $this->requireSocket();

        // The log is readable through getLog(), so credentials never go in it,
        // and a whole message body would only make it unreadable.
        $this->log[] = match (true) {
            $sensitive            => '> [credentials hidden]',
            strlen($data) > 200   => '> [' . strlen($data) . ' bytes of message]',
            default               => '> ' . trim($data),
        };

        if (@fwrite($socket, $data) === false) {
            throw new RuntimeException('Lost the connection to the SMTP server while writing.');
        }
    }

    /**
     * Read one reply. A reply can span several lines: continuation lines have a
     * '-' after the code ("250-SIZE"), the final one has a space ("250 OK").
     */
    private function readResponse(): string
    {
        $socket = $this->requireSocket();
        $lines  = [];

        while (true) {
            $line = fgets($socket, 1024);

            if ($line === false) {
                $info = stream_get_meta_data($socket);
                throw new RuntimeException(
                    !empty($info['timed_out'])
                        ? 'The SMTP server stopped responding (timeout).'
                        : 'The SMTP server closed the connection unexpectedly.'
                );
            }

            $lines[] = rtrim($line, "\r\n");

            // A space in the fourth character means this was the last line.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $response = implode("\n", $lines);
        $this->log[] = '< ' . $response;

        return $response;
    }

    private function expect(string $response, int $expected, string $context): void
    {
        $code = (int) substr($response, 0, 3);

        if ($code !== $expected) {
            throw new RuntimeException(
                "SMTP error after \"{$context}\": expected {$expected}, got: " . trim($response)
            );
        }
    }

    private function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
    }

    /** @return resource */
    private function requireSocket()
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Not connected to the SMTP server.');
        }
        return $this->socket;
    }

    /** SMTP requires CRLF between lines, whatever the message was built with. */
    private function normaliseLineEndings(string $message): string
    {
        return (string) preg_replace('/\r\n|\r|\n/', "\r\n", $message);
    }
}
