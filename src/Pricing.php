<?php
declare(strict_types=1);

// Ticket prices (kr) kept in one place. Business rule, not a DB table:
// price depends on the seat type. Later a Booking class uses priceFor() to
// work out the total it stores on the booking + invoice.
final class Pricing
{
    public const STANDARD    = 150;
    public const LUXURY      = 185;
    public const ACCESSIBLE  = 150;
    public const BOOKING_FEE = 5;

    public static function priceFor(string $seatType): int
    {
        return match ($seatType) {
            'luxury' => self::LUXURY,
            default  => self::STANDARD, // standard + accessible share the base price
        };
    }
}
