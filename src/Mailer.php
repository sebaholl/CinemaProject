<?php
declare(strict_types=1);

// Sends an email. This class knows about TRANSPORT only - how a message is put
// together and where it goes. It knows nothing about tickets or bookings, so the
// contact form (build step 7) can reuse it unchanged.
//
// Two drivers, chosen in config/config.php:
//   'file' - write the finished message to a .eml file. XAMPP on macOS has no
//            working sendmail, so this is how we see and demo the real email
//            locally. The file is a normal RFC 822 message: double-click it and
//            Mail opens it exactly as a recipient would see it.
//   'mail' - hand it to PHP's mail(). This is what a live server with a working
//            sendmail uses.
//   'smtp' - talk to a real mail server ourselves over a socket (SmtpClient).
//   'api'  - post the email to Mailtrap over HTTPS (MailtrapApiClient). This is
//            the one that works here, because this network blocks SMTP but not
//            HTTPS. Mailtrap builds the MIME message at their end.
//
// Security: every value that ends up in a header has its line breaks stripped.
// Without that, an address containing "\r\nBcc: someone@evil.test" would let a
// caller add their own headers - the classic email header injection attack.
final class Mailer
{
    private string $driver;
    private string $fromEmail;
    private string $fromName;
    private string $spoolDir;
    private array $smtp;
    private array $api;

    /** Full path of the last message written by the 'file' driver (for tests/dev). */
    private ?string $lastFile = null;

    public function __construct(array $mailConfig)
    {
        $this->driver    = (string) ($mailConfig['driver'] ?? 'file');
        $this->fromEmail = (string) ($mailConfig['from_email'] ?? 'noreply@localhost');
        $this->fromName  = (string) ($mailConfig['from_name'] ?? 'Cinema');
        $spool = (string) ($mailConfig['spool_dir'] ?? sys_get_temp_dir());
        // Tidy 'config/../storage/mail' into a real path, so error messages and
        // the dev mailbox show something readable.
        $this->spoolDir = is_dir($spool) ? (realpath($spool) ?: $spool) : $spool;

        $this->smtp = is_array($mailConfig['smtp'] ?? null) ? $mailConfig['smtp'] : [];
        $this->api  = is_array($mailConfig['api'] ?? null) ? $mailConfig['api'] : [];

        if (!in_array($this->driver, ['file', 'mail', 'smtp', 'api'], true)) {
            throw new InvalidArgumentException("Unknown mail driver: {$this->driver}");
        }
        if (filter_var($this->fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('The from address in the mail config is not valid.');
        }
    }

    /**
     * Send one message with both a plain-text and an HTML version.
     * Returns true when it was handed off (written, or accepted by mail()).
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        $toEmail = trim($toEmail);
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Cannot send to an invalid email address.');
        }

        // The api driver hands the parts over as data and lets Mailtrap assemble
        // the message, so it does not need any of the MIME building below.
        if ($this->driver === 'api') {
            return (new MailtrapApiClient($this->api))->send(
                $this->fromEmail,
                $this->stripBreaks($this->fromName),
                $toEmail,
                $this->stripBreaks($toName),
                $this->stripBreaks($subject),
                $htmlBody,
                $textBody
            );
        }

        $boundary = 'b' . bin2hex(random_bytes(12));

        // Headers. self::header() removes CR/LF from each value.
        $headers = [
            'From'         => $this->addressHeader($this->fromName, $this->fromEmail),
            'Reply-To'     => $this->addressHeader($this->fromName, $this->fromEmail),
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
            'Date'         => date(DATE_RFC2822),
        ];

        // multipart/alternative: the client picks the richest part it can show,
        // so a text-only client still gets a readable ticket.
        $eol  = "\r\n";
        $body = 'This is a message in MIME format.' . $eol . $eol
            . '--' . $boundary . $eol
            . 'Content-Type: text/plain; charset=UTF-8' . $eol
            . 'Content-Transfer-Encoding: base64' . $eol . $eol
            . chunk_split(base64_encode($textBody)) . $eol
            . '--' . $boundary . $eol
            . 'Content-Type: text/html; charset=UTF-8' . $eol
            . 'Content-Transfer-Encoding: base64' . $eol . $eol
            . chunk_split(base64_encode($htmlBody)) . $eol
            . '--' . $boundary . '--' . $eol;

        // Non-ASCII in a subject must be encoded; mb_encode_mimeheader does that
        // and leaves plain ASCII alone.
        $encodedSubject = mb_encode_mimeheader(
            $this->stripBreaks($subject), 'UTF-8', 'B', $eol
        );

        // Every driver sends the same message; they only differ in where it goes.
        $toHeader = $this->addressHeader($toName, $toEmail);

        if ($this->driver === 'file') {
            return $this->writeToFile($toHeader, $encodedSubject, $headers, $body, $eol);
        }

        if ($this->driver === 'smtp') {
            // SMTP wants the whole thing - headers, blank line, body - in one go.
            $message = $this->buildMessage($toHeader, $encodedSubject, $headers, $body, $eol);

            return (new SmtpClient($this->smtp))->send($this->fromEmail, $toEmail, $message);
        }

        // mail() wants the To and Subject separately, and the rest as a header blob.
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        return mail($toHeader, $encodedSubject, $body, implode($eol, $headerLines));
    }

    /** Headers, a blank line, then the body - one complete RFC 822 message. */
    private function buildMessage(
        string $toHeader,
        string $encodedSubject,
        array $headers,
        string $body,
        string $eol
    ): string {
        $lines = ['To: ' . $toHeader, 'Subject: ' . $encodedSubject];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        return implode($eol, $lines) . $eol . $eol . $body;
    }

    public function getLastFile(): ?string
    {
        return $this->lastFile;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    /** Write the whole message, headers and all, as a .eml file. */
    private function writeToFile(
        string $toHeader,
        string $encodedSubject,
        array $headers,
        string $body,
        string $eol
    ): bool {
        if (!is_dir($this->spoolDir) && !@mkdir($this->spoolDir, 0775, true) && !is_dir($this->spoolDir)) {
            throw new RuntimeException('Mail spool directory could not be created: ' . $this->spoolDir);
        }
        if (!is_writable($this->spoolDir)) {
            throw new RuntimeException('Mail spool directory is not writable: ' . $this->spoolDir);
        }

        $message = $this->buildMessage($toHeader, $encodedSubject, $headers, $body, $eol);

        $file = rtrim($this->spoolDir, '/') . '/'
            . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.eml';

        if (file_put_contents($file, $message, LOCK_EX) === false) {
            throw new RuntimeException('Could not write the email to ' . $file);
        }

        $this->lastFile = $file;
        return true;
    }

    /** '"Lumen Cinema" <noreply@lumencinema.test>', with the name made header-safe. */
    private function addressHeader(string $name, string $email): string
    {
        $name  = $this->stripBreaks($name);
        $email = $this->stripBreaks($email);

        if ($name === '') {
            return $email;
        }

        // Quotes and backslashes would end the quoted name early, so drop them.
        $name = str_replace(['"', '\\'], '', $name);
        $name = mb_encode_mimeheader($name, 'UTF-8', 'B', "\r\n");

        return '"' . $name . '" <' . $email . '>';
    }

    /**
     * Remove CR, LF and NUL. A header value must stay on one line - anything that
     * can start a new line can forge a new header (email header injection).
     */
    private function stripBreaks(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }
}
