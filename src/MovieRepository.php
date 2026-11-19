<?php
declare(strict_types=1);

final class MovieRepository
{
    private const COLUMNS =
        'movie_id,title,genre,description,duration_minutes,cover_image';

    public function __construct(private PDO $db) {}

    /** @return Movie[] */
    public function findAll(): array
    {
        return $this->fetchMovies(
            'SELECT ' . self::COLUMNS . ' FROM Movie ORDER BY title'
        );
    }

    public function findById(int $id): ?Movie
    {
        if ($id < 1) {
            return null;
        }

        $movies = $this->fetchMovies(
            'SELECT ' . self::COLUMNS .
            ' FROM Movie WHERE movie_id = :movie_id',
            [':movie_id' => $id]
        );

        return $movies[0] ?? null;
    }

    /** @return Movie[] */
    public function findAllWithUpcomingShowings(): array
    {
        return $this->fetchMovies(
            'SELECT m.movie_id,m.title,m.genre,m.description,
                    m.duration_minutes,m.cover_image
             FROM Movie AS m
             WHERE EXISTS (
                 SELECT 1 FROM Screening AS s
                 WHERE s.movie_id = m.movie_id
                   AND (
                       s.screening_date > :today
                       OR (
                           s.screening_date = :today_again
                           AND s.screening_time > :now_time
                       )
                   )
             )
             ORDER BY m.title',
            [
                ':today' => gmdate('Y-m-d'),
                ':today_again' => gmdate('Y-m-d'),
                ':now_time' => gmdate('H:i:s'),
            ]
        );
    }

    /** Full rows for the admin list and public movie details. */
    public function findAllDetails(): array
    {
        $stmt = $this->db->prepare(
            'SELECT movie_id, title, genre, description,
                    duration_minutes, age_rating, release_date, cover_image
             FROM Movie
             ORDER BY title'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findDetailsById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT movie_id, title, genre, description,
                    duration_minutes, age_rating, release_date, cover_image
             FROM Movie
             WHERE movie_id = :id'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(array $movie): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Movie
                (title, genre, description, duration_minutes,
                 age_rating, release_date, cover_image)
             VALUES
                (:title, :genre, :description, :duration,
                 :rating, :release_date, :poster)'
        );

        $stmt->execute([
            ':title' => $movie['title'],
            ':genre' => $movie['genre'],
            ':description' => $movie['description'],
            ':duration' => $movie['duration_minutes'],
            ':rating' => $movie['age_rating'],
            ':release_date' => $movie['release_date'],
            ':poster' => $movie['cover_image'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $movie): void
    {
        $stmt = $this->db->prepare(
            'UPDATE Movie
             SET title = :title,
                 genre = :genre,
                 description = :description,
                 duration_minutes = :duration,
                 age_rating = :rating,
                 release_date = :release_date,
                 cover_image = :poster
             WHERE movie_id = :id'
        );

        $stmt->execute([
            ':title' => $movie['title'],
            ':genre' => $movie['genre'],
            ':description' => $movie['description'],
            ':duration' => $movie['duration_minutes'],
            ':rating' => $movie['age_rating'],
            ':release_date' => $movie['release_date'],
            ':poster' => $movie['cover_image'],
            ':id' => $id,
        ]);
    }

    /** Returns false if the movie no longer exists. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM Movie WHERE movie_id = :id'
        );

        try {
            $stmt->execute([':id' => $id]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException(
                    'This movie has showings and cannot be deleted.'
                );
            }

            throw $exception;
        }

        return $stmt->rowCount() === 1;
    }

    /** @return Movie[] */
    private function fetchMovies(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(
            static fn (array $row): Movie => new Movie(
                (int) $row['movie_id'],
                (string) $row['title'],
                (string) $row['genre'],
                (string) $row['description'],
                (int) $row['duration_minutes'],
                $row['cover_image']
            ),
            $stmt->fetchAll()
        );
    }
}