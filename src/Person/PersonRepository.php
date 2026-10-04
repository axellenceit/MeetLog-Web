<?php

declare(strict_types=1);

namespace Src\Person;

use Src\Core\Database;

final class PersonRepository
{
    public function __construct(private Database $db) {}

    public function findAll(bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM tblPerson WHERE IsDeleted = 0';

        if ($onlyActive) {
            $sql .= ' AND IsActive = 1';
        }
        $rows = $this->db->query($sql);

        return array_map(fn($row) => Person::fromArray($row), $rows);
    }

    public function findById(int $id): ?Person
    {
        $sql = 'SELECT * FROM tblPerson WHERE id = ? LIMIT 1';
        $row = $this->db->first($sql, [$id]);

        return $row ? Person::fromArray($row) : null;
    }

    public function findByMail(string $mail): ?Person
    {
        $sql = 'SELECT * FROM tblPerson WHERE Mail = ? AND IsDeleted = 0 LIMIT 1';
        $row = $this->db->first($sql, [$mail]);

        return $row ? Person::fromArray($row) : null;
    }

    public function create(Person $person): int
    {
        $data = $person->toDatabaseArray();

        $sql = 'INSERT INTO tblPerson
                (FirstName, LastName, Mail, Phone, Street, ZipCode, City, Country, IsActive, IsDeleted)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $data['FirstName'],
            $data['LastName'],
            $data['Mail'],
            $data['Phone'],
            $data['Street'],
            $data['ZipCode'],
            $data['City'],
            $data['Country'],
            $data['IsActive'],
            $data['IsDeleted'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Person $person): bool
    {
        if ($person->id === null) {
            return false;
        }

        $data = $person->toDatabaseArray();

        $sql = 'UPDATE tblPerson SET
                FirstName = ?,
                LastName = ?,
                Mail = ?,
                Phone = ?,
                Street = ?,
                ZipCode = ?,
                City = ?,
                Country = ?,
                IsActive = ?,
                IsDeleted = ?
                WHERE id = ?';

        return $this->db->execute($sql, [
            $data['FirstName'],
            $data['LastName'],
            $data['Mail'],
            $data['Phone'],
            $data['Street'],
            $data['ZipCode'],
            $data['City'],
            $data['Country'],
            $data['IsActive'],
            $data['IsDeleted'],
            $person->id,
        ]);
    }
}
