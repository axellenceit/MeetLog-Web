<?php

declare(strict_types=1);

namespace Src\Organization;

final class OrganizationFunction
{
    public function __construct(
        public ?int $id = null,
        public int $organization = 0,
        public string $name = '',
        public ?string $description = null,
        public int $sortOrder = 0,
        public bool $isActive = true,
        public int $maySign = 0,
        public int $mayManageOrganization = 0,
        public int $mayManageMeetings = 0,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            organization: (int)($row['Organization'] ?? 0),
            name: (string)($row['Name'] ?? ''),
            description: $row['Description'] ?? null,
            sortOrder: (int)($row['SortOrder'] ?? 0),
            isActive: ((int) ($row['IsActive'] ?? 1)) === 1,
            maySign: ((int) ($row['MaySign'] ?? 0)),
            mayManageOrganization: ((int) ($row['MayManageOrganization'] ?? 0)),
            mayManageMeetings: ((int) ($row['MayManageMeetings'] ?? 0)),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Organization' => $this->organization,
            'Name' => $this->name,
            'Description' => $this->description,
            'SortOrder' => $this->sortOrder,
            'IsActive' => $this->isActive ? 1 : 0,
            'MaySign' => $this->maySign,
            'MayManageOrganization' => $this->mayManageOrganization,
            'MayManageMeetings' => $this->mayManageMeetings,
        ];
    }
}
