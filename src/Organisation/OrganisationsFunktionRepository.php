<?php

declare(strict_types=1);

namespace Src\Organisation;

use Src\Core\Database;

final class OrganisationsFunktionRepository
{
    public function __construct(
        private Database $db
    ) {}

    /*public function findById(int $id): ?OrganisationsFunktion
    {
        $sql = 'SELECT * FROM tblOrganisationsFunktionen WHERE id = ? LIMIT 1';
        $row = $this->db->query($sql, [$id]);

        return $row ? OrganisationsFunktion::fromArray($row) : null;
    }*/

    public function findById(int $id): ?OrganisationsFunktion
    {
        $sql = 'SELECT * FROM tblOrganisationsFunktionen WHERE id = ? LIMIT 1';
        $rows = $this->db->query($sql, [$id]);

        if (empty($rows)) {
            return null;
        }

        return OrganisationsFunktion::fromArray($rows[0]);
    }

    /**
     * @return OrganisationsFunktion[]
     */
    public function findAllByOrganisation(int $organisationId, bool $nurAktive = false): array
    {
        $sql = 'SELECT * FROM tblOrganisationsFunktionen WHERE Organisation = ?';
        if ($nurAktive) {
            $sql .= ' AND istAktiv = 1';
        }
        $sql .= ' ORDER BY Sortierung ASC, Bezeichnung ASC';

        $rows = $this->db->query($sql, [$organisationId]);

        return array_map(
            static fn(array $row): OrganisationsFunktion => OrganisationsFunktion::fromArray($row),
            $rows
        );
    }

    public function create(OrganisationsFunktion $funktion): int
    {
        $sql = "INSERT INTO tblOrganisationsFunktionen
                (Organisation, Bezeichnung, Beschreibung, Sortierung, istAktiv, DarfUnterschreiben, DarfOrganisationVerwalten, DarfBesprechungenVerwalten)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)";

        $data = $funktion->toDatabaseArray();

        $this->db->execute($sql, [
            $data['Organisation'],
            $data['Bezeichnung'],
            $data['Beschreibung'],
            $data['Sortierung'],
            $data['istAktiv'],
            $data['DarfUnterschreiben'],
            $data['DarfOrganisationVerwalten'],
            $data['DarfBesprechungenVerwalten']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(OrganisationsFunktion $funktion): bool
    {
        if ($funktion->id === null) {
            return false;
        }

        $data = $funktion->toDatabaseArray();

        $sql = "UPDATE tblOrganisationsFunktionen SET
                Organisation = ?,
                Bezeichnung = ?,
                Beschreibung = ?,
                Sortierung = ?,
                istAktiv = ?,
                DarfUnterschreiben = ?,
                DarfOrganisationVerwalten = ?,
                DarfBesprechungenVerwalten = ?
             WHERE id = ?";

        $data['id'] = $funktion->id;

        return $this->db->execute($sql, [
            $data['Organisation'],
            $data['Bezeichnung'],
            $data['Beschreibung'],
            $data['Sortierung'],
            $data['istAktiv'],
            $data['DarfUnterschreiben'],
            $data['DarfOrganisationVerwalten'],
            $data['DarfBesprechungenVerwalten'],
            $data['id']
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM tblOrganisationsFunktionen WHERE id = ?";

        return $this->db->execute($sql, [$id]);
    }
}
