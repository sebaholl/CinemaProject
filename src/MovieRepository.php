<?php
declare(strict_types=1);

// Reads Movie rows from the database and turns them into Movie objects.
// All queries use prepared statements (no user input built into SQL text).
final class MovieRepository
{
    private const COLUMNS = 'movie_id,title,genre,description,duration_minutes,cover_image,backdrop_image,is_featured';

    public function __construct(private PDO $db) {}

    /** The movie that headlines the front-page hero (is_featured = 1), or null if none is marked. */
    public function findFeatured(): ?Movie
    {
        $movies = $this->fetchMovies(
            'SELECT ' . self::COLUMNS . ' FROM Movie WHERE is_featured = 1 ORDER BY movie_id LIMIT 1'
        );
        return $movies[0] ?? null;
    }

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
            'SELECT m.movie_id,m.title,m.genre,m.description,m.duration_minutes,m.cover_image,m.backdrop_image,m.is_featured FROM Movie m
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
            (string)$r['description'], (int)$r['duration_minutes'], $r['cover_image'],
            $r['backdrop_image'] ?? null, (bool)($r['is_featured'] ?? false)
        ), $stmt->fetchAll());
    }
}
