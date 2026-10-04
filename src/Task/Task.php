<?php

declare(strict_types=1);

namespace Src\Task;

final class Task
{
    public function __construct(
        public ?int $id = null,
        public ?int $meetingId = null,
        public ?int $creatorUserId = null,
        public ?int $assigneeUserId = null,
        public string $title = '',
        public ?string $description = null,
        public ?string $deadline = null, // später evtl. DateTimeImmutable
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public bool $isDone = false,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            meetingId: isset($row['MeetingID']) ? (int)$row['MeetingID'] : null,
            creatorUserId: isset($row['CreatorUserID']) ? (int)$row['CreatorUserID'] : null,
            assigneeUserId: isset($row['AssigneeUserID']) ? (int)$row['AssigneeUserID'] : null,
            title: (string)($row['Title'] ?? ''),
            description: $row['Description'] ?? null,
            deadline: $row['Deadline'] ?? null,
            createdAt: $row['CreatedAt'] ?? null,
            updatedAt: $row['UpdatedAt'] ?? null,
            isDone: (bool)($row['IsDone'] ?? false),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'MeetingID' => $this->meetingId,
            'CreatorUserID' => $this->creatorUserId,
            'AssigneeUserID' => $this->assigneeUserId,
            'Title' => $this->title,
            'Description' => $this->description,
            'Deadline' => $this->deadline,
            'IsDone' => $this->isDone ? 1 : 0,
            'CreatedAt' => $this->createdAt,
            'UpdatedAt' => $this->updatedAt,
        ];
    }
}
