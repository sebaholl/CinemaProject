<?php
declare(strict_types=1);

// Everything one finished booking needs in order to be shown as a ticket/receipt.
//
// It holds data only - no SQL, no HTML. BookingRepository::findTicket() builds it
// from the database, and the SAME object is then rendered twice: once as the HTML
// email (templates/email/ticket.php) and once as the page on confirmation.php
// (templates/sections/ticket.php). That is the presentation/logic split the report
// has to describe: the data is worked out in one place, the look in another.
final class Ticket
{
    /**
     * @param array<int,array{label:string,type:string,price:int}> $seats
     */
    public function __construct(
        private int $bookingId,
        private ?int $invoiceId,
        private string $ticketCode,
        private string $customerName,
        private string $customerEmail,
        private string $movieTitle,
        private ?string $posterUrl,
        private string $hallName,
        private string $screeningDate,
        private string $screeningTime,
        private string $format,
        private array $seats,
        private int $total,
        private string $bookedAt,
        private string $status,
    ) {}

    public function getBookingId(): int       { return $this->bookingId; }
    public function getInvoiceId(): ?int      { return $this->invoiceId; }
    public function getTicketCode(): string   { return $this->ticketCode; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerEmail(): string{ return $this->customerEmail; }
    public function getMovieTitle(): string   { return $this->movieTitle; }
    public function getPosterUrl(): ?string   { return $this->posterUrl; }
    public function getHallName(): string     { return $this->hallName; }
    public function getScreeningDate(): string{ return $this->screeningDate; }
    public function getFormat(): string       { return $this->format; }
    public function getStatus(): string       { return $this->status; }
    public function getTotal(): int           { return $this->total; }

    /** @return array<int,array{label:string,type:string,price:int}> */
    public function getSeats(): array         { return $this->seats; }

    /** '20:30' - the DB stores TIME as '20:30:00'. */
    public function getScreeningTime(): string
    {
        return substr($this->screeningTime, 0, 5);
    }

    /** How many seats this booking covers, e.g. "Valid for 3 tickets". */
    public function getSeatCount(): int
    {
        return count($this->seats);
    }

    /** ['F3','F4','F5'] */
    public function getSeatLabels(): array
    {
        return array_column($this->seats, 'label');
    }

    /** 'F3, F4, F5' - or a clear note when the booking was cancelled. */
    public function getSeatList(): string
    {
        if ($this->seats === []) {
            return $this->status === 'cancelled' ? 'Released' : 'No seats recorded';
        }
        return implode(', ', $this->getSeatLabels());
    }

    /** The row letters used, e.g. 'F' or 'F, G' when a booking spans two rows. */
    public function getRowList(): string
    {
        $rows = [];
        foreach ($this->seats as $seat) {
            // 'F12' -> 'F'. Row labels are letters, seat numbers are digits.
            $row = rtrim($seat['label'], '0123456789');
            if ($row !== '' && !in_array($row, $rows, true)) {
                $rows[] = $row;
            }
        }
        return $rows === [] ? '-' : implode(', ', $rows);
    }

    /** 'Thu, 20 Nov 2026' for the ticket face. */
    public function getDateLabel(): string
    {
        $d = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $this->screeningDate,
            new DateTimeZone('UTC')
        );
        return $d ? $d->format('D, j M Y') : $this->screeningDate;
    }

    /** '19.11.2026 at 20:28' - when the purchase was made. */
    public function getBookedAtLabel(): string
    {
        $d = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $this->bookedAt,
            new DateTimeZone('UTC')
        );
        return $d ? $d->format('d.m.Y') . ' at ' . $d->format('H:i') : $this->bookedAt;
    }
}
