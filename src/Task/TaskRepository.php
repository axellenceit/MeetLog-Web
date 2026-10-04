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

    public function findByMeetingId(int $meetingId): array
    {
        $sql = "SELECT * FROM tblTask WHERE MeetingID = ? ORDER BY Deadline ASC";
        $rows = $this->db->query($sql, [$meetingId]);

        return array_map(
            static fn(array $row): Task => Task::fromArray($row),
            $rows
        );
    }

    public function findAllWithFilter(?string $title = null, ?int $userId = null, ?bool $isDone = null): array
    {
        $sql = "SELECT * FROM tblTask WHERE 1=1";
        $params = [];

        if ($title !== null) {
            $sql .= " AND Title LIKE ?";
            $params[] = '%' . $title . '%';
        }

        if ($userId !== null) {
            $sql .= " AND (CreatorUserID = ? OR AssigneeUserID = ?)";
            $params[] = $userId;
            $params[] = $userId;
        }

        if ($isDone !== null) {
            $sql .= " AND IsDone = ?";
            $params[] = (int)$isDone;
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
        $sql = "SELECT * FROM tblTask WHERE AssigneeUserID = ?";
        $params = [$userId];

        if ($onlyOpen) {
            $sql .= " AND IsDone = 0";
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
            (MeetingID, CreatorUserID, AssigneeUserID, Title, Description, Deadline, IsDone)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $this->db->execute($sql, [
            $data['MeetingID'],
            $data['CreatorUserID'],
            $data['AssigneeUserID'],
            $data['Title'],
            $data['Description'],
            $data['Deadline'],
            $data['IsDone'],
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
            MeetingID = ?,
            AssigneeUserID = ?,
            Title = ?,
            Description = ?,
            Deadline = ?,
            IsDone = ?
            WHERE id = ?";

        return $this->db->execute($sql, [
            $data['MeetingID'],
            $data['AssigneeUserID'],
            $data['Title'],
            $data['Description'],
            $data['Deadline'],
            $data['IsDone'],
            $task->id,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM tblTask WHERE id = ?", [$id]);
    }
}
