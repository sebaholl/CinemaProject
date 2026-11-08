<?php
declare(strict_types=1);

// Reads News rows from the database and turns them into News objects.
final class NewsRepository
{
    private const COLUMNS = 'news_id,title,category,description,publication_date,image';

    public function __construct(private PDO $db) {}

    /** @return News[] Newest first. */
    public function findAll(): array
    {
        return $this->fetchNews('SELECT ' . self::COLUMNS . ' FROM News
            ORDER BY publication_date DESC, news_id DESC');
    }

    /** @return News[] The $limit newest items (for the front page). */
    public function findLatest(int $limit = 3): array
    {
        $limit = max(1, min(50, $limit)); // clamp; value is inlined, so keep it an int in a safe range
        return $this->fetchNews('SELECT ' . self::COLUMNS . ' FROM News
            ORDER BY publication_date DESC, news_id DESC LIMIT ' . $limit);
    }

    public function findById(int $id): ?News
    {
        if ($id < 1) { return null; }
        $rows = $this->fetchNews('SELECT ' . self::COLUMNS . ' FROM News WHERE news_id = :news_id',
            [':news_id' => $id]);
        return $rows[0] ?? null;
    }

    /** @return News[] */
    private function fetchNews(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(static fn(array $r): News => new News(
            (int)$r['news_id'], (string)$r['title'], (string)$r['category'],
            (string)$r['description'], (string)$r['publication_date'], $r['image']
        ), $stmt->fetchAll());
    }
}
