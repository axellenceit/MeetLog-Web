<?php

declare(strict_types=1);

namespace Src\Person;

final class Person
{
    public function __construct(
        public ?int $id = null,
        public ?string $vorname = null,
        public ?string $nachname = null,
        public ?string $mail = null,
        public ?string $telefon = null,
        public ?string $strasse = null,
        public ?string $plz = null,
        public ?string $ort = null,
        public ?int $land = null,
        public int $istAktiv = 1,
        public int $istGeloescht = 0,
        public ?string $erstelltAm = null,
        public ?string $geaendertAm = null
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            vorname: $row['Vorname'] ?? null,
            nachname: $row['Nachname'] ?? null,
            mail: $row['Mail'] ?? null,
            telefon: $row['Telefon'] ?? null,
            strasse: $row['Strasse'] ?? null,
            plz: $row['PLZ'] ?? null,
            ort: $row['Ort'] ?? null,
            land: isset($row['Land']) ? (int) $row['Land'] : null,
            istAktiv: (int) ($row['istAktiv'] ?? 1),
            istGeloescht: (int) ($row['istGeloescht'] ?? 0),
            erstelltAm: $row['ErstelltAm'] ?? null,
            geaendertAm: $row['GeaendertAm'] ?? null,
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Vorname' => $this->vorname,
            'Nachname' => $this->nachname,
            'Mail' => $this->mail,
            'Telefon' => $this->telefon,
            'Strasse' => $this->strasse,
            'PLZ' => $this->plz,
            'Ort' => $this->ort,
            'Land' => $this->land,
            'istAktiv' => $this->istAktiv,
            'istGeloescht' => $this->istGeloescht,
        ];
    }
}
