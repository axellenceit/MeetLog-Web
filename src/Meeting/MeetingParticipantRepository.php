<?php

declare(strict_types=1);

namespace Src\Meeting;

use Src\Core\Database;
use Src\Person\Person;
use Src\Person\PersonRepository;

final class MeetingParticipantRepository
{
    private PersonRepository $personRepository;

    public function __construct(private Database $db)
    {
        $this->personRepository = new PersonRepository($db);
    }

    private function baseSelect(): string
    {
        return "SELECT mp.*, p.FirstName, p.LastName, p.Mail, p.Phone
                FROM tblMeetingParticipant mp
                LEFT JOIN tblPerson p ON p.id = mp.Person";
    }

    private function resolvePersonId(MeetingParticipant $participant): ?int
    {
        if ($participant->person !== null) {
            return $participant->person;
        }

        if ($participant->organizationMember !== null) {
            $row = $this->db->first('SELECT Person FROM tblOrganizationMember WHERE id = ? LIMIT 1', [$participant->organizationMember]);
            if (!empty($row['Person'])) {
                return (int) $row['Person'];
            }
        }

        if ($participant->user !== null) {
            $row = $this->db->first('SELECT Person FROM tblUser WHERE id = ? LIMIT 1', [$participant->user]);
            if (!empty($row['Person'])) {
                return (int) $row['Person'];
            }
        }

        if (
            $participant->firstName !== null
            || $participant->lastName !== null
            || $participant->mail !== null
            || $participant->phone !== null
        ) {
            return $this->personRepository->create(new Person(
                firstName: $participant->firstName,
                lastName: $participant->lastName,
                mail: $participant->mail,
                phone: $participant->phone,
            ));
        }

        return null;
    }

    public function findById(int $id): ?MeetingParticipant
    {
        $sql = $this->baseSelect() . " WHERE mp.id = ? AND mp.IsActive = 1 LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? MeetingParticipant::fromArray($row) : null;
    }

    public function findByMeetingId(int $meetingId): array
    {
        $sql = $this->baseSelect() . " WHERE mp.Meeting = ? AND mp.IsActive = 1 ORDER BY mp.id ASC";
        $rows = $this->db->query($sql, [$meetingId]);

        return array_map(
            static fn(array $row): MeetingParticipant => MeetingParticipant::fromArray($row),
            $rows
        );
    }

    public function addParticipant(int $meetingId, MeetingParticipant $participant): int
    {
        $personId = $this->resolvePersonId($participant);

        $sql = "INSERT INTO tblMeetingParticipant
            (Meeting, Person, OrganizationMember, User, Function, IsInvited, IsPresent, IsExcused, IsAbsent, IsChair, IsMinuteTaker, IsActive, MaySign)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->execute($sql, [
            $meetingId,
            $personId,
            $participant->organizationMember,
            $participant->user,
            $participant->function,
            $participant->isInvited ? 1 : 0,
            $participant->isPresent ? 1 : 0,
            $participant->isExcused ? 1 : 0,
            $participant->isAbsent ? 1 : 0,
            $participant->isChair ? 1 : 0,
            $participant->isMinuteTaker ? 1 : 0,
            $participant->isActive ? 1 : 0,
            $participant->maySign ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateParticipant(int $meetingId, MeetingParticipant $participant): bool
    {
        if ($participant->id === null) {
            throw new \InvalidArgumentException('Teilnehmer-ID darf nicht null sein.');
        }

        $personId = $this->resolvePersonId($participant);

        if ($personId !== null) {
            $person = $this->personRepository->findById($personId) ?? new Person(id: $personId);
            $person->firstName = $participant->firstName ?? $person->firstName;
            $person->lastName = $participant->lastName ?? $person->lastName;
            $person->mail = $participant->mail ?? $person->mail;
            $person->phone = $participant->phone ?? $person->phone;
            $this->personRepository->update($person);
        }

        $sql = "UPDATE tblMeetingParticipant SET
            Person = ?,
            OrganizationMember = ?,
            User = ?,
            Function = ?,
            IsInvited = ?,
            IsPresent = ?,
            IsExcused = ?,
            IsAbsent = ?,
            IsChair = ?,
            IsMinuteTaker = ?,
            IsActive = ?,
            MaySign = ?
            WHERE id = ? AND Meeting = ?";

        return $this->db->execute($sql, [
            $personId,
            $participant->organizationMember,
            $participant->user,
            $participant->function,
            $participant->isInvited ? 1 : 0,
            $participant->isPresent ? 1 : 0,
            $participant->isExcused ? 1 : 0,
            $participant->isAbsent ? 1 : 0,
            $participant->isChair ? 1 : 0,
            $participant->isMinuteTaker ? 1 : 0,
            $participant->isActive ? 1 : 0,
            $participant->maySign ? 1 : 0,
            $participant->id,
            $meetingId
        ]);
    }

    public function removeParticipant(int $meetingId, int $participantId): bool
    {
        $sql = "UPDATE tblMeetingParticipant SET IsActive = 0 WHERE id = ? AND Meeting = ?";
        return $this->db->execute($sql, [$participantId, $meetingId]);
    }

    public function findChairmanByMeetingId(int $meetingId): ?MeetingParticipant
    {
        $sql = $this->baseSelect() . " WHERE mp.Meeting = ? AND mp.IsChair = 1 AND mp.IsActive = 1 LIMIT 1";
        $row = $this->db->first($sql, [$meetingId]);

        return $row ? MeetingParticipant::fromArray($row) : null;
    }

    public function findSecretaryByMeetingId(int $meetingId): ?MeetingParticipant
    {
        $sql = $this->baseSelect() . " WHERE mp.Meeting = ? AND mp.IsMinuteTaker = 1 AND mp.IsActive = 1 LIMIT 1";
        $row = $this->db->first($sql, [$meetingId]);

        return $row ? MeetingParticipant::fromArray($row) : null;
    }
}
