<?php

declare(strict_types=1);

namespace Src\Person;

use Src\Core\Database;

final class PersonRepository
{
    public function __construct(private Database $db) {}

    public function findAll(bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM tblPerson WHERE istGeloescht = 0';

        if ($onlyActive) {
            $sql .= ' AND istAktiv = 1';
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
        $sql = 'SELECT * FROM tblPerson WHERE Mail = ? AND istGeloescht = 0 LIMIT 1';
        $row = $this->db->first($sql, [$mail]);

        return $row ? Person::fromArray($row) : null;
    }

    public function create(Person $person): int
    {
        $data = $person->toDatabaseArray();

        $sql = 'INSERT INTO tblPerson
                (Vorname, Nachname, Mail, Telefon, Strasse, PLZ, Ort, Land, istAktiv, istGeloescht)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $data['Vorname'],
            $data['Nachname'],
            $data['Mail'],
            $data['Telefon'],
            $data['Strasse'],
            $data['PLZ'],
            $data['Ort'],
            $data['Land'],
            $data['istAktiv'],
            $data['istGeloescht'],
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
                Vorname = ?,
                Nachname = ?,
                Mail = ?,
                Telefon = ?,
                Strasse = ?,
                PLZ = ?,
                Ort = ?,
                Land = ?,
                istAktiv = ?,
                istGeloescht = ?
                WHERE id = ?';

        return $this->db->execute($sql, [
            $data['Vorname'],
            $data['Nachname'],
            $data['Mail'],
            $data['Telefon'],
            $data['Strasse'],
            $data['PLZ'],
            $data['Ort'],
            $data['Land'],
            $data['istAktiv'],
            $data['istGeloescht'],
            $person->id,
        ]);
    }
}
