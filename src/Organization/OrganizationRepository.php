<?php

declare(strict_types=1);

namespace Src\Organization;

use Src\Core\Database;

final class OrganizationRepository
{
    public function __construct(private Database $db) {}

    public function findById(int $id): ?Organization
    {
        $sql = "SELECT * FROM tblOrganization WHERE id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Organization::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = false): array
    {
        $sql = "SELECT * FROM tblOrganization";
        $params = [];

        if ($onlyActive) {
            $sql .= " WHERE IsActive = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Name1 ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Organization => Organization::fromArray($row),
            $rows
        );
    }

    public function create(Organization $organization): int
    {
        $sql = "INSERT INTO tblOrganization
            (Logo, Name1, Name2, Homepage, EMail, Phone, Fax, Street, ZipCode, City, RegistryCourt, RegistryEntry, IsActive)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $data = $organization->toDatabaseArray();

        $this->db->execute($sql, [
            $data['Logo'],
            $data['Name1'],
            $data['Name2'],
            $data['Homepage'],
            $data['EMail'],
            $data['Phone'],
            $data['Fax'],
            $data['Street'],
            $data['ZipCode'],
            $data['City'],
            $data['RegistryCourt'],
            $data['RegistryEntry'],
            $data['IsActive'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(Organization $organization): bool
    {
        if ($organization->id === null) {
            return false;
        }

        $sql = "UPDATE tblOrganization SET
            Logo = ?,
            Name1 = ?,
            Name2 = ?,
            Homepage = ?,
            EMail = ?,
            Phone = ?,
            Fax = ?,
            Street = ?,
            ZipCode = ?,
            City = ?,
            RegistryCourt = ?,
            RegistryEntry = ?,
            IsActive = ?
            WHERE id = ?";

        $data = $organization->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['Logo'],
            $data['Name1'],
            $data['Name2'],
            $data['Homepage'],
            $data['EMail'],
            $data['Phone'],
            $data['Fax'],
            $data['Street'],
            $data['ZipCode'],
            $data['City'],
            $data['RegistryCourt'],
            $data['RegistryEntry'],
            $data['IsActive'],
            $organization->id,
        ]);
    }
}
