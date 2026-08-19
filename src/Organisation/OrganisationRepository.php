<?php
declare(strict_types=1);

namespace Src\Organisation;

use Src\Core\Database;

final class OrganisationRepository
{
    public function __construct(private Database $db)
    {
    }

    public function findById(int $id): ?Organisation
    {
        $sql = "SELECT * FROM tblOrganisation WHERE id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Organisation::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = false): array
    {
        $sql = "SELECT * FROM tblOrganisation";
        $params = [];

        if ($onlyActive) {
            $sql .= " WHERE istAktiviert = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Name1 ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn (array $row): Organisation => Organisation::fromArray($row),
            $rows
        );
    }

    public function create(Organisation $organisation): int
    {
        $sql = "INSERT INTO tblOrganisation
            (Logo, Name1, Name2, Homepage, EMail, Telefon, Telefax, Straße, PLZ, Ort, Registergericht, Registereintrag, istAktiviert)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $data = $organisation->toDatabaseArray();

        $this->db->execute($sql, [
            $data['Logo'],
            $data['Name1'],
            $data['Name2'],
            $data['Homepage'],
            $data['EMail'],
            $data['Telefon'],
            $data['Telefax'],
            $data['Straße'],
            $data['PLZ'],
            $data['Ort'],
            $data['Registergericht'],
            $data['Registereintrag'],
            $data['istAktiviert'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(Organisation $organisation): bool
    {
        if ($organisation->id === null) {
            return false;
        }

        $sql = "UPDATE tblOrganisation SET
            Logo = ?,
            Name1 = ?,
            Name2 = ?,
            Homepage = ?,
            EMail = ?,
            Telefon = ?,
            Telefax = ?,
            Straße = ?,
            PLZ = ?,
            Ort = ?,
            Registergericht = ?,
            Registereintrag = ?,
            istAktiviert = ?
            WHERE id = ?";

        $data = $organisation->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['Logo'],
            $data['Name1'],
            $data['Name2'],
            $data['Homepage'],
            $data['EMail'],
            $data['Telefon'],
            $data['Telefax'],
            $data['Straße'],
            $data['PLZ'],
            $data['Ort'],
            $data['Registergericht'],
            $data['Registereintrag'],
            $data['istAktiviert'],
            $organisation->id,
        ]);
    }
}