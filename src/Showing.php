<?php
declare(strict_types=1);

// One screening, enriched with seat totals from the v_screenings_with_seats_left view.
final class Showing
{
    public function __construct(
        private int $id,
        private int $movieId,
        private string $movieTitle,
        private int $hallId,
        private string $hallName,
        private string $date,
        private string $time,
        private string $format,
        private int $totalSeats,
        private int $bookedSeats,
        private int $seatsLeft
    ) {}

    public function getId(): int { return $this->id; }
    public function getMovieId(): int { return $this->movieId; }
    public function getMovieTitle(): string { return $this->movieTitle; }
    public function getHallId(): int { return $this->hallId; }
    public function getHallName(): string { return $this->hallName; }
    public function getDate(): string { return $this->date; }
    public function getTime(): string { return $this->time; }
    public function getFormat(): string { return $this->format; }
    public function getTotalSeats(): int { return $this->totalSeats; }
    public function getBookedSeats(): int { return $this->bookedSeats; }
    public function getSeatsLeft(): int { return $this->seatsLeft; }
}
