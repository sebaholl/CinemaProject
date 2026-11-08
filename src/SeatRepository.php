<?php
declare(strict_types=1);

// Builds the seat map for one screening: every seat in that screening's hall,
// with a "sold" flag taken from the BookingSeat rows for that screening.
final class SeatRepository
{
    public function __construct(private PDO $db) {}

    /** @return Seat[] Ordered by row then seat number. */
    public function findForScreening(int $screeningId): array
    {
        if ($screeningId < 1) { return []; }

        // One query: start from the screening to get its hall, join every seat in
        // that hall, and LEFT JOIN the booked seats so unbooked ones come back NULL.
        $sql = 'SELECT s.seat_id, s.row_label, s.seat_number, s.seat_type,
                       (bs.seat_id IS NOT NULL) AS is_sold
                FROM Screening sc
                JOIN Seat s ON s.hall_id = sc.hall_id
                LEFT JOIN BookingSeat bs
                       ON bs.screening_id = sc.screening_id AND bs.seat_id = s.seat_id
                WHERE sc.screening_id = :screening_id
                ORDER BY s.row_label, s.seat_number';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':screening_id' => $screeningId]);

        return array_map(static fn(array $r): Seat => new Seat(
            (int)$r['seat_id'], (string)$r['row_label'], (int)$r['seat_number'],
            (string)$r['seat_type'], (bool)$r['is_sold']
        ), $stmt->fetchAll());
    }
}
