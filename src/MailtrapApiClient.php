<?php
declare(strict_types=1);

// Sends an email through Mailtrap's HTTP API instead of SMTP.
//
// Why this exists: this network blocks outbound SMTP. The connection opens and
// the server says hello, then EHLO never gets an answer - the same against Gmail,
// so it is the network, not the mail provider. Plain HTTPS is not blocked, and
// Mailtrap accepts mail over HTTPS too, so we post the email as JSON.
//
// We hand over the subject and the two bodies and let Mailtrap assemble the MIME
// message, so there are no headers to build here - and no header injection to
// worry about, because nothing we send is interpreted as a header. The address is
// still validated by Mailer before it gets this far.
//
// Still vanilla PHP: this uses cURL, which ships with PHP. No Composer packages.
final class MailtrapApiClient
{
    private string $endpoint;
    private string $token;
    private int $timeout;

    /** @var (Closure(string,string,array):array)|null Swapped out in tests. */
    private ?Closure $transport;

    /**
     * @param array $cfg  endpoint, token, timeout
     * @param Closure|null $transport  fn(string $url, string $json, array $headers): array{status:int,body:string,error:string}
     */
    public function __construct(array $cfg, ?Closure $transport = null)
    {
        $this->endpoint = rtrim((string) ($cfg['endpoint'] ?? ''), '/');
        $this->token    = (string) ($cfg['token'] ?? '');
        $this->timeout  = max(5, (int) ($cfg['timeout'] ?? 20));
        $this->transport = $transport;

        if ($this->endpoint === '') {
            throw new InvalidArgumentException('Mailtrap API endpoint is not configured.');
        }
        if (!str_starts_with($this->endpoint, 'https://')) {
            // The token travels in a header, so the connection has to be encrypted.
            throw new InvalidArgumentException('Mailtrap API endpoint must be an https:// URL.');
        }
        if ($this->token === '') {
            throw new InvalidArgumentException(
                'Mailtrap API token is missing. Add it to the api block in config/config.php.'
            );
        }
        if ($transport === null && !extension_loaded('curl')) {
            throw new RuntimeException('The PHP cURL extension is required to use the api mail driver.');
        }
    }

    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        $payload = [
            'from'    => ['email' => $fromEmail, 'name' => $fromName],
            'to'      => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'text'    => $textBody,
            'html'    => $htmlBody,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Could not encode the email as JSON: ' . json_last_error_msg());
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Api-Token: ' . $this->token,
        ];

        $result = $this->transport !== null
            ? ($this->transport)($this->endpoint, $json, $headers)
            : $this->post($this->endpoint, $json, $headers);

        return $this->interpret($result);
    }

    /** Turn the HTTP result into either true or a useful exception. */
    private function interpret(array $result): bool
    {
        $status = (int) ($result['status'] ?? 0);
        $body   = (string) ($result['body'] ?? '');
        $error  = (string) ($result['error'] ?? '');

        if ($error !== '') {
            throw new RuntimeException('Could not reach the Mailtrap API: ' . $error);
        }

        $decoded = json_decode($body, true);

        if ($status === 401 || $status === 403) {
            throw new RuntimeException(
                'Mailtrap rejected the API token. Check the token and inbox id in config/config.php.'
            );
        }

        if ($status < 200 || $status >= 300) {
            // Mailtrap explains refusals in an "errors" array - show it, it is useful.
            $why = is_array($decoded) && !empty($decoded['errors'])
                ? implode('; ', (array) $decoded['errors'])
                : trim($body);

            throw new RuntimeException("Mailtrap returned HTTP {$status}: " . ($why ?: 'no details'));
        }

        // A 2xx with "success": false still means it was not sent.
        if (is_array($decoded) && array_key_exists('success', $decoded) && $decoded['success'] !== true) {
            $why = !empty($decoded['errors']) ? implode('; ', (array) $decoded['errors']) : 'no details';
            throw new RuntimeException('Mailtrap did not accept the message: ' . $why);
        }

        return true;
    }

    /** @return array{status:int,body:string,error:string} */
    private function post(string $url, string $json, array $headers): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);

        curl_close($ch);

        return [
            'status' => $status,
            'body'   => $body === false ? '' : (string) $body,
            'error'  => $error,
        ];
    }
}
