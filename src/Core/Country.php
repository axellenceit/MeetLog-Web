<?php

declare(strict_types=1);

namespace Src\Core;

final class Country
{
    public function __construct(
        public ?int $countryId = null,
        public string $isoAlpha2 = '',
        public ?string $isoAlpha3 = null,
        public ?string $isoNumeric = null,
        public string $nameEn = '',
        public ?string $nameDe = null,
        public bool $isActive = true,
        public int $sortOrder = 0,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            countryId: isset($row['countryId']) ? (int)$row['countryId'] : null,
            isoAlpha2: (string)($row['isoAlpha2'] ?? ''),
            isoAlpha3: isset($row['isoAlpha3']) ? (string)$row['isoAlpha3'] : null,
            isoNumeric: isset($row['isoNumeric']) ? (string)$row['isoNumeric'] : null,
            nameEn: (string)($row['nameEn'] ?? ''),
            nameDe: isset($row['nameDe']) ? (string)$row['nameDe'] : null,
            isActive: (bool)($row['isActive'] ?? true),
            sortOrder: isset($row['sortOrder']) ? (int)$row['sortOrder'] : 0,
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'countryId' => $this->countryId,
            'isoAlpha2' => $this->isoAlpha2,
            'isoAlpha3' => $this->isoAlpha3,
            'isoNumeric' => $this->isoNumeric,
            'nameEn' => $this->nameEn,
            'nameDe' => $this->nameDe,
            'isActive' => $this->isActive ? 1 : 0,
            'sortOrder' => $this->sortOrder,
        ];
    }
}
