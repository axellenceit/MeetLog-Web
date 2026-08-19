<?php
declare(strict_types=1);

namespace Src\Besprechung;

use Src\Core\Database;

final class BesprechungspunktRepository
{
    public function __construct(private Database $db)
    {
    }

    public function findById(int $id): ?Besprechungspunkt
    {
        $sql = "SELECT * FROM tblBesprechungspunkt WHERE id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Besprechungspunkt::fromArray($row) : null;
    }

    public function findByBesprechungId(int $besprechungId, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM tblBesprechungspunkt WHERE BesprechungID = ?";
        $params = [$besprechungId];

        if ($onlyActive) {
            $sql .= " AND istAktiviert = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY Position ASC, id ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Besprechungspunkt => Besprechungspunkt::fromArray($row),
            $rows
        );
    }

    public function findNextPosition(int $besprechungId, int $elternPosition = 0): int
    {
        $sql = "SELECT MAX(Position) AS maxPosition
            FROM tblBesprechungspunkt
            WHERE BesprechungID = ? AND ElternPosition = ?";

        $row = $this->db->first($sql, [$besprechungId, $elternPosition]);
        $lastPosition = isset($row['maxPosition']) ? (int)$row['maxPosition'] : 0;

        return $lastPosition > 0 ? $lastPosition + 1 : 1;
    }

    public function insert(Besprechungspunkt $punkt): int
    {
        if (!$punkt->isValidTyp()) {
            throw new \InvalidArgumentException('Ungültiger Besprechungspunkt-Typ.');
        }

        $sql = "INSERT INTO tblBesprechungspunkt
            (BesprechungID, Position, ElternPosition, Titel, Beschreibung, Typ, Ergebnis, Ergebnis_Zustimmung, Ergebnis_Ablehnung, Ergebnis_Enthaltung, istAktiviert)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $data = $punkt->toDatabaseArray();

        $this->db->execute($sql, [
            $data['BesprechungID'],
            $data['Position'],
            $data['ElternPosition'],
            $data['Titel'],
            $data['Beschreibung'],
            $data['Typ'],
            $data['Ergebnis'],
            $data['Ergebnis_Zustimmung'],
            $data['Ergebnis_Ablehnung'],
            $data['Ergebnis_Enthaltung'],
            $data['istAktiviert'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(Besprechungspunkt $punkt): bool
    {
        if ($punkt->id === null || !$punkt->isValidTyp()) {
            return false;
        }

        $sql = "UPDATE tblBesprechungspunkt SET
            BesprechungID = ?,
            Position = ?,
            ElternPosition = ?,
            Titel = ?,
            Beschreibung = ?,
            Typ = ?,
            Ergebnis = ?,
            Ergebnis_Zustimmung = ?,
            Ergebnis_Ablehnung = ?,
            Ergebnis_Enthaltung = ?,
            istAktiviert = ?
            WHERE id = ?";

        $data = $punkt->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['BesprechungID'],
            $data['Position'],
            $data['ElternPosition'],
            $data['Titel'],
            $data['Beschreibung'],
            $data['Typ'],
            $data['Ergebnis'],
            $data['Ergebnis_Zustimmung'],
            $data['Ergebnis_Ablehnung'],
            $data['Ergebnis_Enthaltung'],
            $data['istAktiviert'],
            $punkt->id,
        ]);
    }
}