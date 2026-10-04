<?php

declare(strict_types=1);

namespace Src\Organization;

final class Organization
{
    public function __construct(
        public ?int $id = null,
        public string $name1 = '',
        public ?string $name2 = null,
        public ?string $homepage = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $fax = null,
        public ?string $street = null,
        public ?string $zipCode = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $registryCourt = null,
        public ?string $registryEntry = null,
        public ?string $logo = null,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            name1: (string)($row['Name1'] ?? ''),
            name2: $row['Name2'] ?? null,
            homepage: $row['Homepage'] ?? null,
            email: $row['EMail'] ?? null,
            phone: $row['Phone'] ?? null,
            fax: $row['Fax'] ?? null,
            street: $row['Street'] ?? null,
            zipCode: $row['ZipCode'] ?? null,
            city: $row['City'] ?? null,
            country: $row['Country'] ?? null,
            registryCourt: $row['RegistryCourt'] ?? null,
            registryEntry: $row['RegistryEntry'] ?? null,
            logo: $row['Logo'] ?? null,
            isActive: (bool)($row['IsActive'] ?? true),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Logo' => $this->logo,
            'Name1' => $this->name1,
            'Name2' => $this->name2,
            'Homepage' => $this->homepage,
            'EMail' => $this->email,
            'Phone' => $this->phone,
            'Fax' => $this->fax,
            'Street' => $this->street,
            'ZipCode' => $this->zipCode,
            'City' => $this->city,
            'Country' => $this->country,
            'RegistryCourt' => $this->registryCourt,
            'RegistryEntry' => $this->registryEntry,
            'IsActive' => $this->isActive ? 1 : 0,
        ];
    }
}
