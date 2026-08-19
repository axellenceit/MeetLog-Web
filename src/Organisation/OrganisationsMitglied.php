<?php

declare(strict_types=1);

namespace Src\Organisation;

final class OrganisationsMitglied
{
    public function __construct(
        public ?int $id = null,
        public int $organisation = 0,
        public ?int $person = null,
        public ?int $user = null,
        public ?int $funktion = null,
        public ?string $vorname = null,
        public ?string $nachname = null,
        public ?string $mail = null,
        public ?string $telefon = null,
        public ?string $strasse = null,
        public ?string $plz = null,
        public ?string $ort = null,
        public ?int $land = null,
        public ?string $bemerkung = null,
        public int $istAktiv = 1,
        public int $istGeloescht = 0
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            organisation: (int)($row['Organisation'] ?? 0),
            person: isset($row['Person']) ? (int)$row['Person'] : null,
            user: isset($row['User']) ? (int)$row['User'] : null,
            funktion: isset($row['Funktion']) ? (int)$row['Funktion'] : null,
            vorname: $row['Vorname'] ?? null,
            nachname: $row['Nachname'] ?? null,
            mail: $row['Mail'] ?? null,
            telefon: $row['Telefon'] ?? null,
            strasse: $row['Strasse'] ?? null,
            plz: $row['PLZ'] ?? null,
            ort: $row['Ort'] ?? null,
            land: $row['Land'] ?? null,
            bemerkung: $row['Bemerkung'] ?? null,
            istAktiv: (int)($row['istAktiv'] ?? 1),
            istGeloescht: (int)($row['istGeloescht'] ?? 0)
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Organisation' => $this->organisation,
            'Person' => $this->person,
            'User' => $this->user,
            'Funktion' => $this->funktion,
            'Vorname' => $this->vorname,
            'Nachname' => $this->nachname,
            'Mail' => $this->mail,
            'Telefon' => $this->telefon,
            'Strasse' => $this->strasse,
            'PLZ' => $this->plz,
            'Ort' => $this->ort,
            'Land' => $this->land,
            'Bemerkung' => $this->bemerkung,
            'istAktiv' => $this->istAktiv,
            'istGeloescht' => $this->istGeloescht
        ];
    }
}
