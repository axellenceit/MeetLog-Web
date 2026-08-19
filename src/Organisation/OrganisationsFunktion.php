<?php
declare(strict_types=1);

namespace Src\Organisation;

final class OrganisationsFunktion
{
    public function __construct(
        public ?int $id = null,
        public int $organisation = 0,
        public string $bezeichnung = '',
        public ?string $beschreibung = null,
        public int $sortierung = 0,
        public bool $istAktiv = true,
        public int $darfUnterschreiben = 0,
        public int $darfOrganisationVerwalten = 0,
        public int $darfBesprechungenVerwalten = 0,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            organisation: (int)($row['Organisation'] ?? 0),
            bezeichnung: (string)($row['Bezeichnung'] ?? ''),
            beschreibung: $row['Beschreibung'] ?? null,
            sortierung: (int)($row['Sortierung'] ?? 0),
            istAktiv: ((int) ($row['istAktiv'] ?? 1)) === 1,
            darfUnterschreiben: ((int) ($row['DarfUnterschreiben'] ?? 0)),
            darfOrganisationVerwalten: ((int) ($row['DarfOrganisationVerwalten'] ?? 0)),
            darfBesprechungenVerwalten: ((int) ($row['DarfBesprechungenVerwalten'] ?? 0)),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Organisation' => $this->organisation,
            'Bezeichnung' => $this->bezeichnung,
            'Beschreibung' => $this->beschreibung,
            'Sortierung' => $this->sortierung,
            'istAktiv' => $this->istAktiv ? 1 : 0,
            'DarfUnterschreiben' => $this->darfUnterschreiben,
            'DarfOrganisationVerwalten' => $this->darfOrganisationVerwalten,
            'DarfBesprechungenVerwalten' => $this->darfBesprechungenVerwalten,
        ];
    }
}