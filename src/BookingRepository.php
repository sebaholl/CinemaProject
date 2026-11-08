<?php
declare(strict_types=1);

// Writes a booking to the database. This is where seats actually get "locked":
// a BookingSeat row for a confirmed Booking makes the seat unavailable, enforced by
// UNIQUE(screening_id, seat_id) + the BEFORE INSERT trigger in the schema.
//
// Uses the Repository pattern: the controller (checkout.php) never touches SQL.
final class BookingRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Create a confirmed booking + its seats + an invoice, all in one transaction.
     * The total is computed here from DB prices — never trusted from the browser.
     *
     * @param Seat[] $allSeats        Seats of the screening's hall (for pricing, from the DB).
     * @param int[]  $selectedSeatIds Seat ids to book (already validated by SeatRules).
     * @throws BookingException if a seat was taken in the meantime or the write fails.
     */
    public function create(int $screeningId, int $customerId, array $allSeats, array $selectedSeatIds): Booking
    {
        $seatIds = array_values(array_unique(array_map('intval', $selectedSeatIds)));

        // Price from the DB seat types, so a tampered browser total cannot change what we charge.
        $priceById = [];
        foreach ($allSeats as $seat) {
            $priceById[$seat->getId()] = Pricing::priceFor($seat->getSeatType());
        }
        $total = 0;
        foreach ($seatIds as $id) {
            $total += $priceById[$id] ?? 0;
        }

        $now   = gmdate('Y-m-d H:i:s'); // DB session runs in UTC (+00:00)
        $today = gmdate('Y-m-d');

        try {
            $this->db->beginTransaction();

            // 1) The booking itself, confirmed up front (the seat trigger requires that).
            $stmt = $this->db->prepare(
                'INSERT INTO Booking (customer_id, screening_id, price, booking_date, status)
                 VALUES (:customer_id, :screening_id, :price, :booking_date, :status)'
            );
            $stmt->execute([
                ':customer_id'  => $customerId,
                ':screening_id' => $screeningId,
                ':price'        => $total,
                ':booking_date' => $now,
                ':status'       => 'confirmed',
            ]);
            $bookingId = (int) $this->db->lastInsertId();

            // 2) One seat row per seat. UNIQUE(screening_id,seat_id) blocks a double-book
            //    atomically even if another customer is checking out at the same instant.
            $seatStmt = $this->db->prepare(
                'INSERT INTO BookingSeat (booking_id, screening_id, seat_id)
                 VALUES (:booking_id, :screening_id, :seat_id)'
            );
            foreach ($seatIds as $seatId) {
                $seatStmt->execute([
                    ':booking_id'   => $bookingId,
                    ':screening_id' => $screeningId,
                    ':seat_id'      => $seatId,
                ]);
            }

            // 3) The invoice (mock checkout still produces a real invoice record).
            $invStmt = $this->db->prepare(
                'INSERT INTO Invoice (booking_id, booking_date, invoice_date, total)
                 VALUES (:booking_id, :booking_date, :invoice_date, :total)'
            );
            $invStmt->execute([
                ':booking_id'   => $bookingId,
                ':booking_date' => $now,
                ':invoice_date' => $today,
                ':total'        => $total,
            ]);

            $this->db->commit();

            return new Booking($bookingId, $customerId, $screeningId, $seatIds, $total);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $sqlState   = (string) $e->getCode();          // '23000', '45000', ...
            $driverCode = (int) ($e->errorInfo[1] ?? 0);   // 1062 = duplicate key

            // A seat was grabbed in the gap between our check and our insert:
            //  - 23000 / 1062  -> the UNIQUE(screening_id,seat_id) constraint fired
            //  - 45000         -> the BEFORE INSERT trigger SIGNALled (e.g. hall/time guard)
            if ($sqlState === '23000' || $driverCode === 1062 || $sqlState === '45000') {
                throw new BookingException(
                    'Sorry — one or more of those seats was just taken. Please choose again.'
                );
            }

            // Anything else is an unexpected failure.
            throw new BookingException('The booking could not be completed. Please try again.');
        }
    }

    /** One booking's summary row from the v_booking_overview view, or null. */
    public function findOverview(int $bookingId): ?array
    {
        if ($bookingId < 1) { return null; }
        $stmt = $this->db->prepare(
            'SELECT booking_id, customer_name, movie_title, screening_date, screening_time,
                    hall_name, status, price, invoice_id, invoice_date, invoice_total
             FROM v_booking_overview WHERE booking_id = :id'
        );
        $stmt->execute([':id' => $bookingId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return string[] Seat labels (e.g. "F3") for a booking, ordered by row then number. */
    public function findSeatLabels(int $bookingId): array
    {
        if ($bookingId < 1) { return []; }
        $stmt = $this->db->prepare(
            'SELECT s.row_label, s.seat_number
             FROM BookingSeat bs JOIN Seat s ON s.seat_id = bs.seat_id
             WHERE bs.booking_id = :id
             ORDER BY s.row_label, s.seat_number'
        );
        $stmt->execute([':id' => $bookingId]);
        return array_map(
            static fn(array $r): string => $r['row_label'] . $r['seat_number'],
            $stmt->fetchAll()
        );
    }
}
