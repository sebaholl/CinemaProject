<?php
declare(strict_types=1);

// Writes bookings, allocated seats, and invoices to the database.
final class BookingRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Create a confirmed booking, its seats, and an invoice in one transaction.
     *
     * @param Seat[] $allSeats
     * @param int[] $selectedSeatIds
     * @throws BookingException
     */
    public function create(
        int $screeningId,
        int $customerId,
        array $allSeats,
        array $selectedSeatIds
    ): Booking {
        $seatIds = array_values(
            array_unique(array_map('intval', $selectedSeatIds))
        );

        if ($screeningId < 1 || $customerId < 1 || $seatIds === []) {
            throw new BookingException('Please choose valid seats.');
        }

        try {
            $this->db->beginTransaction();

            // Lock the showing while we read its price and create the booking.
            // An admin price update must wait until this transaction finishes.
            $priceStmt = $this->db->prepare(
                'SELECT price_kr
                 FROM Screening
                 WHERE screening_id = :id
                 FOR UPDATE'
            );
            $priceStmt->execute([':id' => $screeningId]);
            $standardPrice = $priceStmt->fetchColumn();

            if ($standardPrice === false) {
                throw new BookingException('Showing not found.');
            }

            // Seat types were loaded from the database for this showing.
            // Never use a total submitted by the browser.
            $priceById = [];

            foreach ($allSeats as $seat) {
                $priceById[$seat->getId()] = Pricing::priceFor(
                    $seat->getSeatType(),
                    (int) $standardPrice
                );
            }

            $total = 0;

            foreach ($seatIds as $seatId) {
                if (!isset($priceById[$seatId])) {
                    throw new BookingException(
                        'Please choose seats from this auditorium.'
                    );
                }

                $total += $priceById[$seatId];
            }

            $now = gmdate('Y-m-d H:i:s');
            $today = gmdate('Y-m-d');

            // The seat-allocation trigger requires a confirmed booking.
            $stmt = $this->db->prepare(
                'INSERT INTO Booking
                    (customer_id, screening_id, price, booking_date, status)
                 VALUES
                    (:customer_id, :screening_id, :price, :booking_date, :status)'
            );
            $stmt->execute([
                ':customer_id' => $customerId,
                ':screening_id' => $screeningId,
                ':price' => $total,
                ':booking_date' => $now,
                ':status' => 'confirmed',
            ]);

            $bookingId = (int) $this->db->lastInsertId();

            // UNIQUE(screening_id, seat_id) prevents two bookings from
            // allocating the same seat for this showing.
            $seatStmt = $this->db->prepare(
                'INSERT INTO BookingSeat
                    (booking_id, screening_id, seat_id)
                 VALUES
                    (:booking_id, :screening_id, :seat_id)'
            );

            foreach ($seatIds as $seatId) {
                $seatStmt->execute([
                    ':booking_id' => $bookingId,
                    ':screening_id' => $screeningId,
                    ':seat_id' => $seatId,
                ]);
            }

            // Mock checkout still creates an invoice record.
            $invoiceStmt = $this->db->prepare(
                'INSERT INTO Invoice
                    (booking_id, booking_date, invoice_date, total)
                 VALUES
                    (:booking_id, :booking_date, :invoice_date, :total)'
            );
            $invoiceStmt->execute([
                ':booking_id' => $bookingId,
                ':booking_date' => $now,
                ':invoice_date' => $today,
                ':total' => $total,
            ]);

            $this->db->commit();

            return new Booking(
                $bookingId,
                $customerId,
                $screeningId,
                $seatIds,
                $total
            );
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $sqlState = (string) $exception->getCode();
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);

            if ($sqlState === '23000'
                || $driverCode === 1062
                || $sqlState === '45000') {
                throw new BookingException(
                    'Sorry — those seats could not be booked. Please choose again.'
                );
            }

            throw new BookingException(
                'The booking could not be completed. Please try again.'
            );
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /** One booking summary from v_booking_overview, or null. */
    public function findOverview(int $bookingId): ?array
    {
        if ($bookingId < 1) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT booking_id, customer_name, movie_title,
                    screening_date, screening_time, hall_name,
                    status, price, invoice_id, invoice_date, invoice_total
             FROM v_booking_overview
             WHERE booking_id = :id'
        );
        $stmt->execute([':id' => $bookingId]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Return the booking only if it belongs to this customer. */
    public function findOwnedOverview(
        int $bookingId,
        int $customerId
    ): ?array {
        if ($bookingId < 1 || $customerId < 1) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT booking_id, customer_name, movie_title,
                    screening_date, screening_time, hall_name,
                    status, price, invoice_id, invoice_date, invoice_total
             FROM v_booking_overview
             WHERE booking_id = :booking_id
               AND customer_id = :customer_id'
        );
        $stmt->execute([
            ':booking_id' => $bookingId,
            ':customer_id' => $customerId,
        ]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Cancel an owned booking only if its showing is over two hours away. */
    public function cancelOwnedBooking(
        int $bookingId,
        int $customerId
    ): bool {
        if ($bookingId < 1 || $customerId < 1) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE Booking AS b
             JOIN Screening AS sc ON sc.screening_id = b.screening_id
             SET b.status = 'cancelled'
             WHERE b.booking_id = :booking_id
               AND b.customer_id = :customer_id
               AND b.status = 'confirmed'
               AND TIMESTAMP(sc.screening_date, sc.screening_time)
                   > UTC_TIMESTAMP() + INTERVAL 2 HOUR"
        );
        $stmt->execute([
            ':booking_id' => $bookingId,
            ':customer_id' => $customerId,
        ]);

        return $stmt->rowCount() === 1;
    }

    /** @return string[] Seat labels such as "F3". */
    public function findSeatLabels(int $bookingId): array
    {
        if ($bookingId < 1) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT s.row_label, s.seat_number
             FROM BookingSeat AS bs
             JOIN Seat AS s ON s.seat_id = bs.seat_id
             WHERE bs.booking_id = :id
             ORDER BY s.row_label, s.seat_number'
        );
        $stmt->execute([':id' => $bookingId]);

        return array_map(
            static fn (array $row): string =>
                $row['row_label'] . $row['seat_number'],
            $stmt->fetchAll()
        );
    }
}