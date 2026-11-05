<?php
declare(strict_types=1);

// Thrown when a booking cannot be created (e.g. a seat was taken a moment ago).
// The message is safe to show to the customer.
final class BookingException extends RuntimeException
{
}
