<?php
declare(strict_types=1);

namespace Src\Besprechung;

final class Besprechungsteilnehmer
{
    public function __construct(
        public ?int $id = null,
        public int $besprechung = 0,
        public ?int $person = null,
        public ?int $organisationsMitglied = null,
        public ?int $user = null,
        public ?int $funktion = null,
        public ?string $vorname = null,
        public ?string $nachname = null,
        public ?string $mail = null,
        public ?string $telefon = null,
        public bool $istEingeladen = true,
        public bool $istAnwesend = false,
        public bool $istEntschuldigt = false,
        public bool $istAbwesend = false,
        public bool $istAktiv = true,
        public bool $darfUnterschreiben = false,
        public bool $istSitzungsleitung = false,
        public bool $istProtokollfuehrer = false
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            besprechung: (int)($row['Besprechung'] ?? 0),
            person: isset($row['Person']) ? (int)$row['Person'] : null,
            organisationsMitglied: isset($row['OrganisationsMitglied']) ? (int)$row['OrganisationsMitglied'] : null,
            user: isset($row['User']) ? (int)$row['User'] : null,
            funktion: isset($row['Funktion']) ? (int)$row['Funktion'] : null,
            vorname: $row['Vorname'] ?? null,
            nachname: $row['Nachname'] ?? null,
            mail: $row['Mail'] ?? null,
            telefon: $row['Telefon'] ?? null,
            istEingeladen: (bool)($row['istEingeladen'] ?? true),
            istAnwesend: (bool)($row['istAnwesend'] ?? false),
            istEntschuldigt: (bool)($row['istEntschuldigt'] ?? false),
            istAbwesend: (bool)($row['istAbwesend'] ?? false),
            istAktiv: (bool)($row['istAktiv'] ?? true),
            darfUnterschreiben: (bool)($row['darfUnterschreiben'] ?? false),
            istSitzungsleitung: (bool)($row['istSitzungsleitung'] ?? false),
            istProtokollfuehrer: (bool)($row['istProtokollfuehrer'] ?? false),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Besprechung' => $this->besprechung,
            'Person' => $this->person,
            'OrganisationsMitglied' => $this->organisationsMitglied,
            'User' => $this->user,
            'Funktion' => $this->funktion,
            'Vorname' => $this->vorname,
            'Nachname' => $this->nachname,
            'Mail' => $this->mail,
            'Telefon' => $this->telefon,
            'istEingeladen' => $this->istEingeladen ? 1 : 0,
            'istAnwesend' => $this->istAnwesend ? 1 : 0,
            'istEntschuldigt' => $this->istEntschuldigt ? 1 : 0,
            'istAbwesend' => $this->istAbwesend ? 1 : 0,
            'istAktiv' => $this->istAktiv ? 1 : 0,
            'darfUnterschreiben' => $this->darfUnterschreiben ? 1 : 0,
            'istSitzungsleitung' => $this->istSitzungsleitung ? 1 : 0,
            'istProtokollfuehrer' => $this->istProtokollfuehrer ? 1 : 0,
        ];
    }
}