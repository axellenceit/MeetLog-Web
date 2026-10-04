<?php

declare(strict_types=1);

namespace Src\Meeting;

use Src\Core\Database;

final class MeetingPointRepository
{
    public function __construct(private Database $db) {}

    public function findById(int $id): ?MeetingPoint
    {
        $sql = "SELECT * FROM tblMeetingPoint WHERE id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? MeetingPoint::fromArray($row) : null;
    }

    public function findByMeetingId(int $meetingId, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM tblMeetingPoint WHERE MeetingID = ?";
        $params = [$meetingId];

        if ($onlyActive) {
            $sql .= " AND IsActive = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Position ASC, id ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): MeetingPoint => MeetingPoint::fromArray($row),
            $rows
        );
    }

    public function findNextPosition(int $meetingId, int $parentPosition = 0): int
    {
        $sql = "SELECT MAX(Position) AS maxPosition
            FROM tblMeetingPoint
            WHERE MeetingID = ? AND ParentPosition = ?";

        $row = $this->db->first($sql, [$meetingId, $parentPosition]);
        $lastPosition = isset($row['maxPosition']) ? (int)$row['maxPosition'] : 0;

        return $lastPosition > 0 ? $lastPosition + 1 : 1;
    }

    public function insert(MeetingPoint $point): int
    {
        if (!$point->isValidType()) {
            throw new \InvalidArgumentException('Ungültiger Tagesordnungspunkt-Typ.');
        }

        $sql = "INSERT INTO tblMeetingPoint
            (MeetingID, Position, ParentPosition, Title, Description, Type, Result, ResultApproval, ResultRejection, ResultAbstention, IsActive)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $data = $point->toDatabaseArray();

        $this->db->execute($sql, [
            $data['MeetingID'],
            $data['Position'],
            $data['ParentPosition'],
            $data['Title'],
            $data['Description'],
            $data['Type'],
            $data['Result'],
            $data['ResultApproval'],
            $data['ResultRejection'],
            $data['ResultAbstention'],
            $data['IsActive'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(MeetingPoint $point): bool
    {
        if ($point->id === null || !$point->isValidType()) {
            return false;
        }

        $sql = "UPDATE tblMeetingPoint SET
            MeetingID = ?,
            Position = ?,
            ParentPosition = ?,
            Title = ?,
            Description = ?,
            Type = ?,
            Result = ?,
            ResultApproval = ?,
            ResultRejection = ?,
            ResultAbstention = ?,
            IsActive = ?
            WHERE id = ?";

        $data = $point->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['MeetingID'],
            $data['Position'],
            $data['ParentPosition'],
            $data['Title'],
            $data['Description'],
            $data['Type'],
            $data['Result'],
            $data['ResultApproval'],
            $data['ResultRejection'],
            $data['ResultAbstention'],
            $data['IsActive'],
            $point->id,
        ]);
    }
}
