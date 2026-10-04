<?php

declare(strict_types=1);

namespace Src\Person;

final class Person
{
    public function __construct(
        public ?int $id = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $mail = null,
        public ?string $phone = null,
        public ?string $street = null,
        public ?string $zipCode = null,
        public ?string $city = null,
        public ?int $country = null,
        public int $isActive = 1,
        public int $isDeleted = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            firstName: $row['FirstName'] ?? null,
            lastName: $row['LastName'] ?? null,
            mail: $row['Mail'] ?? null,
            phone: $row['Phone'] ?? null,
            street: $row['Street'] ?? null,
            zipCode: $row['ZipCode'] ?? null,
            city: $row['City'] ?? null,
            country: isset($row['Country']) ? (int) $row['Country'] : null,
            isActive: (int) ($row['IsActive'] ?? 1),
            isDeleted: (int) ($row['IsDeleted'] ?? 0),
            createdAt: $row['CreatedAt'] ?? null,
            updatedAt: $row['UpdatedAt'] ?? null,
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'FirstName' => $this->firstName,
            'LastName' => $this->lastName,
            'Mail' => $this->mail,
            'Phone' => $this->phone,
            'Street' => $this->street,
            'ZipCode' => $this->zipCode,
            'City' => $this->city,
            'Country' => $this->country,
            'IsActive' => $this->isActive,
            'IsDeleted' => $this->isDeleted,
        ];
    }
}
