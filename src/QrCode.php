<?php
declare(strict_types=1);

// Builds the URL of a QR code image for a piece of text.
//
// The image itself is rendered by a free external service. Keeping that detail in
// ONE class means the templates just ask for "the QR image for this code": if we
// later swap the provider, or generate the PNG locally, nothing else changes.
//
// Trade-off worth knowing: because the QR is a remote image, mail clients that
// block remote images will not show it. That is why the ticket templates also
// print the code as plain text underneath.
final class QrCode
{
    private const ENDPOINT = 'https://api.qrserver.com/v1/create-qr-code/';

    public static function imageUrl(string $data, int $size = 240): string
    {
        $data = trim($data);
        if ($data === '') {
            throw new InvalidArgumentException('Nothing to encode in the QR code.');
        }

        // Clamp the size so a bad caller cannot ask for a 9000px image.
        $size = max(80, min(600, $size));

        // Plain '&' separator: the templates run every URL through e(), which
        // turns it into &amp; at the point it is printed. Escaping it here too
        // would double-escape and break the link.
        return self::ENDPOINT . '?' . http_build_query([
            'size'   => $size . 'x' . $size,
            'margin' => 8,
            'format' => 'png',
            'data'   => $data,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
