<?php
declare(strict_types=1);

// Reads Movie rows from the database and turns them into Movie objects.
// All queries use prepared statements (no user input built into SQL text).
final class MovieRepository
{
    private const COLUMNS = 'movie_id,title,genre,description,duration_minutes,cover_image';

    public function __construct(private PDO $db) {}

    /** @return Movie[] */
    public function findAll(): array
    {
        return $this->fetchMovies('SELECT ' . self::COLUMNS . ' FROM Movie ORDER BY title');
    }

    public function findById(int $id): ?Movie
    {
        if ($id < 1) { return null; }
        $movies = $this->fetchMovies(
            'SELECT ' . self::COLUMNS . ' FROM Movie WHERE movie_id = :movie_id',
            [':movie_id' => $id]
        );
        return $movies[0] ?? null;
    }

    /** @return Movie[] Movies that still have at least one upcoming screening. */
    public function findAllWithUpcomingShowings(): array
    {
        return $this->fetchMovies(
            'SELECT m.movie_id,m.title,m.genre,m.description,m.duration_minutes,m.cover_image FROM Movie m
             WHERE EXISTS (SELECT 1 FROM Screening s WHERE s.movie_id=m.movie_id
             AND (s.screening_date > :today
                  OR (s.screening_date = :today_again AND s.screening_time > :now_time)))
             ORDER BY m.title',
            [':today' => gmdate('Y-m-d'), ':today_again' => gmdate('Y-m-d'), ':now_time' => gmdate('H:i:s')]
        );
    }

    /** @return Movie[] */
    private function fetchMovies(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(static fn(array $r): Movie => new Movie(
            (int)$r['movie_id'], (string)$r['title'], (string)$r['genre'],
            (string)$r['description'], (int)$r['duration_minutes'], $r['cover_image']
        ), $stmt->fetchAll());
    }
}
