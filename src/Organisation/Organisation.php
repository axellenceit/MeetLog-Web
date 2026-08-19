<?php
declare(strict_types=1);

namespace Src\Organisation;

final class Organisation
{
    public function __construct(
        public ?int $id = null,
        public string $name1 = '',
        public ?string $name2 = null,
        public ?string $homepage = null,
        public ?string $email = null,
        public ?string $telefon = null,
        public ?string $telefax = null,
        public ?string $strasse = null,
        public ?string $plz = null,
        public ?string $ort = null,
        public ?string $land = null,
        public ?string $registergericht = null,
        public ?string $registereintrag = null,
        public ?string $logo = null,
        public bool $istAktiviert = true,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            name1: (string)($row['Name1'] ?? ''),
            name2: $row['Name2'] ?? null,
            homepage: $row['Homepage'] ?? null,
            email: $row['EMail'] ?? null,
            telefon: $row['Telefon'] ?? null,
            telefax: $row['Telefax'] ?? null,
            strasse: $row['Straße'] ?? null,
            plz: $row['PLZ'] ?? null,
            ort: $row['Ort'] ?? null,
            land: $row['Land'] ?? null,
            registergericht: $row['Registergericht'] ?? null,
            registereintrag: $row['Registereintrag'] ?? null,
            logo: $row['Logo'] ?? null,
            istAktiviert: (bool)($row['istAktiviert'] ?? true),
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Logo' => $this->logo,
            'Name1' => $this->name1,
            'Name2' => $this->name2,
            'Homepage' => $this->homepage,
            'EMail' => $this->email,
            'Telefon' => $this->telefon,
            'Telefax' => $this->telefax,
            'Straße' => $this->strasse,
            'PLZ' => $this->plz,
            'Ort' => $this->ort,
            'Land' => $this->land,
            'Registergericht' => $this->registergericht,
            'Registereintrag' => $this->registereintrag,
            'istAktiviert' => $this->istAktiviert ? 1 : 0,
        ];
    }
}