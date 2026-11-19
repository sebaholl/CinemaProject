<?php
declare(strict_types=1);

final class AdminDashboardRepository
{
    public function __construct(private PDO $db) {}

    public function statistics(): array
    {
        return [
            'today_bookings' => (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM Booking
                 WHERE booking_date >= UTC_DATE()
                   AND booking_date < UTC_DATE() + INTERVAL 1 DAY
                   AND status = 'confirmed'"
            ),
            'upcoming_showings' => (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM Screening
                 WHERE TIMESTAMP(screening_date, screening_time) > UTC_TIMESTAMP()"
            ),
            'month_revenue' => (float) $this->scalar(
                "SELECT COALESCE(SUM(i.total), 0)
                 FROM Invoice AS i
                 JOIN Booking AS b ON b.booking_id = i.booking_id
                 WHERE b.status = 'confirmed'
                   AND YEAR(i.invoice_date) = YEAR(UTC_DATE())
                   AND MONTH(i.invoice_date) = MONTH(UTC_DATE())"
            ),
        ];
    }

    public function recentBookings(): array
    {
        $stmt = $this->db->prepare(
            'SELECT booking_id, customer_name, movie_title,
                    screening_date, screening_time, status,
                    price, invoice_id
             FROM v_booking_overview
             ORDER BY booking_date DESC, booking_id DESC
             LIMIT 10'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function scalar(string $sql): string
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return (string) $stmt->fetchColumn();
    }
}