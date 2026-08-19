<?php

declare(strict_types=1);

namespace Src\Besprechung;

final class Besprechung
{
    public function __construct(
        public ?int $id = null,
        public ?int $organisationId = null,
        public ?int $erstellerUserId = null,
        public string $titel = '',
        public ?string $datum = null,        // später evtl. DateTimeImmutable
        public ?string $beginn = null,       // HH:MM:SS
        public ?string $ende = null,         // HH:MM:SS
        public ?string $ort = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $status = null,
        public bool $istAktiviert = true,
        public bool $istVeröffentlicht = false,
        public ?string $public_code = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            organisationId: isset($row['OrganisationID']) ? (int)$row['OrganisationID'] : null,
            erstellerUserId: isset($row['ErstellerUserID']) ? (int)$row['ErstellerUserID'] : null,
            titel: (string)($row['Titel'] ?? ''),
            datum: $row['Datum'] ?? null,
            beginn: $row['Beginn'] ?? null,
            ende: $row['Ende'] ?? null,
            ort: $row['Ort'] ?? null,
            createdAt: $row['erstelltAm'] ?? null,
            updatedAt: $row['geaendertAm'] ?? null,
            status: $row['Status'] ?? null,
            istAktiviert: (bool)($row['istAktiviert'] ?? true),
            istVeröffentlicht: (bool)($row['istVeröffentlicht'] ?? false),
            public_code: $row['public_code'] ?? null,
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'OrganisationID' => $this->organisationId,
            'ErstellerUserID' => $this->erstellerUserId,
            'Titel' => $this->titel,
            'Datum' => $this->datum,
            'Beginn' => $this->beginn,
            'Ende' => $this->ende,
            'Ort' => $this->ort,
            'Status' => $this->status,
            'istAktiviert' => $this->istAktiviert ? 1 : 0,
            'istVeröffentlicht' => $this->istVeröffentlicht ? 1 : 0,
            'public_code' => $this->public_code,
            'erstelltAm' => $this->createdAt,
            'geaendertAm' => $this->updatedAt,
        ];
    }
}
