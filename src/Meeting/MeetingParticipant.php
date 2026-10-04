<?php

declare(strict_types=1);

namespace Src\Meeting;

final class MeetingParticipant
{
    public function __construct(
        public ?int $id = null,
        public int $meeting = 0,
        public ?int $person = null,
        public ?int $organizationMember = null,
        public ?int $user = null,
        public ?int $function = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $mail = null,
        public ?string $phone = null,
        public bool $isInvited = true,
        public bool $isPresent = false,
        public bool $isExcused = false,
        public bool $isAbsent = false,
        public bool $isActive = true,
        public bool $maySign = false,
        public bool $isChair = false,
        public bool $isMinuteTaker = false
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            meeting: (int)($row['Meeting'] ?? 0),
            person: isset($row['Person']) ? (int)$row['Person'] : null,
            organizationMember: isset($row['OrganizationMember']) ? (int)$row['OrganizationMember'] : null,
            user: isset($row['User']) ? (int)$row['User'] : null,
            function: isset($row['Function']) ? (int)$row['Function'] : null,
            firstName: $row['FirstName'] ?? null,
            lastName: $row['LastName'] ?? null,
            mail: $row['Mail'] ?? null,
            phone: $row['Phone'] ?? null,
            isInvited: (bool)($row['IsInvited'] ?? true),
            isPresent: (bool)($row['IsPresent'] ?? false),
            isExcused: (bool)($row['IsExcused'] ?? false),
            isAbsent: (bool)($row['IsAbsent'] ?? false),
            isActive: (bool)($row['IsActive'] ?? true),
            maySign: (bool)($row['MaySign'] ?? false),
            isChair: (bool)($row['IsChair'] ?? false),
            isMinuteTaker: (bool)($row['IsMinuteTaker'] ?? false),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Meeting' => $this->meeting,
            'Person' => $this->person,
            'OrganizationMember' => $this->organizationMember,
            'User' => $this->user,
            'Function' => $this->function,
            'FirstName' => $this->firstName,
            'LastName' => $this->lastName,
            'Mail' => $this->mail,
            'Phone' => $this->phone,
            'IsInvited' => $this->isInvited ? 1 : 0,
            'IsPresent' => $this->isPresent ? 1 : 0,
            'IsExcused' => $this->isExcused ? 1 : 0,
            'IsAbsent' => $this->isAbsent ? 1 : 0,
            'IsActive' => $this->isActive ? 1 : 0,
            'MaySign' => $this->maySign ? 1 : 0,
            'IsChair' => $this->isChair ? 1 : 0,
            'IsMinuteTaker' => $this->isMinuteTaker ? 1 : 0,
        ];
    }
}
