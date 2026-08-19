<?php

declare(strict_types=1);

namespace Src\Besprechung;

use Src\Core\Database;

final class BesprechungRepository
{
    public function __construct(private Database $db) {}

    public function findById(int $id): ?Besprechung
    {
        $sql = "SELECT * FROM tblBesprechung WHERE id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Besprechung::fromArray($row) : null;
    }

    public function findByPublicCode(string $publicCode): ?Besprechung
    {
        $sql = "SELECT * FROM tblBesprechung WHERE public_code = ? LIMIT 1";
        $row = $this->db->first($sql, [$publicCode]);

        return $row ? Besprechung::fromArray($row) : null;
    }


    public function findAll(bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM tblBesprechung";
        $params = [];

        if ($onlyActive) {
            $sql .= " WHERE istAktiviert = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Datum ASC, Beginn ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Besprechung => Besprechung::fromArray($row),
            $rows
        );
    }

    public function findLastMeetings(int $count): array
    {
        $sql = "SELECT * FROM tblBesprechung ORDER BY BesprechungId DESC LIMIT ?";
        $rows = $this->db->query($sql, [$count]);

        return array_map(
            static fn(array $row): Besprechung => Besprechung::fromArray($row),
            $rows
        );
    }

    public function findNextMeetings(int $count): array
    {
        $sql = "SELECT * FROM tblBesprechung WHERE Datum >= CURDATE() AND istAktiviert = 1 ORDER BY Datum ASC LIMIT ?";
        $rows = $this->db->query($sql, [$count]);

        return array_map(
            static fn(array $row): Besprechung => Besprechung::fromArray($row),
            $rows
        );
    }

    public function findByOrganisationId(int $organisationId, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM tblBesprechung WHERE OrganisationID = ?";
        $params = [$organisationId];

        if ($onlyActive) {
            $sql .= " AND istAktiviert = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Datum DESC, Beginn DESC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Besprechung => Besprechung::fromArray($row),
            $rows
        );
    }

    public function create(Besprechung $besprechung): int
    {
        $sql = "INSERT INTO tblBesprechung
            (OrganisationID, ErstellerUserID, Titel, Beschreibung, Datum, Beginn, Ende, Ort, Status, istAktiviert, istVeröffentlicht)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $data = $besprechung->toDatabaseArray();

        $this->db->execute($sql, [
            $data['OrganisationID'],
            $data['ErstellerUserID'],
            $data['Titel'],
            $data['Beschreibung'],
            $data['Datum'],
            $data['Beginn'],
            $data['Ende'],
            $data['Ort'],
            $data['Status'],
            $data['istAktiviert'],
            $data['istVeröffentlicht'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(Besprechung $besprechung): bool
    {
        if ($besprechung->id === null) {
            return false;
        }

        $sql = "UPDATE tblBesprechung SET
            Titel = ?,
            Datum = ?,
            Beginn = ?,
            Ende = ?,
            Ort = ?,
            Status = ?,
            istAktiviert = ?,
            istVeröffentlicht = ?,
            public_code = ?
            WHERE id = ?";

        $data = $besprechung->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['Titel'],
            $data['Datum'],
            $data['Beginn'],
            $data['Ende'],
            $data['Ort'],
            $data['Status'],
            $data['istAktiviert'],
            $data['istVeröffentlicht'],
            $data['public_code'],
            $besprechung->id,
        ]);
    }
}
