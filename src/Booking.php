<?php
declare(strict_types=1);

// One confirmed booking, as created by BookingRepository. Plain value object:
// holds what the confirmation page needs after the DB write succeeds.
final class Booking
{
    /** @param int[] $seatIds */
    public function __construct(
        private int $id,
        private int $customerId,
        private int $screeningId,
        private array $seatIds,
        private int $total
    ) {}

    public function getId(): int { return $this->id; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getScreeningId(): int { return $this->screeningId; }
    /** @return int[] */
    public function getSeatIds(): array { return $this->seatIds; }
    public function getTotal(): int { return $this->total; }
}
