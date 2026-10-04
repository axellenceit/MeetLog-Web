<?php

declare(strict_types=1);

namespace Src\Meeting;

final class MeetingPoint
{
    public const TYPE_INFORMATION = 'information';
    public const TYPE_DISCUSSION = 'discussion';
    public const TYPE_VOTING = 'voting';

    public function __construct(
        public ?int $id = null,
        public ?int $meetingId = null,
        public int $position = 1,
        public int $parentPosition = 0,
        public string $title = '',
        public ?string $description = null,
        public string $type = self::TYPE_INFORMATION,
        public ?string $result = null,
        public ?int $resultApproval = null,
        public ?int $resultRejection = null,
        public ?int $resultAbstention = null,
        public bool $isActive = true,
    ) {}

    public static function allowedTypes(): array
    {
        return [
            self::TYPE_INFORMATION,
            self::TYPE_DISCUSSION,
            self::TYPE_VOTING,
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            meetingId: isset($row['MeetingID']) ? (int)$row['MeetingID'] : null,
            position: isset($row['Position']) ? (int)$row['Position'] : 1,
            parentPosition: isset($row['ParentPosition']) ? (int)$row['ParentPosition'] : 0,
            title: (string)($row['Title'] ?? ''),
            description: $row['Description'] ?? null,
            type: (string)($row['Type'] ?? self::TYPE_INFORMATION),
            result: $row['Result'] ?? null,
            resultApproval: $row['ResultApproval'] ?? null,
            resultRejection: $row['ResultRejection'] ?? null,
            resultAbstention: $row['ResultAbstention'] ?? null,
            isActive: (bool)($row['IsActive'] ?? true),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'MeetingID' => $this->meetingId,
            'Position' => $this->position,
            'ParentPosition' => $this->parentPosition,
            'Title' => $this->title,
            'Description' => $this->description,
            'Type' => $this->type,
            'Result' => $this->result,
            'ResultApproval' => $this->resultApproval,
            'ResultRejection' => $this->resultRejection,
            'ResultAbstention' => $this->resultAbstention,
            'IsActive' => $this->isActive ? 1 : 0,
        ];
    }

    public function isValidType(): bool
    {
        return in_array($this->type, self::allowedTypes(), true);
    }
}
