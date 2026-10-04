<?php

declare(strict_types=1);

namespace Src\User;

final class User
{

    public function __construct(
        public ?int $id = null,
        public ?int $person = null,
        public string $firstName = '',
        public string $lastName = '',
        public string $mail = '',
        public string $password = '',
        public int $defaultOrganization = 1,
        public string $photo = '',
        public int $isActive = 1,
        public int $isDeleted = 0,
        public int $isAdmin = 0,
        public string $lang = 'de',
        public string $avatar = ''
    ) {}

    public static function fromArray(array $row): self
    {

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            person: isset($row['Person']) ? (int) $row['Person'] : null,
            firstName: (string) ($row['FirstName'] ?? ''),
            lastName: (string) ($row['LastName'] ?? ''),
            mail: (string) ($row['Mail'] ?? ''),
            password: (string) $row['Password'] ?? '',
            defaultOrganization: (int) ($row['DefaultOrganization'] ?? 1),
            photo: (string) ($row['Photo'] ?? ''),
            isActive: (int) ($row['IsActive'] ?? 1),
            isDeleted: (int) ($row['IsDeleted'] ?? 0),
            isAdmin: (int) ($row['IsAdmin'] ?? 0),
            lang: (string) ($row['Lang'] ?? 'de'),
            avatar: (string) substr((string) ($row['FirstName'] ?? ''), 0, 1) . substr((string) ($row['LastName'] ?? ''), 0, 1)
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'ID' => $this->id,
            'Person' => $this->person,
            'FirstName' => $this->firstName,
            'LastName' => $this->lastName,
            'Mail' => $this->mail,
            'Password' => $this->password,
            'DefaultOrganization' => $this->defaultOrganization,
            'Photo' => $this->photo,
            'IsActive' => $this->isActive ? 1 : 0,
            'IsDeleted' => $this->isDeleted ? 1 : 0,
            'IsAdmin' => $this->isAdmin ? 1 : 0,
            'Lang' => $this->lang,
            'avatar' => substr($this->firstName, 0, 1) . substr($this->lastName, 0, 1)
        ];
    }
}
