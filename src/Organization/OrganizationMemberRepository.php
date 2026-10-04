<?php

declare(strict_types=1);

namespace Src\Organization;

use Src\Core\Database;
use Src\Person\Person;
use Src\Person\PersonRepository;

final class OrganizationMemberRepository
{
    private PersonRepository $personRepository;

    public function __construct(private Database $db)
    {
        $this->personRepository = new PersonRepository($db);
    }

    private function baseSelect(): string
    {
        return "SELECT m.*, p.FirstName, p.LastName, p.Mail, p.Phone, p.Street, p.ZipCode, p.City, p.Country
                FROM tblOrganizationMember m
                LEFT JOIN tblPerson p ON p.id = m.Person";
    }

    public function findById(int $id): ?OrganizationMember
    {
        $sql = $this->baseSelect() . " WHERE m.id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? OrganizationMember::fromArray($row) : null;
    }

    /**
     * @return OrganizationMember[]
     */
    public function findAllByOrganization(int $organizationId, bool $onlyActive = false): array
    {
        $sql = $this->baseSelect() . " WHERE m.Organization = ? AND m.IsDeleted = 0";
        $params = [$organizationId];

        if ($onlyActive) {
            $sql .= " AND m.IsActive = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY p.LastName ASC, p.FirstName ASC, m.id ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): OrganizationMember => OrganizationMember::fromArray($row),
            $rows
        );
    }

    /**
     * @return OrganizationMember[]
     */
    public function findByUserID(int $userId): array
    {
        $sql = $this->baseSelect() . "
            WHERE m.User = ? AND m.IsDeleted = 0
            ORDER BY m.Organization ASC, p.LastName ASC, p.FirstName ASC";

        $rows = $this->db->query($sql, [$userId]);

        return array_map(
            static fn(array $row): OrganizationMember => OrganizationMember::fromArray($row),
            $rows
        );
    }

    public function findByUserAndOrganization(int $userId, int $organizationId): ?OrganizationMember
    {
        $sql = $this->baseSelect() . ' WHERE m.User = ? AND m.Organization = ? LIMIT 1';
        $row = $this->db->first($sql, [$userId, $organizationId]);

        return $row ? OrganizationMember::fromArray($row) : null;
    }

    public function findByPersonAndOrganization(int $personId, int $organizationId): ?OrganizationMember
    {
        $sql = $this->baseSelect() . ' WHERE m.Person = ? AND m.Organization = ? AND m.IsDeleted = 0 LIMIT 1';
        $row = $this->db->first($sql, [$personId, $organizationId]);

        return $row ? OrganizationMember::fromArray($row) : null;
    }

    public function addMember(OrganizationMember $member): int
    {
        $personId = $member->person;
        if ($personId === null) {
            $personId = $this->personRepository->create(new Person(
                firstName: $member->firstName,
                lastName: $member->lastName,
                mail: $member->mail,
                phone: $member->phone,
                street: $member->street,
                zipCode: $member->zipCode,
                city: $member->city,
                country: $member->country,
                isActive: $member->isActive,
                isDeleted: $member->isDeleted,
            ));
        }

        $sql = "INSERT INTO tblOrganizationMember
            (Organization, Person, User, Function, Note, IsActive, IsDeleted)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $data = $member->toDatabaseArray();

        $this->db->execute($sql, [
            $data['Organization'],
            $personId,
            $data['User'],
            $data['Function'],
            $data['Note'],
            $data['IsActive'],
            $data['IsDeleted'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateMember(OrganizationMember $member): bool
    {
        if ($member->id === null) {
            return false;
        }

        $existing = $this->findById($member->id);
        if ($existing === null) {
            return false;
        }

        $personId = $member->person ?? $existing->person;
        if ($personId === null) {
            $personId = $this->personRepository->create(new Person(
                firstName: $member->firstName,
                lastName: $member->lastName,
                mail: $member->mail,
                phone: $member->phone,
                street: $member->street,
                zipCode: $member->zipCode,
                city: $member->city,
                country: $member->country,
                isActive: $member->isActive,
                isDeleted: $member->isDeleted,
            ));
        } else {
            $person = $this->personRepository->findById($personId) ?? new Person(id: $personId);
            $person->firstName = $member->firstName ?? $person->firstName;
            $person->lastName = $member->lastName ?? $person->lastName;
            $person->mail = $member->mail ?? $person->mail;
            $person->phone = $member->phone ?? $person->phone;
            $person->street = $member->street ?? $person->street;
            $person->zipCode = $member->zipCode ?? $person->zipCode;
            $person->city = $member->city ?? $person->city;
            $person->country = $member->country ?? $person->country;
            $person->isActive = $member->isActive;
            $person->isDeleted = $member->isDeleted;
            $this->personRepository->update($person);
        }

        $sql = "UPDATE tblOrganizationMember SET
            Organization = ?,
            Person = ?,
            User = ?,
            Function = ?,
            Note = ?,
            IsActive = ?,
            IsDeleted = ?
            WHERE id = ?";

        $data = $member->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['Organization'],
            $personId,
            $data['User'],
            $data['Function'],
            $data['Note'],
            $data['IsActive'],
            $data['IsDeleted'],
            $member->id,
        ]);
    }

    public function update(OrganizationMember $member): bool
    {
        return $this->updateMember($member);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM tblOrganizationMember WHERE id = ?";

        return $this->db->execute($sql, [$id]);
    }

    public function getBoardOfDirectors(int $organizationId): array
    {
        $sql = "SELECT m.*, p.FirstName, p.LastName, p.Mail, p.Phone, p.Street, p.ZipCode, p.City, p.Country
            FROM tblOrganizationMember m
            LEFT JOIN tblPerson p ON p.id = m.Person
                INNER JOIN tblOrganizationFunction f ON m.Function = f.id
                WHERE m.Organization = ? AND m.IsDeleted = 0 AND f.MayManageOrganization = 1
            ORDER BY p.LastName ASC, p.FirstName ASC, m.id ASC";

        $rows = $this->db->query($sql, [$organizationId]);

        return array_map(
            static fn(array $row): OrganizationMember => OrganizationMember::fromArray($row),
            $rows
        );
    }
}
