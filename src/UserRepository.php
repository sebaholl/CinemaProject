<?php
declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $db) {}

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT customer_id, first_name, last_name, email
             FROM Customer
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new User(
            (int) $row['customer_id'],
            $row['first_name'],
            $row['last_name'],
            $row['email']
        );
    }

    public function create(
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash
    ): User {
        $stmt = $this->db->prepare(
            'INSERT INTO Customer (first_name, last_name, email, password_hash)
             VALUES (:first_name, :last_name, :email, :password_hash)'
        );

        $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':password_hash' => $passwordHash,
        ]);

        return new User(
            (int) $this->db->lastInsertId(),
            $firstName,
            $lastName,
            $email
        );
    }
}