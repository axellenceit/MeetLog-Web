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
        $sql = "SELECT u.*, p.FirstName, p.LastName, p.Mail
                FROM tblUser u
                LEFT JOIN tblPerson p ON p.id = u.Person
                WHERE u.id = ? AND u.IsDeleted = 0
                LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? User::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = false): array
    {
        $sql = "SELECT u.*, p.FirstName, p.LastName, p.Mail
                FROM tblUser u
                LEFT JOIN tblPerson p ON p.id = u.Person
                WHERE u.IsDeleted = 0";
        $params = [];

        if ($onlyActive) {
            $sql .= " AND u.IsActive = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY p.LastName ASC, p.FirstName ASC, u.id ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): User => User::fromArray($row),
            $rows
        );
    }

    public function findActiveByEmail(string $email): ?User
    {
        $sql = "SELECT u.*, p.FirstName, p.LastName, p.Mail
                FROM tblUser u
                INNER JOIN tblPerson p ON p.id = u.Person
                WHERE p.Mail = ? AND u.IsActive = 1 AND u.IsDeleted = 0
                LIMIT 1";

        $row = $this->db->first($sql, [$email]);
        return $row ? User::fromArray($row) : null;
    }

    public function findByPersonId(int $personId): ?User
    {
        $sql = "SELECT u.*, p.FirstName, p.LastName, p.Mail
                FROM tblUser u
                LEFT JOIN tblPerson p ON p.id = u.Person
                WHERE u.Person = ? AND u.IsDeleted = 0
                LIMIT 1";
        $row = $this->db->first($sql, [$personId]);

        return $row ? User::fromArray($row) : null;
    }

    public function updatePasswordHash(int $userId, string $hash): bool
    {
        $sql = "UPDATE tblUser SET Password = ? WHERE ID = ?";
        return $this->db->execute($sql, [$hash, $userId]);
    }

    public function addUser(User $user): int
    {
        $personId = $user->person;

        if ($personId === null) {
            $personId = $this->personRepository->create(new Person(
                firstName: $user->firstName !== '' ? $user->firstName : null,
                lastName: $user->lastName !== '' ? $user->lastName : null,
                mail: $user->mail !== '' ? $user->mail : null,
                isActive: $user->isActive ? 1 : 0,
                isDeleted: $user->isDeleted ? 1 : 0,
            ));
        }

        $sql = "INSERT INTO tblUser (Person, Password, DefaultOrganization, Photo, Lang, IsActive, IsAdmin, IsDeleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $personId,
            $user->password,
            $user->defaultOrganization ? $user->defaultOrganization : 1,
            $user->photo !== '' ? $user->photo : null,
            $user->lang !== '' ? $user->lang : 'de',
            $user->isActive ? 1 : 0,
            $user->isAdmin ? 1 : 0,
            $user->isDeleted ? 1 : 0
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
                firstName: $user->firstName !== '' ? $user->firstName : null,
                lastName: $user->lastName !== '' ? $user->lastName : null,
                mail: $user->mail !== '' ? $user->mail : null,
                isActive: $user->isActive ? 1 : 0,
                isDeleted: $user->isDeleted ? 1 : 0,
            ));
        } else {
            $person = $this->personRepository->findById($personId) ?? new Person(id: $personId);
            $person->firstName = $user->firstName !== '' ? $user->firstName : $person->firstName;
            $person->lastName = $user->lastName !== '' ? $user->lastName : $person->lastName;
            $person->mail = $user->mail !== '' ? $user->mail : $person->mail;
            $person->isActive = $user->isActive ? 1 : 0;
            $person->isDeleted = $user->isDeleted ? 1 : 0;
            $this->personRepository->update($person);
        }

        $sql = "UPDATE tblUser SET Person = ?, Password = ?, DefaultOrganization = ?, Photo = ?, Lang = ?, IsActive = ?, IsAdmin = ?, IsDeleted = ? WHERE ID = ?";
        return $this->db->execute($sql, [
            $personId,
            $user->password !== '' ? $user->password : $existing->password,
            $user->defaultOrganization ?: $existing->defaultOrganization,
            $user->photo !== '' ? $user->photo : $existing->photo,
            $user->lang !== '' ? $user->lang : $existing->lang,
            $user->isActive ? 1 : 0,
            $user->isAdmin ? 1 : 0,
            $user->isDeleted ? 1 : 0,
            $user->id
        ]);
    }

    public function update(User $user): bool
    {
        return $this->updateUser($user);
    }

    public function deleteProfilePhoto(int $userId): bool
    {

        $sql = "SELECT Photo FROM tblUser WHERE ID = ?";
        $photo = $this->db->query($sql, [$userId]);
        $path = __DIR__ . '/../..' . $photo[0]['Photo'];

        if (is_file($path)) {
            unlink($path);

            $sql = "UPDATE tblUser SET Photo = NULL WHERE ID = ?";
            return $this->db->execute($sql, [$userId]);
        }

        return false;
    }

    public function addProfilePhoto(int $userId, string $path): bool
    {
        $sql = "UPDATE tblUser SET Photo = ? WHERE ID = ?";

        return $this->db->execute($sql, [$path, $userId]);
    }
}
