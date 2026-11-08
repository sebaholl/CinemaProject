<?php
declare(strict_types=1);

final class ProfileRepository
{
    public function __construct(private PDO $db) {}

    public function findCustomer(int $customerId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT customer_id, first_name, last_name, email, created_at
             FROM Customer
             WHERE customer_id = :customer_id'
        );

        $stmt->execute([':customer_id' => $customerId]);
        $customer = $stmt->fetch();

        return $customer === false ? null : $customer;
    }

    public function updateDetails(
    int $customerId,
    string $firstName,
    string $lastName,
    string $email
): void {
    $stmt = $this->db->prepare(
        'UPDATE Customer
         SET first_name = :first_name,
             last_name = :last_name,
             email = :email
         WHERE customer_id = :customer_id'
    );

    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
        ':customer_id' => $customerId,
    ]);
}

public function findPasswordHash(int $customerId): ?string
{
    $stmt = $this->db->prepare(
        'SELECT password_hash
         FROM Customer
         WHERE customer_id = :customer_id'
    );
    $stmt->execute([':customer_id' => $customerId]);

    $hash = $stmt->fetchColumn();
    return $hash === false ? null : (string) $hash;
}

public function updatePassword(
    int $customerId,
    string $previousHash,
    string $newHash
): bool {
    $stmt = $this->db->prepare(
        'UPDATE Customer
         SET password_hash = :new_hash
         WHERE customer_id = :customer_id
           AND password_hash = :previous_hash'
    );
    $stmt->execute([
        ':new_hash' => $newHash,
        ':customer_id' => $customerId,
        ':previous_hash' => $previousHash,
    ]);

    return $stmt->rowCount() === 1;
}


public function findBookings(int $customerId): array
{
    $stmt = $this->db->prepare(
        "SELECT v.booking_id, v.movie_title, v.screening_date,
                v.screening_time, v.hall_name, v.status,
                v.price, v.invoice_id, v.invoice_total,
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(s.row_label, s.seat_number)
                        ORDER BY s.row_label, s.seat_number
                        SEPARATOR ', '
                    )
                    FROM BookingSeat bs
                    JOIN Seat s ON s.seat_id = bs.seat_id
                    WHERE bs.booking_id = v.booking_id
                ) AS seat_labels
         FROM v_booking_overview v
         WHERE v.customer_id = :customer_id
         ORDER BY v.booking_date DESC, v.booking_id DESC"
    );

    $stmt->execute([':customer_id' => $customerId]);
    return $stmt->fetchAll();
}

}