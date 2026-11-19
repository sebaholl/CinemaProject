<?php
declare(strict_types=1);

final class OpeningHoursRepository
{
    public function __construct(private PDO $db) {}

    public function weekdays(): array
    {
        $stmt = $this->db->prepare(
            'SELECT weekday, opens_at, closes_at, is_closed
             FROM OpeningHours ORDER BY weekday'
        );
        $stmt->execute();

        $days = [];
        for ($number = 1; $number <= 7; $number++) {
            $days[$number] = [
                'weekday' => $number,
                'opens_at' => null,
                'closes_at' => null,
                'is_closed' => 1,
            ];
        }

        foreach ($stmt->fetchAll() as $row) {
            $days[(int) $row['weekday']] = $row;
        }

        return $days;
    }

    public function saveWeekdays(array $days): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO OpeningHours
                (weekday, opens_at, closes_at, is_closed)
             VALUES (:weekday, :opens_at, :closes_at, :is_closed)
             ON DUPLICATE KEY UPDATE
                opens_at = VALUES(opens_at),
                closes_at = VALUES(closes_at),
                is_closed = VALUES(is_closed)'
        );

        $this->db->beginTransaction();

        try {
            foreach ($days as $weekday => $day) {
                $stmt->execute([
                    ':weekday' => $weekday,
                    ':opens_at' => $day['opens_at'],
                    ':closes_at' => $day['closes_at'],
                    ':is_closed' => $day['is_closed'],
                ]);
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function specialDates(): array
    {
        $stmt = $this->db->prepare(
            'SELECT special_hours_id, special_date, label,
                    opens_at, closes_at, is_closed
             FROM SpecialHours
             ORDER BY special_date'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findSpecial(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT special_hours_id, special_date, label,
                    opens_at, closes_at, is_closed
             FROM SpecialHours
             WHERE special_hours_id = :id'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function saveSpecial(
        ?int $id,
        string $date,
        string $label,
        ?string $opens,
        ?string $closes,
        int $closed
    ): void {
        if ($id === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO SpecialHours
                    (special_date, label, opens_at, closes_at, is_closed)
                 VALUES (:date, :label, :opens, :closes, :closed)'
            );
            $stmt->execute([
                ':date' => $date,
                ':label' => $label,
                ':opens' => $opens,
                ':closes' => $closes,
                ':closed' => $closed,
            ]);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE SpecialHours
             SET special_date = :date, label = :label,
                 opens_at = :opens, closes_at = :closes,
                 is_closed = :closed
             WHERE special_hours_id = :id'
        );
        $stmt->execute([
            ':date' => $date,
            ':label' => $label,
            ':opens' => $opens,
            ':closes' => $closes,
            ':closed' => $closed,
            ':id' => $id,
        ]);
    }

    public function deleteSpecial(int $id): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM SpecialHours WHERE special_hours_id = :id'
        );
        $stmt->execute([':id' => $id]);
    }
}