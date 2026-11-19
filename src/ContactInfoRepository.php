<?php
declare(strict_types=1);

final class ContactInfoRepository
{
    public function __construct(private PDO $db) {}

    public function findAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT contact_info_id, kind, label, value
             FROM ContactInfo
             ORDER BY kind, contact_info_id'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT contact_info_id, kind, label, value
             FROM ContactInfo WHERE contact_info_id = :id'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(string $kind, string $label, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ContactInfo (kind, label, value)
             VALUES (:kind, :label, :value)'
        );
        $stmt->execute([
            ':kind' => $kind,
            ':label' => $label,
            ':value' => $value,
        ]);
    }

    public function update(int $id, string $kind, string $label, string $value): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ContactInfo
             SET kind = :kind, label = :label, value = :value
             WHERE contact_info_id = :id'
        );
        $stmt->execute([
            ':kind' => $kind,
            ':label' => $label,
            ':value' => $value,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM ContactInfo WHERE contact_info_id = :id'
        );
        $stmt->execute([':id' => $id]);
    }
}