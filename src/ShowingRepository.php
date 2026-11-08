<?php
declare(strict_types=1);

// Reads screenings (with seats-left numbers) from the v_screenings_with_seats_left view.
final class ShowingRepository
{
    private const COLUMNS = 'screening_id,movie_id,movie_title,hall_id,hall_name,screening_date,screening_time,format,total_seats,booked_seats,seats_left';

    public function __construct(private PDO $db) {}

    /** @return Showing[] Today's screenings, including ones that have already started. */
    public function findToday(): array
    {
        return $this->fetchShowings('SELECT ' . self::COLUMNS . ' FROM v_screenings_with_seats_left
            WHERE screening_date = :today ORDER BY screening_time,screening_id',
            [':today' => gmdate('Y-m-d')]);
    }

    /** @return Showing[] Future screenings, excluding already started ones. */
    public function findUpcoming(): array
    {
        return $this->fetchShowings('SELECT ' . self::COLUMNS . ' FROM v_screenings_with_seats_left
            WHERE screening_date > :today OR (screening_date = :today_again AND screening_time > :now_time)
            ORDER BY screening_date,screening_time,screening_id',
            [':today' => gmdate('Y-m-d'), ':today_again' => gmdate('Y-m-d'), ':now_time' => gmdate('H:i:s')]);
    }

    /** @return Showing[] All screenings from today across the next $days days (for the weekly matrix). */
    public function findWeek(int $days = 7): array
    {
        $days = max(1, $days);
        $start = gmdate('Y-m-d');
        $end   = gmdate('Y-m-d', time() + ($days - 1) * 86400); // UTC, like $start
        return $this->fetchShowings('SELECT ' . self::COLUMNS . ' FROM v_screenings_with_seats_left
            WHERE screening_date BETWEEN :start AND :end
            ORDER BY screening_date,screening_time,screening_id',
            [':start' => $start, ':end' => $end]);
    }

    public function findById(int $id): ?Showing
    {
        if ($id < 1) { return null; }
        $rows = $this->fetchShowings('SELECT ' . self::COLUMNS . ' FROM v_screenings_with_seats_left
            WHERE screening_id = :screening_id', [':screening_id' => $id]);
        return $rows[0] ?? null;
    }

    /** @return Showing[] */
    public function findByMovie(int $movieId): array
    {
        if ($movieId < 1) { return []; }
        return $this->fetchShowings('SELECT ' . self::COLUMNS . ' FROM v_screenings_with_seats_left
            WHERE movie_id = :movie_id ORDER BY screening_date,screening_time,screening_id',
            [':movie_id' => $movieId]);
    }

    /** @return Showing[] */
    private function fetchShowings(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(static fn(array $r): Showing => new Showing(
            (int)$r['screening_id'], (int)$r['movie_id'], (string)$r['movie_title'],
            (int)$r['hall_id'], (string)$r['hall_name'], (string)$r['screening_date'],
            (string)$r['screening_time'], (string)$r['format'], (int)$r['total_seats'],
            (int)$r['booked_seats'], (int)$r['seats_left']
        ), $stmt->fetchAll());
    }
}
