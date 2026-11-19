<?php
declare(strict_types=1);

final class CompanyRepository
{
    public function __construct(private PDO $db) {}

    public function find(): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT company_info_id, description
             FROM CompanyInfo
             ORDER BY company_info_id
             LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function saveDescription(string $description): void
    {
        $company = $this->find();

        if ($company === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO CompanyInfo
                    (opening_hours, phone, address, email, description)
                 VALUES (:hours, :phone, :address, :email, :description)'
            );
            $stmt->execute([
                ':hours' => '',
                ':phone' => '',
                ':address' => '',
                ':email' => '',
                ':description' => $description,
            ]);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE CompanyInfo
             SET description = :description
             WHERE company_info_id = :id'
        );
        $stmt->execute([
            ':description' => $description,
            ':id' => $company['company_info_id'],
        ]);
    }
}