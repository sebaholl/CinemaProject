<?php
declare(strict_types=1);

final class NewsRepository
{
    private const SELECT_COLUMNS =
        'n.news_id,n.title,n.category,n.description,' .
        'n.publication_date,n.image,a.name AS author_name';

    private const FROM_TABLES =
        ' FROM News AS n' .
        ' LEFT JOIN AdminUser AS a ON a.admin_id = n.admin_id';

    public function __construct(private PDO $db) {}

    /** @return News[] Published articles, newest first. */
    public function findAll(): array
    {
        return $this->fetchNews(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_TABLES .
            ' WHERE n.publication_date <= :today
              ORDER BY n.publication_date DESC,n.news_id DESC',
            [':today' => gmdate('Y-m-d')]
        );
    }

    /** @return News[] Latest published articles for the homepage. */
    public function findLatest(int $limit = 3): array
    {
        $limit = max(1, min(50, $limit));

        $stmt = $this->db->prepare(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_TABLES .
            ' WHERE n.publication_date <= :today
              ORDER BY n.publication_date DESC,n.news_id DESC
              LIMIT :limit'
        );
        $stmt->bindValue(':today', gmdate('Y-m-d'));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapRows($stmt->fetchAll());
    }

    /** A public article; scheduled articles return null. */
    public function findById(int $id): ?News
    {
        if ($id < 1) {
            return null;
        }

        $rows = $this->fetchNews(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_TABLES .
            ' WHERE n.news_id = :id
                AND n.publication_date <= :today',
            [
                ':id' => $id,
                ':today' => gmdate('Y-m-d'),
            ]
        );

        return $rows[0] ?? null;
    }

    /** @return News[] Published and scheduled articles for admins. */
    public function findAllForAdmin(): array
    {
        return $this->fetchNews(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_TABLES .
            ' ORDER BY n.publication_date DESC,n.news_id DESC'
        );
    }

    public function findForAdmin(int $id): ?News
    {
        if ($id < 1) {
            return null;
        }

        $rows = $this->fetchNews(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_TABLES .
            ' WHERE n.news_id = :id',
            [':id' => $id]
        );

        return $rows[0] ?? null;
    }

    public function create(array $article, int $adminId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO News
                (title,category,description,publication_date,image,admin_id)
             VALUES
                (:title,:category,:description,:publication_date,
                 :image,:admin_id)'
        );
        $stmt->execute([
            ':title' => $article['title'],
            ':category' => $article['category'],
            ':description' => $article['description'],
            ':publication_date' => $article['publication_date'],
            ':image' => $article['image'],
            ':admin_id' => $adminId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $article): void
    {
        // Keep the original author when an article is edited.
        $stmt = $this->db->prepare(
            'UPDATE News
             SET title = :title,
                 category = :category,
                 description = :description,
                 publication_date = :publication_date,
                 image = :image
             WHERE news_id = :id'
        );
        $stmt->execute([
            ':title' => $article['title'],
            ':category' => $article['category'],
            ':description' => $article['description'],
            ':publication_date' => $article['publication_date'],
            ':image' => $article['image'],
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM News WHERE news_id = :id'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() === 1;
    }

    /** @return News[] */
    private function fetchNews(
        string $sql,
        array $params = []
    ): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->mapRows($stmt->fetchAll());
    }

    /** @return News[] */
    private function mapRows(array $rows): array
    {
        return array_map(
            static fn (array $row): News => new News(
                (int) $row['news_id'],
                (string) $row['title'],
                (string) $row['category'],
                (string) $row['description'],
                (string) $row['publication_date'],
                $row['image'],
                $row['author_name']
            ),
            $rows
        );
    }
}