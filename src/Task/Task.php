<?php

declare(strict_types=1);

namespace Src\Task;

final class Task
{
    public function __construct(
        public ?int $id = null,
        public ?int $besprechungId = null,
        public ?int $erstellerUserId = null,
        public ?int $verantwortlicherUserId = null, 
        public string $titel = '',
        public ?string $beschreibung = null,
        public ?string $deadline = null, // später evtl. DateTimeImmutable
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public bool $istErledigt = false,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            besprechungId: isset($row['BesprechungID']) ? (int)$row['BesprechungID'] : null,
            erstellerUserId: isset($row['ErstellerUserID']) ? (int)$row['ErstellerUserID'] : null,
            verantwortlicherUserId: isset($row['VerantwortlicherUserID']) ? (int)$row['VerantwortlicherUserID'] : null,
            titel: (string)($row['Titel'] ?? ''),
            beschreibung: $row['Beschreibung'] ?? null,
            deadline: $row['Deadline'] ?? null,
            createdAt: $row['erstelltAm'] ?? null,
            updatedAt: $row['geaendertAm'] ?? null,
            istErledigt: (bool)($row['istErledigt'] ?? false),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'BesprechungID' => $this->besprechungId,
            'ErstellerUserID' => $this->erstellerUserId,
            'VerantwortlicherUserID' => $this->verantwortlicherUserId,
            'Titel' => $this->titel,
            'Beschreibung' => $this->beschreibung,
            'Deadline' => $this->deadline,
            'istErledigt' => $this->istErledigt ? 1 : 0,
            'erstelltAm' => $this->createdAt,
            'geaendertAm' => $this->updatedAt,
        ];
    }
}