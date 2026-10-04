<?php

declare(strict_types=1);

namespace Src\Meeting;

final class Meeting
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_LIVE = 'live';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        public ?int $id = null,
        public ?int $organizationId = null,
        public ?int $creatorUserId = null,
        public string $title = '',
        public ?string $date = null,        // später evtl. DateTimeImmutable
        public ?string $startTime = null,   // HH:MM:SS
        public ?string $endTime = null,     // HH:MM:SS
        public ?string $location = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $status = null,
        public bool $isActive = true,
        public bool $isPublished = false,
        public ?string $publicCode = null,
    ) {}

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PLANNED,
            self::STATUS_LIVE,
            self::STATUS_FINISHED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            organizationId: isset($row['OrganizationID']) ? (int)$row['OrganizationID'] : null,
            creatorUserId: isset($row['CreatorUserID']) ? (int)$row['CreatorUserID'] : null,
            title: (string)($row['Title'] ?? ''),
            date: $row['Date'] ?? null,
            startTime: $row['StartTime'] ?? null,
            endTime: $row['EndTime'] ?? null,
            location: $row['Location'] ?? null,
            createdAt: $row['CreatedAt'] ?? null,
            updatedAt: $row['UpdatedAt'] ?? null,
            status: $row['Status'] ?? null,
            isActive: (bool)($row['IsActive'] ?? true),
            isPublished: (bool)($row['IsPublished'] ?? false),
            publicCode: $row['PublicCode'] ?? null,
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'OrganizationID' => $this->organizationId,
            'CreatorUserID' => $this->creatorUserId,
            'Title' => $this->title,
            'Date' => $this->date,
            'StartTime' => $this->startTime,
            'EndTime' => $this->endTime,
            'Location' => $this->location,
            'Status' => $this->status,
            'IsActive' => $this->isActive ? 1 : 0,
            'IsPublished' => $this->isPublished ? 1 : 0,
            'PublicCode' => $this->publicCode,
            'CreatedAt' => $this->createdAt,
            'UpdatedAt' => $this->updatedAt,
        ];
    }
}
