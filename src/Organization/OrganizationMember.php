<?php

declare(strict_types=1);

namespace Src\Organization;

final class OrganizationMember
{
    public function __construct(
        public ?int $id = null,
        public int $organization = 0,
        public ?int $person = null,
        public ?int $user = null,
        public ?int $function = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $mail = null,
        public ?string $phone = null,
        public ?string $street = null,
        public ?string $zipCode = null,
        public ?string $city = null,
        public ?int $country = null,
        public ?string $note = null,
        public int $isActive = 1,
        public int $isDeleted = 0
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            organization: (int)($row['Organization'] ?? 0),
            person: isset($row['Person']) ? (int)$row['Person'] : null,
            user: isset($row['User']) ? (int)$row['User'] : null,
            function: isset($row['Function']) ? (int)$row['Function'] : null,
            firstName: $row['FirstName'] ?? null,
            lastName: $row['LastName'] ?? null,
            mail: $row['Mail'] ?? null,
            phone: $row['Phone'] ?? null,
            street: $row['Street'] ?? null,
            zipCode: $row['ZipCode'] ?? null,
            city: $row['City'] ?? null,
            country: isset($row['Country']) ? (int)$row['Country'] : null,
            note: $row['Note'] ?? null,
            isActive: (int)($row['IsActive'] ?? 1),
            isDeleted: (int)($row['IsDeleted'] ?? 0)
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Organization' => $this->organization,
            'Person' => $this->person,
            'User' => $this->user,
            'Function' => $this->function,
            'FirstName' => $this->firstName,
            'LastName' => $this->lastName,
            'Mail' => $this->mail,
            'Phone' => $this->phone,
            'Street' => $this->street,
            'ZipCode' => $this->zipCode,
            'City' => $this->city,
            'Country' => $this->country,
            'Note' => $this->note,
            'IsActive' => $this->isActive,
            'IsDeleted' => $this->isDeleted
        ];
    }
}
