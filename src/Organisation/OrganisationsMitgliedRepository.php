<?php

declare(strict_types=1);

namespace Src\Organisation;

use Src\Core\Database;
use Src\Person\Person;
use Src\Person\PersonRepository;

final class OrganisationsMitgliedRepository
{
    private PersonRepository $personRepository;

    public function __construct(private Database $db)
    {
        $this->personRepository = new PersonRepository($db);
    }

    private function baseSelect(): string
    {
        return "SELECT m.*, p.Vorname, p.Nachname, p.Mail, p.Telefon, p.Strasse, p.PLZ, p.Ort, p.Land
                FROM tblOrganisationsMitglieder m
                LEFT JOIN tblPerson p ON p.id = m.Person";
    }

    public function findById(int $id): ?OrganisationsMitglied
    {
        $sql = $this->baseSelect() . " WHERE m.id = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? OrganisationsMitglied::fromArray($row) : null;
    }

    /**
     * @return OrganisationsMitglied[]
     */
    public function findAllByOrganisation(int $organisationId, bool $nurAktive = false): array
    {
        $sql = $this->baseSelect() . " WHERE m.Organisation = ? AND m.istGeloescht = 0";
        $params = [$organisationId];

        if ($nurAktive) {
            $sql .= " AND m.istAktiv = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY p.Nachname ASC, p.Vorname ASC, m.id ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): OrganisationsMitglied => OrganisationsMitglied::fromArray($row),
            $rows
        );
    }

    /**
     * @return OrganisationsMitglied[]
     */
    public function findByUserID(int $userId): array
    {
        $sql = $this->baseSelect() . "
            WHERE m.User = ? AND m.istGeloescht = 0
            ORDER BY m.Organisation ASC, p.Nachname ASC, p.Vorname ASC";

        $rows = $this->db->query($sql, [$userId]);

        return array_map(
            static fn(array $row): OrganisationsMitglied => OrganisationsMitglied::fromArray($row),
            $rows
        );
    }

    public function findByUserAndOrganisation(int $userId, int $organisationId): ?OrganisationsMitglied
    {
        $sql = $this->baseSelect() . ' WHERE m.User = ? AND m.Organisation = ? LIMIT 1';
        $row = $this->db->first($sql, [$userId, $organisationId]);

        return $row ? OrganisationsMitglied::fromArray($row) : null;
    }

    public function findByPersonAndOrganisation(int $personId, int $organisationId): ?OrganisationsMitglied
    {
        $sql = $this->baseSelect() . ' WHERE m.Person = ? AND m.Organisation = ? AND m.istGeloescht = 0 LIMIT 1';
        $row = $this->db->first($sql, [$personId, $organisationId]);

        return $row ? OrganisationsMitglied::fromArray($row) : null;
    }

    public function addMember(OrganisationsMitglied $mitglied): int
    {
        $personId = $mitglied->person;
        if ($personId === null) {
            $personId = $this->personRepository->create(new Person(
                vorname: $mitglied->vorname,
                nachname: $mitglied->nachname,
                mail: $mitglied->mail,
                telefon: $mitglied->telefon,
                strasse: $mitglied->strasse,
                plz: $mitglied->plz,
                ort: $mitglied->ort,
                land: $mitglied->land,
                istAktiv: $mitglied->istAktiv,
                istGeloescht: $mitglied->istGeloescht,
            ));
        }

        $sql = "INSERT INTO tblOrganisationsMitglieder
            (Organisation, Person, User, Funktion, Bemerkung, istAktiv, istGeloescht)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $data = $mitglied->toDatabaseArray();

        $this->db->execute($sql, [
            $data['Organisation'],
            $personId,
            $data['User'],
            $data['Funktion'],
            $data['Bemerkung'],
            $data['istAktiv'],
            $data['istGeloescht'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateMember(OrganisationsMitglied $mitglied): bool
    {
        if ($mitglied->id === null) {
            return false;
        }

        $existing = $this->findById($mitglied->id);
        if ($existing === null) {
            return false;
        }

        $personId = $mitglied->person ?? $existing->person;
        if ($personId === null) {
            $personId = $this->personRepository->create(new Person(
                vorname: $mitglied->vorname,
                nachname: $mitglied->nachname,
                mail: $mitglied->mail,
                telefon: $mitglied->telefon,
                strasse: $mitglied->strasse,
                plz: $mitglied->plz,
                ort: $mitglied->ort,
                land: $mitglied->land,
                istAktiv: $mitglied->istAktiv,
                istGeloescht: $mitglied->istGeloescht,
            ));
        } else {
            $person = $this->personRepository->findById($personId) ?? new Person(id: $personId);
            $person->vorname = $mitglied->vorname ?? $person->vorname;
            $person->nachname = $mitglied->nachname ?? $person->nachname;
            $person->mail = $mitglied->mail ?? $person->mail;
            $person->telefon = $mitglied->telefon ?? $person->telefon;
            $person->strasse = $mitglied->strasse ?? $person->strasse;
            $person->plz = $mitglied->plz ?? $person->plz;
            $person->ort = $mitglied->ort ?? $person->ort;
            $person->land = $mitglied->land ?? $person->land;
            $person->istAktiv = $mitglied->istAktiv;
            $person->istGeloescht = $mitglied->istGeloescht;
            $this->personRepository->update($person);
        }

        $sql = "UPDATE tblOrganisationsMitglieder SET
            Organisation = ?,
            Person = ?,
            User = ?,
            Funktion = ?,
            Bemerkung = ?,
            istAktiv = ?,
            istGeloescht = ?
            WHERE id = ?";

        $data = $mitglied->toDatabaseArray();

        return $this->db->execute($sql, [
            $data['Organisation'],
            $personId,
            $data['User'],
            $data['Funktion'],
            $data['Bemerkung'],
            $data['istAktiv'],
            $data['istGeloescht'],
            $mitglied->id,
        ]);
    }

    public function update(OrganisationsMitglied $mitglied): bool
    {
        return $this->updateMember($mitglied);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM tblOrganisationsMitglieder WHERE id = ?";

        return $this->db->execute($sql, [$id]);
    }

    public function getBoardOfDirectors(int $organisationId): array
    {
        $sql = "SELECT m.*, p.Vorname, p.Nachname, p.Mail, p.Telefon, p.Strasse, p.PLZ, p.Ort, p.Land
            FROM tblOrganisationsMitglieder m
            LEFT JOIN tblPerson p ON p.id = m.Person
                INNER JOIN tblOrganisationsFunktionen f ON m.Funktion = f.id
                WHERE m.Organisation = ? AND m.istGeloescht = 0 AND f.DarfOrganisationVerwalten = 1
            ORDER BY p.Nachname ASC, p.Vorname ASC, m.id ASC";

        $rows = $this->db->query($sql, [$organisationId]);

        return array_map(
            static fn(array $row): OrganisationsMitglied => OrganisationsMitglied::fromArray($row),
            $rows
        );
    }
}
