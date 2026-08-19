<?php

declare(strict_types=1);

namespace Src\User;

final class User
{

    public function __construct(
        public ?int $id = null,
        public ?int $person = null,
        public string $vorname = '',
        public string $nachname = '',
        public string $mail = '',
        public string $password = '',
        public int $standardOrganisation = 1,
        public string $foto = '',
        public int $istAktiv = 1,
        public int $istGeloescht = 0,
        public int $istAdmin = 0,
        public string $lang = 'de',
        public string $avatar = ''
    ) {}

    public static function fromArray(array $row): self
    {

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            person: isset($row['Person']) ? (int) $row['Person'] : null,
            vorname: (string) ($row['Vorname'] ?? ''),
            nachname: (string) ($row['Nachname'] ?? ''),
            mail: (string) ($row['Mail'] ?? ''),
            password: (string) $row['Pass'] ?? '',
            standardOrganisation: (int) ($row['StandardOrganisation'] ?? 1),
            foto: (string) ($row['Foto'] ?? ''),
            istAktiv: (int) ($row['istAktiv'] ?? 1),
            istGeloescht: (int) ($row['istGeloescht'] ?? 0),
            istAdmin: (int) ($row['istAdmin'] ?? 0),
            lang: (string) ($row['lang'] ?? 'de'),
            avatar: (string) substr((string) ($row['Vorname'] ?? ''), 0, 1) . substr((string) ($row['Nachname'] ?? ''), 0, 1)
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'ID' => $this->id,
            'Person' => $this->person,
            'Vorname' => $this->vorname,
            'Nachname' => $this->nachname,
            'Mail' => $this->mail,
            'Pass' => $this->password,
            'StandardOrganisation' => $this->standardOrganisation,
            'Foto' => $this->foto,
            'istAktiv' => $this->istAktiv ? 1 : 0,
            'istGeloescht' => $this->istGeloescht ? 1 : 0,
            'istAdmin' => $this->istAdmin ? 1 : 0,
            'lang' => $this->lang,
            'avatar' => substr($this->vorname, 0, 1) . substr($this->nachname, 0, 1)
        ];
    }
}
