<?php

declare(strict_types=1);

namespace Src\Besprechung;

use Src\Core\Database;
use Src\Person\Person;
use Src\Person\PersonRepository;

final class BesprechungsteilnehmerRepository
{
    private PersonRepository $personRepository;

    public function __construct(private Database $db)
    {
        $this->personRepository = new PersonRepository($db);
    }

    private function baseSelect(): string
    {
        return "SELECT bt.*, p.Vorname, p.Nachname, p.Mail, p.Telefon
                FROM tblBesprechungsteilnehmer bt
                LEFT JOIN tblPerson p ON p.id = bt.Person";
    }

    private function resolvePersonId(Besprechungsteilnehmer $teilnehmer): ?int
    {
        if ($teilnehmer->person !== null) {
            return $teilnehmer->person;
        }

        if ($teilnehmer->organisationsMitglied !== null) {
            $row = $this->db->first('SELECT Person FROM tblOrganisationsMitglieder WHERE id = ? LIMIT 1', [$teilnehmer->organisationsMitglied]);
            if (!empty($row['Person'])) {
                return (int) $row['Person'];
            }
        }

        if ($teilnehmer->user !== null) {
            $row = $this->db->first('SELECT Person FROM tblUser WHERE id = ? LIMIT 1', [$teilnehmer->user]);
            if (!empty($row['Person'])) {
                return (int) $row['Person'];
            }
        }

        if (
            $teilnehmer->vorname !== null
            || $teilnehmer->nachname !== null
            || $teilnehmer->mail !== null
            || $teilnehmer->telefon !== null
        ) {
            return $this->personRepository->create(new Person(
                vorname: $teilnehmer->vorname,
                nachname: $teilnehmer->nachname,
                mail: $teilnehmer->mail,
                telefon: $teilnehmer->telefon,
            ));
        }

        return null;
    }

    public function findById(int $id): ?Besprechungsteilnehmer
    {
        $sql = $this->baseSelect() . " WHERE bt.id = ? AND bt.istAktiv = 1 LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Besprechungsteilnehmer::fromArray($row) : null;
    }

    public function findByBesprechungID(int $besprechungId): array
    {
        $sql = $this->baseSelect() . " WHERE bt.Besprechung = ? AND bt.istAktiv = 1 ORDER BY bt.id ASC";
        $rows = $this->db->query($sql, [$besprechungId]);

        return array_map(
            static fn(array $row): Besprechungsteilnehmer => Besprechungsteilnehmer::fromArray($row),
            $rows
        );
    }

    public function addTeilnehmer(int $besprechungId, Besprechungsteilnehmer $teilnehmer): int
    {
        $personId = $this->resolvePersonId($teilnehmer);

        $sql = "INSERT INTO tblBesprechungsteilnehmer
            (Besprechung, Person, OrganisationsMitglied, User, Funktion, istEingeladen, istAnwesend, istEntschuldigt, istAbwesend, istSitzungsleitung, istProtokollfuehrer, istAktiv, darfUnterschreiben)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->execute($sql, [
            $besprechungId,
            $personId,
            $teilnehmer->organisationsMitglied,
            $teilnehmer->user,
            $teilnehmer->funktion,
            $teilnehmer->istEingeladen ? 1 : 0,
            $teilnehmer->istAnwesend ? 1 : 0,
            $teilnehmer->istEntschuldigt ? 1 : 0,
            $teilnehmer->istAbwesend ? 1 : 0,
            $teilnehmer->istSitzungsleitung ? 1 : 0,
            $teilnehmer->istProtokollfuehrer ? 1 : 0,
            $teilnehmer->istAktiv ? 1 : 0,
            $teilnehmer->darfUnterschreiben ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateTeilnehmer(int $besprechungId, Besprechungsteilnehmer $teilnehmer): bool
    {
        if ($teilnehmer->id === null) {
            throw new \InvalidArgumentException('Teilnehmer-ID darf nicht null sein.');
        }

        $personId = $this->resolvePersonId($teilnehmer);

        if ($personId !== null) {
            $person = $this->personRepository->findById($personId) ?? new Person(id: $personId);
            $person->vorname = $teilnehmer->vorname ?? $person->vorname;
            $person->nachname = $teilnehmer->nachname ?? $person->nachname;
            $person->mail = $teilnehmer->mail ?? $person->mail;
            $person->telefon = $teilnehmer->telefon ?? $person->telefon;
            $this->personRepository->update($person);
        }

        $sql = "UPDATE tblBesprechungsteilnehmer SET
            Person = ?,
            OrganisationsMitglied = ?,
            User = ?,
            Funktion = ?,
            istEingeladen = ?,
            istAnwesend = ?,
            istEntschuldigt = ?,
            istAbwesend = ?,
            istSitzungsleitung = ?,
            istProtokollfuehrer = ?,
            istAktiv = ?,
            darfUnterschreiben = ?
            WHERE id = ? AND Besprechung = ?";

        return $this->db->execute($sql, [
            $personId,
            $teilnehmer->organisationsMitglied,
            $teilnehmer->user,
            $teilnehmer->funktion,
            $teilnehmer->istEingeladen ? 1 : 0,
            $teilnehmer->istAnwesend ? 1 : 0,
            $teilnehmer->istEntschuldigt ? 1 : 0,
            $teilnehmer->istAbwesend ? 1 : 0,
            $teilnehmer->istSitzungsleitung ? 1 : 0,
            $teilnehmer->istProtokollfuehrer ? 1 : 0,
            $teilnehmer->istAktiv ? 1 : 0,
            $teilnehmer->darfUnterschreiben ? 1 : 0,
            $teilnehmer->id,
            $besprechungId
        ]);
    }

    public function removeTeilnehmer(int $besprechungId, int $teilnehmerId): bool
    {
        $sql = "UPDATE tblBesprechungsteilnehmer SET istAktiv = 0 WHERE id = ? AND Besprechung = ?";
        return $this->db->execute($sql, [$teilnehmerId, $besprechungId]);
    }

    public function findChairmanByMeetingId(int $besprechungId): ?Besprechungsteilnehmer
    {
        $sql = $this->baseSelect() . " WHERE bt.Besprechung = ? AND bt.istSitzungsleitung = 1 AND bt.istAktiv = 1 LIMIT 1";
        $row = $this->db->first($sql, [$besprechungId]);

        return $row ? Besprechungsteilnehmer::fromArray($row) : null;
    }

    public function findSecretaryByMeetingId(int $besprechungId): ?Besprechungsteilnehmer
    {
        $sql = $this->baseSelect() . " WHERE bt.Besprechung = ? AND bt.istProtokollfuehrer = 1 AND bt.istAktiv = 1 LIMIT 1";
        $row = $this->db->first($sql, [$besprechungId]);

        return $row ? Besprechungsteilnehmer::fromArray($row) : null;
    }
}
