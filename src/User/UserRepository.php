<?php

namespace Src\User;

use Src\Core\Database;
use Src\Person\Person;
use Src\Person\PersonRepository;

final class UserRepository
{
    private PersonRepository $personRepository;

    public function __construct(private Database $db)
    {
        $this->personRepository = new PersonRepository($db);
    }

    public function findById(int $id): ?User
    {
        $sql = "SELECT u.*, p.Vorname, p.Nachname, p.Mail
                FROM tblUser u
                LEFT JOIN tblPerson p ON p.id = u.Person
                WHERE u.id = ? AND u.istGeloescht = 0
                LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? User::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = false): array
    {
        $sql = "SELECT u.*, p.Vorname, p.Nachname, p.Mail
                FROM tblUser u
                LEFT JOIN tblPerson p ON p.id = u.Person
                WHERE u.istGeloescht = 0";
        $params = [];

        if ($onlyActive) {
            $sql .= " AND u.istAktiv = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY p.Nachname ASC, p.Vorname ASC, u.id ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): User => User::fromArray($row),
            $rows
        );
    }

    public function findActiveByEmail(string $email): ?User
    {
        $sql = "SELECT u.*, p.Vorname, p.Nachname, p.Mail
                FROM tblUser u
                INNER JOIN tblPerson p ON p.id = u.Person
                WHERE p.Mail = ? AND u.istAktiv = 1 AND u.istGeloescht = 0
                LIMIT 1";

        $row = $this->db->first($sql, [$email]);
        return $row ? User::fromArray($row) : null;
    }

    public function findByPersonId(int $personId): ?User
    {
        $sql = "SELECT u.*, p.Vorname, p.Nachname, p.Mail
                FROM tblUser u
                LEFT JOIN tblPerson p ON p.id = u.Person
                WHERE u.Person = ? AND u.istGeloescht = 0
                LIMIT 1";
        $row = $this->db->first($sql, [$personId]);

        return $row ? User::fromArray($row) : null;
    }

    public function updatePasswordHash(int $userId, string $hash): bool
    {
        $sql = "UPDATE tblUser SET Pass = ? WHERE ID = ?";
        return $this->db->execute($sql, [$hash, $userId]);
    }

    public function addUser(User $user): int
    {
        $personId = $user->person;

        if ($personId === null) {
            $personId = $this->personRepository->create(new Person(
                vorname: $user->vorname !== '' ? $user->vorname : null,
                nachname: $user->nachname !== '' ? $user->nachname : null,
                mail: $user->mail !== '' ? $user->mail : null,
                istAktiv: $user->istAktiv ? 1 : 0,
                istGeloescht: $user->istGeloescht ? 1 : 0,
            ));
        }

        $sql = "INSERT INTO tblUser (Person, Pass, StandardOrganisation, Foto, lang, istAktiv, istAdmin, istGeloescht) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $personId,
            $user->password,
            $user->standardOrganisation ? $user->standardOrganisation : 1,
            $user->foto !== '' ? $user->foto : null,
            $user->lang !== '' ? $user->lang : 'de',
            $user->istAktiv ? 1 : 0,
            $user->istAdmin ? 1 : 0,
            $user->istGeloescht ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateUser(User $user): bool
    {
        if ($user->id === null) {
            return false;
        }

        $existing = $this->findById($user->id);
        if ($existing === null) {
            return false;
        }

        $personId = $user->person ?? $existing->person;
        if ($personId === null) {
            $personId = $this->personRepository->create(new Person(
                vorname: $user->vorname !== '' ? $user->vorname : null,
                nachname: $user->nachname !== '' ? $user->nachname : null,
                mail: $user->mail !== '' ? $user->mail : null,
                istAktiv: $user->istAktiv ? 1 : 0,
                istGeloescht: $user->istGeloescht ? 1 : 0,
            ));
        } else {
            $person = $this->personRepository->findById($personId) ?? new Person(id: $personId);
            $person->vorname = $user->vorname !== '' ? $user->vorname : $person->vorname;
            $person->nachname = $user->nachname !== '' ? $user->nachname : $person->nachname;
            $person->mail = $user->mail !== '' ? $user->mail : $person->mail;
            $person->istAktiv = $user->istAktiv ? 1 : 0;
            $person->istGeloescht = $user->istGeloescht ? 1 : 0;
            $this->personRepository->update($person);
        }

        $sql = "UPDATE tblUser SET Person = ?, Pass = ?, StandardOrganisation = ?, Foto = ?, lang = ?, istAktiv = ?, istAdmin = ?, istGeloescht = ? WHERE ID = ?";
        return $this->db->execute($sql, [
            $personId,
            $user->password !== '' ? $user->password : $existing->password,
            $user->standardOrganisation ?: $existing->standardOrganisation,
            $user->foto !== '' ? $user->foto : $existing->foto,
            $user->lang !== '' ? $user->lang : $existing->lang,
            $user->istAktiv ? 1 : 0,
            $user->istAdmin ? 1 : 0,
            $user->istGeloescht ? 1 : 0,
            $user->id
        ]);
    }

    public function update(User $user): bool
    {
        return $this->updateUser($user);
    }

    public function deleteProfilePhoto(int $userId): bool
    {
        
        $sql = "SELECT Foto FROM tblUser WHERE ID = ?";
        $foto = $this->db->query($sql, [$userId]);
        $path = __DIR__ . '/../..' . $foto[0]['Foto'];

        if (is_file($path)) {
            unlink($path);

            $sql = "UPDATE tblUser SET Foto = NULL WHERE ID = ?";
            return $this->db->execute($sql, [$userId]);
        }

        return false;

    }

    public function addProfilePhoto(int $userId, string $path): bool{
        $sql = "UPDATE tblUser SET FOTO = ? WHERE ID = ?";

        return $this->db->execute($sql, [$path, $userId]);
    }

}

?>