<?php
declare(strict_types=1);

final class ShowingRepository
{
    private const COLUMNS =
        'v.screening_id,v.movie_id,v.movie_title,v.hall_id,v.hall_name,' .
        'v.screening_date,v.screening_time,v.format,v.total_seats,' .
        'v.booked_seats,v.seats_left,s.price_kr';

    private const SOURCE =
        ' FROM v_screenings_with_seats_left AS v' .
        ' JOIN Screening AS s ON s.screening_id = v.screening_id';

    public function __construct(private PDO $db) {}

    /** @return Showing[] */
    public function findToday(): array
    {
        return $this->fetchShowings(
            'SELECT ' . self::COLUMNS . self::SOURCE .
            ' WHERE v.screening_date = :today
              ORDER BY v.screening_time,v.screening_id',
            [':today' => gmdate('Y-m-d')]
        );
    }

    /** @return Showing[] */
    public function findUpcoming(): array
    {
        return $this->fetchShowings(
            'SELECT ' . self::COLUMNS . self::SOURCE .
            ' WHERE v.screening_date > :today
                 OR (v.screening_date = :today_again
                     AND v.screening_time > :now_time)
              ORDER BY v.screening_date,v.screening_time,v.screening_id',
            [
                ':today' => gmdate('Y-m-d'),
                ':today_again' => gmdate('Y-m-d'),
                ':now_time' => gmdate('H:i:s'),
            ]
        );
    }

    /** @return Showing[] */
    public function findWeek(int $days = 7): array
    {
        $days = max(1, $days);
        $start = gmdate('Y-m-d');
        $end = gmdate('Y-m-d', time() + ($days - 1) * 86400);

        return $this->fetchShowings(
            'SELECT ' . self::COLUMNS . self::SOURCE .
            ' WHERE v.screening_date BETWEEN :start AND :end
              ORDER BY v.screening_date,v.screening_time,v.screening_id',
            [':start' => $start, ':end' => $end]
        );
    }

    public function findById(int $id): ?Showing
    {
        if ($id < 1) {
            return null;
        }

        $rows = $this->fetchShowings(
            'SELECT ' . self::COLUMNS . self::SOURCE .
            ' WHERE v.screening_id = :id',
            [':id' => $id]
        );

        return $rows[0] ?? null;
    }

    /** @return Showing[] */
    public function findByMovie(int $movieId): array
    {
        if ($movieId < 1) {
            return [];
        }

        return $this->fetchShowings(
            'SELECT ' . self::COLUMNS . self::SOURCE .
            ' WHERE v.movie_id = :movie_id
              ORDER BY v.screening_date,v.screening_time,v.screening_id',
            [':movie_id' => $movieId]
        );
    }

    /** @return Showing[] */
    public function findFeaturedForDate(string $date): array
    {
        return $this->fetchShowings(
            'SELECT ' . self::COLUMNS . self::SOURCE .
            ' JOIN front_page_showing AS f
                ON f.screening_id = v.screening_id
             WHERE f.showing_date = :date
               AND v.screening_date = :date_again
               AND TIMESTAMP(v.screening_date, v.screening_time)
                   > UTC_TIMESTAMP()
             ORDER BY v.screening_time,v.screening_id',
            [':date' => $date, ':date_again' => $date]
        );
    }

    public function listForAdmin(): array
    {
        $stmt = $this->db->prepare(
            'SELECT v.screening_id,v.movie_title,v.hall_name,
                    v.screening_date,v.screening_time,v.format,
                    v.total_seats,v.seats_left,s.price_kr,
                    f.screening_id IS NOT NULL AS featured
             FROM v_screenings_with_seats_left AS v
             JOIN Screening AS s ON s.screening_id = v.screening_id
             LEFT JOIN front_page_showing AS f
                ON f.screening_id = v.screening_id
             ORDER BY v.screening_date DESC,v.screening_time DESC,
                      v.screening_id DESC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function details(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT screening_id,movie_id,hall_id,screening_date,
                    screening_time,format,price_kr
             FROM Screening WHERE screening_id = :id'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function movieOptions(): array
    {
        $stmt = $this->db->prepare(
            'SELECT movie_id,title FROM Movie ORDER BY title'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function hallOptions(): array
    {
        $stmt = $this->db->prepare(
            'SELECT hall_id,name FROM MovieHall ORDER BY name'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function save(?int $id, array $data): int
    {
        $this->db->beginTransaction();

        try {
            $existing = null;

            if ($id !== null) {
                $stmt = $this->db->prepare(
                    'SELECT movie_id,hall_id,screening_date,screening_time
                     FROM Screening
                     WHERE screening_id = :id
                     FOR UPDATE'
                );
                $stmt->execute([':id' => $id]);
                $existing = $stmt->fetch();

                if (!$existing) {
                    throw new DomainException('Showing not found.');
                }
            }

            // Serialise admin scheduling changes for this hall.
            $stmt = $this->db->prepare(
                'SELECT hall_id FROM MovieHall
                 WHERE hall_id = :id FOR UPDATE'
            );
            $stmt->execute([':id' => $data['hall_id']]);

            if (!$stmt->fetch()) {
                throw new DomainException('Choose an existing auditorium.');
            }

            $stmt = $this->db->prepare(
                'SELECT duration_minutes FROM Movie
                 WHERE movie_id = :id'
            );
            $stmt->execute([':id' => $data['movie_id']]);
            $duration = $stmt->fetchColumn();

            if ($duration === false || (int) $duration < 1) {
                throw new DomainException('Choose a movie with a valid duration.');
            }

            $start = new DateTimeImmutable(
                $data['screening_date'] . ' ' . $data['screening_time'],
                new DateTimeZone('UTC')
            );

            if ($start <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
                throw new DomainException('Choose a future showing time.');
            }

            $slotChanged = $existing === null
                || (int) $existing['movie_id'] !== $data['movie_id']
                || (int) $existing['hall_id'] !== $data['hall_id']
                || $existing['screening_date'] !== $data['screening_date']
                || substr($existing['screening_time'], 0, 5)
                    !== $data['screening_time'];

            if ($slotChanged && $id !== null) {
                $stmt = $this->db->prepare(
                    'SELECT COUNT(*) FROM Booking
                     WHERE screening_id = :id'
                );
                $stmt->execute([':id' => $id]);

                if ((int) $stmt->fetchColumn() > 0) {
                    throw new DomainException(
                        'This showing has bookings. Its movie, hall and time cannot change.'
                    );
                }
            }

            if ($slotChanged) {
                // Include a 15-minute turnaround after each movie.
                $end = $start->modify('+' . ((int) $duration + 15) . ' minutes');

                $stmt = $this->db->prepare(
                    'SELECT s.screening_id
                     FROM Screening AS s
                     JOIN Movie AS m ON m.movie_id = s.movie_id
                     WHERE s.hall_id = :hall_id
                       AND s.screening_id <> :exclude_id
                       AND TIMESTAMP(s.screening_date,s.screening_time) < :new_end
                       AND DATE_ADD(
                           TIMESTAMP(s.screening_date,s.screening_time),
                           INTERVAL (m.duration_minutes + 15) MINUTE
                       ) > :new_start
                     LIMIT 1'
                );
                $stmt->execute([
                    ':hall_id' => $data['hall_id'],
                    ':exclude_id' => $id ?? 0,
                    ':new_start' => $start->format('Y-m-d H:i:s'),
                    ':new_end' => $end->format('Y-m-d H:i:s'),
                ]);

                if ($stmt->fetch()) {
                    throw new DomainException(
                        'This auditorium already has an overlapping showing.'
                    );
                }
            }

            if ($id === null) {
                $stmt = $this->db->prepare(
                    'INSERT INTO Screening
                        (movie_id,hall_id,screening_date,
                         screening_time,format,price_kr)
                     VALUES (:movie,:hall,:date,:time,:format,:price)'
                );
            } else {
                $stmt = $this->db->prepare(
                    'UPDATE Screening
                     SET movie_id=:movie,hall_id=:hall,
                         screening_date=:date,screening_time=:time,
                         format=:format,price_kr=:price
                     WHERE screening_id=:id'
                );
            }

            $params = [
                ':movie' => $data['movie_id'],
                ':hall' => $data['hall_id'],
                ':date' => $data['screening_date'],
                ':time' => $data['screening_time'],
                ':format' => $data['format'],
                ':price' => $data['price_kr'],
            ];

            if ($id !== null) {
                $params[':id'] = $id;
            }

            $stmt->execute($params);
            $savedId = $id ?? (int) $this->db->lastInsertId();

            if ($slotChanged && $id !== null) {
                // A featured selection belongs to its original day.
                $stmt = $this->db->prepare(
                    'DELETE FROM front_page_showing
                     WHERE screening_id = :id'
                );
                $stmt->execute([':id' => $id]);
            }

            $this->db->commit();
            return $savedId;
        } catch (Throwable $error) {
            $this->db->rollBack();

            if ($error instanceof PDOException
                && $error->getCode() === '23000') {
                throw new DomainException(
                    'Another showing already starts at that time in this auditorium.'
                );
            }

            throw $error;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM Screening WHERE screening_id = :id'
        );

        try {
            $stmt->execute([':id' => $id]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new DomainException(
                    'This showing has bookings and cannot be deleted.'
                );
            }
            throw $error;
        }

        return $stmt->rowCount() === 1;
    }

    public function feature(int $id): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO front_page_showing (screening_id,showing_date)
             SELECT screening_id,screening_date
             FROM Screening
             WHERE screening_id = :id
               AND TIMESTAMP(screening_date,screening_time) > UTC_TIMESTAMP()'
        );

        try {
            $stmt->execute([':id' => $id]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new DomainException('This showing is already featured.');
            }
            throw $error;
        }

        if ($stmt->rowCount() !== 1) {
            throw new DomainException('Only a future showing can be featured.');
        }
    }

    public function unfeature(int $id): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM front_page_showing WHERE screening_id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /** @return Showing[] */
    private function fetchShowings(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(
            static fn (array $row): Showing => new Showing(
                (int) $row['screening_id'],
                (int) $row['movie_id'],
                (string) $row['movie_title'],
                (int) $row['hall_id'],
                (string) $row['hall_name'],
                (string) $row['screening_date'],
                (string) $row['screening_time'],
                (string) $row['format'],
                (int) $row['total_seats'],
                (int) $row['booked_seats'],
                (int) $row['seats_left'],
                (int) $row['price_kr']
            ),
            $stmt->fetchAll()
        );
    }
}