<?php
declare(strict_types=1);

// The code printed on the ticket and encoded in the QR image, e.g. "LUM-00042-7F3A".
//
// It is DERIVED from the booking id, not stored in a column. The last four
// characters are the start of an HMAC of the booking id, keyed with a secret that
// only the server knows. So:
//   - staff can recompute the code for any booking id and compare,
//   - a customer cannot guess a valid code for a booking that is not theirs,
//   - and the database needs no extra column that could drift out of sync
//     with the booking it describes (keeps the schema in 3NF).
final class TicketCode
{
    private const PREFIX = 'LUM';

    public static function forBooking(int $bookingId, string $secret): string
    {
        if ($bookingId < 1) {
            throw new InvalidArgumentException('Ticket code needs a real booking id.');
        }

        // hash_hmac, not a plain hash: without the secret the check digits
        // cannot be worked out from the booking id alone.
        $mac = hash_hmac('sha256', self::PREFIX . ':' . $bookingId, $secret);

        return sprintf(
            '%s-%05d-%s',
            self::PREFIX,
            $bookingId,
            strtoupper(substr($mac, 0, 4))
        );
    }

    /** True when $code is the right code for this booking. Constant-time compare. */
    public static function matches(string $code, int $bookingId, string $secret): bool
    {
        if ($bookingId < 1) {
            return false;
        }
        return hash_equals(self::forBooking($bookingId, $secret), strtoupper(trim($code)));
    }
}
