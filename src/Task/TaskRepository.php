<?php

declare(strict_types=1);

namespace Src\Task;

final class TaskRepository
{
    public function __construct(private \Src\Core\Database $db) {}

    public function findById(int $id): ?Task
    {
        $sql = "SELECT * FROM tblTask WHERE id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Task::fromArray($row) : null;
    }

    public function findAllByBesprechungId(int $besprechungId): array
    {
        $sql = "SELECT * FROM tblTask WHERE BesprechungID = ? ORDER BY Deadline ASC";
        $rows = $this->db->query($sql, [$besprechungId]);

        return array_map(
            static fn(array $row): Task => Task::fromArray($row),
            $rows
        );
    }

    public function findAllWithFilter(?string $titel = null, ?int $userId = null, ?bool $istErledigt = null): array
    {
        $sql = "SELECT * FROM tblTask WHERE 1=1";
        $params = [];

        if ($titel !== null) {
            $sql .= " AND Titel LIKE ?";
            $params[] = '%' . $titel . '%';
        }

        if ($userId !== null) {
            $sql .= " AND (ErstellerUserID = ? OR VerantwortlicherUserID = ?)";
            $params[] = $userId;
            $params[] = $userId;
        }

        if ($istErledigt !== null) {
            $sql .= " AND istErledigt = ?";
            $params[] = (int)$istErledigt;
        }

        $sql .= " ORDER BY Deadline ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Task => Task::fromArray($row),
            $rows
        );
    }

    public function findAllByUserId(int $userId, bool $onlyOpen = true): array
    {
        $sql = "SELECT * FROM tblTask WHERE VerantwortlicherUserID = ?";
        $params = [$userId];

        if ($onlyOpen) {
            $sql .= " AND istErledigt = 0";
        }

        $sql .= " ORDER BY Deadline ASC";
        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Task => Task::fromArray($row),
            $rows
        );
    }

    public function create(Task $task): int
    {
        $data = $task->toDatabaseArray();
        $sql = "INSERT INTO tblTask
            (BesprechungID, ErstellerUserID, VerantwortlicherUserID, Titel, Beschreibung, Deadline, istErledigt)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $this->db->execute($sql, [
            $data['BesprechungID'],
            $data['ErstellerUserID'],
            $data['VerantwortlicherUserID'],
            $data['Titel'],
            $data['Beschreibung'],
            $data['Deadline'],
            $data['istErledigt'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(Task $task): bool
    {
        if ($task->id === null) {
            return false;
        }

        $data = $task->toDatabaseArray();
        $sql = "UPDATE tblTask SET
            BesprechungID = ?,
            VerantwortlicherUserID = ?,
            Titel = ?,
            Beschreibung = ?,
            Deadline = ?,
            istErledigt = ?
            WHERE id = ?";

        return $this->db->execute($sql, [
            $data['BesprechungID'],
            $data['VerantwortlicherUserID'],
            $data['Titel'],
            $data['Beschreibung'],
            $data['Deadline'],
            $data['istErledigt'],
            $task->id,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM tblTask WHERE id = ?", [$id]);
    }
}
