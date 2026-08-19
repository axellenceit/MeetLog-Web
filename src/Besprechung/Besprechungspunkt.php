<?php
declare(strict_types=1);

namespace Src\Besprechung;

final class Besprechungspunkt
{
    public const TYP_INFORMATION = 'information';
    public const TYP_DISKUSSION = 'diskussion';
    public const TYP_ABSTIMMUNG = 'abstimmung';

    public function __construct(
        public ?int $id = null,
        public ?int $besprechungId = null,
        public int $position = 1,
        public int $elternPosition = 0,
        public string $titel = '',
        public ?string $beschreibung = null,
        public string $typ = self::TYP_INFORMATION,
        public ?string $ergebnis = null,
        public ?int $ergebnis_zustimmung = null,
        public ?int $ergebnis_ablehnung = null,
        public ?int $ergebnis_enthaltung = null,
        public bool $istAktiviert = true,
    ) {
    }

    public static function allowedTypen(): array
    {
        return [
            self::TYP_INFORMATION,
            self::TYP_DISKUSSION,
            self::TYP_ABSTIMMUNG,
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            besprechungId: isset($row['BesprechungID']) ? (int)$row['BesprechungID'] : null,
            position: isset($row['Position']) ? (int)$row['Position'] : 1,
            elternPosition: isset($row['ElternPosition']) ? (int)$row['ElternPosition'] : 0,
            titel: (string)($row['Titel'] ?? ''),
            beschreibung: $row['Beschreibung'] ?? null,
            typ: (string)($row['Typ'] ?? self::TYP_INFORMATION),
            ergebnis: $row['Ergebnis'] ?? null,
            ergebnis_zustimmung: $row['Ergebnis_Zustimmung'] ?? null,
            ergebnis_ablehnung: $row['Ergebnis_Ablehnung'] ?? null,
            ergebnis_enthaltung: $row['Ergebnis_Enthaltung'] ?? null,
            istAktiviert: (bool)($row['istAktiviert'] ?? true),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'BesprechungID' => $this->besprechungId,
            'Position' => $this->position,
            'ElternPosition' => $this->elternPosition,
            'Titel' => $this->titel,
            'Beschreibung' => $this->beschreibung,
            'Typ' => $this->typ,
            'Ergebnis' => $this->ergebnis,
            'Ergebnis_Zustimmung' => $this->ergebnis_zustimmung,
            'Ergebnis_Ablehnung' => $this->ergebnis_ablehnung,
            'Ergebnis_Enthaltung' => $this->ergebnis_enthaltung,
            'istAktiviert' => $this->istAktiviert ? 1 : 0,
        ];
    }

    public function isValidTyp(): bool
    {
        return in_array($this->typ, self::allowedTypen(), true);
    }
}