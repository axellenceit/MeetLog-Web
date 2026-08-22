<?php
declare(strict_types=1);

namespace Src\Log;

final class Log
{
    public function __construct(
        public ?int $id = null,
        public int $userId,
        public string $action,
        public string $severity,
        public string $timestamp,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            userId: (int)($row['UserId'] ?? 0),
            action: (string)($row['Action'] ?? ''),
            severity: (string)($row['Severity'] ?? ''),
            timestamp: (string)($row['Timestamp'] ?? ''),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'UserId' => $this->userId,
            'Action' => $this->action,
            'Severity' => $this->severity,
            'Timestamp' => $this->timestamp,
        ];
    }
}