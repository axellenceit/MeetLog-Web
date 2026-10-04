<?php

declare(strict_types=1);

namespace Src\Besprechung;

final class Besprechungsanhang
{
    public function __construct(
        public ?int $id = null,
        public ?int $besprechung = null,
        public string $dateiname = '',
        public string $originalDateiname = '',
        public ?string $dateipfad = null,
        public ?string $mimeTyp = null,
        public ?int $dateigroesse = null,
        public ?string $beschreibung = null,
        public int $sortierung = 0,
        public bool $druckenBeiEinladung = false,
        public bool $druckenBeiProtokoll = false,
        public ?int $hochgeladenVon = null,
        public bool $istAktiviert = true,
        public ?string $erstelltAm = null,
        public ?string $geaendertAm = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            besprechung: isset($row['Besprechung']) ? (int)$row['Besprechung'] : null,
            dateiname: (string)($row['Dateiname'] ?? ''),
            originalDateiname: (string)($row['OriginalDateiname'] ?? ''),
            dateipfad: $row['Dateipfad'] ?? null,
            mimeTyp: $row['MimeTyp'] ?? null,
            dateigroesse: isset($row['Dateigroesse']) ? (int)$row['Dateigroesse'] : null,
            beschreibung: $row['Beschreibung'] ?? null,
            sortierung: isset($row['Sortierung']) ? (int)$row['Sortierung'] : 0,
            druckenBeiEinladung: ($row['DruckenBeiEinladung'] ?? 'N') === 'Y',
            druckenBeiProtokoll: ($row['DruckenBeiProtokoll'] ?? 'N') === 'Y',
            hochgeladenVon: isset($row['HochgeladenVon']) ? (int)$row['HochgeladenVon'] : null,
            istAktiviert: (bool)($row['istAktiviert'] ?? true),
            erstelltAm: $row['erstelltAm'] ?? null,
            geaendertAm: $row['geaendertAm'] ?? null,
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Besprechung' => $this->besprechung,
            'Dateiname' => $this->dateiname,
            'OriginalDateiname' => $this->originalDateiname,
            'Dateipfad' => $this->dateipfad,
            'MimeTyp' => $this->mimeTyp,
            'Dateigroesse' => $this->dateigroesse,
            'Beschreibung' => $this->beschreibung,
            'Sortierung' => $this->sortierung,
            'DruckenBeiEinladung' => $this->druckenBeiEinladung ? 'Y' : 'N',
            'DruckenBeiProtokoll' => $this->druckenBeiProtokoll ? 'Y' : 'N',
            'HochgeladenVon' => $this->hochgeladenVon,
            'istAktiviert' => $this->istAktiviert ? 1 : 0,
            'erstelltAm' => $this->erstelltAm,
            'geaendertAm' => $this->geaendertAm,
        ];
    }

    /**
     * Lesbar formatierte Dateigröße (z. B. "1,4 MB")
     */
    public function formatierteGroesse(): string
    {
        if ($this->dateigroesse === null) {
            return '';
        }

        $groessen = ['B', 'KB', 'MB', 'GB'];
        $wert = (float)$this->dateigroesse;
        $stufe = 0;

        while ($wert >= 1024 && $stufe < count($groessen) - 1) {
            $wert /= 1024;
            $stufe++;
        }

        return number_format($wert, $stufe === 0 ? 0 : 1, ',', '.') . ' ' . $groessen[$stufe];
    }
}
