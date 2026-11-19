<?php
declare(strict_types=1);

final class Pricing
{
    public const STANDARD = 150;
    public const LUXURY = 185;
    public const ACCESSIBLE = 150;
    public const BOOKING_FEE = 5;
    public const LUXURY_EXTRA = 35;

    public static function priceFor(
        string $seatType,
        int $standardPrice = self::STANDARD
    ): int {
        return $standardPrice
            + ($seatType === 'luxury' ? self::LUXURY_EXTRA : 0);
    }
}
