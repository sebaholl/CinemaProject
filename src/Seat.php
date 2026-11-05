<?php
declare(strict_types=1);

// One seat in a hall, plus whether it is already sold for a given screening.
final class Seat
{
    public function __construct(
        private int $id,
        private string $rowLabel,   // 'A'..'H'
        private int $seatNumber,    // 1..10
        private string $seatType,   // standard | luxury | accessible
        private bool $sold
    ) {}

    public function getId(): int { return $this->id; }
    public function getRowLabel(): string { return $this->rowLabel; }
    public function getSeatNumber(): int { return $this->seatNumber; }
    public function getSeatType(): string { return $this->seatType; }
    public function isSold(): bool { return $this->sold; }

    // Numeric row index for the seat-map layout: A->1, B->2, ... H->8.
    public function getRowIndex(): int
    {
        return ord(strtoupper($this->rowLabel[0] ?? 'A')) - 64;
    }
}
