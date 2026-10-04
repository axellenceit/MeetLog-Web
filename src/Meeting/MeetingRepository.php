<?php

declare(strict_types=1);

namespace Src\Meeting;

use Src\Core\Database;

final class MeetingRepository
{
    public function __construct(private Database $db) {}

    public function findById(int $id): ?Meeting
    {
        $sql = "SELECT * FROM tblMeeting WHERE id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Meeting::fromArray($row) : null;
    }

    public function findByPublicCode(string $publicCode): ?Meeting
    {
        $sql = "SELECT * FROM tblMeeting WHERE PublicCode = ? LIMIT 1";
        $row = $this->db->first($sql, [$publicCode]);

        return $row ? Meeting::fromArray($row) : null;
    }


    public function findAll(bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM tblMeeting";
        $params = [];

        if ($onlyActive) {
            $sql .= " WHERE IsActive = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Date ASC, StartTime ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Meeting => Meeting::fromArray($row),
            $rows
        );
    }

    public function findLastMeetings(int $count): array
    {
        $sql = "SELECT * FROM tblMeeting ORDER BY id DESC LIMIT ?";
        $rows = $this->db->query($sql, [$count]);

        return array_map(
            static fn(array $row): Meeting => Meeting::fromArray($row),
            $rows
        );
    }

    public function findNextMeetings(int $count): array
    {
        $sql = "SELECT * FROM tblMeeting WHERE Date >= CURDATE() AND IsActive = 1 ORDER BY Date ASC LIMIT ?";
        $rows = $this->db->query($sql, [$count]);

        return array_map(
            static fn(array $row): Meeting => Meeting::fromArray($row),
            $rows
        );
    }

    public function findOpenMeetings(int $count): array
    {
        $sql = "SELECT * FROM tblMeeting WHERE IsActive = 1 ORDER BY Date ASC LIMIT ?";
        $rows = $this->db->query($sql, [$count]);

        return array_map(
            static fn(array $row): Meeting => Meeting::fromArray($row),
            $rows
        );
    }

    public function findByOrganizationId(int $organizationId, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM tblMeeting WHERE OrganizationID = ?";
        $params = [$organizationId];

        if ($onlyActive) {
            $sql .= " AND IsActive = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Date DESC, StartTime DESC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Meeting => Meeting::fromArray($row),
            $rows
        );
    }

    public function create(Meeting $meeting): int
    {
        $sql = "INSERT INTO tblMeeting
            (OrganizationID, CreatorUserID, Title, Date, StartTime, EndTime, Location, Status, IsActive, IsPublished)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $data = $meeting->toDatabaseArray();

        $this->db->execute($sql, [
            $data['OrganizationID'],
            $data['CreatorUserID'],
            $data['Title'],
            $data['Date'],
            $data['StartTime'],
            $data['EndTime'],
            $data['Location'],
            $data['Status'],
            $data['IsActive'],
            $data['IsPublished'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(Meeting $meeting): bool
    {
        if ($meeting->id === null) {
            return false;
        }

        $sql = "UPDATE tblMeeting SET
            Title = ?,
            Date = ?,
            StartTime = ?,
            EndTime = ?,
            Location = ?,
            Status = ?,
            IsActive = ?,
            IsPublished = ?,
            PublicCode = ?
            WHERE id = ?";

        $data = $meeting->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['Title'],
            $data['Date'],
            $data['StartTime'],
            $data['EndTime'],
            $data['Location'],
            $data['Status'],
            $data['IsActive'],
            $data['IsPublished'],
            $data['PublicCode'],
            $meeting->id,
        ]);
    }
}
