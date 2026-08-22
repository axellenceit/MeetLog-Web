<?php
declare(strict_types=1);

namespace Src\Log;

use Src\Core\Database;

final class LogRepository
{
    public function __construct(private Database $db)
    {
    }

    public function create(Log $log): ?Log
    {
        $sql = "INSERT INTO tblLog (UserId, Action, Severity, Timestamp) VALUES (?, ?, ?, ?)";
        $data = $log->toDatabaseArray();

        $this->db->execute($sql, [
            $data['UserId'],
            $data['Action'],
            $data['Severity'],
            $data['Timestamp'],
        ]);

        $id = (int)$this->db->lastInsertId();
        return $id ? new Log(id: $id, userId: $data['UserId'], action: $data['Action'], severity: $data['Severity'], timestamp: $data['Timestamp']) : null;
    }

    public function findAll(int $limit = 100): array
    {
        $sql = "SELECT * FROM tblLog ORDER BY Timestamp DESC LIMIT ?";
        $rows = $this->db->query($sql, [$limit]);

        return array_map(
            static fn (array $row): Log => Log::fromArray($row),
            $rows
        );
    }

    public function clearLogs(): void
    {
        $sql = "DELETE FROM tblLog";
        $this->db->execute($sql);
    }
}